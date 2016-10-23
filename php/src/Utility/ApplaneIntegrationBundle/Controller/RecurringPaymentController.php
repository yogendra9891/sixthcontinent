<?php

namespace Utility\ApplaneIntegrationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use StoreManager\StoreBundle\Entity\Store;
use StoreManager\StoreBundle\Entity\UserToStore;
use CardManagement\CardManagementBundle\Entity\Contract;
use Transaction\TransactionBundle\Document\RecurringPaymentLog;
use Notification\NotificationBundle\Document\UserNotifications;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactionDetail;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Utility\ApplaneIntegrationBundle\Document\TransactionPaymentNotificationLog;

class RecurringPaymentController extends Controller {

    protected $miss_param = '';
    protected $base_six = 1000000;

    /**
     * Functionality decoding data
     * @param json $object	
     * @return array
     */
    public function decodeData($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     * @return int
     */
    private function checkParamsAction($chk_params, $object_info) {
        $converted_array = (array) $object_info;
        foreach ($chk_params as $param) {
            if (array_key_exists($param, $converted_array) && ($converted_array[$param] != '')) {
                $check_error = 0;
            } else {
                $check_error = 1;
                $this->miss_param = $param;
                break;
            }
        }
        return $check_error;
    }

    /**
     * Decoding the json string to object
     * @param json string $encode_object
     * @return object $decode_object
     */
    public function decodeObjectAction($encode_object) {
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $decode_object = $serializer->decode($encode_object, 'json');
        return $decode_object;
    }

    /**
     * method for decoding the raw data.
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeObjectAction($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * import shop transaction data from applane
     * @return json
     */
    public function importshoptransactionAction() {
        $res_data = array();
        $data = $shop_ids = $shop_ids_users = array();
        $time = new \DateTime('now');
        $data['start_date'] = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y')) - (60 * 60 * 24))); //previous day data
        $data['end_date']   = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y')))); //current date

        //get the applane service for transaction data
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $import_transaction_data = $applane_service->gettransactiondata($data); //get data from applane of previous day.
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        if ($import_transaction_data->code == 200) {
            $transaction_data = $import_transaction_data->response->result;
            foreach ($transaction_data as $transaction) {
                $shop_ids[] = $transaction->shop_id->_id;
            }
            //get shop objects and extract shop owner id
            $user_object = $this->get('user_object.service');
            $user_object_service = $user_object->getShopsOwnerIds($shop_ids, array(), true);
            $shop_ids_users = $user_object_service['owner_ids']; //userid,shop_owner_id associated array
            if (count($transaction_data)) {
                foreach ($transaction_data as $transaction_record) { 
                    $date = $transaction_record->date;
                    $current_date = date(DATE_RFC3339, strtotime($date)); //change it according to application time zone
                    $date1   = new \DateTime($current_date); //we need h:i:s also se we need to pass in datetime object
                    $shop_id = $transaction_record->shop_id->_id;
                    $amount = $transaction_record->total_income;
                    $payable_amount = $transaction_record->total_checkout;
                    $invoice_id = $transaction_record->_id;
                    $user_id = (isset($shop_ids_users[$shop_id]) ? $shop_ids_users[$shop_id] : 0); //store owner id.

                     //check if invoice exist
                    $check_import = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionDetail')
                        ->findOneBy(array('invoiceId' => $invoice_id));
                   
                    if(!$check_import){
                    /** save date in db * */
                    $shop_transaction = new ShopTransactionDetail();
                    $shop_transaction->setDate($date1);
                    $shop_transaction->setCreatedAt($time);
                    $shop_transaction->setShopId($shop_id);
                    $shop_transaction->setUserId($user_id);
                    $shop_transaction->setAmount($amount);
                    $shop_transaction->setPayableAmount($payable_amount);
                    $shop_transaction->setInvoiceId($invoice_id);
                    $em->persist($shop_transaction);
                    }
                }
                try {
                    $em->flush(); 
                    $applane_service->writeTransactionLogs('Transaction Data to be import: '.  json_encode($transaction_data), 'transaction imported data successfully');  //write log
                    $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array());
                } catch (\Exception $ex) {
                     $applane_service->writeTransactionLogs('Transaction Data to be import: '.  json_encode($transaction_data), 'transaction imported data failed');  //write log
                    $res_data = array('code' => 1029, 'message' => 'FAILURE', 'data' => array());
                }
            }
        } 
        return true;
    }

