<?php

namespace CardManagement\CardManagementBundle\Controller;

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
use CardManagement\CardManagementBundle\Entity\Contract;
use CardManagement\CardManagementBundle\Entity\Payment;
use CardManagement\CardManagementBundle\Entity\ShopRegPayment;
use StoreManager\StoreBundle\Entity\Store;
use Transaction\TransactionBundle\Entity\RecurringPendingPayment;
use Notification\NotificationBundle\Document\UserNotifications;
use Transaction\TransactionBundle\Document\RecurringPaymentLog;
use Transaction\TransactionBundle\Entity\RecurringPayment;
use StoreManager\StoreBundle\Entity\Storeoffers;
use StoreManager\StoreBundle\Entity\UserToStore;
use WalletManagement\WalletBundle\Entity\ShopDiscountPosition;
use WalletManagement\WalletBundle\Entity\UserDiscountPosition;
use WalletManagement\WalletBundle\Entity\UserShopCredit;
use StoreManager\StoreBundle\Entity\Transactionshop;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Sonata\UserBundle\Entity\BusinessCategory;
require_once(__DIR__ . '/../Resources/lib/tcpdf/tcpdf_include.php');
require_once(__DIR__ . '/../Resources/lib/tcpdf/tcpdf.php');
use TCPDF;        

class OneClickPaymentController extends Controller {

