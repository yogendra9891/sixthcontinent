<?php

namespace CardManagement\CardManagementBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use CardManagement\CardManagementBundle\Entity\ShopSubscription;
use Paypal\PaypalIntegrationBundle\Entity\PaymentTransaction;
use CardManagement\CardManagementBundle\Entity\SubscriptionCartasiLog;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactions;

// validate the data.like iban, vatnumber etc
class SubscriptionService {

    protected $em;
    protected $container;
    const FAILED = "FAILED";
    const CONFIRMED = "CONFIRMED";
    const CONTRACT_NOT_FOUND = "CONTRACT_NOT_FOUND";
    const SUBSCRIBED = "SUBSCRIBED";
    const UNSUBSCRIBED = "UNSUBSCRIBED";
    const S = "S";

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em, Container $container) {
        $this->em = $em;
        $this->container = $container;
    }

    /**
     * Varify Iban Number
     * @param string $iban
     * @return boolean
     */
    public function recurringSubscription() {
        //create log
        $this->createLog('Enter In SubscriptionService->recurringSubscription');
        //get all the subscribed users whose renewal date is current date and 
        //subscription status is subscribed
        $subscribed_users = $this->em
                ->getRepository('CardManagementBundle:ShopSubscription')
                ->getSubscribedUsers();

        //prepare subscribed users array
        if (!$subscribed_users) {
          //no users
          $res_data = array('code' => 1073, 'message' => 'NO_SUBSCRIBED_USERS', 'data' => array());
          $this->createLog('SubscriptionService->recurringSubscription:'.  json_encode($res_data));
          $this->returnResponse($res_data);
        }
        //do subscription
       // $this->payTransaction($subscribed_users);
        $this->subscriptionAddInShopTransaction($subscribed_users);
        //create log
        $this->createLog('Exit From SubscriptionService->recurringSubscription');
    }

    /**
     * 
     * @param type $subscribed_users
     */
    public function subscriptionAddInShopTransaction($subscribed_users)
    {
         $this->createLog('Entering In SubscriptionService->payTransaction');//create Log
          $this->createLog('SubscriptionService->payTransaction User Array'.json_encode($subscribed_users));//create Log
          $em = $this->em;
           foreach ($subscribed_users as $subscribed_user) {
                $time = new \DateTime('now');
                $shop_id = $subscribed_user['shopId'];
                $user_id = $subscribed_user['subscriberId'];
            //check if already make entry in shop transaction table
                    $check_registration = $em
                            ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                            ->findOneBy(array('shopId' => $shop_id, 'type' => 'S', 'status' => 0));

                    if (!$check_registration) {
                            $vat = $this->container->getParameter('vat');
                            $subs_fee_shop = $this->container->getParameter('subscription_fee');
                            $sub_fee = $subs_fee_shop / 100;
                            $sub_fee_vat = (($sub_fee * $vat) / 100);
                            $total_payable_amount = ($sub_fee+$sub_fee_vat);
                            $shop_transaction = new ShopTransactions();
                            $shop_transaction->setDate($time);
                            $shop_transaction->setCreatedAt($time);
                            $shop_transaction->setShopId($shop_id);
                            $shop_transaction->setUserId($user_id);
                            $shop_transaction->setTotalTransactionAmount($sub_fee);
                            $shop_transaction->setPayableAmount($sub_fee);
                            $shop_transaction->setInvoiceId('');
                            $shop_transaction->setStatus(0);
                            $shop_transaction->setType(self::S);
                            $shop_transaction->setVat($sub_fee_vat);
                            $shop_transaction->setTotalPayableAmount($total_payable_amount);
                            $shop_transaction->setTransactionId('');
                            $em->persist($shop_transaction);
                    }
           }
             $em->flush();
             $this->createLog("SubscriptionService->payTransaction: SUCCESS");//create Log
    }
    
    
    /**
     * Subscription transaction
     * @param array $subscribed_users
     */
    public function payTransaction($subscribed_users) {
        $this->createLog('Entering In SubscriptionService->payTransaction');//create Log
        $this->createLog('SubscriptionService->payTransaction User Array'.json_encode($subscribed_users));//create Log
        $time = new \DateTime("now");
        $em = $this->em;
        $amount = (float)$this->container->getParameter('subscription_fee');
        $vat = $this->container->getParameter('vat');
        $total_amount_to_pay = $amount + (($amount * $vat) / 100); 
        foreach ($subscribed_users as $subscribed_user) {
            $shop_id = $subscribed_user['shopId'];
            $user_id = $subscribed_user['subscriberId'];
            $contract_id = $subscribed_user['contractId'];
            $txn_id = $subscribed_user['id'];
            
            //get default contract object
            $contract_obj = $this->getDefaultContract($shop_id);
            
            //get subscription contract object
            //$contract_obj = $this->getSubscriptionContract($contract_id);

            if ($contract_obj) {
                $contract_number = $contract_obj->getContractNumber();
                $contract_id = $contract_obj->getId();
                $contract_email = $contract_obj->getMail();
                $contract_expiration = $contract_obj->getExpirationPan();
                // code for chiave 
                $prod_payment_mac_key = $this->container->getParameter('prod_payment_mac_key');

                // code for alias
                $prod_alias = $this->container->getParameter('prod_alias');

                // code for recurring_pay_url
                $recurring_pay_url = $this->container->getParameter('recurring_pay_url');
                $pay_type = 'S';
                //code for codTrans
                $codTrans = "6THCH" . time() . $user_id . $pay_type;
                $dec_amount = sprintf("%01.2f", $total_amount_to_pay);

                //$amount_to_pay = $dec_amount * 100;
                $amount_to_pay = $dec_amount;

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
                $monolog_data = "SubscriptionService->payTransaction: Data=>" . json_encode($data) . " \n Url:" . $recurring_pay_url;
                $monolog_data_pay_result = json_encode($pay_result);
                $this->createLog($monolog_data, $monolog_data_pay_result);//create Log
                //end to maintain the logger

                if (!empty($pay_result)) {
                    if ($pay_result['RootResponse']['StoreResponse']['codiceEsito'] == 0) {
                        $sub_status = self::CONFIRMED;                        
                        //update shop subscription
                        $this->updateShopSubscription($txn_id, $sub_status);                       
                        //make new transaction in payament Transaction table.;
                        $txn_ref = $pay_result['RootResponse']['StoreRequest']['codTrans'];
                        $txn_value = $amount_to_pay;
                        $vat = (($amount * $vat) / 100);
                        $payment_status = self::CONFIRMED;;
                        $error_code = $pay_result['RootResponse']['StoreResponse']['codiceEsito'];
                        $error_description = $pay_result['RootResponse']['StoreResponse']['descrizioneEsito'];
                        $this->updatePaymentProcess($txn_id, 'SUBSCRIPTION', $user_id, $shop_id, 'CARTASI', $payment_status, $error_code, $error_description, $txn_ref, $txn_value, $vat, $contract_id, $paypal_id='');
                        
                        //mark the shop as subscribed
                        $this->updateShopSubscriptionStatus($shop_id, '1');
                        //update applane for subscribed transaction
                        $this->updateOnApplaneSusbcription($shop_id);
                        //maintain cartasi log
                        $this->cartasiLog($pay_result,$contract_id, $txn_id);
                    } else {
                        //code for payment failed
                        //make new transaction in payament Transaction table.;
                        $txn_ref = $pay_result['RootResponse']['StoreRequest']['codTrans'];
                        $txn_value = $amount_to_pay;
                        $vat = (($amount * $vat) / 100);
                        $payment_status = self::FAILED;
                        $error_code = $pay_result['RootResponse']['StoreResponse']['codiceEsito'];
                        $error_description = $pay_result['RootResponse']['StoreResponse']['descrizioneEsito'];
                        $this->updatePaymentProcess($txn_id, 'SUBSCRIPTION', $user_id, $shop_id, 'CARTASI', $payment_status, $error_code, $error_description, $txn_ref, $txn_value, $vat, $contract_id, $paypal_id='');
                        
                        //update the shop as unsubscribed
                        $this->updateShopSubscriptionStatus($shop_id, '0');
                        
                        //maintain cartasi log
                        $this->cartasiLog($pay_result,$contract_id, $txn_id);
                    }
                }
            } else {
                        //code for card not found
                        $this->createLog("SubscriptionService->payTransaction:Card Not Found");//create Log
                        $txn_ref = (isset($pay_result['RootResponse']['StoreRequest']['codTrans'])) ? $pay_result['RootResponse']['StoreRequest']['codTrans'] : '';
                        $txn_value = $total_amount_to_pay;
                        $vat = (($amount * $vat) / 100);
                        $payment_status = self::FAILED;
                        $error_code = self::CONTRACT_NOT_FOUND;
                        $error_description = self::CONTRACT_NOT_FOUND;
                        $this->updatePaymentProcess($txn_id, 'SUBSCRIPTION', $user_id, $shop_id, 'CARTASI', $payment_status, $error_code, $error_description, $txn_ref, $txn_value, $vat, $contract_id, $paypal_id='');
                        
                        //update the shop as unsubscribed
                        $this->updateShopSubscriptionStatus($shop_id, '0');                        
            }
        }
       $this->createLog("SubscriptionService->payTransaction: SUCCESS");//create Log
       exit('ok');
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

        $data_response = "<RootResponse>
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

    /**
     * Conver xml to array
     * @param type $contents
     * @param type $get_attributes
     * @param type $priority
     * @return type
     */
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
     * Maintain cartasi log
     */
    public function cartasiLog($pay_result, $contract_id = 1, $subscriptionId = 2) {
        $em = $this->em;
        ///code for cartasi success code 
        $time = new \DateTime("now");
        $SubscriptionCartasiLog = new SubscriptionCartasiLog();
        $SubscriptionCartasiLog->setPaymentDate($time);
        $SubscriptionCartasiLog->setTipoCarta($pay_result['RootResponse']['StoreResponse']['tipoCarta']);
        $SubscriptionCartasiLog->setPaese($pay_result['RootResponse']['StoreResponse']['paese']);
        $SubscriptionCartasiLog->setTipoProdotto($pay_result['RootResponse']['StoreResponse']['tipoProdotto']);
        $SubscriptionCartasiLog->setTipoTransazione($pay_result['RootResponse']['StoreResponse']['tipoTransazione']);
        $SubscriptionCartasiLog->setCodiceAutorizzazione($pay_result['RootResponse']['StoreResponse']['codiceAutorizzazione']);
        $SubscriptionCartasiLog->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
        $SubscriptionCartasiLog->setCodiceEstio($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
        $SubscriptionCartasiLog->setDescrizioneEstio($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
        $SubscriptionCartasiLog->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
        $SubscriptionCartasiLog->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
        $SubscriptionCartasiLog->setContractTxnId($pay_result['RootResponse']['StoreRequest']['codTrans']);
        $SubscriptionCartasiLog->setCreatedAt($time);
        $SubscriptionCartasiLog->setContractId($contract_id);
        $SubscriptionCartasiLog->setSubscriptionId($subscriptionId);
        try{
        $em->persist($SubscriptionCartasiLog);
        $em->flush();
        } catch(\Exception $e){
            
        }
        return true;
    }

    /**
     * Update payment process table
     * @param int $txn_id
     * @param string $subscripton
     * @param int $user_id
     * @param int $shop_id
     * @param string $mode
     * @param string $payment_status
     * @param string $error_code
     * @param string $error_description
     * @param string $txn_ref
     * @param float $txn_value
     * @param float $vat
     * @param int $contract_id
     * @param int $paypal_id
     * @return boolean
     */
    public function updatePaymentProcess($txn_id, $subscripton, $user_id, $shop_id, $mode, $payment_status, $error_code, $error_description, $txn_ref, $txn_value, $vat, $contract_id, $paypal_id)
    {
        $this->createLog("Enter In SubscriptionService->updatePaymentProcess");//create Log
        $pay_tx_data['item_id'] = $txn_id;
        $pay_tx_data['reason'] = $subscripton;
        $pay_tx_data['citizen_id'] = $user_id;
        $pay_tx_data['shop_id'] = $shop_id;
        $pay_tx_data['payment_via'] = $mode;
        $pay_tx_data['payment_status'] = $payment_status;
        $pay_tx_data['error_code'] = $error_code;
        $pay_tx_data['error_description'] = $error_description;
        $pay_tx_data['transaction_reference'] = $txn_ref;
        $pay_tx_data['transaction_value'] = $txn_value;
        $pay_tx_data['vat_amount'] = $vat;
        $pay_tx_data['contract_id'] = $contract_id;
        $pay_tx_data['paypal_id'] = $paypal_id;
        $payment_txn = $this->container->get('paypal_integration.payment_transaction');
        $payment_txn->addPaymentTransaction($pay_tx_data);
        $this->createLog("Exit From SubscriptionService->updatePaymentProcess");//create Log
        return true;
    }
    
    /**
     * Update shop subscription
     * @param int $txn_id
     * @param string $sub_status
     */
    public function updateShopSubscription($txn_id, $sub_status)
    {
        $payment_confirmed = self::CONFIRMED;
        $payment_failed = self::CONFIRMED;
        $subscribed = self::SUBSCRIBED;
        $unsubscribed = self::UNSUBSCRIBED;
        //get subscription object
        $em = $this->em;
        $subscription_obj = $em
                ->getRepository('CardManagementBundle:ShopSubscription')
                ->findOneBy(array('id' => $txn_id));
        if ($subscription_obj) {
             //manage start date
             $start_date = new \DateTime('now');
             $start_date_updated = $start_date;

             //manage expiry date
             $expiry_date = $subscription_obj->getExpiryDate();
             $interval_date = $subscription_obj->getIntervalDate();
             
             $expiry_date_updated = $expiry_date->modify('+1 month'); //adding 1 month
             $interval_date_updated = $interval_date->modify('+1 month'); //adding 1 month
             
             $expiry_date_updated1 = $expiry_date_updated->format('Y-m-d H:i:s');
             $expiry_date_updated2 = strtotime($expiry_date_updated1);
             $expiry_date_updated3 = new \DateTime("@$expiry_date_updated2");

             $interval_date_updated1 = $interval_date_updated->format('Y-m-d H:i:s');
             $interval_date_updated2 = strtotime($interval_date_updated1);
             $interval_date_updated3 = new \DateTime("@$interval_date_updated2");

            if ($sub_status == $payment_confirmed) {
                //if success
                $subscription_obj->setStartDate($start_date_updated);
                $subscription_obj->setExpiryDate($expiry_date_updated3);
                $subscription_obj->setIntervalDate($interval_date_updated3);
                $subscription_obj->setStatus($subscribed);
            } elseif($sub_status == $payment_failed){
                //if failed
                 $subscription_obj->setStatus($unsubscribed);
            }

            try{
            $em->persist($subscription_obj);
            $em->flush();
            }catch(\Exception $e){
                
            }
        }
        return true;
    }
    
    /**
     * Update shop subscription
     * @param type $shop_id
     * @return boolean
     */
    public function updateShopSubscriptionStatus($shop_id, $status)
    {
        $this->createLog("Enter In SubscriptionService->updateShopSubscriptionStatus");//create Log
        $em = $this->em;
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $shop_id));
        if($store_obj){
           $store_obj->setIsSubscribed($status);
           try{
           $em->persist($store_obj);
           $em->flush();
           }catch(\Exception $e){
               
           }
        }
        $this->createLog("Exit From SubscriptionService->updateShopSubscriptionStatus");//create Log
        return true;
    }
    
    /**
     * Get Default contract
     * @param int $shop_id
     * @return array
     */
    public function getDefaultContract($shop_id)
    {
        $em = $this->em;
          $contract_default_obj = $em
                    ->getRepository('CardManagementBundle:Contract')
                    ->findOneBy(array('profileId' => $shop_id, 'defaultflag' => 1, 'status' => 1, 'isDelete' => 0));
          return $contract_default_obj;
    }
    
    /**
     * Get Subscription contract
     * @param int $txn_id
     * @return type
     */
    public function getSubscriptionContract($txn_id)
    {
        $em = $this->em;
          $contract_obj = $em
                    ->getRepository('CardManagementBundle:Contract')
                    ->findOneBy(array('id' => $txn_id, 'status' => 1));
          return $contract_obj;
    }
    
    /**
     * Update on applane for shop subscription
     * @param type $shopid
     */
    public function updateOnApplaneSusbcription($shopid) {
        $this->createLog("Enter In SubscriptionService->updateOnApplaneSusbcription");//create Log
        $applane_data['shop_id'] = $shopid;
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $susbcription_id = $applane_service->onShopSubscriptionAddAction($shopid);
        $this->createLog("Exit From SubscriptionService->updateOnApplaneSusbcription");//create Log
        return $susbcription_id;
    }
    
   /**
    * Create subscription log
    * @param string $monolog_req
    * @param string $monolog_response
    */
    public function createLog($monolog_req = null, $monolog_response = null){
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.subscription_log');
        //$monolog_data = "Entering In RecurringShopPaymentService.importshopstransaction";
        $applane_service->writeAllLogs($handler, $monolog_req, array());  
        return true;
    }
    
    /**
     * Return response
     * @param string $resp_data
     */
    public function returnResponse($resp_data)
    {
        echo json_encode($resp_data);
        exit();
    }
    
    /**
     * 
     */
    public function __toString() {
    }
}