    /**
     * Call cartasi service
     * @param array $data
     * @param string $url
     * @return type
     */
    public function recurringPaymentCurl($data, $url) {

        $timeout = 5;
        $data_to_url = http_build_query($data);
        $data_to_post = utf8_encode($data_to_url);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        //TRUE to do a regular HTTP POST.
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // make SSL checking false
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_to_post);
        //TRUE to return the transfer as a string of the return value
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        // grab URL and pass it to the browser
        $data_response = curl_exec($ch);

        $data_response_suc = "<RootResponse>
	<StoreRequest>
		<alias>payment_31297124</alias>
		<codTrans>PRA7684653448</codTrans>
		<divisa>EUR</divisa>
		<importo>1</importo>
		<mail>yiresse.abia@gmail.com</mail>
		<scadenza>201508</scadenza>
		<pan>1233</pan>
		<cv2></cv2>
		<num_contratto>shop_contract_50004_9</num_contratto>
		<tipo_richiesta>PR</tipo_richiesta>
		<tipo_servizio>paga_rico</tipo_servizio>
		<gruppo></gruppo>
		<descrizione>recurring payment</descrizione>
	</StoreRequest>
	<StoreResponse>
		<tipoCarta>VISA</tipoCarta>
		<paese>ITA</paese>
		<tipoProdotto>ELECTRON+-+DEBIT+-+S</tipoProdotto>
		<tipoTransazione>NO_3DSECURE</tipoTransazione>
		<codiceAutorizzazione>005598</codiceAutorizzazione>
		<dataOra>20141127T163445</dataOra>
		<codiceEsito>0</codiceEsito>
		<descrizioneEsito>autorizzazione concessa</descrizioneEsito>
		<ParametriAggiuntivi></ParametriAggiuntivi>
		<mac>63c73bea18e32a8123afa4d76a7710128ca30b8d</mac>
	</StoreResponse>
</RootResponse>";

        $data_response_unsuc = "<RootResponse>
<StoreRequest>
<alias>payment_3444168</alias>
<codTrans>ASDG45345345345</codTrans>
<divisa>EUR</divisa>
<importo>1</importo>
<mail>sunil.thakur@daffodilsw.com</mail>
<scadenza>201710</scadenza>
<pan>9992</pan>
<cv2>***</cv2>
<num_contratto>test_shop_contract_test_1_2</num_contratto>
<tipo_richiesta/>
<tipo_servizio>paga_rico</tipo_servizio>
<gruppo/>
<descrizione>dummy</descrizione>
</StoreRequest>
<StoreResponse>
	<tipoCarta>MasterCard</tipoCarta>
	<codiceAutorizzazione/>
	<dataOra>20141127T152153</dataOra>
	<codiceEsito>101</codiceEsito>
	<descrizioneEsito>errore nei parametri</descrizioneEsito>
	<ParametriAggiuntivi></ParametriAggiuntivi>
	<mac>ef60bb0a66cfbbb481d7cd476f795169af671454</mac>
</StoreResponse>
</RootResponse>";

        $res = $this->xml2array($data_response);
        return $res;
        // close cURL resource, and free up systesm resources
        curl_close($ch);
    }

    function xml2array($contents, $get_attributes = 1, $priority = 'tag') {
        if (!$contents)
            return array();

        if (!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return array();
        }

        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);

        if (!$xml_values)
            return; //Hmm...

            
//Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array; //Refference
        //Go through the tags.
        $repeated_tag_index = array(); //Multiple tags with same name will be turned into an array
        foreach ($xml_values as $data) {
            unset($attributes, $value); //Remove existing values, or there will be trouble
            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data); //We could use the array by itself, but this cooler.

            $result = array();
            $attributes_data = array();

            if (isset($value)) {
                if ($priority == 'tag')
                    $result = $value;
                else
                    $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
            }

            //Set the attributes too.
            if (isset($attributes) and $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag')
                        $attributes_data[$attr] = $val;
                    else
                        $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }

            //See tag status and do the needed.
            if ($type == "open") {//The starting of the tag '<tag>'
                $parent[$level - 1] = &$current;
                if (!is_array($current) or ( !in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if ($attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag . '_' . $level] = 1;

                    $current = &$current[$tag];
                } else { //There was another element with the same tag name
                    if (isset($current[$tag][0])) {//If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level] ++;
                    } else {//This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array($current[$tag], $result); //This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag . '_' . $level] = 2;

                        if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = &$current[$tag][$last_item_index];
                }
            } elseif ($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if (!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                } else { //If taken, put all things inside a list(array)
                    if (isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;

                        if ($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level] ++;
                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag], $result); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' and $get_attributes) {
                            if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                            }

                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level] ++; //0 and 1 index is already taken
                    }
                }
            } elseif ($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level - 1];
            }
        }

        return($xml_array);
    }

    /**
     * Pay recurring transaction
     */
    public function payrecurringtransactionAction() {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //STEP:1
        //import shop transaction data from applane
        //$this->importShopTransactionData();
        $this->importshoptransactionAction();
        //STEP:2
        $this->UpdateShopTransactionDetail();
        //STEP:3 
        $this->payTransaction();

        exit('ok');
    }

    /**
     * Update shop transaction detail table
     */
    public function UpdateShopTransactionDetail() {
        $time = new \DateTime("now");
        $user_service = $this->get('user_object.service');
        $shop_profile_url = $this->container->getParameter('shop_profile_url'); //shop profile url
        $shop_wallet_url = $this->container->getParameter('shop_wallet_url'); //shop wallet url
        //get object of email template service
        $email_template_service = $this->container->get('email_template.service');
        //get object of shopping plus service
        $curl_obj = $this->container->get("store_manager_store.shoppingplus");
        //get angular host
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');

        /*         * get mongo db object * */
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $em = $this->container->get('doctrine')->getManager();
        /** get admin id * */
        $admin_id = $em
                ->getRepository('TransactionTransactionBundle:RecurringPayment')
                ->findByRole('ROLE_ADMIN');

        //get locale
        $locale = $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        $vat = $this->container->getParameter('vat');
        $reg_fee_newshop = $this->container->getParameter('reg_fee');
        $reg_fee_oldshop = $this->container->getParameter('reg_fee_oldshop');


        $em = $this->container->get('doctrine')->getManager();
        /** get entries from transaction detail * */
        $shop_transaction_entry = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionDetail')
                ->getShopTransaction();

        if (count($shop_transaction_entry) > 0) {
            foreach ($shop_transaction_entry as $transaction_record) {
                $transaction_id = $transaction_record->getId();
                $shop_id = $transaction_record->getShopId();
                $user_id = $transaction_record->getUserId();
                $shop_payable_amount = $transaction_record->getPayableAmount();
                $shop_pending_transaction = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionDetail')
                        ->getShopPedningTransaction($shop_id, $transaction_id);

                $pending_transaction_ids = array();
                $pending_transaction_amount = array();
                if (count($shop_pending_transaction) > 0) {

                    //pending transaction id
                    $pending_transaction_ids = array_map(function($shop_pending_record) {
                        return "{$shop_pending_record->getId()}";
                    }, $shop_pending_transaction);

                    //transaction pending amount
                    $pending_transaction_amount = array_map(function($shop_pending_record) {
                        return "{$shop_pending_record->getPayableAmount()}";
                    }, $shop_pending_transaction);
                } //end if

                $previous_pending_amount = array_sum($pending_transaction_amount);
                $previous_pending_id = implode(',', $pending_transaction_ids);
                $total_pending_amount = $shop_payable_amount + $previous_pending_amount;
                $total_pending_vat = ($total_pending_amount * $vat) / 100;
                $reg_fee = $reg_fee_newshop / 100;
                $reg_fee_vat = (($reg_fee * $vat) / 100);

                $total_shop_revenue = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionDetail')
                        ->getShopTotalRevenue($shop_id);
                $store_obj = $em
                        ->getRepository('StoreManagerStoreBundle:Store')
                        ->findOneBy(array('id' => $shop_id));
                $payment_status = '';
                if (count($store_obj) > 0) {
                    $payment_status = $store_obj->getPaymentStatus();
                }
                $pay_type = '';
                if ($total_shop_revenue > 200 && $payment_status == 0) {
                    $pay_type = 'T';
                    $total_amount_to_pay = $total_pending_amount + $total_pending_vat + $reg_fee + $reg_fee_vat;
                } else {
                    $pay_type = 'P';
                    $total_amount_to_pay = $total_pending_amount + $total_pending_vat;
                }

                //update the table
                $transaction_record->setPayType($pay_type);
                $transaction_record->setTotalAmount($total_amount_to_pay);
                $transaction_record->setRecurringVat($total_pending_vat);
                if ($pay_type == 'T') {
                    $transaction_record->setRegFee($reg_fee);
                    $transaction_record->setRegVat($reg_fee_vat);
                }

                $transaction_record->setPendingAmount($previous_pending_amount);
                $transaction_record->setPendingIds($previous_pending_id);
                $transaction_record->setPaymentDate($time);

                $transaction_record->setStatus(0);
                $transaction_record->setContractId(0); //this field will be updated in pay transaction function
                $em->persist($transaction_record);
                $em->flush();
            }
        }
    }

    /**
     * Pay transaction through cartasi
     */
    public function payTransaction() {
        $time = new \DateTime("now");
        $em = $this->container->get('doctrine')->getManager();
        //get all shops that status 0 and and id is max
        $shop_pending_transactions = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionDetail')
                ->getAllPedningTransaction();

        foreach ($shop_pending_transactions as $shop_pending_transaction) {
            $current_txn_id = $shop_pending_transaction->getId();
            $shop_id = $shop_pending_transaction->getShopId();
            $user_id = $shop_pending_transaction->getUserId();
            $pay_type = $shop_pending_transaction->getPayType();
            $total_amount_to_pay = $shop_pending_transaction->getTotalAmount();
            $pending_transaction_ids = $shop_pending_transaction->getPendingIds();
            /** get contract object * */
            $contract_default_obj = $em
                    ->getRepository('CardManagementBundle:Contract')
                    ->findOneBy(array('profileId' => $shop_id, 'defaultflag' => 1));

            if ($contract_default_obj) {
                $contract_number = $contract_default_obj->getContractNumber();
                $contract_id = $contract_default_obj->getId();
                $contract_email = $contract_default_obj->getMail();
                $contract_expiration = $contract_default_obj->getExpirationPan();
                // code for chiave 
                $prod_payment_mac_key = $this->container->getParameter('prod_payment_mac_key');

                // code for alias
                $prod_alias = $this->container->getParameter('prod_alias');

                // code for recurring_pay_url
                $recurring_pay_url = $this->container->getParameter('recurring_pay_url');

                //code for codTrans
                $codTrans = "6THCH" . time() . $user_id . $pay_type;
                $dec_amount = sprintf("%01.2f", $total_amount_to_pay);
                $amount_to_pay = $dec_amount * 100;
                $currency_code = 'EUR';
                //live
                $string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount_to_pay . "$prod_payment_mac_key";
                //testing
                //$string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount_to_pay . "$test_payment_mac_key";
                //$amount_to_pay = 1;
                $mac = sha1($string);
                $data = array(
                    'alias' => $prod_alias,
                    'tipo_servizio' => 'paga_rico',
                    'tipo_richiesta' => 'PR',
                    'mac' => $mac,
                    'divisa' => 'EUR',
                    'importo' => $amount_to_pay,
                    'codTrans' => $codTrans,
                    'num_contratto' => $contract_number,
                    'descrizione' => 'recurring payment',
                    'mail' => $contract_email,
                    'scadenza' => $contract_expiration
                );

               $pay_result = $this->recurringPaymentCurl($data, $recurring_pay_url);
               
               //maintain logger for cartasi response
               $applane_service = $this->container->get('appalne_integration.callapplaneservice');
               $handler = $this->container->get('monolog.logger.recurring');
               $monolog_data = "Request: Data=>".json_encode($data)." \n Url:".$recurring_pay_url;
               $monolog_data_pay_result = json_encode($pay_result);
               $applane_service->writeAllLogs($handler, $monolog_data, $monolog_data_pay_result);  
               //end to maintain the logger
               
                if (!empty($pay_result)) {

                    if ($pay_result['RootResponse']['StoreResponse']['codiceEsito'] == 0) {
                        ///code for payment success code 
                        $shop_pending_transaction->setPaymentDate($time);
                        $shop_pending_transaction->setTipoCarta($pay_result['RootResponse']['StoreResponse']['tipoCarta']);
                        $shop_pending_transaction->setPaese($pay_result['RootResponse']['StoreResponse']['paese']);
                        $shop_pending_transaction->setTipoProdotto($pay_result['RootResponse']['StoreResponse']['tipoProdotto']);
                        $shop_pending_transaction->setTipoTransazione($pay_result['RootResponse']['StoreResponse']['tipoTransazione']);
                        $shop_pending_transaction->setCodiceAutorizzazione($pay_result['RootResponse']['StoreResponse']['codiceAutorizzazione']);
                        $shop_pending_transaction->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
                        $shop_pending_transaction->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
                        $shop_pending_transaction->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_pending_transaction->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
                        $shop_pending_transaction->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_pending_transaction->setComment($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_pending_transaction->setContractTxnId($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_pending_transaction->setStatus(1);
                        $shop_pending_transaction->setContractId($contract_id);
                        $em->persist($shop_pending_transaction);
                        $em->flush();

                        /** mark shop registration fee paid* */
                        $store_obj = $em
                                ->getRepository('StoreManagerStoreBundle:Store')
                                ->findOneBy(array('id' => $shop_id));
                        if ($pay_type == 'T') {
                            if (count($store_obj) > 0) {
                                $store_obj->setPaymentStatus(1);
                                $em->persist($store_obj);
                                $em->flush();
                                //update on applane
                                $this->updateOnApplaneRegistration($shop_id);
                            }
                        }

                        //check if shop status is enabled
                        if (count($store_obj) > 0) {
                            $shop_status = $store_obj->getShopStatus();
                            if ($shop_status != 1) {
                                //enable the shop
                                $store_obj->setShopStatus(1);
                                $em->persist($store_obj);
                                $em->flush();
                            }
                        }
                        /** mark status as success for previous pending transaction * */
                        if (count($pending_transaction_ids) > 0) {
                            $update_pending_transaction = $em
                                    ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionDetail')
                                    ->setMultiTransactionStatus($pending_transaction_ids, 1);
                        }
                        //update on applane for success
                        $this->updateOnApplane($pending_transaction_ids, $current_txn_id, 'SUCCESS');
                        $this->transactionPaymentSuccessLogs($user_id, $shop_id); //remove the transaction payment notification logs if exists.
                    } else {
                        //code for payment failed
                        $shop_pending_transaction->setPaymentDate($time);
                        $shop_pending_transaction->setTipoCarta('');
                        $shop_pending_transaction->setPaese('');
                        $shop_pending_transaction->setTipoProdotto('');
                        $shop_pending_transaction->setTipoTransazione('');
                        $shop_pending_transaction->setCodiceAutorizzazione('');
                        $shop_pending_transaction->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
                        $shop_pending_transaction->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
                        $shop_pending_transaction->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_pending_transaction->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
                        $shop_pending_transaction->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_pending_transaction->setComment($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_pending_transaction->setContractTxnId($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_pending_transaction->setStatus(0);
                        $shop_pending_transaction->setContractId($contract_id);
                        $em->persist($shop_pending_transaction);
                        $em->flush();

                        $this->updateOnApplane($pending_transaction_ids, $current_txn_id, 'FAILED');
                        $this->transactionPaymentLogs($user_id, $shop_id); //make logs when payment failed.
                    }
                }
            } else {
                $shop_pending_transaction->setPaymentDate($time);
                $shop_pending_transaction->setComment("Contract not found");
                $shop_pending_transaction->setStatus(0);
                $shop_pending_transaction->setContractId(0);
                $em->persist($shop_pending_transaction);
                $em->flush();
                $this->updateOnApplane($pending_transaction_ids, $current_txn_id, 'FAILED');
                $this->transactionPaymentLogs($user_id, $shop_id); //make logs when payment failed.
            }
        }
    }

    /**
     * import shop transaction data from applane
     * @return boolean
     */
    public function importShopTransactionData() {
        /** to do - import transaction data from applane * */
        //logic here
        $time = new \DateTime('0000-00-00 00:00:00');
        $current_time = new \DateTime('now');

        $transaction_data = array(
            array(
                'date' => new \DateTime('2015-04-14T10:56:35.696Z'),
                'shop_id' => 1495,
                'amount' => 301,
                'payable_amount' => 6,
                'user_id' => 24,
                //'invoice_id' => '55316648dc51783a28b0f23f'
                'invoice_id' => '55316648dc51783a28b0f23b'
            ),
            array(
                'date' => new \DateTime('2015-04-14T10:56:35.696Z'),
                'shop_id' => 2146,
                'amount' => 301,
                'payable_amount' => 12,
                'user_id' => 25,
                //'invoice_id' => '55316648dc51783a28b0f23e'
                'invoice_id' => '55316648dc51783a28b0f23d'
            )
        );
        /** get entity manager object * */
        $em = $this->getDoctrine()->getManager();

        if (count($transaction_data) > 0) {
            foreach ($transaction_data as $transaction_record) {
                $date = $transaction_record['date'];
                $shop_id = $transaction_record['shop_id'];
                $amount = $transaction_record['amount'];
                $payable_amount = $transaction_record['payable_amount'];
                $user_id = $transaction_record['user_id'];

                /** save date in db * */
                $shop_transaction = new ShopTransactionDetail();
                $shop_transaction->setDate($date);
                $shop_transaction->setCreatedAt($current_time);
                $shop_transaction->setPaymentDate($time);
                $shop_transaction->setShopId($shop_id);
                $shop_transaction->setUserId($user_id);
                $shop_transaction->setAmount($amount);
                $shop_transaction->setPayableAmount($payable_amount);
                $shop_transaction->setInvoiceId($transaction_record['invoice_id']);
                $em->persist($shop_transaction);
                $em->flush();
            }
        }
        return true;
    }

    /**
     * Update on applane
     * @param string $pending_ids
     * @param int $current_id
     */
    public function updateOnApplane($pending_ids, $current_id, $status) {
        $vat = $this->container->getParameter('vat');
        $em = $this->container->get('doctrine')->getManager();
        $pending_shop_array = array();
        if (strlen($pending_ids) > 0) {
            //prepare pending shop array
            $pending_shop_array = explode(',', $pending_ids);
        }
        $current_id_array = array($current_id);
        //merge array
        $txns = array_merge($pending_shop_array, $current_id_array);

        foreach ($txns as $txn) {
            $txn_id = $txn;
            $transaction_data = $em
                    ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionDetail')
                    ->findOneBy(array('id' => $txn_id));

            $invoice_id = $transaction_data->getInvoiceId();
            $transaction_id_carte_si = $transaction_data->getContractTxnId();
            $transaction_note = $transaction_data->getComment();
            $paid_on = $transaction_data->getPaymentDate();
            $payment_date = $transaction_data->getPaymentDate();
            $payment_status = $status;
            $vat_amount = ($transaction_data->getPayableAmount() * $vat) / 100;  //calculate vat
            $amount_paid = ($transaction_data->getPayableAmount()) + $vat_amount; //calculate total amount paid
            $applane_data['invoice_id'] = $invoice_id;
            $applane_data['transaction_id_carte_si'] = $transaction_id_carte_si;
            $applane_data['transaction_note'] = $transaction_note;
            $applane_data['amount_paid'] = $amount_paid;
            //$applane_data['paid_on'] = $paid_on->format('Y-m-d') . "T00:00:00.000Z";
            $paid_on_sec = strtotime($paid_on->format('Y-m-d'));
            $applane_data['paid_on'] = date(DATE_RFC3339, ($paid_on_sec));
            //$applane_data['payment_date'] = $payment_date->format('Y-m-d') . "T00:00:00.000Z";
            $payment_date_sec = strtotime($payment_date->format('Y-m-d'));
            $applane_data['payment_date'] = date(DATE_RFC3339, ($payment_date_sec));
            $applane_data['payment_status'] = $payment_status;
            $applane_data['vat_amount'] = $vat_amount;

            //get dispatcher object
            $event = new FilterDataEvent($applane_data);
            $dispatcher = $this->container->get('event_dispatcher');
            $dispatcher->dispatch('shop.recurringupdate', $event);
        }
        return true;
    }

    /**
     * Update on applane for shop registration
     * @param type $shopid
     */
    public function updateOnApplaneRegistration($shopid) {
        $applane_data['shop_id'] = $shopid;
        //get dispatcher object
        $event = new FilterDataEvent($applane_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.registrationfeeupdate', $event);
    }

    /**
     * call payment logs need to uncomment the exit clause(first line)
     * 
     */
    public function callpaymentlogAction() {
      //  $paypal_service = $this->container->get('paypal_integration.paypal_transaction_check');
      //  $paypal_service->verifyPaypalTransactionStatus();
     //   $applane_service = $this->container->get('appalne_integration.callapplaneservice');
    //echo $applane_service->getShopRevenueFromApplaneByDate(5727);
    // exit;
       // exit('do not call me.');
//       $recurring_service1 = $this->container->get('recurring_shop.payment_notification');
//       $recurring_service = $this->container->get('recurring_shop.payment');
//       $recurring_service->subscriptionTransactionPaymentLogs(1, 1);
//       $recurring_service1->pendingSubscriptionPaymentFailedNotification(3); exit('sent');
        // $recurring_service->subscriptionPaymentSuccessLogs(1, 1); exit('removed');
      // $purchase_service = $this->container->get('export_management.purchase_import_export_command_service');
      // $purchase_service->purchaseimport(); exit('imported');
        $purchase_service = $this->container->get('export_management.purchase_import_export_command_service');
        $purchase_service->purchaseimport(); exit;
       $sales_service = $this->container->get('export_management.incassi_sale_export_command_service');
     //  $sales_service->salesexport(); exit('exit');
       $sales_service = $this->container->get('export_management.sales_import_export_command_service');
      // $sales_service->salesDataimport(); exit;
     //  $sales_service->findCounter();
       //$sales_service->importConnectTransaction();exit('counter');
      // $sales_service->salesimporttransactions();
      exit('imported');
//        $this->transactionPaymentLogs(1, 1); exit('saved....');
        //$this->transactionPaymentSuccessLogs(2, 1);
        $recurring_service = $this->container->get('recurring_shop.payment');
        $response = $recurring_service->importshopstransaction(); exit('saved');
        
        $recurring_service = $this->container->get('recurring_shop.payment');
        $response = $recurring_service->transactionPaymentLogs(1, 1);
       $recurring_service1 = $this->container->get('recurring_shop.payment_notification');
        $response = $recurring_service1->pendingPaymentFailedNotification(3); exit('saved'); //here 3 is admin id.
    }

    /**
     * pending payment failed logs 
     * @param int $user_id
     * @param int $shop_id
     * @return boolean 
     */
    public function transactionPaymentLogs($user_id, $shop_id) {
        $time = new \DateTime('now');
        $start_date = new \DateTime('now'); //current date time
        $end_date = $time->modify('+1 week'); //add 7 days.
        $pending_payment_type = 'PENDING_PAYMENT';
        $counter = 0; 
        $transaction_payment_log = new TransactionPaymentNotificationLog();
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $em = $this->container->get('doctrine')->getManager();
        /** get record if exists * */
        $transaction_payment_logs = $dm->getRepository('UtilityApplaneIntegrationBundle:TransactionPaymentNotificationLog')
                ->checkTransactionPaymentLogs($user_id, $shop_id, $pending_payment_type);

        if (!$transaction_payment_logs) {
            $transaction_payment_log->setToUserId($user_id);
            $transaction_payment_log->setToShopId($shop_id);
            $transaction_payment_log->setStartDate($start_date);
            $transaction_payment_log->setEndDate($end_date);
            $transaction_payment_log->setUpdatedDate($start_date);
            $transaction_payment_log->setSendCount($counter);
            $transaction_payment_log->setIsActive(1);
            $transaction_payment_log->setNotificationType($pending_payment_type);
            $dm->persist($transaction_payment_log);
            try {
                $dm->flush();
            } catch (\Exception $ex) {
                
            }
        }
//        else { //this else bolck is commented because we handling this in other cron
//            $counter = $transaction_payment_logs->getSendCount();
//            $new_counter = $counter + 1;
//            $transaction_payment_logs->setSendCount($new_counter);
//            $transaction_payment_logs->setUpdatedDate($start_date);
//            $dm->persist($transaction_payment_logs);
//        }
        return true;
    }

    /**
     * pending payment success logs remove
     * @param int $user_id
     * @param int $shop_id
     * @return boolean 
     */
    public function transactionPaymentSuccessLogs($user_id, $shop_id) {
        $pending_payment_type = 'PENDING_PAYMENT';
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        /** get record if exists * */
        $transaction_payment_log = $dm->getRepository('UtilityApplaneIntegrationBundle:TransactionPaymentNotificationLog')
                                      ->checkTransactionPaymentLogs($user_id, $shop_id, $pending_payment_type);
        if ($transaction_payment_log) { //if record exists we will remove.
            $dm->remove($transaction_payment_log);
            try {
                $dm->flush();
            } catch (\Exception $ex) {
                
            }
        }
        return true;
    }
}