    protected $miss_param = '';
    protected $base_six = 1000000;

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
     * creating one click payment url
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function postCreateoneclickpaymenturlsAction(Request $request) {

        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('profile_id', 'user_id', 'payment_type', 'return_url', 'cancel_url');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $em = $this->container->get('doctrine')->getManager();
        $payment_type_check_arr = array('REG_FEE', 'ADD_CARD', 'PENDING_PAYMENT');

        $user_id = $object_info->user_id;
        $profile_id = $object_info->profile_id;
        $return_url = urlencode($object_info->return_url);
        $cancel_url = urlencode($object_info->cancel_url);

        //code for tipo_servizio
        $payment_type = $object_info->payment_type; // REG_FEE / ADD_CARD

        if (!in_array($payment_type, $payment_type_check_arr)) {
            return array('code' => 300, 'message' => 'YOU_HAVE_ENTER_A_WRONG_PAYMENT_TYPE ' . $payment_type, 'data' => $data);
        }

        // code for chiave 
        $prod_payment_mac_key = $this->container->getParameter('prod_payment_mac_key');

        // code for alias
        $prod_alias = $this->container->getParameter('prod_alias');

        // test credentiail
        //$test_payment_mac_key = $this->container->getParameter('test_payment_mac_key');
        //$test_alias = $this->container->getParameter('test_alias');

        $vat = $this->container->getParameter('vat');
        $reg_fee_newshop = $this->container->getParameter('reg_fee');
        $reg_fee_oldshop = $this->container->getParameter('reg_fee_oldshop');
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => (int) $profile_id));
        //if store not found
        if (!$store_obj) {
            $res_data = array('code' => 100, 'message' => 'STORE_DOES_NOT_EXISTS', 'data' => $data);
            return $res_data;
        }
        
        $reg_fee = 0;
        $old_shop_date = new \DateTime('2014-11-14');
        $shop_created_at = $store_obj->getCreatedAt();
        
        if($shop_created_at<$old_shop_date) {
            $reg_fee = $reg_fee_oldshop;
        }else{
            $reg_fee = $reg_fee_newshop;
        }
        
        
        // code for importo
        $amount = 0;
        $reg_amount = 0;
        $payment_type_send = '';
        if ($payment_type == 'REG_FEE') {
            $amount = $reg_fee + (($reg_fee * $vat) / 100);
            $payment_type_send = 'R';
        } else if ($payment_type == 'ADD_CARD') {
            $amount = 1;
            $payment_type_send = 'C';
        } else if ($payment_type == 'PENDING_PAYMENT') {

            //get entries from transaction shop
            $store_pending_amount = $em
                    ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                    ->getShopPendingAmount($profile_id);
            $reg_fee_flag = 0;
            if ($store_obj) {
                $payment_status = $store_obj->getPaymentStatus();
                if ($payment_status == 0) {
                    $reg_fee_flag = 1;
                    $reg_amount = $reg_fee + (($reg_fee * $vat) / 100);
                }
            }
            $amount_to_check = $this->converToEuro($store_pending_amount);

            if ($amount_to_check <= 5 && $reg_fee_flag == 0) {
                $data = array('store_id' => $profile_id);
                $res_data = array('code' => '251', 'message' => 'AMOUNT_LESS_THAN_MINIMUM_AMOUNT', 'data' => $data);
                echo json_encode($res_data);
                exit();
            }
            $store_pending_amount = $store_pending_amount + (($store_pending_amount * $vat) / 100);
          
            $cal_amount = $this->converToEuro($store_pending_amount);
           
            $dec_amount = sprintf("%01.2f", $cal_amount);
            $pending_amount = $dec_amount * 100;

            $sub_total = 0;
            /*
            if ($reg_fee_flag == 1 && $store_pending_amount <= 5) {
                $sub_total = $reg_amount;
                $payment_type_send = 'R';
            } else if ($reg_fee_flag == 1 && $store_pending_amount > 5) {
                $sub_total = $reg_amount + $pending_amount;
                $payment_type_send = 'T';
            } else if ($reg_fee_flag == 0 && $store_pending_amount > 5) {
                $sub_total = $pending_amount;
                $payment_type_send = 'P';
            }
            */
            
            if ($reg_fee_flag == 1 && $store_pending_amount > 0) {
                $sub_total = $reg_amount + $pending_amount;
                $payment_type_send = 'T';
            } else if ($reg_fee_flag == 1 ) {
                $sub_total = $reg_amount;
                $payment_type_send = 'R';
            } else if ($reg_fee_flag == 0 && $store_pending_amount > 5) {
                $sub_total = $pending_amount;
                $payment_type_send = 'P';
            }
            $amount = $sub_total;
        }

        //code for codTrans
        $codTrans = "6THCH" . time() . $user_id . $payment_type_send;

        // code for divisa
        $currency_code = 'EUR';

        //code for string for live
        $string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount . "$prod_payment_mac_key";
        //code for sting for test - fix
        //$string = "codTrans=" . $codTrans . "divisa=" . $currency_code . "importo=" . $amount . "$test_payment_mac_key";
        //code for mac
        $mac = sha1($string);

        // fix
        //$prod_alias = $test_alias;
        //code for symfony url hit by payment gateway
        $urlpost = $this->container->getParameter('urlpost_payment');


        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        //check user is already registerd
        $contract_result = $em
                ->getRepository('CardManagementBundle:Contract')
                ->findBy(array('profileId' => $profile_id), array('createTime' => 'DESC'), 1, 0);

        //code for contract number 
        if ($contract_result) {
            $pre_contract_num = $contract_result[0]->getContractNumber();
            $num_to_inc = explode('_', $pre_contract_num);
            $inc_count = (int) $num_to_inc[3] + 1;
            //$contract_number = 'shop_contract_' . $profile_id . '_' . $inc_count;
            $contract_number = 'shop_contract_' . $profile_id . '_' . time();
        } else {
            //$contract_number = 'shop_contract_' . $profile_id . '_1';
            $contract_number = 'shop_contract_' . $profile_id . '_'. time();
        }

        //code for session id
        $session_id = $user_id;

        //code for descrizione
        $description_amount = $amount/100;
        $description = "payment for $description_amount euro with type $payment_type to Sixthcontinent";

        //code for url that is angular js url for payment success and failure
        $url = $return_url;

        //code for tipo_servizio (type service)        
        $type_service = "paga_rico";


        //code for final url to return
        $final_url = $this->container->getParameter('oneclick_pay_url')."?session_id=$session_id&alias=$prod_alias&urlpost=$urlpost&tipo_servizio=$type_service&mac=$mac&divisa=$currency_code&importo=$amount&codTrans=$codTrans&url=$url&url_back=$cancel_url&num_contratto=$contract_number&descrizione=$description";

        $data = array('url' => $final_url);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Function to retrieve current applications base URI 
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        // return scheme, host and base URL
        // return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl() . '/';

        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl();
    }

    /**
     * creating one creating new contract
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return boolean
     */
    public function createcontractAction() {
        $this->_log('Initialize create contract process.');
        //finding the entity manager object.
        $em = $this->getDoctrine()->getManager();
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $time = new \DateTime('now');
        $curl_obj = $this->container->get("store_manager_store.shoppingplus");
        //get object of email template service
        $email_template_service = $this->container->get('email_template.service');
        $user_service = $this->get('user_object.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_profile_url   = $this->container->getParameter('shop_profile_url'); //shop profile url
        $shop_wallet_url   = $this->container->getParameter('shop_wallet_url'); //shop wallet url
        //get locale
        $locale = $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        $store_object_info = array();
        $pending_amount_vat_apply = 0;
        $vat = $this->container->getParameter('vat');
        $reg_fee_newshop = $this->container->getParameter('reg_fee');
        $reg_fee_oldshop = $this->container->getParameter('reg_fee_oldshop');
        $reg_fee = 0;

       
       /*  $_POST['num_contratto'] = "shop_contract_1495_109";
       
          $_POST['alias'] = "fdg";
          $_POST['tipoTransazione'] = "india";
          $_POST['data'] = '34343434';
          $_POST['orario'] = '3455';
          $_POST['$BRAND'] ="sdf";
          $_POST['tipoProdotto'] = "register";
          $_POST['nome'] = 'pradeep';
          $_POST['cognome'] = 'kumar';
          $_POST['languageId'] = 'in';
          $_POST['pan'] = '345345345';
          $_POST['nazionalita'] = 'indian';
          $_POST['session_id'] = '1';
          $_POST['email'] = "pradeep@gmail.com";
          $_POST['scadenza_pan'] = "1234534";
          $_POST['importo'] = '12810';
          #$_POST['importo'] = '13908';
          #$_POST['importo'] = '12078';
          
          $_POST['descrizione'] = '138520000';
          $_POST['codTrans'] = '6THCH14219319624T';
          $_POST['mac'] = '1098ccccc0';
          $_POST['divisa'] = 'ered';
          $_POST['esito'] = "Ok";
          $_POST['payment_type'] = "Ok";
          
          */
     
       //test data
   

        $_contractFileToWriteData = __DIR__ . '/../Resources/createcontract.txt';
        $this->_log('Open file to write $_POST data on path '. $_contractFileToWriteData);

        // code for save the $_POST data to createcontract.txt file.
        $file = fopen(__DIR__ . '/../Resources/createcontract.txt', "w");
        $msg_to_write = serialize($_POST);
        fwrite($file, $msg_to_write);
        fclose($file);
        $this->_log('$_POST data has been written in file '.$_contractFileToWriteData);

        if (isset($_POST['esito']) && strtolower($_POST['esito']) == 'ok') {
            
            /*check for brand */
            if(isset($_POST['$BRAND'])) {
                $brand = $_POST['$BRAND'];
            }else if(isset($_POST['brand'])) {
                $brand = $_POST['brand'];
            }else{
                $brand = '';
            }
            
            /*check for email */
            if(isset($_POST['email'])) {
                $gateway_email = $_POST['email'];
            }else if(isset($_POST['mail'])) {
                $gateway_email = $_POST['mail'];
            }else{
                $gateway_email = '';
            }

            $this->_log('Checking contract if already exists.');
            //check contract is already exist
            $check_contract_result = $em
                    ->getRepository('CardManagementBundle:Contract')
                    ->findBy(array('contractNumber' => $_POST['num_contratto']));
            if (!$check_contract_result) {
                $this->_log('Contract not found. Continue process for new contract generation');
                $activate_shop = 0;
                $shop_status_for_check = 0;
                $first_attempt_to_add_cc = 0;
                $payment_id= 0;
                $is_default_flag = 0;
                $contract_str = explode('_', $_POST['num_contratto']);
                $this->_log("post object from payment gateway is :" .json_encode($_POST));
                $profile_id = $contract_str[2];
                $shop_name = '';
                $em = $this->getDoctrine()->getManager();
                $this->_log('Getting store information using id : '.$profile_id);
                $store_obj = $em
                        ->getRepository('StoreManagerStoreBundle:Store')
                        ->findOneBy(array('id' => (int) $profile_id));
                
                /** logic for registration fee **/
                $old_shop_date = new \DateTime('2014-11-14');
                $shop_created_at = $store_obj->getCreatedAt();
                
                //get old contract status
                $shop_old_contract_status = $store_obj->getNewContractStatus();
                
                if($shop_created_at<$old_shop_date) {
                    $reg_fee = $reg_fee_oldshop;
                }else{
                    $reg_fee = $reg_fee_newshop;
                }
                
                $pay_type = substr($_POST['codTrans'], -1);
                $this->_log('Getting information from TransactionTransactionBundle:RecurringPayment by role : ROLE_ADMIN');
                $admin_id = $em
                        ->getRepository('TransactionTransactionBundle:RecurringPayment')
                        ->findByRole('ROLE_ADMIN');
                if ($store_obj) {
                    $store_object_info = $user_service->getStoreObjectService($profile_id);
                    $shop_status_for_check = $store_obj->getShopStatus();
                    $shop_name = $store_obj->getName();

                    $credit_status_check = $store_obj->getCreditCardStatus();
                    if ($credit_status_check == 0 || $credit_status_check == '0') {
                        $is_default_flag = 1;
                    }
                }

                // create a new contract
                $contract = new Contract();
                $contract->setAlias($_POST['alias']);
                $contract->setRegion($_POST['tipoTransazione']);
                $contract->setRegistrationTime($time);
                $contract->setContractNumber($_POST['num_contratto']);
                $contract->setBrand($brand);
                $contract->setProductType($_POST['tipoProdotto']);
                $contract->setName($_POST['nome']);
                $contract->setLastName($_POST['cognome']);
                $contract->setLanguageCode($_POST['languageId']);
                $contract->setPan($_POST['pan']);
                $contract->setNationality($_POST['nazionalita']);
                $contract->setSessionId($_POST['session_id']);
                $contract->setMail($gateway_email);
                $contract->setDeleted(0);
                $contract->setProfileId($profile_id);
                $contract->setStatus(1);
                $contract->setDefaultflag($is_default_flag);
                $contract->setCreateTime($time);
                $contract->setExpirationPan($_POST['scadenza_pan']);
                $em->persist($contract); //storing the comment data.
                $em->flush();
                $this->_log('Contract data saved to database with id: '. $contract->getId());

                $contract_id = $contract->getId();
                $amount_to_save = $_POST['importo'] * 10000;
                $payment_id = 0;
                if ($contract_id) {
                    $this->_log('Setting data for contract '.$contract_id.'  to update in Payment table');
                    $payment = new Payment();
                    $payment->setAmount($_POST['importo']);
                    $payment->setContractId($contract_id);
                    $payment->setDescription($_POST['descrizione']);
                    $payment->setTrasactionCode($_POST['codTrans']);
                    $payment->setMac($_POST['mac']);
                    $payment->setCurrencyCode($_POST['divisa']);
                    $payment->setRegistrationTime($time);
                    $payment->setTrasactionTime($time);
                    $payment->setPaymentType($pay_type);
                    $payment->setStatus(1);
                    $em->persist($payment); //storing the comment data.
                    $em->flush();
                    $payment_id = $payment->getId();
                    $this->_log('Data for contract '.$contract_id.'  has been updated in Payment table on ID: '.$payment_id);
                }
                if($payment) {
                    $payment_id = $payment->getId();
                }

                if ($store_obj) {
                    $credit_status = $store_obj->getCreditCardStatus();
                    $shop_status = $store_obj->getShopStatus();
                    $this->_log('Initial status of store '.$store_obj->getId().'. Credit Card Status : '.$credit_status.', Shop Status : '.$shop_status);
                    if ($credit_status == 0 && $shop_status == 0) {
                        $activate_shop = 1;                        
                    }
                    if($credit_status == 0) {
                        $first_attempt_to_add_cc = 1;
                    }
                    //check for payment type
                    //if payment is done only for card
                    if ($pay_type == 'C') {
                        $store_obj->setCreditCardStatus(1);
                        $em->persist($store_obj);
                        $em->flush();
                    } else if ($pay_type == 'R') {
                        $transaction_id_arr = array();
                        $transaction_recurring_id = serialize(array_unique($transaction_id_arr));
                        //if payment is done only for registration fee and card
                        $activate_shop = 1;

                        $store_obj->setCreditCardStatus(1);
                        $store_obj->setPaymentStatus(1);
                        $em->persist($store_obj);
                        $em->flush();
                        $this->_log('Updated status of store '.$store_obj->getId().'. Credit Card Status : '.$store_obj->getCreditCardStatus().', Payment Status : '.$store_obj->getPaymentStatus());
                        $vat = $this->container->getParameter('vat');

                        $vat_amount = ($reg_fee * $vat) / 100;
                        $vat_amount_save = $vat_amount * 10000;
                        $reg_fee_save = $reg_fee * 10000;
                        
                        $shop_reg_fee = new ShopRegPayment();
                        $shop_reg_fee->setRegFee($reg_fee_save);
                        $shop_reg_fee->setShopId($profile_id);
                        $shop_reg_fee->setAmount($amount_to_save);
                        $shop_reg_fee->setCreatedAt($time);
                        $shop_reg_fee->setStatus(1);
                        $shop_reg_fee->setVat($vat_amount_save);
                        $shop_reg_fee->setPendingAmount(0);
                        $shop_reg_fee->setContractId($contract_id);
                        $shop_reg_fee->setPaymentId($payment_id);
                        $shop_reg_fee->setMethod('onclick');
                        $shop_reg_fee->setTransactionCode($_POST['codTrans']);
                        $shop_reg_fee->setRecurringPaymentId(0);
                        $shop_reg_fee->setPendingAmountVat(0);
                        $shop_reg_fee->setDescription('registration fee samount paid using onclick');
                        $shop_reg_fee->setTransactionType('R');
                        $shop_reg_fee->setTransactionShopId($transaction_recurring_id);
                        $em->persist($shop_reg_fee);
                        $em->flush();
                        $this->_log('Updated ShopRegPayment with ID: '.$shop_reg_fee->getId());
                    }else if($pay_type == 'P' || $pay_type == 'T') {
                        $activate_shop = 1;
                        if ($pay_type == 'T') {
                            $store_obj->setPaymentStatus(1);
                        }
                        $store_obj->setCreditCardStatus(1);
                        $em->persist($store_obj);
                        $em->flush();
                        //get entries from transaction shop
                        $this->_log('Getting information from TransactionTransactionBundle:RecurringPendingPayment with SHOP ID: '.$profile_id);
                        $get_transaction_id = $em
                                ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                                ->getTransactionIdForShop($profile_id);
                        $transaction_id_arr = array();
                        foreach ($get_transaction_id as $record) {
                            $transaction_id_arr[] = $record['transactionId'];
                        }
                        $update_transaction_shop_ids = array_unique($transaction_id_arr);
                      
                        
                        $transaction_recurring_id = serialize(array_unique($transaction_id_arr));


                        $tot_quota = $_POST['importo'] * 10000;
                        $time = new \DateTime("now");
                        // comment for now

                        $recurring_payment = new RecurringPayment();
                        $recurring_payment->setShopId($profile_id);
                        $recurring_payment->setTipoCarta($brand);
                        $recurring_payment->setPaese($_POST['nazionalita']);
                        $recurring_payment->setTipoProdotto($_POST['tipoProdotto']);
                        $recurring_payment->setTipoTransazione('');
                        $recurring_payment->setCodiceAutorizzazione('');
                        $recurring_payment->setDataOra(date('Ymd:h:i:s'));
                        $recurring_payment->setCodiceEsito(0);
                        $recurring_payment->setDescrizioneEsito("pending payment done + credit card add");
                        $recurring_payment->setMac($_POST['mac']);
                        $recurring_payment->setCreatedAt($time);
                        $recurring_payment->setCodTrans($_POST['codTrans']);
                        $recurring_payment->setTransactionId($transaction_recurring_id);
                        $recurring_payment->setAmount($tot_quota);

                        $em->persist($recurring_payment);
                        $em->flush();
                       $this->_log('Updating information for RecurringPayment with ID: '.$recurring_payment->getId());
                        // save data in shop_reg_payment table
                        $shop_reg_fee = new ShopRegPayment();
                        $shop_reg_fee->setShopId($profile_id);
                        $shop_reg_fee->setAmount($amount_to_save);
                        $shop_reg_fee->setCreatedAt($time);
                        $shop_reg_fee->setStatus(1);
                        $shop_reg_fee->setContractId($contract_id);
                        $shop_reg_fee->setPaymentId($payment_id);
                        $shop_reg_fee->setMethod('onclick');
                        $shop_reg_fee->setTransactionShopId($transaction_recurring_id);
                        $shop_reg_fee->setTransactionCode($_POST['codTrans']);
                        $shop_reg_fee->setRecurringPaymentId(0);
                        if ($pay_type == 'P') {
                            $pending_amount_vat_apply = ($amount_to_save * $vat)/(100 + $vat);
                            $shop_reg_fee->setRegFee(0);
                            $shop_reg_fee->setPendingAmountVat($pending_amount_vat_apply);
                            $shop_reg_fee->setTransactionType('P');
                            $shop_reg_fee->setVat(0);
                            $shop_reg_fee->setPendingAmount($amount_to_save);
                            $shop_reg_fee->setDescription('pending amount paid using onclick');
                        } else if ($pay_type == 'T') {
                            $vat = $this->container->getParameter('vat');                           
                            

                            $vat_amount = ($reg_fee * $vat) / 100;
                            $vat_amount_save = $vat_amount * 10000;

                            $reg_fee_save = $reg_fee * 10000;
                            $pending_amount = $amount_to_save - ($reg_fee_save + $vat_amount_save);
                            $pending_amount_vat_apply = ($pending_amount * $vat)/(100 + $vat);
                            $shop_reg_fee->setRegFee($reg_fee_save);
                            $shop_reg_fee->setVat($vat_amount_save);
                            $shop_reg_fee->setPendingAmountVat($pending_amount_vat_apply);
                            $shop_reg_fee->setPendingAmount($pending_amount);
                            $shop_reg_fee->setDescription('pending + registration fee samount paid using onclick');
                            $shop_reg_fee->setTransactionType('T');
                        }

                        $em->persist($shop_reg_fee);
                        $em->flush();
                        $this->_log('Updating information for ShopRegPayment with ID: '.$shop_reg_fee->getId());  
                        /*change status in transaction shop table for payment is done*/
                        if(count($update_transaction_shop_ids) > 0) {
                            $update_transaction_status = $em
					->getRepository('StoreManagerStoreBundle:Transactionshop')
					->updateTransactionShopStatus($update_transaction_shop_ids);
                        }
                        
                        $this->_log('update paid field in recurringpendingpayment Shop ID: '.$profile_id);
                        //update paid field in recurringpendingpayment
                        $store_pending_update = $em
                                ->getRepository('TransactionTransactionBundle:RecurringPendingPayment')
                                ->updatePaidRecurringPayment($profile_id);
                        /*
                        $update_pay_log = $dm
                                ->getRepository('TransactionTransactionBundle:RecurringPaymentLog')
                                ->updateRecurringPaymentLog($profile_id);
                        */
                        
                        /** remove log from billing cycle mongo db table **/
                        $this->_log('remove log from billing cycle mongo db table Shop ID: '.$profile_id.' and PENDING_PAYMENT_NOT_DONE');
                        $delete_billing_cycle_log = $dm
                                        ->getRepository('StoreManagerStoreBundle:BillerCycleLog')
                                        ->removeDeleteBillingCycleLog($profile_id, 'PENDING_PAYMENT_NOT_DONE');
                        
                        $to_id = $_POST['session_id'];
                        
                        $postService = $this->container->get('post_detail.service');
                        $reciever = $postService->getUserData($to_id, true);
                        $_locale = empty($reciever[$to_id]['current_language']) ? $this->container->getParameter('locale') : $reciever[$to_id]['current_language'];
                        $_lang_array = $this->container->getParameter($_locale);
                        
                        $mail_recurring_sub = $_lang_array['PAYMENT_SUCCESS_SUBJECT'];
                        $mail_recurring_body = sprintf($_lang_array['PAYMENT_SUCCESS_BODY'],$shop_name);
                        if ($store_object_info) {
                            $thumb_path = $store_object_info['thumb_path'];
                        } else {
                            $thumb_path = '';
                        }

                        $this->_log('Sending welcome mail for shop owner with store wallet link.');
                        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
                        $href = $angular_app_hostname.$shop_wallet_url. "/".$profile_id;
                        $link = $email_template_service->getLinkForMail($href, $_locale);
                        
                        $bodyData = $mail_recurring_body.'<br><br>'.$link;
                        
                        
//                        $email_body_to_send = $email_template_service->EmailTemplateService($mail_recurring_body, $thumb_path, $link, $_POST['session_id']);

                       // $mail_notification = $email_template_service->sendEmailNotification($mail_recurring_sub, $admin_id, $_POST['session_id'], $email_body_to_send);
                                
                        $mail_notification = $email_template_service->sendMail($reciever, $bodyData, $mail_recurring_sub, $mail_recurring_sub, $thumb_path, 'STORE_JOIN_REQUEST');
                        $this->_log('Welcome mail sent for shop owner <'.$reciever[$to_id]['email'].'> with store wallet link.');

                        $this->saveUserNotification($admin_id, $_POST['session_id'], $recurring_payment->getId(), 'recurringpayment', 'paymentsuccess', 'N');

                        /* code for push notification */
                        $angular_host_url = $this->container->getParameter('angular_app_hostname');
                        $push_message = sprintf($lang_array['PUSH_PAYMENT_SUCCESS_BODY'],$shop_name);
                        $label_of_button = $lang_array['PUSH_LINK_LABEL'];
                        $redirection_link = "<a href='$angular_host_url" . "$shop_wallet_url/$profile_id'>$label_of_button</a>";
                        $message_title = $lang_array['PUSH_PAYMENT_SUCCESS_TITLE'];
                        $curl_obj->pushNotification($_POST['session_id'], $push_message, $label_of_button, $redirection_link, $message_title);
                        
                    }
                }
                $pay_type = substr($_POST['codTrans'], -1);
                
                /** remove alert from billing cycle log **/
                if($first_attempt_to_add_cc == 1) {
                    /** remove log from billing cycle mongo db table **/
                    $this->_log('remove log from billing cycle mongo db table Shop ID: '.$profile_id.' and CC_NOT_ADDED');
                    $delete_billing_cycle_log = $dm
                                    ->getRepository('StoreManagerStoreBundle:BillerCycleLog')
                                    ->removeDeleteBillingCycleLog($profile_id, 'CC_NOT_ADDED');
                }
                //code for activat the shop on social and shopping plus
                if (($activate_shop == 1 && $shop_status_for_check == 0) || ($shop_old_contract_status == 0)) {
                   
                    // code related active the shop and send the notification on social and email
                    if ($store_obj) {
                        $store_obj->setShopStatus(1);        
                        $store_obj->setNewContractStatus(1);
                    }
                    $em->persist($store_obj);
                    $em->flush();
                    // code for sending social notification of shop activation
                    $this->_log('Preparing mail content to send with contract pdf attachments');
                    $to_id = $_POST['session_id'];
                    $this->_log("1");
                    $postService = $this->container->get('post_detail.service');
                    $this->_log("2");
                    $reciever = $postService->getUserData($to_id, true);
                    $this->_log("3");
                    $locale = empty($reciever[$to_id]['current_language']) ? $this->container->getParameter('locale') : $reciever[$to_id]['current_language'];
                    $this->_log("4");
                    $lang_array = $this->container->getParameter($locale);
                    $this->_log("5");
                    $click_here = $lang_array['CLICK_HERE'];
                    $this->_log("6");
                    $detail_text = $lang_array['SHOP_AFFILIATION_ACTIVE_LINK_TEXT'];
                    $this->_log("7");
                    $discount_position_amount = $this->container->getParameter('shop_discount_position_amount');
                    $this->_log("8");
                    $discount_shot_amount = $this->container->getParameter('shot_amount');
                    $this->_log("9");
                    $href = $angular_app_hostname.$shop_profile_url. "/".$profile_id;
                    $shopProfileLink = "<a href='$href"."'>$click_here</a>";
                    $this->_log("10");
                    $mail_sub = $lang_array['SHOP_AFFILIATION_ACTIVE_SUBJECT'];
                    $this->_log("11");
                    $mail_body = sprintf($lang_array['SHOP_AFFILIATION_ACTIVE_BODY'],$shop_name);
                    // replace shopName, tutorialLink,tutorialLinkClickHere, appleLink, androidLink
                    $this->_log("12");
                    $tutorialHref = $angular_app_hostname.'shoptutorial';
                    $this->_log("13");
                    $tutorialLinkClickHere = "<a href='{$tutorialHref}'>".$lang_array['CLICK_HERE']."</a>";
                    $this->_log("14");
                    $appleLink = "<a href='https://itunes.apple.com/it/artist/sixthcontinent/id532299092'>Apple</a>";
                    $this->_log("15");
                    $androidLink = "<a href='https://play.google.com/store/apps/developer?id=SixthContinent+INC'>Android</a>";
                    $this->_log("16");
                    $mail_text = sprintf($lang_array['SHOP_AFFILIATION_ACTIVE_TEXT'],$shop_name,$shopProfileLink,$appleLink,$androidLink,$tutorialLinkClickHere);
                    $this->_log("17");
                    $link_affilation = $shopProfileLink." $detail_text";
                    $this->_log("18");
                    $link =  $mail_text.$link_affilation;
                    $this->_log("19");
                    if($store_object_info){
                        $thumb_path = $store_object_info['thumb_path'];
                    } else{
                        $thumb_path = '';
                    }
//                    $email_body_active = $email_template_service->EmailTemplateService($mail_body,$thumb_path,$link ,'');
                    $this->_log("20");
                    $from_id = $admin_id;
                    $this->_log("21");
                    $to_id = $_POST['session_id'];
                    $this->_log("22");
                    $dm = $this->get('doctrine.odm.mongodb.document_manager');                   
                   
                    
                    // notification for shop active
                    $this->_log('Save web notification for shop owner : '.$_POST['session_id']);
                    $this->saveUserNotification($admin_id, $_POST['session_id'], $profile_id, 'shopstatus', 'shop_active', 'N');
                    //code to get email attchment                   
                    $type = 1; //from admin
                    $attachment = 1; //with attchment
                    $store_details = $em
                                       ->getRepository('StoreManagerStoreBundle:Store')
                                       ->find($profile_id);
                    $store_category_percentage = $this->getCategoryWiseCardAndTxnInfo($store_details);
                    $this->_log('Calling method generatepdf to generate contract pdf.');
                    $pdfurl = $this->generatepdf($store_details,$store_category_percentage);
                    $this->_log('Calling method generatepdfA to generate contract pdf.');
                    $pdfurl_a = $this->generatepdfA($store_details,$store_category_percentage);
                    $attachmemt_path = array();
                    $attachmemt_path_admin = array();
                    //if some error occured
                    if(!$pdfurl){
                    $attachment = 1; //no attchment
                    $pdfurl = '';
                    }
                    if($pdfurl) {
                        $attachmemt_path['contract_b'] = $pdfurl;
                        $attachmemt_path_admin['contract_b'] = $pdfurl;
                    }
                    if($pdfurl_a) {
                        $attachmemt_path_admin['contract_a'] = $pdfurl_a;
                    }
                   // code for sending email notification of shop activation with attchment
                   //$email_template_service->sendEmailNotification($mail_sub, $from_id, $to_id, $email_body_active, $type, $attachment, $attachmemt_path);
                   //$email_template_service->sendEmailNotification($mail_sub, $from_id, $to_id, $email_body_active, $type, $attachment, $attachmemt_path_admin,2);
                   //send mail by send grid of shop activation with attchment
                    $this->_log('Sending contract mail for shop owner with contract b pdf.');
                   $email_template_service->sendMail($reciever, $link, $mail_body, $mail_sub, $thumb_path, 'TRANSACTION', $attachmemt_path);
                   $sixthcontinent_shop_admin_email = $this->container->getParameter('sixthcontinent_shop_admin_email'); 
                   $this->_log('Sending contract mail for admin with contract a and b pdf.');
                   $email_template_service->sendMail(array($sixthcontinent_shop_admin_email), $link, $mail_body, $mail_sub, $thumb_path, 'TRANSACTION', $attachmemt_path_admin, 2, 1);
                   $shopping_plus_obj = $this->container->get("store_manager_store.shoppingplus");
                    //activate the shop on shopping plus
                   $this->_log('Calling method changeStoreStatusOnShoppingPlus');
                   // BLOCK SHOPPING PLUS
                    //$shop_deactive_output = $shopping_plus_obj->changeStoreStatusOnShoppingPlus($profile_id, 'A');
                    /* code for push notification */
                    $angular_host_url = $this->container->getParameter('angular_app_hostname');
                    $push_message = sprintf($lang_array['PUSH_SHOP_ACTIVE_BODY'],$shop_name);
                    $label_of_button = $lang_array['PUSH_LINK_LABEL'];
                    $redirection_link = "<a href='$angular_host_url" . "$shop_profile_url/$profile_id'>$label_of_button</a>";
                    $message_title =$lang_array['PUSH_SHOP_ACTIVE_TITLE'];
                    $this->_log('Calling method pushNotification');
                    $curl_obj->pushNotification($_POST['session_id'], $push_message, $label_of_button, $redirection_link, $message_title);
                
                    //code for open discount position on sixthcontinent and shopping plus 
                    // send referral amount notification
                    $postService = $this->container->get('post_detail.service');
                    $postService->sendReferralAmountNotifications($profile_id, $_POST['session_id']);
                }
                
                $em->persist($store_obj);
                $em->flush();
                 
                /**
                if ($first_attempt_to_add_cc == 1) {
                    $this->openDPShots($profile_id);
                }
                **/
                /* code for sending the mail for shop contract
                if ($first_attempt_to_add_cc == 1) {
                    $this->sendShopContractEmail($profile_id);
                }*/
                
                $this->_log('Contract process has been finished for SHOP ID: '.$profile_id);
               
            }
        }
       
        exit('Yes Post');
        return new Response('ok');
    }

    /**
     * Save user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @param string $msg_type 
     * @return boolean
     */
    public function saveUserNotification($user_id, $sender_id, $item_id, $msgtype, $msg, $msg_type) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($user_id);
        $notification->setTo($sender_id);
        $notification->setMessageType($msgtype);
        $notification->setMessage($msg);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $notification->setItemId($item_id);
        $notification->setMessageStatus($msg_type);
        $dm->persist($notification);
        $dm->flush();
        return true;
    }

    /**
     * send email for notification on shop activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @return boolean
     */
    public function sendEmailNotification($mail_sub, $from_id, $to_id, $mail_body) {
        $userManager = $this->getUserManager();
        $from_user = $userManager->findUserBy(array('id' => (int) $from_id));
        $to_user = $userManager->findUserBy(array('id' => (int) $to_id));
        $sixthcontinent_admin_email = $this->container->getParameter('sixthcontinent_admin_email');
        $notification_msg = \Swift_Message::newInstance()
                ->setSubject($mail_sub)
                ->setFrom($sixthcontinent_admin_email)
                ->setTo(array($to_user->getEmail()))
                ->setBody($mail_body, 'text/html');

        if ($this->container->get('mailer')->send($notification_msg)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * return credit card detial for shop
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function postCreditcardlistsAction(Request $request) {

        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('store_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);

        $store_id = $object_info->store_id;

        $em = $this->container->get('doctrine')->getManager();

        // checking for shop id
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id));
        if (!$store_obj) {
            $res_data = array('code' => 89, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        $contract_obj = $em
                ->getRepository('CardManagementBundle:Contract')
                ->findBy(array('profileId' => $store_id, 'status' => 1, 'deleted' => 0), null, $limit, $offset);

        $contract_count = $em
                ->getRepository('CardManagementBundle:Contract')
                ->countContracts($store_id);

        $contract_arr = array();
        if ($contract_obj) {
            foreach ($contract_obj as $contract) {
                $card_obj = array(
                    'contract_id' => $contract->getId(),
                    'store_id' => $contract->getProfileId(),
                    'contract_number' => $contract->getContractNumber(),
                    'registration_time' => $contract->getRegistrationTime(),
                    'email' => $contract->getMail(),
                    'pan' => $contract->getPan(),
                    'brand' => $contract->getBrand(),
                    'expiration_pan' => $contract->getExpirationPan(),
                    'alias' => $contract->getAlias(),
                    'name' => $contract->getName(),
                    'last_name' => $contract->getLastName(),
                    'nationality' => $contract->getNationality(),
                    'user_id' => $contract->getSessionId(),
                    'product_type' => $contract->getProductType(),
                    'language_code' => $contract->getLanguageCode(),
                    'region' => $contract->getRegion(),
                    'create_time' => $contract->getCreateTime(),
                    'deleted' => $contract->getDeleted(),
                    'status' => $contract->getStatus(),
                    'defaultflag' => $contract->getDefaultflag(),
                );

                $contract_arr[] = $card_obj;
            }
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $contract_arr, 'count' => $contract_count);
        echo json_encode($res_data);
        exit();
    }

    /**
     * make a card default credit card
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function postMakedefaultcardsAction(Request $request) {

        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('contract_id', 'store_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $store_id = $object_info->store_id;
        $contract_id = $object_info->contract_id;

        $em = $this->container->get('doctrine')->getManager();
        // checking for shop id
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id));
        if (!$store_obj) {
            $res_data = array('code' => 89, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        // checking for contract id
        $contract_obj = $em
                ->getRepository('CardManagementBundle:Contract')
                ->findOneBy(array('id' => $contract_id));

        if (!$contract_obj) {
            $res_data = array('code' => 89, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        // remove default cc for shop
        $remove_default_cc = $em
                ->getRepository('CardManagementBundle:Contract')
                ->removeDefaultForStore((int) $store_id);

        // make cc as default
        $add_default_cc = $em
                ->getRepository('CardManagementBundle:Contract')
                ->addDefaultForStore((int) $contract_id);
        $data = array(
            'contract_id' => $contract_id,
            'store_id' => $store_id
        );
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * digital delete a credit card
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function postDeletecreditcardsAction(Request $request) {

        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('contract_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $contract_id = $object_info->contract_id;

        $em = $this->container->get('doctrine')->getManager();

        // checking for contract id
        $contract_obj = $em
                ->getRepository('CardManagementBundle:Contract')
                ->findOneBy(array('id' => $contract_id));

        if (!$contract_obj) {
            $res_data = array('code' => 89, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }
       
        //get shop id
        $shop_id = $contract_obj->getProfileId();
        // digital credit card
        $delete_cc = $em
                ->getRepository('CardManagementBundle:Contract')
                ->deleteCreditCard((int) $contract_id);

        $data = array(
            'contract_id' => $contract_id
        );
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        
        //Check if any credit card active for user on shop
        // digital credit card
        $active_cc = $em
                ->getRepository('CardManagementBundle:Contract')
                ->checkCardForShop((int) $shop_id);
        
        if($active_cc == 0){
            
        //save the credit_card_status as 0 on store table
        $store_card = $this->getDoctrine()
                ->getRepository('StoreManagerStoreBundle:Store')
                ->find($shop_id);
        if (count($store_card) > 0) {
            $store_card->setCreditCardStatus(0);
            $em = $this->getDoctrine()->getManager();
            $em->persist($store_card);
            $em->flush();
          
        // call applane service to deactivate the card   
//        $appalne_data = $de_serialize;
//        $appalne_data['shop_id'] = $shop_id;
//        //get dispatcher object
//        $event = new FilterDataEvent($appalne_data);
//        $dispatcher = $this->container->get('event_dispatcher');
//        $dispatcher->dispatch('shop.updatecardstatus', $event);
        //end of applane service
        }
        } 
        
        echo json_encode($res_data);
        exit();
    }

    public function openDPShots($shop_id) {
        $user_service = $this->get('user_object.service');
        $store_object = $user_service->getStoreObjectService($shop_id);
        $shop_email = isset($store_object['email']) ? $store_object['email'] : '';
        $store_zip = isset($store_object['zip']) ? $store_object['zip'] : '';
        $referral_id = $this->getRefferalIdFromShopId($shop_id);
        if ($referral_id == null) {
            $referral_id = '';
        }
        //open discount position on the shopping plus
        $this->openDiscount($shop_id);
        $this->openShopDPInWallet($shop_id);
        //assign DP and SHOT
        $this->assignDiscountPositionToUser($shop_id, $referral_id, $store_zip);
        $this->assignShotsToUser($shop_id, $referral_id);
    }

    /**
     * function for assigning the DP to the users
     * @param type $shop_id
     * @param type $referral_id
     * @param type $store_zip
     * @return boolean TRUE
     */
    private function assignDiscountPositionToUser($shop_id, $referral_id, $store_zip) {
        $affilation_amount = $this->container->getParameter('shop_discount_position_amount');
        $total_affiliation_amount = $this->totalAmount($affilation_amount);
        $from_email = $this->container->getParameter('sixthcontinent_admin_email');
        $user_service = $this->get('user_object.service');
        $store_object = $user_service->getStoreObjectService($shop_id);
        $dm = $this->getDoctrine()->getManager();
        $curl_obj = $this->container->get("store_manager_store.shoppingplus");
        $email_template_service =  $this->container->get('email_template.service');
        $postService = $this->container->get('post_detail.service');
        if ($referral_id != '') {
            $from_id = $this->getAdminId();
            $to_id = $referral_id;
            //getting the users data
            $receiver = $postService->getUserData($to_id, true);
            //get locale
            $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);
            //get language constant
            $subject = $lang_array['USER_DP_SUBJECT'];
            //get language constant
            $lang = $lang_array['USER_DP_BODY'];
            //get mail text constant
            $lang_mail_text = $lang_array['USER_MAIL_TEXT'];
            //get mail body
            $mail_body = sprintf($lang, $store_object['name']);
            $user_shop_percent = $lang_array['USER_SHOP_PERCENT'];
            $mail_text = sprintf($lang_mail_text, $store_object['name'], $affilation_amount, $user_shop_percent, $store_object['name']);
            if ($store_object) {
                $thumb_path = $store_object['thumb_path'];
            } else {
                $thumb_path = '';
            }
            
            
            $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
            $url = $this->container->getParameter('shop_profile_url');
            $href = $angular_app_hostname.$url. "/".$shop_id;
            $click_here = '';//$email_template_service->getLinkForMail($href);
            $link = $mail_text.$click_here;
            $bodyData  = $mail_text."<br><br>".$link;
            
            //store DIscount position on the local server
            $store_offer_itemID = $this->storeDiscountPositionOnLocal($shop_id, $referral_id, $store_zip);
            //open discount for the reffereal user on the shopping plus
            // BLOCK SHOPPING PLUS
            //$this->registerdpshot($shop_id, $referral_id, 'P', $total_affiliation_amount);
            //opening the user discount position in the wallet
            $this->openUserDPInWallet($referral_id);
            //getting the mail id of the affliate user
            //$to_email = $this->getEmailFromUserID($referral_id);
            
            //$bodyData  = $mail_body."<br><br>".$link;
            
            //saving usernotification for social notification
            $this->saveUserNotification($shop_id, $referral_id, $store_offer_itemID, 'discount_position', 'assigned', 'N');
            $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $subject, $thumb_path, 'OPEN_DP_USER');
            //send push notification 
            $push_object_service = $this->container->get('push_notification.service');
            //get user devices
            $device_array = $push_object_service->getReceiverDeviceInfo($to_id);
            $push_lang = $lang_array['USER_DP_PUSH_BODY'];
            $push_body = sprintf($push_lang, $store_object['name']);
            $ref_type = 'DP_SHOT';
            $msg_code = 'OPEN_DP_USER';
            $msg = $push_body;
            $ref_id = $shop_id;
            $notification_role = 4;
            $push_object_service->sendNotificationByRole($from_id, $to_id, $device_array, $msg_code, $msg, $ref_type, $ref_id, $notification_role,'CITIZEN');
        }

        return true;
    }

    /**
     * function for saving the DP on local server
     * @param int $shop_id shop id for which we have to give discount and shots
     * @param type $referral_id referal id of the user if present
     * @param type $store_zip zip code of the store location
     */
    private function storeDiscountPositionOnLocal($shop_id, $referral_id, $store_zip) {
        $shots = $this->container->getParameter('shop_registration_shots');
        $affilation_amount = $this->container->getParameter('shop_discount_position_amount');
        $discount_position_amount = $this->container->getParameter('shop_discount_position_amount');
        $affilation_amount = $this->totalAmount($affilation_amount);
        $discount_position_amount = $this->totalAmount($discount_position_amount);
        $store_offer = new Storeoffers();
        $store_offer->setShopId($shop_id);
        $store_offer->setUserId($referral_id);
        $store_offer->setDiscountPosition($discount_position_amount);
        $store_offer->setAffilationAmount($affilation_amount);
        $em = $this->getDoctrine()->getManager();
        $em->persist($store_offer);
        $em->flush();
        return $store_offer->getId();
    }

    /**
     * function for giving the shots to the user
     * @param int $shop_id shop id for which we have to give discount and shots
     * @param type $referral_id referal id of the user if present
     */
    private function assignShotsToUser($shop_id, $referral_id) {
        //get entity manager object
        $discount_position_amount = $this->container->getParameter('shop_discount_position_amount');
        $discount_shot_amount = $this->container->getParameter('shot_amount');
        $total_shop_shot_amount = $this->totalAmount($discount_shot_amount);
        $from_email = $this->container->getParameter('sixthcontinent_admin_email');
        $user_service = $this->get('user_object.service');
        $store_object = $user_service->getStoreObjectService($shop_id);
        $dm = $this->getDoctrine()->getManager();
        $email_template_service =  $this->container->get('email_template.service');
        $postService = $this->container->get('post_detail.service');
        
            
        $curl_obj = $this->container->get("store_manager_store.shoppingplus");
        if ($store_object) {
            $thumb_path = $store_object['thumb_path'];
        } else {
            $thumb_path = '';
        }
            $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
            $url = $this->container->getParameter('shop_profile_url');
            $href = $angular_app_hostname.$url. "/".$shop_id;

        //get shots count based on refferal id
        $shots = $this->getShotsCount($referral_id);
        //fire the query in Storeoffers Repository
        $shot_users = $dm
                ->getRepository('StoreManagerStoreBundle:Storeoffers')
                ->getRendomUsers($shots);
        $to_ids = array();
        foreach ($shot_users as $shot_user) {
            //saving the user shots on local
            $store_offer_itemId = $this->assignShotOnLocal($shop_id, $shot_user);
            $this->openShotsInWallet($shop_id, $shot_user['userId']);
            //distribute shots to user on shopping plus
            // BLOCK SHOPPING PLUS
            //$this->registerdpshot($shop_id, $shot_user['userId'], 'S', $total_shop_shot_amount);
            $from_id = $this->getAdminId();
            $to_id = $shot_user['userId'];
            $receiver = $postService->getUserData($to_id, true);
            //get locale
            $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);
            //get language constant
            $subject = $lang_array['USER_SHOT_SUBJECT'];
            //get language constant
            $lang = $lang_array['USER_SHOT_BODY'];
            //get language constant
            $lang_text = $lang_array['SHOT_MAIL_TEXT'];
            //get mail body
            $mail_body = sprintf($lang, $store_object['name']);
            $mail_text = sprintf($lang_text, $discount_shot_amount, $store_object['name']);
            
            $click_link = $email_template_service->getLinkForMail($href,$locale);
            $bodyData = $mail_text.'<br><br>'.$click_link;
            
            $this->saveUserNotification($shop_id, $shot_user['userId'], $store_offer_itemId, 'Shot', 'assigned', 'N');
            
            $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $subject, $thumb_path, 'USER_SHOTS');
            
            $push_object_service = $this->container->get('push_notification.service');
            //get user devices
            $device_array = $push_object_service->getReceiverDeviceInfo($to_id);
            $push_lang = $lang_array['USER_SHOT_PUSH_BODY'];
            $push_body = sprintf($push_lang, $store_object['name']);
            $ref_type = 'DP_SHOT';
            $msg_code = 'ASSIGN_SHOT_USER';
            $msg = $push_body;
            $ref_id = $shop_id;
            $notification_role = 4;
            $push_object_service->sendNotificationByRole($from_id, $to_id, $device_array, $msg_code, $msg, $ref_type, $ref_id, $notification_role,'CITIZEN');
        }
//        $receiver = $postService->getUserData($to_ids, true);
//        $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $subject, $thumb_path, 'USER_SHOTS');     
        }

    /**
     * function for getting the shop count based on the referral id 
     * @param type $referral_id 
     * @return Int number of shots to be assigned
     */
    private function getShotsCount($referral_id) {
        $shots = $this->container->getParameter('shop_registration_shots');
        $affiliation_amount = $this->container->getParameter('shop_discount_position_amount');
        $shot_amount = $this->container->getParameter('shot_amount');
        if ($referral_id != '') {
            return $shots;
        } else {
            $shots += $affiliation_amount / $shot_amount;
            return $shots;
        }
    }

    /**
     * function for saving the shots on the local server
     * @param type $shop_id
     * @param type $shot_user
     * @return Int id of field store in the local server
     */
    private function assignShotOnLocal($shop_id, $shot_user) {
        $discount_position_amount = $this->container->getParameter('shop_discount_position_amount');
        $discount_position_amount = $this->totalAmount($discount_position_amount);
        $shot_amount = $this->container->getParameter('shot_amount');
        $shot_amount = $this->totalAmount($shot_amount);
        $store_offer = new Storeoffers();
        $store_offer->setShopId($shop_id);
        $store_offer->setUserId($shot_user['userId']);
        $store_offer->setDiscountPosition($discount_position_amount);
        $store_offer->setShots($shot_amount);
        $em = $this->getDoctrine()->getManager();
        $em->persist($store_offer);
        $em->flush();
        return $store_offer->getId();
    }

    /*
     * function for open DP on shopping plus server.
     * after registration of shop DP of 50 euro will open on that shop
     * @param int $shop_id shop id for which we have to open discount
     */

    public function openDiscount($shop_id) {
        $discount_position_amount = $this->container->getParameter('shop_discount_position_amount');
        $from_email = $this->container->getParameter('sixthcontinent_admin_email');
        $admin_id = $this->getAdminId();
        $shop_id = $shop_id;
        $process = 'A';
        $discount = $discount_position_amount;
        $discount = $this->totalAmount($discount);
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        // BLOCK SHOPPING PLUS
        //$shoppingplus_obj->pdvpsupdate($shop_id, $process, $discount);
        $from_id = $admin_id;
        $to_id = $this->getUserIdFromShopId($shop_id);
        $user_service = $this->get('user_object.service');
        $store_object = $user_service->getStoreObjectService($shop_id);
        $curl_obj = $this->container->get("store_manager_store.shoppingplus");
        $email_template_service =  $this->container->get('email_template.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $postService = $this->container->get('post_detail.service');
        if ($to_id != null) {
            $receiver = $postService->getUserData($to_id, true);
            $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);
            //generating the body for mail
            $subject =$lang_array['SHOP_DP_SUBJECT'];
            $lang = $lang_array['SHOP_DP_BODY'];
            $lang_text = $lang_array['SHOP_MAIL_TEXT'];
            //generating the link for the mail
            $url = $this->container->getParameter('shop_profile_url');
            $href = $angular_app_hostname.$url. "/".$shop_id;
            $click_link = $email_template_service->getLinkForMail($href, $locale);
            $link = $lang_text.$click_link;
            $mail_body = sprintf($lang, $discount_position_amount);
            $bodyData  = $lang_text."<br>".$link;           
            
            if ($store_object) {
                $thumb_path = $store_object['thumb_path'];
            } else {
                $thumb_path = '';
            }          
            $this->saveUserNotification($admin_id, $to_id, $shop_id, 'discount_position_shop', 'open', 'N');
            $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $subject, $thumb_path, 'OPEN_DP_SHOP');
            //sending push notification to the user
            $push_object_service = $this->container->get('push_notification.service');
            //get user devices
            $device_array = $push_object_service->getReceiverDeviceInfo($to_id);
            $push_lang = $lang_array['SHOP_DP_PUSH_BODY'];
            $push_body = sprintf($push_lang, $discount_position_amount);
            $ref_type = 'DP_SHOT';
            $msg_code = 'OPEN_DP_SHOP';
            $msg = $push_body;
            $ref_id = $shop_id;
            $notification_role = 4;
            $push_object_service->sendNotificationByRole($from_id, $to_id, $device_array, $msg_code, $msg, $ref_type, $ref_id, $notification_role,'SHOP');
        }
    }

    /*
     * function for giving the shots to the user
     * @param int $shop_id shop id for which we have to open discount
     * @param int $user_id user id for which we have to open discount
     * @param int $type it would be P and S
     */

    public function registerdpshot($shop_id, $user_id, $type, $amount) {
        $accumulo = 'A';
        $user_id = $user_id;
        $amount = $amount;
        $importo = 0;
        $shop_id = $shop_id;
        $type = $type;
        $shoppingplus_obj = $this->container->get("store_manager_store.shoppingplus");
        $shoppingplus_obj->movreg($accumulo, $user_id, $amount, $importo, $shop_id, $type);
    }

    /**
     * function for getting the total amount from the amount
     * @param type $amount
     * @return Int $amount*1000000
     */
    public function totalAmount($amount) {
        return ($amount * 1000000);
    }

    /**
     * function for getting the emil from the User id
     * @param type $user_id
     * @return Int email id of the user
     */
    private function getEmailFromUserID($user_id) {
        // echo $user_id;
        $um = $this->getUserManager();
        $user_obj = $um->findUserBy(array('id' => $user_id));
        if (count($user_obj) > 0) {
            $to_email = $user_obj->getEmail();
        } else {
            $to_email = null;
        }
        return $to_email;
    }

    /**
     * function for gettign the admin id
     * @param None
     */
    private function getAdminId() {
        $dm = $this->getDoctrine()->getManager();
        $admin_id = $dm
                ->getRepository('StoreManagerStoreBundle:Storeoffers')
                ->findByRole('ROLE_ADMIN');
        return $admin_id;
    }

    private function getRefferalIdFromShopId($shop_id) {
        $dm = $this->getDoctrine()->getManager();
        $result = $dm
                ->getRepository('StoreManagerStoreBundle:Storeoffers')
                ->getRefferalIdFromShopId($shop_id);
        return $result;
    }

    /**
     * 
     * @param type $amount
     * @return type
     */
    public function converToEuro($amount) {
        $amount_euro = $amount / $this->base_six;
        return $amount_euro;
    }

    /**
     * 
     * @param type $amount
     * @return type
     */
    public function converToBaseSix($amount) {
        $amount_euro = $amount * $this->base_six;
        return $amount_euro;
    }

    public function openShopDPInWallet($shop_id) {
        $discount_position_amount = $this->container->getParameter('shop_discount_position_amount');
        $discount_position_amount = $this->totalAmount($discount_position_amount);
        $total_dp = $discount_position_amount;
        $citizen_income = 0;
        $store_dp = $this->getDoctrine()
                ->getRepository('StoreManagerStoreBundle:Store')
                ->find($shop_id);
        if (count($store_dp) > 0) {
            $discount_position_amount += $store_dp->getBalanceDp();
            $total_dp += $store_dp->getTotalDp();
            $store_dp->setTotalDp($total_dp);
            $store_dp->setBalanceDp($discount_position_amount);
            $em = $this->getDoctrine()->getManager();
            $em->persist($store_dp);
            $em->flush();
            return $store_dp->getId();
        }
    }

    public function openUserDPInWallet($user_id) {
        $discount_position_amount = $this->container->getParameter('shop_discount_position_amount');
        $discount_position_amount = $this->totalAmount($discount_position_amount);
        $total_dp = $discount_position_amount;
        $citizen_income = 0;
        $user_dp = $this->getDoctrine()
                ->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                ->findOneBy(array('userId' => $user_id));
        if (count($user_dp) > 0) {
            $discount_position_amount += $user_dp->getBalanceDp();
            $citizen_income = $user_dp->getCitizenIncome();
            $time = $user_dp->getCreatedAt();
            $total_dp += $user_dp->getTotalDp();
        } else {
            $user_dp = new UserDiscountPosition();
            $time = new \DateTime('now');
        }
        $user_dp->setUserId($user_id);
        $user_dp->setTotalDp($total_dp);
        $user_dp->setBalanceDp($discount_position_amount);
        $user_dp->setCitizenIncome($citizen_income);
        $user_dp->setCreatedAt($time);
        $em = $this->getDoctrine()->getManager();
        $em->persist($user_dp);
        $em->flush();
    }

    public function openShotsInWallet($shop_id, $user_id) {
        $shot_amount = $this->container->getParameter('shot_amount');
        $shot_amount = $this->totalAmount($shot_amount);
        $total_shot = $shot_amount;
        $total_gc = 0;
        $balance_gc = 0;
        $total_momosy = 0;
        $balance_momosy = 0;
        $user_shop_credit = $this->getDoctrine()
                ->getRepository('WalletManagementWalletBundle:UserShopCredit')
                ->findOneBy(array('userId' => $user_id, 'shopId' => $shop_id));
        if (count($user_shop_credit) > 0) {
            $shot_amount += $user_shop_credit->getBalanceShots();
            $time = $user_shop_credit->getCreatedAt();
            $total_shot += $user_shop_credit->getTotalShots();
            $total_gc = $user_shop_credit->getTotalGiftCard();
            $balance_gc = $user_shop_credit->getBalanceGiftCard();
            $total_momosy = $user_shop_credit->getTotalMomosyCard();
            $balance_momosy = $user_shop_credit->getBalanceMomosyCard();
        } else {
            $user_shop_credit = new UserShopCredit();
            $time = new \DateTime('now');
        }
        $user_shop_credit->setShopId($shop_id);
        $user_shop_credit->setUserId($user_id);
        $user_shop_credit->setTotalShots($total_shot);
        $user_shop_credit->setBalanceShots($shot_amount);
        $user_shop_credit->setTotalGiftCard($total_gc);
        $user_shop_credit->setBalanceGiftCard($balance_gc);
        $user_shop_credit->setTotalMomosyCard($total_momosy);
        $user_shop_credit->setBalanceMomosyCard($balance_momosy);
        $user_shop_credit->setCreatedAt($time);
        $em = $this->getDoctrine()->getManager();
        $em->persist($user_shop_credit);
        $em->flush();
        return $user_shop_credit->getId();
    }

    public function getUserIdFromShopId($shop_id) {
        $user_shop_credit = $this->getDoctrine()
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId' => $shop_id));
        if (count($user_shop_credit) > 0) {
            return $user_shop_credit->getUserId();
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $push_message
     * @param string $message_title
     * @param type $shop_id
     */
    public function sendPushNotification($push_message, $message_title, $shop_id, $user_id) {
        /* code for push notification */
        $curl_obj = $this->container->get("store_manager_store.shoppingplus");
        $angular_host_url = $this->container->getParameter('angular_app_hostname');
        $push_message = $push_message;
        $label_of_button = 'View shop';
        $redirection_link = "<a href='$angular_host_url" . "shope/view/$shop_id'>View shop</a>";
        $message_title = $message_title;
        //echo $user_id.$push_message.$label_of_button.$redirection_link.$message_title;
        $curl_obj->pushNotification($user_id, $push_message, $label_of_button, $redirection_link, $message_title);
        return true;
    }
    
    
    /**
     * Send shop contratct emails
     * @param int $shop_id
     * @return boolean
     */
    public function sendShopContractEmail($shop_id){
         //get entity manager object
       
       $user_id = '';
       $em = $this->getDoctrine()->getManager();

       $user_service = $this->get('user_object.service');
       $store_object_info = $user_service->getStoreObjectService($shop_id);
       
        if(!$store_object_info){
           return true;
       }
       
       $store_id = $store_object_info['id'];
       $shop_name = $store_object_info['name'];
       $shop_business_name = $store_object_info['businessName'];
       
       
       //get shop owner info
       $shops_admin = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->findOneBy(array('storeId'=>$shop_id, 'role'=>15));
       
        $shop_admin_id = $shops_admin->getUserId();
       $type = 1;
       $attachment = 1;
       $pdfurl = $this->generatepdf($store_object_info, $shop_admin_id);
       //if some error occured
       if(!$pdfurl){
       $attachment = 0; //no attchment
       }
       
        //get store profile thumb..
        $store_profile_image_thumb_path = $store_object_info['thumb_path'];
        $postService = $this->container->get('post_detail.service');
        $receiver = $postService->getUserData($shop_admin_id);
       //for mail template..
        $email_template_service =  $this->container->get('email_template.service'); //email template service.
        //get the local parameters in parameters file.
        $locale = !empty($receiver['current_language']) ? $receiver['current_language'] : $this->container->getParameter('locale');
        $language_const_array = $this->container->getParameter($locale);
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
        $shop_profile_url     = $this->container->getParameter('shop_profile_url'); //shop profile url
        $thumb_path   = $store_profile_image_thumb_path;
        $href = $angular_app_hostname.$shop_profile_url.'/'.$store_id;
        $link =  $email_template_service->getLinkForMail($href, $locale); //making the link html from service
        
        $mail_sub  = sprintf($language_const_array['STORE_CONTRACT_SUBJECT']);
        $mail_body = sprintf($language_const_array['STORE_CONTRACT_BODY']);
         
        $email_body = $email_template_service->EmailTemplateService($mail_body, $thumb_path, $link, $shop_admin_id);
        $email_template_service->sendEmailNotification($mail_sub, $user_id, $shop_admin_id, $email_body,$type, $attachment, $pdfurl);
        return true;
    }
    
    
    /**
     * Generate Pdf
     * @param array $store_object_info
     * @return string
     */
     public function generatepdf($store_object_info,$store_category_percentage=array()) {
        $this->_log('Contract-b pdf generating process start');
        $store_id = $store_object_info->getId();
        $shop_name = $store_object_info->getName();
        $shop_business_name = $store_object_info->getBusinessName();
        $iban = $store_object_info->getIban();
        $zip = $store_object_info->getZip();
        $province = $store_object_info->getProvince();
        $region = $store_object_info->getBusinessRegion();
        $city = $store_object_info->getBusinessCity();
        $shop_vatnumber = $store_object_info->getVatNumber();
        $shop_address = $store_object_info->getBusinessAddress();
        
        $shop_point_of_sale_region = $store_object_info->getSaleRegion();
        $shop_point_of_sale_city = $store_object_info->getSaleCity();
        $shop_point_of_sale_zip = $store_object_info->getSaleZip();
        $shop_point_of_sale_province = $store_object_info->getSaleProvince();
        $shop_point_of_sale_address = $store_object_info->getSaleAddress();
        
        $shop_legal_rappresentative_firstname = $store_object_info->getRepresFirstName();
        $shop_legal_rappresentative_lastname = $store_object_info->getRepresLastName();
        $shop_legal_rappresentative_birthplace = $store_object_info->getRepresPlaceOfBirth();
        $shop_legal_rappresentative_birthdate = $store_object_info->getRepresDob();
        $shop_legal_rappresentative_zip = $store_object_info->getRepresZip();
        $shop_legal_rappresentative_city = $store_object_info->getRepresCity();
        $shop_legal_rappresentative_province = $store_object_info->getRepresProvince();
        $shop_legal_rappresentative_fiscal_code = $store_object_info->getRepresFiscalCode();
        $shop_legal_rappresentative_detail_tel = $store_object_info->getRepresPhoneNumber();
        $shop_legal_rappresentative_detail_email = $store_object_info->getRepresEmail();
        $shop_legal_rappresentative_zip = $store_object_info->getRepresZip();
        $shop_legal_rappresentative_address = $store_object_info->getRepresAddress();
        $shop_catogory_id = $store_object_info->getSaleCatid();
        $up_to_50 = isset($store_category_percentage['txn_percentage']) ? $store_category_percentage['txn_percentage'] : $this->container->getParameter('txn_percentage');
        $up_to_100 = isset($store_category_percentage['amount_pecentage']) ? $store_category_percentage['amount_pecentage']: $this->container->getParameter('card_percentage');
        //generate pdf name
        $attchment_name = $shop_name."_contract.pdf";
        $em = $this->getDoctrine()->getManager();
        $store_details = $em->getRepository('UserManagerSonataUserBundle:BusinessCategory')
                            ->getCategoryNameFromId('it',$shop_catogory_id);
        //create html
        //$html =  "Shop id: ".$store_id."<br />";
        //$html .= "Shop name: ".$shop_name."<br />";
        //$html .= "Shop business name: ".$shop_business_name."<br />";
        //$html .= "IBAN: ".$iban."<br />";
        
        //get contract html
        $this->_log('Getting content of '. __DIR__.'/../Resources/contract/sixthcontinent_contract.html'.' to generate Contract B');
        $body = file_get_contents(__DIR__.'/../Resources/contract/sixthcontinent_contract.html');
     
        $body =str_replace('%shop_business_name%', $shop_business_name, $body);
        $body =str_replace('%shop_point_of_sale_region%', $shop_point_of_sale_region, $body);
        $body =str_replace('%shop_point_of_sale_city%', $shop_point_of_sale_city, $body);
        $body =str_replace('%shop_point_of_sale_zip%', $shop_point_of_sale_zip, $body);
        $body =str_replace('%shop_point_of_sale_province%', $shop_point_of_sale_province, $body);
        $body =str_replace('%shop_vatnumber%', $shop_vatnumber, $body);
        $body =str_replace('%shop_legal_rappresentative_firstname%', $shop_legal_rappresentative_firstname, $body);
        $body =str_replace('%shop_legal_rappresentative_lastname%', $shop_legal_rappresentative_lastname, $body);
        $body =str_replace('%shop_legal_rappresentative_birthplace%', $shop_legal_rappresentative_birthplace, $body);
        $body =str_replace('%shop_legal_rappresentative_birthdate%', isset($shop_legal_rappresentative_birthdate)?$shop_legal_rappresentative_birthdate->format('d/m/Y'):'', $body);
        $body =str_replace('%shop_legal_rappresentative_zip%', $shop_legal_rappresentative_zip, $body);
        $body =str_replace('%shop_legal_rappresentative_city%', $shop_legal_rappresentative_city, $body);
        $body =str_replace('%shop_legal_rappresentative_province%', $shop_legal_rappresentative_province, $body);
        $body =str_replace('%shop_legal_rappresentative_fiscal_code%', $shop_legal_rappresentative_fiscal_code, $body);
        $body =str_replace('%shop_legal_rappresentative_detail_tel%', $shop_legal_rappresentative_detail_tel, $body);
        $body =str_replace('%shop_legal_rappresentative_detail_email%', $shop_legal_rappresentative_detail_email, $body);
        $body =str_replace('%shop_region%', $region, $body);
        $body =str_replace('%date%', date('d-m-Y'),$body);
        $image_path = 'https://prod.sixthcontinent.com/uploads/images/signature.jpg';
        $body =str_replace('%signature%',$image_path,$body);
        $body =str_replace('%shop_legal_rappresentative_zip%',$shop_legal_rappresentative_zip,$body);
        $body =str_replace('%shop_point_of_sale_address%',$shop_point_of_sale_address,$body);
        $body =str_replace('%shop_legal_rappresentative_address%',$shop_legal_rappresentative_address,$body);
        $body =str_replace('%shop_category%',$store_details,$body);
        $body =str_replace('%shop_address%',$shop_address,$body);
        $body =str_replace('%shop_city%',$city,$body);
        $body =str_replace('%shop_zip%',$zip,$body);
        $body =str_replace('%shop_proviance%',$province,$body);
        $body =str_replace('%down_arrow_img%','https://prod.sixthcontinent.com/uploads/images/down-arrow.png',$body);
        $body =str_replace('%up_to_100%',$up_to_100,$body);
        $body =str_replace('%up_to_50%',$up_to_50,$body);
        $template = htmlspecialchars_decode($body);
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SixthContinent');
        $pdf->SetTitle('Contract');
        $pdf->SetSubject('Contract');
        $pdf->SetKeywords('TCPDF, PDF, lawfirm, test, guide');
        // set default header data

        $pdf->SetHeaderData('logo_person.png', '37', '', '');
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // ---------------------------------------------------------
        // set font
        $pdf->SetFont('helvetica', '', 9);
        // add a page
        $pdf->AddPage();

        // output the HTML content
        $pdf->writeHTML($template, true, 0, true, 0);
        // reset pointer to the last page
        $pdf->lastPage();
        // ---------------------------------------------------------
        //Close and output PDF document
        $attachment_path = __DIR__ . "/../../../../web/uploads/attachments/".$store_id."/";
        if (!file_exists($attachment_path)) {
            $this->_log('Creating folder for store pdf. Folder is '. $attachment_path);
            if (!mkdir($attachment_path, 0777, true)) {
                $this->_log('Unable to create folder for store pdf. Folder is '. $attachment_path);
                return false;
            }
        }
        $attachment_path_name = $attachment_path.$attchment_name;
        ob_clean(); 
        $pdf->Output($attachment_path_name, 'F');
        $this->_log('Contract pdf has been created. File is '. $attachment_path_name);
        return $attachment_path_name;
    }
    /**
     * Generate Pdf
     * @param array $store_object_info
     * @return string
     */
     public function generatepdfA($store_object_info,$store_category_percentage=array()) {
        $this->_log('Contract-A pdf generating process start');
        $store_id = $store_object_info->getId();
        $shop_name = $store_object_info->getName();
        $shop_business_name = $store_object_info->getBusinessName();
        $iban = $store_object_info->getIban();
        $zip = $store_object_info->getZip();
        $province = $store_object_info->getProvince();
        $region = $store_object_info->getBusinessRegion();
        $city = $store_object_info->getBusinessCity();
        $shop_vatnumber = $store_object_info->getVatNumber();
        $shop_address = $store_object_info->getBusinessAddress();
        
        $shop_point_of_sale_region = $store_object_info->getSaleRegion();
        $shop_point_of_sale_city = $store_object_info->getSaleCity();
        $shop_point_of_sale_zip = $store_object_info->getSaleZip();
        $shop_point_of_sale_province = $store_object_info->getSaleProvince();
        $shop_point_of_sale_address = $store_object_info->getSaleAddress();
        
        $shop_legal_rappresentative_firstname = $store_object_info->getRepresFirstName();
        $shop_legal_rappresentative_lastname = $store_object_info->getRepresLastName();
        $shop_legal_rappresentative_birthplace = $store_object_info->getRepresPlaceOfBirth();
        $shop_legal_rappresentative_birthdate = $store_object_info->getRepresDob();
        $shop_legal_rappresentative_zip = $store_object_info->getRepresZip();
        $shop_legal_rappresentative_city = $store_object_info->getRepresCity();
        $shop_legal_rappresentative_province = $store_object_info->getRepresProvince();
        $shop_legal_rappresentative_fiscal_code = $store_object_info->getRepresFiscalCode();
        $shop_legal_rappresentative_detail_tel = $store_object_info->getRepresPhoneNumber();
        $shop_legal_rappresentative_detail_email = $store_object_info->getRepresEmail();
        $shop_legal_rappresentative_zip = $store_object_info->getRepresZip();
        $shop_legal_rappresentative_address = $store_object_info->getRepresAddress();
        $shop_catogory_id = $store_object_info->getSaleCatid();
        $up_to_50 = isset($store_category_percentage['txn_percentage']) ? $store_category_percentage['txn_percentage'] : $this->container->getParameter('txn_percentage');
        $up_to_100 = isset($store_category_percentage['amount_pecentage']) ? $store_category_percentage['amount_pecentage']: $this->container->getParameter('card_percentage');
        //generate pdf name
        $attchment_name = $shop_name."_contract_A.pdf";
        $em = $this->getDoctrine()->getManager();
        $store_details = $em->getRepository('UserManagerSonataUserBundle:BusinessCategory')
                            ->getCategoryNameFromId('it',$shop_catogory_id);
        //create html
        //$html =  "Shop id: ".$store_id."<br />";
        //$html .= "Shop name: ".$shop_name."<br />";
        //$html .= "Shop business name: ".$shop_business_name."<br />";
        //$html .= "IBAN: ".$iban."<br />";
        
        //get contract html
        $this->_log('Getting content of '. __DIR__.'/../Resources/contract/sixthcontinent_contract_A.html'.' to generate Contract A');
        $body = file_get_contents(__DIR__.'/../Resources/contract/sixthcontinent_contract_A.html');
        
  
        $body =str_replace('%shop_business_name%', $shop_business_name, $body);
        $body =str_replace('%shop_point_of_sale_region%', $shop_point_of_sale_region, $body);
        $body =str_replace('%shop_point_of_sale_city%', $shop_point_of_sale_city, $body);
        $body =str_replace('%shop_point_of_sale_zip%', $shop_point_of_sale_zip, $body);
        $body =str_replace('%shop_point_of_sale_province%', $shop_point_of_sale_province, $body);
        $body =str_replace('%shop_vatnumber%', $shop_vatnumber, $body);
        $body =str_replace('%shop_legal_rappresentative_firstname%', $shop_legal_rappresentative_firstname, $body);
        $body =str_replace('%shop_legal_rappresentative_lastname%', $shop_legal_rappresentative_lastname, $body);
        $body =str_replace('%shop_legal_rappresentative_birthplace%', $shop_legal_rappresentative_birthplace, $body);
        $body =str_replace('%shop_legal_rappresentative_birthdate%', isset($shop_legal_rappresentative_birthdate)?$shop_legal_rappresentative_birthdate->format('d/m/Y'):'', $body);
        $body =str_replace('%shop_legal_rappresentative_zip%', $shop_legal_rappresentative_zip, $body);
        $body =str_replace('%shop_legal_rappresentative_city%', $shop_legal_rappresentative_city, $body);
        $body =str_replace('%shop_legal_rappresentative_province%', $shop_legal_rappresentative_province, $body);
        $body =str_replace('%shop_legal_rappresentative_fiscal_code%', $shop_legal_rappresentative_fiscal_code, $body);
        $body =str_replace('%shop_legal_rappresentative_detail_tel%', $shop_legal_rappresentative_detail_tel, $body);
        $body =str_replace('%shop_legal_rappresentative_detail_email%', $shop_legal_rappresentative_detail_email, $body);
        $body =str_replace('%shop_region%', $region, $body);
        $body =str_replace('%date%', date('d-m-Y'),$body);
        $body =str_replace('%shop_legal_rappresentative_zip%',$shop_legal_rappresentative_zip,$body);
        $body =str_replace('%shop_point_of_sale_address%',$shop_point_of_sale_address,$body);
        $body =str_replace('%shop_legal_rappresentative_address%',$shop_legal_rappresentative_address,$body);
        $body =str_replace('%shop_category%',$store_details,$body);
        $body =str_replace('%shop_address%',$shop_address,$body);
        $body =str_replace('%shop_city%',$city,$body);
        $body =str_replace('%shop_zip%',$zip,$body);
        $body =str_replace('%shop_proviance%',$province,$body);
        $body =str_replace('%down_arrow_img%','https://prod.sixthcontinent.com/uploads/images/down-arrow.png',$body);
        $body =str_replace('%up_to_100%',$up_to_100,$body);
        $body =str_replace('%up_to_50%',$up_to_50,$body);
        $template = htmlspecialchars_decode($body);
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SixthContinent');
        $pdf->SetTitle('Contract A');
        $pdf->SetSubject('Contract A');
        $pdf->SetKeywords('TCPDF, PDF, lawfirm, test, guide');
        // set default header data

        $pdf->SetHeaderData('logo_person.png', '37', '', '');
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // ---------------------------------------------------------
        // set font
        $pdf->SetFont('helvetica', '', 9);
        // add a page
        $pdf->AddPage();

        // output the HTML content
        $pdf->writeHTML($template, true, 0, true, 0);
        // reset pointer to the last page
        $pdf->lastPage();
        // ---------------------------------------------------------
        //Close and output PDF document
        $attachment_path = __DIR__ . "/../../../../web/uploads/attachments/".$store_id."/";
        if (!file_exists($attachment_path)) {
            $this->_log('Creating folder for store pdf. Folder is '. $attachment_path);
            if (!mkdir($attachment_path, 0777, true)) {
                $this->_log('Unable to create folder for store pdf. Folder is '. $attachment_path);
                return false;
            }
        }
        $attachment_path_name = $attachment_path.$attchment_name;
        ob_clean(); 
        $pdf->Output($attachment_path_name, 'F');
        $this->_log('Contract pdf has been created. File is '. $attachment_path_name);
        return $attachment_path_name;
    }
    
    /**
     * Generate Pdf Contract A
     * @param array $store_object_info
     * @return string
     */
     public function generateContractAPdf($store_object_info) {
        $store_id = $store_object_info['id'];
        $shop_name = $store_object_info['name'];
        $shop_business_name = $store_object_info['businessName'];
        $iban = $store_object_info['iban'];
        $zip = $store_object_info['zip'];
        $province = $store_object_info['province'];
        $region = $store_object_info['businessRegion'];
        $city = $store_object_info['businessCity'];
        //generate pdf name
        $attchment_name = $shop_name."_contract_A.pdf";
        
        //create html
        //$html =  "Shop id: ".$store_id."<br />";
        //$html .= "Shop name: ".$shop_name."<br />";
        //$html .= "Shop business name: ".$shop_business_name."<br />";
        //$html .= "IBAN: ".$iban."<br />";
        
        //get contract html
        $body = file_get_contents(__DIR__.'/../Resources/contract/sixthcontinent_contract_A.html');
     /*
        $body =str_replace('%business_name%', $shop_business_name, $body);
        $body =str_replace('%zip%', $zip, $body);
        $body =str_replace('%province%', $province, $body);
        $body =str_replace('%region%', $region, $body);
        $body =str_replace('%city%', $city, $body);
    */
        $template = htmlspecialchars_decode($body);
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SixthContinent');
        $pdf->SetTitle('Contract A');
        $pdf->SetSubject('Contract A');
        $pdf->SetKeywords('TCPDF, PDF, lawfirm, test, guide');
        // set default header data

        $pdf->SetHeaderData('logo.png', '40', '', '');
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // ---------------------------------------------------------
        // set font
        $pdf->SetFont('helvetica', '', 9);
        // add a page
        $pdf->AddPage();

        // output the HTML content
        $pdf->writeHTML($template, true, 0, true, 0);
        // reset pointer to the last page
        $pdf->lastPage();
        // ---------------------------------------------------------
        //Close and output PDF document
        $attachment_path = __DIR__ . "/../../../../web/uploads/attachments/".$store_id."/";
        if (!file_exists($attachment_path)) {
            if (!mkdir($attachment_path, 0777, true)) {
                return false;
            }
        }
        $attachment_path_name = $attachment_path.$attchment_name;
        ob_clean(); 
        $pdf->Output($attachment_path_name, 'F');
        return $attachment_path_name;
    }

    /**
     * function for locking the logs
     * @param type $sMessage
     */
    public function _log($sMessage){
        $monoLog = $this->container->get('monolog.logger.contract_log');
        $monoLog->info($sMessage);
    }
    
    /**
     * function for sending the contract 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function postSendcontractsAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id', 'store_id');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $this->_log('Exiting with message YOU_HAVE_MISSED_A_PARAMETER_');
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //get store id from the request object
        $store_id = $object_info->store_id;
        $to_id = $object_info->session_id;
        $em = $this->container->get('doctrine')->getManager();
        //getting the store object 
        $store_details = $em
                        ->getRepository('StoreManagerStoreBundle:Store')
                        ->findOneBy(array('id' => (int) $store_id));
        $store_category_percentage = $this->getCategoryWiseCardAndTxnInfo($store_details);
        //getting the user to store relationship 
        $user_to_store = $em
                        ->getRepository('StoreManagerStoreBundle:UserToStore')
                        ->findOneBy(array('storeId' => (int) $store_id,'role' => 15,'userId' => (int) $object_info->session_id));
        //Check for the valid user and store relationship
        if(count($user_to_store) > 0) {
            $user_service = $this->get('user_object.service');
            $email_template_service =  $this->container->get('email_template.service'); //email template service.
            //cehck if store exist
            if(count($store_details) > 0) {
                if($store_details->getNewContractStatus() == 0) {
                // code for sending social notification of shop activation
                $shop_name = $store_details->getName();
                $this->_log('[Enter into sending contract process [user_id='. $this->_toJSON($to_id).',shop_id = '.$store_id.']]');
                //get store object
                $store_object_info = $user_service->getStoreObjectService($store_id);
                //get admin id
                $admin_id = $em
                        ->getRepository('TransactionTransactionBundle:RecurringPayment')
                        ->findByRole('ROLE_ADMIN');
                $this->_log('[Get Admin id [Admin_id='. $this->_toJSON($admin_id).']]');
                //get post service object
                $postService = $this->container->get('post_detail.service');
                $reciever = $postService->getUserData($to_id, true);
                $locale = empty($reciever[$to_id]['current_language']) ? $this->container->getParameter('locale') : $reciever[$to_id]['current_language'];
                $this->_log('[Current User Local [user_id='. $this->_toJSON($to_id).', Local = '.$this->_toJSON($locale).']]');
                $lang_array = $this->container->getParameter($locale);
                //get the mail information for the user based on the user language setting
                $click_here = $lang_array['CLICK_HERE'];
                $detail_text = $lang_array['SHOP_AFFILIATION_ACTIVE_LINK_TEXT'];
                $discount_position_amount = $this->container->getParameter('shop_discount_position_amount');
                $discount_shot_amount = $this->container->getParameter('shot_amount');
                $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
                $shop_profile_url     = $this->container->getParameter('shop_profile_url'); //shop profile url
                $href = $angular_app_hostname.$shop_profile_url. "/".$store_id;
                $shopProfileLink = "<a href='$href"."'>$click_here</a>";
                $mail_sub = $lang_array['SHOP_AFFILIATION_ACTIVE_SUBJECT'];
                $mail_body = sprintf($lang_array['SHOP_AFFILIATION_ACTIVE_BODY'],$shop_name);
                // replace shopName, tutorialLink,tutorialLinkClickHere, appleLink, androidLink
                $tutorialHref = $angular_app_hostname.'shoptutorial';
                $tutorialLinkClickHere = "<a href='{$tutorialHref}'>".$lang_array['CLICK_HERE']."</a>";
                $appleLink = "<a href='https://itunes.apple.com/it/artist/sixthcontinent/id532299092'>Apple</a>";
                $androidLink = "<a href='https://play.google.com/store/apps/developer?id=SixthContinent+INC'>Android</a>";
                $mail_text = sprintf($lang_array['SHOP_AFFILIATION_ACTIVE_TEXT'],$shop_name,$shopProfileLink,$appleLink,$androidLink,$tutorialLinkClickHere);
                $link_affilation = $shopProfileLink." $detail_text";
                $link =  $mail_text.$link_affilation;
                if($store_object_info){
                    $thumb_path = $store_object_info['thumb_path'];
                } else{
                    $thumb_path = '';
                }
                $this->_log('[Mail content prepration ends for [user_id='. $this->_toJSON($to_id).', shop_id = '.$this->_toJSON($store_id).']]');
                $from_id = $admin_id;
                $dm = $this->get('doctrine.odm.mongodb.document_manager');                   
                // notification for shop active
                //code to get email attchment
                $type = 1; //from admin
                $attachment = 1; //with attchment
                $pdfurl = $this->generatepdf($store_details,$store_category_percentage);
                $pdfurl_a = $this->generatepdfA($store_details,$store_category_percentage);
                $attachmemt_path = array();
                $attachmemt_path_admin = array();
                //if some error occured
                if(!$pdfurl){
                $attachment = 1; //no attchment
                $pdfurl = '';
                }
                if($pdfurl) {
                    $attachmemt_path['contract_b'] = $pdfurl;
                    $attachmemt_path_admin['contract_b'] = $pdfurl;
                }
                if($pdfurl_a) {
                    $attachmemt_path_admin['contract_a'] = $pdfurl_a;
                }
               $this->_log('[Start sending mail via sendgrid for [user_id='. $this->_toJSON($to_id).', shop_id = '.$this->_toJSON($store_id).']]');
               $email_template_service->sendMail($reciever, $link, $mail_body, $mail_sub, $thumb_path, 'TRANSACTION', $attachmemt_path);
               $sixthcontinent_shop_admin_email = $this->container->getParameter('sixthcontinent_shop_admin_email'); 
               $email_template_service->sendMail(array($sixthcontinent_shop_admin_email), $link, $mail_body, $mail_sub, $thumb_path, 'TRANSACTION', $attachmemt_path_admin, 2, 1);

               //update new contarct status to 1
               $store_details->setNewContractStatus(1);
               $em->persist($store_details);
               $em->flush();
               $this->_log('[Set New Contract Status to 1 for [user_id='. $this->_toJSON($to_id).', shop_id = '.$this->_toJSON($store_id).']]');
              }
             $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
             echo json_encode($res_data);
             exit();         
             } else {
                $res_data = array('code' => 413, 'message' => 'INVALID_STORE', 'data' => $data);
                $this->_log('Exiting with message INVALID_STORE [user_id='. $this->_toJSON($to_id).', shop_id = '.$this->_toJSON($store_id).']]');
                echo json_encode($res_data);
                exit();
        }
        } else {
             $res_data = array('code' => 1054, 'message' => 'ACCESS_VOILATION', 'data' => $data);
             $this->_log('Exiting with message ACCESS_VOILATION [user_id='. $this->_toJSON($to_id).', shop_id = '.$this->_toJSON($store_id).']]');
             echo json_encode($res_data);
             exit();
        }
    }
    
    /**
     *  function for converting the input to the json object
     * @param type $data
     * @return type
     */
    private function _toJSON($data){
        return json_encode($data);
    }
    
    /**
     *  function for getting the percantage of amount deduction for shop based on category
     * @param type $shop_info
     */
    public function getCategoryWiseCardAndTxnInfo($shop_info) {
        $amount_pecentage = $this->container->getParameter('card_percentage');
        $txn_percentage = $this->container->getParameter('txn_percentage');
        $em = $this->container->get('doctrine')->getManager();
        //get card percentage to be used.
        $shop_cat_id = $shop_info->getSaleCatid();
        $card_percentage = $shop_info->getCardPercentage();
        $tranaction_percentage = $shop_info->getTxnPercentage();
        //get card transaction percentage by category
        if ($card_percentage != '' || $card_percentage != 0 || $card_percentage != null) { //shop card percentage
            $amount_pecentage = $card_percentage;
        } else {
            if ($shop_cat_id != null) { //if shop category is not null
                $business_category = $em->getRepository('UserManagerSonataUserBundle:BusinessCategory')->find($shop_cat_id);
                if ($business_category) { //category is exist.
                    $business_category_pecentage = $business_category->getCardPercentage();
                    if ($business_category_pecentage != '' || $business_category_pecentage != 0 || $business_category_pecentage != null) { //if shop category card percentage is not null.
                        $amount_pecentage = $business_category_pecentage;
                    }
                }
            }
        }
        
        //get transaction transaction percentage by category
        if ($tranaction_percentage != '' || $tranaction_percentage != 0 || $tranaction_percentage != null) { //shop card percentage
            $txn_percentage = $tranaction_percentage;
        } else {
            if ($shop_cat_id != null) { //if shop category is not null
                $business_category = $em->getRepository('UserManagerSonataUserBundle:BusinessCategory')->find($shop_cat_id);
                if ($business_category) { //category is exist.
                    $business_category_txn_pecentage = $business_category->getTxnPercentage();
                    if ($business_category_txn_pecentage != '' || $business_category_txn_pecentage != 0 || $business_category_txn_pecentage != null) { //if shop category card percentage is not null.
                        $txn_percentage = $business_category_txn_pecentage;
                    }
                }
            }
        }       
        $final_result = array('txn_percentage' => $txn_percentage,'amount_pecentage' => $amount_pecentage);
        return $final_result;
    }
}
