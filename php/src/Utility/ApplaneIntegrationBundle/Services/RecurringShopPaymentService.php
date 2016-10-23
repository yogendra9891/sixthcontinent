<?php

namespace Utility\ApplaneIntegrationBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactionsPayment;
use Utility\ApplaneIntegrationBundle\Entity\ShopTransactions;
use Transaction\TransactionSystemBundle\Entity\Transaction;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Utility\ApplaneIntegrationBundle\Document\TransactionPaymentNotificationLog;
use Utility\ApplaneIntegrationBundle\Document\SubscriptionPaymentNotificationLog;
use Utility\ApplaneIntegrationBundle\Document\TransactionNotificationLog;

// service method  class
class RecurringShopPaymentService {

    protected $em;
    protected $dm;
    protected $container;

    CONST SUCCESS = "SUCCESS";
    CONST FAILED = "FAILED";
    CONST R = "R";
    CONST T = "T";
    CONST S = "S";
    CONST SYSTEM = "SYSTEM";
    CONST PENDING_PAYMENT = "PENDING_PAYMENT";

    protected $payment_limit = 0.01;
    protected $payment_limit_recurring = 1;

    CONST CONFIRMED = "CONFIRMED";
    CONST RECURRING = "RECURRING";
    CONST SUBSCRIPTION_PENDING_PAYMENT = "SUBSCRIPTION_PENDING_PAYMENT";
    CONST SUBSCRIBED = "SUBSCRIBED";
    CONST UNSUBSCRIBED = "UNSUBSCRIBED";
    CONST DAYS_COUNTER = 8;
    CONST PENDING = 'PENDING';
    CONST REG_FEE_NOT_PAID = "REG_FEE_NOT_PAID";
    CONST SHOP_RECURRING_SUBSCRIPTION_FEE_WAIVER = "SHOP_RECURRING_SUBSCRIPTION_FEE_WAIVER";
    CONST SUBSCRIPTION_WAIVER_STATUS = 3;
    CONST WAIVER_TYPE = 'SHOP';
    CONST SHOP_RECURRING_REGISTRATION_FEE_WAIVER = "SHOP_RECURRING_REGISTRATION_FEE_WAIVER";
    CONST REGISTRATION_WAIVER_STATUS = 3;

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
        //$this->request   = $request;
    }

    /**
     * 
     */
    public function payrecurringpayment() {
        //STEP:1

        $this->payTransaction();

        exit('ok');
    }

    /**
     * Get Pay Type
     * @param string $current_type
     * @param string $pending_type
     * @return string
     */
    public function getPayType($current_type, $pending_type) {
        $pos = strpos($pending_type, $current_type);
        if ($pos === false) {
            return $current_type . $pending_type;
        }
        return $pending_type;
    }

    /**
     * Pay transaction through cartasi
     */
    public function payTransaction() {
        //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In RecurringShopPaymentService.payTransaction";
        $applane_service->writeAllLogs($handler, $monolog_data, array());

        $time = new \DateTime("now");
        $em = $this->container->get('doctrine')->getManager();
        //get all shops that status 0 and and id is max
        //$this is ok
        $shop_pending_transactions = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                ->getAllPedningTransaction();

        foreach ($shop_pending_transactions as $shop_pending_transaction) {
            $shop_ids = array();
            $reg_txn_id = '';
            $current_txn_id = $shop_pending_transaction->getId();
            $shop_id = $shop_pending_transaction->getShopId();
            //get shop owner id
            $shop_ids[] = $shop_id;
            //get shop objects and extract shop owner id
            $user_object = $this->container->get('user_object.service');
            $user_object_service = $user_object->getShopsOwnerIds($shop_ids, array(), true);
            $shop_ids_users = $user_object_service['owner_ids']; //userid,shop_owner_id associated array
            $user_id = (isset($shop_ids_users[$shop_id]) ? $shop_ids_users[$shop_id] : 0); //store owner id.
            $pay_type = $shop_pending_transaction->getPayType();
            $total_amount_to_pay = $shop_pending_transaction->getTotalAmount();
            $pending_transaction_ids = $shop_pending_transaction->getPendingIds();


            //get registration fee txn id
            $shop_pending_type_val = self::R;
            $pos = strpos($pay_type, $shop_pending_type_val); //check if R exist
            if ($pos === false) {
                $reg_txn_id = '';
            } else {
                $reg_txns = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                        ->findOneBy(array('shopId' => $shop_id, 'type' => 'R', 'status' => 0));
                if ($reg_txns) {
                    $reg_txn_id = $reg_txns->getId();
                }
            }

            $pos_s = strpos($pay_type, self::S); //check if S exist
            if ($pos_s === false) {
                $sub_txn_id = '';
            } else {
                $sub_txns = $em
                        ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                        ->findOneBy(array('shopId' => $shop_id, 'type' => 'S', 'status' => 0));
                if ($sub_txns) {
                    $sub_txn_id = $sub_txns->getId();
                }
            }
            //get contract object
            $contract_default_obj = $em
                    ->getRepository('CardManagementBundle:Contract')
                    ->findOneBy(array('profileId' => $shop_id, 'defaultflag' => 1, 'deleted' => 0));

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
                    'divisa' => $currency_code,
                    'importo' => $amount_to_pay,
                    'codTrans' => $codTrans,
                    'num_contratto' => $contract_number,
                    'descrizione' => 'recurring payment',
                    'mail' => $contract_email,
                    'scadenza' => $contract_expiration
                );

                $pay_result = $this->recurringPaymentCurl($data, $recurring_pay_url);

                //maintain logger for cartasi response
                $monolog_data = "Request: Data=>" . json_encode($data) . " \n Url:" . $recurring_pay_url;
                $monolog_data_pay_result = json_encode($pay_result);
                $applane_service->writeAllLogs($handler, $monolog_data, $monolog_data_pay_result);
                //end to maintain the logger

                if (!empty($pay_result)) {

                    if ($pay_result['RootResponse']['StoreResponse']['codiceEsito'] == 0) {
                        ///code for payment success code 
                        $pay_result['RootResponse']['StoreResponse']['paese'] = (isset($pay_result['RootResponse']['StoreResponse']['paese']) ? $pay_result['RootResponse']['StoreResponse']['paese'] : '');
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

                        //mark shop registration fee paid
                        $store_obj = $em
                                ->getRepository('StoreManagerStoreBundle:Store')
                                ->findOneBy(array('id' => $shop_id));

                        $pos = strpos($pay_type, self::R); //check if R exist
                        if ($pos !== false) {
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

                        $pos_s = strpos($pay_type, self::S); //check if S exist
                        if ($pos_s !== false) {
                            //mark the shop as subscribed
                            $this->updateShopSubscriptionStatus($shop_id, '1');
                            //update applane for subscribed transaction
                            $applane_txn_id = $this->updateOnApplaneSusbcription($shop_id);
                            //update for applane_transaction_id
                            $this->updateTransactionId($shop_id, $applane_txn_id);
                            $sub_status = self::CONFIRMED;
                            //update shop subscription
                            $this->updateShopSubscription($shop_id, $sub_status);
                        }

                        // mark status as success for previous pending transaction
                        if (count($pending_transaction_ids) > 0) {
                            $update_pending_transaction = $em
                                    ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                                    ->setMultiTransactionStatus($pending_transaction_ids, 1);
                        }
                        //update on applane for success
                        $this->updateOnApplane($pending_transaction_ids, $current_txn_id, $reg_txn_id, $sub_txn_id, self::SUCCESS);
                        //check if S type
                        if ($pos_s !== false) {
                            //Send Success Mail
                            $this->sendNotification($shop_id, $user_id, 'SUCCESS');
                            //Update subscription log
                            $this->subscriptionPaymentSuccessLogs($user_id, $shop_id);
                        }
                        if ($pay_type != self::S) {
                            $this->transactionPaymentSuccessLogs($user_id, $shop_id); //remove the transaction payment notification logs if exists.
                        }
                        $pos_r = strpos($pay_type, self::R); //check if R exist
                        if ($pos_r !== false) {
                            $this->transactionRegistrationPaymentSuccessLogs($user_id, $shop_id); //make logs when registration payment failed for notifications
                        }

                        //update payment transaction table
                        $pstatus = self::CONFIRMED;
                        $error_code = '';
                        $error_description = '';
                        $transaction_reference = $pay_result['RootResponse']['StoreRequest']['codTrans'];
                        $transaction_value = $amount_to_pay;
                        $transaction_value = $transaction_value / 100;
                        $vat_amount = 0;
                        $paypal_id = '';
                        //update on payment transaction table
                        $this->updatePaymentTransaction($current_txn_id, $pay_type, $user_id, $shop_id, 'CARTASI', $pstatus, $error_code, $error_description, $transaction_reference, $transaction_value, $vat_amount, $contract_id, $paypal_id);
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
                        $shop_pending_transaction->setStatus(2);
                        $shop_pending_transaction->setContractId($contract_id);
                        $em->persist($shop_pending_transaction);
                        $em->flush();

                        $this->updateOnApplane($pending_transaction_ids, $current_txn_id, $reg_txn_id, $sub_txn_id, self::FAILED);
                        $pos_s = strpos($pay_type, self::S); //check if S exist
                        $pos_r = strpos($pay_type, self::R); //check if R exist
                        if ($pos_s !== false) {
                            //make log for subscription 
                            $this->subscriptionTransactionPaymentLogs($user_id, $shop_id);
                        }
                        if ($pay_type != self::S) {
                            $this->transactionPaymentLogs($user_id, $shop_id); //make logs when payment failed for notifications
                        }
                        if ($pos_r !== false) {
                            //if registration fee exist
                            $this->transactionRegistrationPaymentLogs($user_id, $shop_id); //make logs when registration payment failed for notifications
                        }

                        //update payment transaction table
                        $pstatus = self::FAILED;
                        $error_code = '';
                        $error_description = '';
                        $transaction_reference = $pay_result['RootResponse']['StoreRequest']['codTrans'];
                        $transaction_value = $amount_to_pay;
                        $vat_amount = 0;
                        $paypal_id = '';
                        $transaction_value = $transaction_value / 100;
                        //update on payment transaction table
                        $this->updatePaymentTransaction($current_txn_id, $pay_type, $user_id, $shop_id, 'CARTASI', $pstatus, $error_code, $error_description, $transaction_reference, $transaction_value, $vat_amount, $contract_id, $paypal_id);

                        //maintain logger for cartasi response
                        $monolog_data_pay_result = "Payment failed: " . json_encode($pay_result);
                        $applane_service->writeAllLogs($handler, '', $monolog_data_pay_result);
                        //end to maintain the logger
                    }
                }
            } else {
                $shop_pending_transaction->setPaymentDate($time);
                $shop_pending_transaction->setComment("Contract not found");
                $shop_pending_transaction->setStatus(2);
                $shop_pending_transaction->setContractId(0);
                $em->persist($shop_pending_transaction);
                $em->flush();
                $this->updateOnApplane($pending_transaction_ids, $current_txn_id, $reg_txn_id, $sub_txn_id, self::FAILED);
                $pos_s = strpos($pay_type, self::S); //check if S exist
                $pos_r = strpos($pay_type, self::R); //check if R exist
                if ($pos_s !== false) {
                    // make log for subscription
                    $this->subscriptionTransactionPaymentLogs($user_id, $shop_id);
                }
                if ($pay_type != self::S) {
                    $this->transactionPaymentLogs($user_id, $shop_id); //make logs when payment failed for notifications
                }
                if ($pos_r !== false) {
                    //if registration fee exist
                    $this->transactionRegistrationPaymentLogs($user_id, $shop_id); //make logs when registration payment failed for notifications
                }

                //maintain logger for cartasi response
                $monolog_data_pay_result = "Contract Not Found";
                $applane_service->writeAllLogs($handler, '', $monolog_data_pay_result);
                //end to maintain the logger
            }
        }
        $monolog_data = "Exiting From RecurringShopPaymentService.payTransaction";
        $applane_service->writeAllLogs($handler, $monolog_data, '');
        return true;
    }

    /**
     *  PAy Single transaction charge throw  cartasì gateway
     */
    public function paySingleRecurrinTransaction($sellerId, $id_transaction, $time_close, $currency_code = "EUR") {

        //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In RecurringShopPaymentService.paySingleRecurrinTransaction with params sellerid: $sellerId, id_trnasaction : $id_transaction,  time_close : $time_close";
        $applane_service->writeAllLogs($handler, $monolog_data, array());
        $time = new \DateTime("now");
        $em = $this->container->get('doctrine')->getManager();

        //get all shops that status 0 and and id is max
        //$this is ok
        $total_amount_to_pay = $em
                ->getRepository('TransactionSystemBundle:Transaction')
                ->getTotalSixthContinentCheckout($sellerId, $id_transaction, $time_close);
        //get shop objects and extract shop owner id
        $user_object = $this->container->get('user_object.service');

        $pay_type = "PAYINSHOP";


        //get contract object
        $contract_default_obj = $em
                ->getRepository('CardManagementBundle:Contract')
                ->findOneBy(array('profileId' => $sellerId, 'defaultflag' => 1, 'deleted' => 0));
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $sellerId));
        $redistribution_ci  = $this->container->get('redistribution_ci');

        if ($total_amount_to_pay >= $this->payment_limit_recurring) {
            $shop_transaction_payment = new ShopTransactionsPayment;
            $shop_transaction_payment->setCreatedAt(new \DateTime("now"));
            $shop_transaction_payment->setMode("");
            $shop_transaction_payment->setPaymentDate($time);
            $shop_transaction_payment->setPayType($pay_type);
            $shop_transaction_payment->setShopId($sellerId);
            $shop_transaction_payment->setPendingIds("");
            
            if ($contract_default_obj) {
                //$contract_number = $contract_default_obj->getContractNumber();
                $contract_number =$contract_default_obj->getContractNumber();
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
                $codTrans = "6THCH" . time() . $sellerId . $pay_type;
                $amount_to_pay = $total_amount_to_pay; 
                // has to update wallet of the shop
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
                    'divisa' => $currency_code,
                    'importo' => $amount_to_pay,
                    'codTrans' => $codTrans,
                    'num_contratto' => $contract_number,
                    'descrizione' => 'recurring payment',
                    'mail' => $contract_email,
                    'scadenza' => $contract_expiration
                );


                $pay_result = $this->recurringPaymentCurl($data, $recurring_pay_url);

                //maintain logger for cartasi response
                $monolog_data = "Request: Data=>" . json_encode($data) . " \n Url:" . $recurring_pay_url;
                $monolog_data_pay_result = json_encode($pay_result);
                $applane_service->writeAllLogs($handler, $monolog_data, $monolog_data_pay_result);
                //end to maintain the logger
                
                if (!empty($pay_result)) {
                    if ($pay_result['RootResponse']['StoreResponse']['codiceEsito'] == 0) {

                        ///code for payment success code 
                        $pay_result['RootResponse']['StoreResponse']['paese'] = (isset($pay_result['RootResponse']['StoreResponse']['paese']) ? $pay_result['RootResponse']['StoreResponse']['paese'] : '');
                        $shop_transaction_payment->setTipoCarta($pay_result['RootResponse']['StoreResponse']['tipoCarta']);
                        $shop_transaction_payment->setPaese($pay_result['RootResponse']['StoreResponse']['paese']);
                        $shop_transaction_payment->setTipoProdotto($pay_result['RootResponse']['StoreResponse']['tipoProdotto']);
                        $shop_transaction_payment->setTipoTransazione($pay_result['RootResponse']['StoreResponse']['tipoTransazione']);
                        $shop_transaction_payment->setCodiceAutorizzazione($pay_result['RootResponse']['StoreResponse']['codiceAutorizzazione']);
                        $shop_transaction_payment->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
                        $shop_transaction_payment->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
                        $shop_transaction_payment->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_transaction_payment->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
                        $shop_transaction_payment->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_transaction_payment->setComment($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_transaction_payment->setContractTxnId($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_transaction_payment->setStatus(1);
                        $shop_transaction_payment->setContractId($contract_id);
                        $shop_transaction_payment->setPendingAmount(0);
                        $shop_transaction_payment->setTotalAmount($amount_to_pay);
                        $em->persist($shop_transaction_payment);
                        $em->flush();
                        // has to update wallet of the shop
                        $redistribution_ci->updateSuccessRecurring($sellerId, $id_transaction, $time_close , $codTrans , true);

                        
                    } else {
                        //code for payment failed
                        $shop_transaction_payment->setPendingAmount($amount_to_pay);
                        $shop_transaction_payment->setTotalAmount($amount_to_pay);
                        $shop_transaction_payment->setPaese('');
                        $shop_transaction_payment->setTipoProdotto('');
                        $shop_transaction_payment->setTipoTransazione('');
                        $shop_transaction_payment->setCodiceAutorizzazione('');
                        $shop_transaction_payment->setTipoCarta('');
                        $shop_transaction_payment->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
                        $shop_transaction_payment->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
                        $shop_transaction_payment->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_transaction_payment->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
                        $shop_transaction_payment->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_transaction_payment->setComment($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_transaction_payment->setContractTxnId($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_transaction_payment->setStatus(2);
                        $shop_transaction_payment->setContractId($contract_id);

                        $em->persist($shop_transaction_payment);
                        $em->flush();
                        //maintain logger for cartasi response
                        $monolog_data_pay_result = "Payment failed: " . json_encode($pay_result);
                        $applane_service->writeAllLogs($handler, '', $monolog_data_pay_result);
                        //end to maintain the logger
                    }
                }
            } else {
                $shop_transaction_payment->setPendingAmount($total_amount_to_pay);
                $shop_transaction_payment->setTotalAmount($total_amount_to_pay);
                $shop_transaction_payment->setPaese('');
                $shop_transaction_payment->setTipoProdotto('');
                $shop_transaction_payment->setTipoTransazione('');
                $shop_transaction_payment->setCodiceAutorizzazione('');
                $shop_transaction_payment->setTipoCarta('');
                $shop_transaction_payment->setDataOra("");
                $shop_transaction_payment->setCodiceEsito("");
                $shop_transaction_payment->setDescrizioneEsito("no");
                $shop_transaction_payment->setMac("");
                $shop_transaction_payment->setCodTrans("");
                $shop_transaction_payment->setContractTxnId("");
                
                $shop_transaction_payment->setPaymentDate($time);
                $shop_transaction_payment->setComment("Contract not found");
                $shop_transaction_payment->setStatus(2);
                $shop_transaction_payment->setContractId(0);
                $em->persist($shop_transaction_payment);
                $em->flush();
                $monolog_data_pay_result = "Payment no contract id associated: ";
                $applane_service->writeAllLogs($handler, '', $monolog_data_pay_result);
            }
        }else {
            $codTrans="NOTHINGTOPAY";
            $redistribution_ci->updateSuccessRecurring($sellerId, $id_transaction, $time_close , $codTrans , false);

            $monolog_data = "Total amount to pay  $total_amount_to_pay is < than ".$this->payment_limit_recurring." \n Url:";
            $applane_service->writeAllLogs($handler, $monolog_data, '');
        }
        //}
        $monolog_data = "Exiting Exiting from RecurringShopPaymentService.paySingleRecurrinTransaction";
        $applane_service->writeAllLogs($handler, $monolog_data, '');
        return true;
    }

    
        /**
     *  PAy Single transaction charge throw  cartasì gateway
     */
    public function justRecurrinPay($sellerId, $id_transaction, $total_amount_to_pay, $time_close, $currency_code = "EUR") {

        //maintain log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data = "Entering In RecurringShopPaymentService.paySingleRecurrinTransaction with params sellerid: $sellerId, id_trnasaction : $id_transaction,  time_close : $time_close";
        $applane_service->writeAllLogs($handler, $monolog_data, array());
        $time = new \DateTime("now");
        $em = $this->container->get('doctrine')->getManager();

        //get shop objects and extract shop owner id
        $user_object = $this->container->get('user_object.service');

        $pay_type = self::RECURRING;


        $contract_default_obj = $em
                ->getRepository('CardManagementBundle:Contract')
                ->findOneBy(array('profileId' => $sellerId, 'defaultflag' => 1, 'deleted' => 0));
        
        $redistribution_ci  = $this->container->get('redistribution_ci');

        if ($total_amount_to_pay >= $this->payment_limit_recurring) {
                $shop_transaction_payment = new ShopTransactionsPayment;
                
                $shop_transaction_payment->setCreatedAt(new \DateTime("now"));
                $shop_transaction_payment->setMode("");
                $shop_transaction_payment->setPaymentDate($time);
                $shop_transaction_payment->setPayType($pay_type);
                $shop_transaction_payment->setShopId($sellerId);
                $shop_transaction_payment->setPendingIds("");
                
            if ($contract_default_obj) {
                //$contract_number = $contract_default_obj->getContractNumber();
                $contract_number =$contract_default_obj->getContractNumber();
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
                $codTrans = "6THCH" . time() . $sellerId . $pay_type;
                $amount_to_pay = $total_amount_to_pay; 
                // has to update wallet of the shop
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
                    'divisa' => $currency_code,
                    'importo' => $amount_to_pay,
                    'codTrans' => $codTrans,
                    'num_contratto' => $contract_number,
                    'descrizione' => 'recurring payment',
                    'mail' => $contract_email,
                    'scadenza' => $contract_expiration
                );


                $pay_result = $this->recurringPaymentCurl($data, $recurring_pay_url);

                //maintain logger for cartasi response
                $monolog_data = "Request: Data=>" . json_encode($data) . " \n Url:" . $recurring_pay_url;
                $monolog_data_pay_result = json_encode($pay_result);
                $applane_service->writeAllLogs($handler, $monolog_data, $monolog_data_pay_result);
                //end to maintain the logger
                
                
                if (!empty($pay_result)) {
                    

                    if ($pay_result['RootResponse']['StoreResponse']['codiceEsito'] == 0) {

                        ///code for payment success code 
                        $pay_result['RootResponse']['StoreResponse']['paese'] = (isset($pay_result['RootResponse']['StoreResponse']['paese']) ? $pay_result['RootResponse']['StoreResponse']['paese'] : '');
                        $shop_transaction_payment->setTipoCarta($pay_result['RootResponse']['StoreResponse']['tipoCarta']);
                        $shop_transaction_payment->setPaese($pay_result['RootResponse']['StoreResponse']['paese']);
                        $shop_transaction_payment->setTipoProdotto($pay_result['RootResponse']['StoreResponse']['tipoProdotto']);
                        $shop_transaction_payment->setTipoTransazione($pay_result['RootResponse']['StoreResponse']['tipoTransazione']);
                        $shop_transaction_payment->setCodiceAutorizzazione($pay_result['RootResponse']['StoreResponse']['codiceAutorizzazione']);
                        $shop_transaction_payment->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
                        $shop_transaction_payment->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
                        $shop_transaction_payment->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_transaction_payment->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
                        $shop_transaction_payment->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_transaction_payment->setComment($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_transaction_payment->setContractTxnId($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_transaction_payment->setStatus(1);
                        $shop_transaction_payment->setContractId($contract_id);
                        $shop_transaction_payment->setPendingAmount(0);
                        $shop_transaction_payment->setTotalAmount($amount_to_pay);
                        $em->persist($shop_transaction_payment);
                        $em->flush();
                        // has to update wallet of the shop
                        $redistribution_ci->updateSuccessRecurring($sellerId, $id_transaction, $time_close , $codTrans , true);

                        
                    } else {
                        //code for payment failed
                        $shop_transaction_payment->setPendingAmount($amount_to_pay);
                        $shop_transaction_payment->setTotalAmount($amount_to_pay);
                        $shop_transaction_payment->setPaese('');
                        $shop_transaction_payment->setTipoProdotto('');
                        $shop_transaction_payment->setTipoTransazione('');
                        $shop_transaction_payment->setCodiceAutorizzazione('');
                        $shop_transaction_payment->setTipoCarta('');
                        $shop_transaction_payment->setDataOra($pay_result['RootResponse']['StoreResponse']['dataOra']);
                        $shop_transaction_payment->setCodiceEsito($pay_result['RootResponse']['StoreResponse']['codiceEsito']);
                        $shop_transaction_payment->setDescrizioneEsito($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_transaction_payment->setMac($pay_result['RootResponse']['StoreResponse']['mac']);
                        $shop_transaction_payment->setCodTrans($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_transaction_payment->setComment($pay_result['RootResponse']['StoreResponse']['descrizioneEsito']);
                        $shop_transaction_payment->setContractTxnId($pay_result['RootResponse']['StoreRequest']['codTrans']);
                        $shop_transaction_payment->setStatus(2);
                        $shop_transaction_payment->setContractId($contract_id);

                        $em->persist($shop_transaction_payment);
                        $em->flush();
                        //maintain logger for cartasi response
                        $monolog_data_pay_result = "Payment failed: " . json_encode($pay_result);
                        $applane_service->writeAllLogs($handler, '', $monolog_data_pay_result);
                        //end to maintain the logger
                    }
                }
            }else{
                $shop_transaction_payment->setPendingAmount($total_amount_to_pay);
                $shop_transaction_payment->setTotalAmount($total_amount_to_pay);
                $shop_transaction_payment->setPaese('');
                $shop_transaction_payment->setTipoProdotto('');
                $shop_transaction_payment->setTipoTransazione('');
                $shop_transaction_payment->setCodiceAutorizzazione('');
                $shop_transaction_payment->setTipoCarta('');
                $shop_transaction_payment->setDataOra("");
                $shop_transaction_payment->setCodiceEsito("");
                $shop_transaction_payment->setDescrizioneEsito("no");
                $shop_transaction_payment->setMac("");
                $shop_transaction_payment->setCodTrans("");
                $shop_transaction_payment->setContractTxnId("");
                
                $shop_transaction_payment->setPaymentDate($time);
                $shop_transaction_payment->setComment("Contract not found");
                $shop_transaction_payment->setStatus(2);
                $shop_transaction_payment->setContractId(0);
                $em->persist($shop_transaction_payment);
                $em->flush();
                $monolog_data_pay_result = "Payment no contract id associated: ";
                $applane_service->writeAllLogs($handler, '', $monolog_data_pay_result);
            }
        }
        //}
        $monolog_data = "Exiting Exiting from RecurringShopPaymentService.paySingleRecurrinTransaction";
        $applane_service->writeAllLogs($handler, $monolog_data, '');
        
        $resut = array("sellere_id"=>$sellerId, "total_amount_to_pay"=>$total_amount_to_pay,"esito"=>$shop_transaction_payment->getDescrizioneEsito() ,"codTrans"=>$codTrans );
//        $resut = array("sellere_id"=>$sellerId, "total_amount_to_pay"=>$total_amount_to_pay,"esito"=>"test" ,"codTrans"=>"test"  );
        return $resut;
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

    /**
     * 
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
     * Update on applane
     * @param string $pending_ids
     * @param int $current_id
     */
    public function updateOnApplane($pending_ids, $current_txn_id, $reg_txn_id, $sub_txn_id, $status) {
        $this->__subscriptionLog('Enter In RecurringShopPaymentService->updateOnApplane', array());
        //$reg_txn_id = array($reg_txn_id);
        $em = $this->container->get('doctrine')->getManager();
        $pending_shop_array = array();
        if (strlen($pending_ids) > 0) {
            //prepare pending shop array
            $pending_shop_array = explode(',', $pending_ids);
        }

        $transaction_data_detail = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactionsPayment')
                ->findOneBy(array('id' => $current_txn_id));

        foreach ($pending_shop_array as $txn) {
            $txn_id = $txn;
            $transaction_data = $em
                    ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                    ->findOneBy(array('id' => $txn_id));

            $invoice_id = $transaction_data->getInvoiceId();
            $transaction_id_carte_si = $transaction_data_detail->getContractTxnId();
            $transaction_note = $transaction_data_detail->getComment();
            $paid_on = $transaction_data_detail->getPaymentDate();
            $payment_date = $transaction_data_detail->getPaymentDate();
            $payment_status = $status;
            $vat_amount = $transaction_data->getVat();  //calculate vat
            $amount_paid = ($transaction_data->getPayableAmount()) + $vat_amount; //calculate total amount paid
            $applane_data['invoice_id'] = $invoice_id;
            $applane_data['transaction_id_carte_si'] = $transaction_id_carte_si;
            $applane_data['transaction_note'] = $transaction_note;
            $applane_data['amount_paid'] = $amount_paid;
            $paid_on_sec = strtotime($paid_on->format('Y-m-d'));
            $applane_data['paid_on'] = date(DATE_RFC3339, ($paid_on_sec));
            //$applane_data['paid_on'] = $paid_on->format('Y-m-d') . "T00:00:00.000Z";
            $payment_date_sec = strtotime($payment_date->format('Y-m-d'));
            $applane_data['payment_date'] = date(DATE_RFC3339, ($payment_date_sec));
            //$applane_data['payment_date'] = $payment_date->format('Y-m-d') . "T00:00:00.000Z";
            $applane_data['payment_status'] = $payment_status;
            $applane_data['vat_amount'] = $vat_amount;
            $applane_data['shop_id'] = $transaction_data_detail->getShopId();
            //if transaction id is not equal to register txn id and subscription id
            if ($txn_id != $reg_txn_id && $txn_id != $sub_txn_id) {
                //get dispatcher object
                $event = new FilterDataEvent($applane_data);
                $dispatcher = $this->container->get('event_dispatcher');
                $dispatcher->dispatch('shop.recurringupdate', $event);
            } elseif ($txn_id == $reg_txn_id && $status == self::SUCCESS) {
                $event = new FilterDataEvent($applane_data);
                $dispatcher = $this->container->get('event_dispatcher');
                $dispatcher->dispatch('shop.recurringinsert', $event);
            }
        }
        $this->__subscriptionLog('Exit From RecurringShopPaymentService->updateOnApplane', array());
        return true;
    }

    /**
     * Update on applane for shop registration
     * @param type $shopid
     */
    public function updateOnApplaneRegistration($shopid) {
        $this->__subscriptionLog('Enter In RecurringShopPaymentService->updateOnApplaneRegistration', array());
        $applane_data['shop_id'] = $shopid;
        //get dispatcher object
        $event = new FilterDataEvent($applane_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('shop.registrationfeeupdate', $event);
        $this->__subscriptionLog('Exit From RecurringShopPaymentService->updateOnApplaneRegistration', array());
    }

    /**
     * pending payment failed logs 
     * @param int $user_id
     * @param int $shop_id
     * @return boolean 
     */
    public function transactionPaymentLogs($user_id, $shop_id) {
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [transactionPaymentLogs]', array());
        $time = new \DateTime('now');
        $start_date = new \DateTime('now'); //current date time
        $end_date = $time->modify('+1 week'); //add 7 days.
        $pending_payment_type = self::PENDING_PAYMENT;
        $counter = 1;

        $transaction_payment_log = new TransactionPaymentNotificationLog();
        $dm = $this->dm;
        $em = $this->em;
        /** get record if exists * */
        $transaction_payment_logs = $dm->getRepository('UtilityApplaneIntegrationBundle:TransactionPaymentNotificationLog')
                ->checkTransactionPaymentLogs($user_id, $shop_id, $pending_payment_type);
        //get shop object
        $shop_status = $this->getShopObject($shop_id);
        if ($shop_status == 0) {
            if ($transaction_payment_logs) { //if shop is already blocked so notification will be removed.
                $dm->remove($transaction_payment_logs);
                $dm->flush();
            }
            return true;  //if shop is blocked then no need to add notification
        }
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
        } else { //this else clause is for updating the counter and block the shop
            $counter = $transaction_payment_logs->getSendCount();
            $new_counter = $counter + 1;
            if ($new_counter >= self::DAYS_COUNTER) { //check if counter is >=8
                $resp = $this->blockShop($shop_id); //block the shop on 8 days counter(7 days end)
                if (!$resp) {
                    //if shop is blocked already
                    try {
                        $dm->remove($transaction_payment_logs);
                        $dm->flush();
                    } catch (\Exception $e) {
                        
                    }
                    return true;
                }
                $this->__subscriptionLog('Blocked the shop for userid: ' . $user_id . ' and shopid: ' . $shop_id, array());
            }
            $transaction_payment_logs->setSendCount($new_counter);
            $transaction_payment_logs->setUpdatedDate($start_date);
            $dm->persist($transaction_payment_logs);
        }
        try {
            $dm->flush();
            $this->__subscriptionLog('Update the log into collection [TransactionPaymentNotificationLog]  for userid: ' . $user_id . ' and shopid: ' . $shop_id, array());
        } catch (\Exception $ex) {
            $this->__subscriptionLog('There is some error in updating the log into collection [TransactionPaymentNotificationLog] for userid: ' . $user_id . ' and shopid: ' . $shop_id, 'Error is: ' . $ex->getMessage());
        }
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [transactionPaymentLogs]', array());
        return true;
    }

    /**
     * pending payment success logs remove(will not sent furthernotifications)
     * @param int $user_id
     * @param int $shop_id
     * @return boolean 
     */
    public function transactionPaymentSuccessLogs($user_id, $shop_id) {
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [transactionPaymentSuccessLogs]', array());
        $pending_payment_type = self::PENDING_PAYMENT;
        $dm = $this->dm;
        /** get record if exists * */
        $transaction_payment_log = $dm->getRepository('UtilityApplaneIntegrationBundle:TransactionPaymentNotificationLog')
                ->checkTransactionPaymentLogs($user_id, $shop_id, $pending_payment_type);
        if ($transaction_payment_log) { //if record exists we will remove.
            $dm->remove($transaction_payment_log);
            try {
                $dm->flush();
                $this->__subscriptionLog('Remove the log from collection [transactionPaymentNotificationLog] for user id:' . $user_id . ' and for shop id:' . $shop_id, array());
            } catch (\Exception $ex) {
                $this->__subscriptionLog('There is some error for removing the log from collection [transactionPaymentNotificationLog] for useid: ' . $user_id . ' and for shop id:' . $shop_id, 'Error is:' . $ex->getMessage());
            }
        }
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [transactionPaymentSuccessLogs]', array());
        return true;
    }

    /**
     * Update paymant transaction table
     * @param type $recurring_id
     * @param type $recurring
     * @param type $citizen_id
     * @param type $shop_id
     * @param type $mode
     * @param type $pstatus
     * @param type $error_code
     * @param type $error_description
     * @param type $transaction_reference
     * @param type $transaction_value
     * @param type $vat_amount
     * @param type $contract_id
     * @param type $paypal_id
     * $return boolean
     */
    public function updatePaymentTransaction($recurring_id, $recurring, $citizen_id, $shop_id, $mode, $pstatus, $error_code, $error_description, $transaction_reference, $transaction_value, $vat_amount, $contract_id, $paypal_id) {
        //update payment transaction table
        $pay_tx_data['item_id'] = $recurring_id;
        $pay_tx_data['reason'] = $recurring;
        $pay_tx_data['citizen_id'] = $citizen_id;
        $pay_tx_data['shop_id'] = $shop_id;
        $pay_tx_data['payment_via'] = $mode;
        $pay_tx_data['payment_status'] = $pstatus;
        $pay_tx_data['error_code'] = $error_code;
        $pay_tx_data['error_description'] = $error_description;
        $pay_tx_data['transaction_reference'] = $transaction_reference;
        $pay_tx_data['transaction_value'] = $transaction_value;
        $pay_tx_data['vat_amount'] = $vat_amount;
        $pay_tx_data['contract_id'] = $contract_id;
        $pay_tx_data['paypal_id'] = $paypal_id;
        $payment_txn = $this->container->get('paypal_integration.payment_transaction');
        $payment_txn->addPaymentTransaction($pay_tx_data);
    }

    /**
     * Update shop subscription
     * @param type $shop_id
     * @return boolean
     */
    public function updateShopSubscriptionStatus($shop_id, $status) {
        $this->__subscriptionLog('Enter In SubscriptionService->updateShopSubscriptionStatus', array());
        $em = $this->em;
        $store_obj = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $shop_id));
        if ($store_obj) {
            $store_obj->setIsSubscribed($status);
            try {
                $em->persist($store_obj);
                $em->flush();
            } catch (\Exception $e) {
                
            }
        }
        $this->__subscriptionLog('Exit From SubscriptionService->updateShopSubscriptionStatus', array());
        return true;
    }

    /**
     * Update on applane for shop subscription
     * @param type $shopid
     */
    public function updateOnApplaneSusbcription($shopid) {
        $this->__subscriptionLog('Enter In SubscriptionService->updateOnApplaneSusbcription', array());
        $applane_data['shop_id'] = $shopid;
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $susbcription_id = $applane_service->onShopSubscriptionAddAction($shopid);
        $this->__subscriptionLog('Exit From SubscriptionService->updateOnApplaneSusbcription: With Subscription id' . $susbcription_id, array());
        return $susbcription_id;
    }

    /**
     * Update shop subscription
     * @param int $txn_id
     * @param string $sub_status
     */
    public function updateShopSubscription($shop_id, $sub_status) {
        $this->__subscriptionLog('Enter into RecurringShopPaymentService->updateShopSubscription', array());
        $payment_confirmed = self::CONFIRMED;
        $payment_failed = self::FAILED;
        $subscribed = self::SUBSCRIBED;
        $unsubscribed = self::UNSUBSCRIBED;
        //get subscription object
        $em = $this->em;
        $subscription_obj = $em
                ->getRepository('CardManagementBundle:ShopSubscription')
                ->findOneBy(array('shopId' => $shop_id, 'status' => $subscribed));
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
            } elseif ($sub_status == $payment_failed) {
                //if failed
                $subscription_obj->setStatus($unsubscribed);
            }

            try {
                $em->persist($subscription_obj);
                $em->flush();
            } catch (\Exception $e) {
                $this->__subscriptionLog('Error in save the RecurringShopPaymentService->updateShopSubscription' . $e->getMessage());
            }
        }
        $this->__subscriptionLog('Exit From RecurringShopPaymentService->updateShopSubscription', array());
        return true;
    }

    /**
     * pending payment failed logs for subscription
     * @param int $user_id
     * @param int $shop_id
     * @return boolean 
     */
    public function subscriptionTransactionPaymentLogs($user_id, $shop_id) {
        $serializer = $this->container->get('serializer');
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [subscriptionTransactionPaymentLogs]', array());
        $time = new \DateTime('now');
        $start_date = new \DateTime('now'); //current date time
        $end_date = $time->modify('+1 week'); //add 7 days.
        $subscription_payment_type = self::SUBSCRIPTION_PENDING_PAYMENT;
        $counter = 1;
        $subscription_payment_log = new SubscriptionPaymentNotificationLog();
        $dm = $this->dm;

        /** get record if exists * */
        $subscription_payment_logs = $dm->getRepository('UtilityApplaneIntegrationBundle:SubscriptionPaymentNotificationLog')
                ->checkSubscriptionTransactionPaymentLogs($user_id, $shop_id, $subscription_payment_type);
        //get shop object
        $shop_status = $this->getShopObject($shop_id);
        if ($shop_status == 0) {
            if ($subscription_payment_logs) { //if shop is already blocked so notification will be removed.
                $dm->remove($subscription_payment_logs);
                $dm->flush();
            }
            return true;  //if shop is blocked then no need to add notification
        }
        if (!$subscription_payment_logs) { //if record does not exists enter new
            $subscription_payment_log->setToUserId($user_id);
            $subscription_payment_log->setToShopId($shop_id);
            $subscription_payment_log->setStartDate($start_date);
            $subscription_payment_log->setEndDate($end_date);
            $subscription_payment_log->setUpdatedDate($start_date);
            $subscription_payment_log->setSendCount($counter);
            $subscription_payment_log->setIsActive(1);
            $subscription_payment_log->setNotificationType($subscription_payment_type);
            $dm->persist($subscription_payment_log);
            try {
                $dm->flush();
                $json = $serializer->serialize($subscription_payment_log, 'json');
                $this->__subscriptionLog('Save the subscription notification logs successfully into collection [SubscriptionPaymentNotificationLog] for shop: ' . $shop_id . ' and user id: ' . $user_id . ' and saved data: ' . $json, array());
            } catch (\Exception $ex) {
                $this->__subscriptionLog('Error in save the subscription notification logs into collection [SubscriptionPaymentNotificationLog] for shop: ' . $shop_id . ' and user id: ' . $user_id, 'Exception is: ' . $ex->getMessage());
            }
        } else {
            $counter = $subscription_payment_logs->getSendCount();
            $new_counter = $counter + 1;
            if ($new_counter >= self::DAYS_COUNTER) {
                $shop_info = $this->em->getRepository('StoreManagerStoreBundle:Store')
                        ->findOneBy(array('id' => $shop_id, 'isActive' => 1));
                if ($shop_info) { //if shop is already blocked then no need to send notification, so we need to remove the notificaion log.
                    if ($shop_info->getShopStatus() == 0) {
                        $dm->remove($subscription_payment_logs);
                        $dm->flush();
                        $this->__subscriptionLog('Shop is already blocked so subscription notification to be removed for shop:' . $shop_id, array());
                        return true;
                    }
                }
                //update the subscription status.
                $this->__unsubscribeshop($shop_id, $user_id); //unsubscribe the shop in [ShopSubscription] table
                $this->__updatShopSubscription($shop_id, 0); //update subscribed status in [Store] table
                $this->updateShopTransactionSubscription($shop_id, $user_id); //update the [ShopTransaction] table on unsubscribed a shop.
                $this->__subscriptionLog('Blocking the shop subscription in table [ShopSubscription] and  table [Store] for shop:' . $shop_id, array());
            }
            $subscription_payment_logs->setSendCount($new_counter);
            $subscription_payment_logs->setUpdatedDate($start_date);
            $dm->persist($subscription_payment_logs);
            $this->__subscriptionLog('Counter is increased for shop subscription pending payment: counter=>' . $new_counter, array());
            $dm->flush();
        }
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [subscriptionTransactionPaymentLogs]', array());
        return true;
    }

    /**
     * write the subscription logs
     * @param string $request
     * @param string $response
     */
    private function __subscriptionLog($request, $response = array()) {
        $handler = $this->container->get('monolog.logger.recurring');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        try {
            $applane_service->writeAllLogs($handler, $request, $response);
        } catch (\Exception $ex) {
            
        }
        return true;
    }

    /**
     * subscription payment success logs remove(will not sent further notifications)
     * @param int $user_id
     * @param int $shop_id
     * @return boolean 
     */
    public function subscriptionPaymentSuccessLogs($user_id, $shop_id) {
        $serializer = $this->container->get('serializer');
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [subscriptionPaymentSuccessLogs]', array());
        $pending_payment_type = self::SUBSCRIPTION_PENDING_PAYMENT;
        $dm = $this->dm;
        /** get record if exists * */
        $subscription_payment_log = $dm->getRepository('UtilityApplaneIntegrationBundle:SubscriptionPaymentNotificationLog')
                ->checkSubscriptionTransactionPaymentLogs($user_id, $shop_id, $pending_payment_type);
        if ($subscription_payment_log) { //if record exists we will remove.
            $subscription_payment_log1 = $subscription_payment_log;
            $dm->remove($subscription_payment_log);
            try {
                $dm->flush();
                $json = $serializer->serialize($subscription_payment_log1, 'json'); //convert documnt object to json string
                $this->__subscriptionLog('Removing the logs from collection [SubscriptionPaymentNotificationLog] for shop: ' . $shop_id . ' and user: ' . $user_id . ' and data:' . $json, array());
            } catch (\Exception $ex) {
                $this->__subscriptionLog('Exception for removing the record for shop: ' . $shop_id . ' and user: ' . $user_id, 'Exception is:' . $ex->getMessage());
            }
        }
        return true;
    }

    /**
     * Map applane transaction id with subscription id
     * @param string $applane_txn_id
     * @param int $subsc_id
     */
    public function updateTransactionId($shop_id, $applane_txn_id) {
        $em = $this->em;
        $subscription_obj = $em
                ->getRepository('CardManagementBundle:ShopSubscription')
                ->findOneBy(array('shopId' => $shop_id, 'status' => self::SUBSCRIBED));
        if ($subscription_obj) {
            $subscription_obj->setTransactionId($applane_txn_id);
            $em->persist($subscription_obj);
            $em->flush();
        }

        //$user_id = $subscription_obj->getSubscriberId();
        return true;
    }

    /**
     * Send Mail
     * @param int $profile_id
     * @param string $status
     * @return boolean
     */
    public function sendNotification($shop_id, $receiver_id, $status) {
        $this->sendEmailNotification($shop_id, $receiver_id, true, true);
    }

    /**
     * send email for notification on shop activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @return boolean
     */
    public function sendEmailNotification($shop_id, $receiver_id, $isWeb = false, $isPush = false) {
        //$link = null;
        $email_template_service = $this->container->get('email_template.service');
        $postService = $this->container->get('post_detail.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_url = $this->container->getParameter('shop_profile_url');
        //send email service
        $receiver = $postService->getUserData($receiver_id, true);
        $recieverByLanguage = $postService->getUsersByLanguage($receiver);
        $emailResponse = '';
        foreach ($recieverByLanguage as $lng => $recievers) {
            $locale = $lng === 0 ? $this->container->getParameter('locale') : $lng;
            $lang_array = $this->container->getParameter($locale);
            $mail_sub = $lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_SUBJECT'];
            $mail_body = $lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_BODY'];
            $mail_text = $lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_TEXT'];
            $_shopUrl = $angular_app_hostname . $shop_url . '/' . $shop_id;
            $link = "<a href='$_shopUrl'>" . $lang_array['CLICK_HERE'] . "</a>";
            $mail_link_text = sprintf($lang_array['SHOPOWNER_SUBSCRIPTION_CARD_UPTO_100_LINK'], $link);
            $bodyData = $mail_text . '<br /><br />' . $mail_link_text;
            $thumb_path = "";
            $emailResponse = $email_template_service->sendMail($recievers, $bodyData, $mail_body, $mail_sub, $thumb_path, 'SUBSCRIPTION');
        }

        // push and web
        $msgtype = 'SUBSCRIPTION';
        $msg = '39EURO_SHOPPING_CARD';
        $extraParams = array('store_id' => $shop_id);
        $itemId = $shop_id;
        $postService->sendUserNotifications($receiver_id, $receiver_id, $msgtype, $msg, $itemId, $isWeb, $isPush, null, 'SHOP', $extraParams, 'T');
        return true;
    }

    /**
     * pending registration payment failed logs 
     * @param int $user_id
     * @param int $shop_id
     * @return boolean 
     */
    public function transactionRegistrationPaymentLogs($user_id, $shop_id) {
        $this->__subscriptionLog('Entering in [transactionRegistrationPaymentLogs] for shop: ' . $shop_id . ' and user id: ' . $user_id);
        $user_id = (int) $user_id;
        $shop_id = (int) $shop_id;
        $time = new \DateTime('now');
        $start_date = new \DateTime('now'); //current date time
        $end_date = $time->modify('+1 week'); //add 7 days.
        $pending_payment_type = self::PENDING_PAYMENT;
        $counter = 1;
        $msg_type = self::REG_FEE_NOT_PAID;
        $transaction_payment_log = new TransactionNotificationLog();
        $dm = $this->dm;
        $em = $this->em;
        //check notification sent
        $notifications_sent = $dm
                ->getRepository('UtilityApplaneIntegrationBundle:TransactionNotificationLog')
                ->checkNotificationSent($shop_id);
        //get shop object
        $shop_status = $this->getShopObject($shop_id);
        if ($shop_status == 0) {
            if ($notifications_sent) { //if shop is already blocked so notification will be removed.
                $dm->remove($notifications_sent);
                $dm->flush();
            }
            return true;  //if shop is blocked then no need to add notification
        }

        if (!$notifications_sent) {
            $transaction_payment_log->setToUserId($user_id);
            $transaction_payment_log->setToShopId($shop_id);
            $transaction_payment_log->setIsActive(1);
            $transaction_payment_log->setStartDate($start_date);
            $transaction_payment_log->setEndDate($end_date);
            $transaction_payment_log->setUpdatedDate($start_date);
            $transaction_payment_log->setSendCount(1);
            $transaction_payment_log->setNotificationType($msg_type);
            try {
                $dm->persist($transaction_payment_log);
                $dm->flush();
            } catch (\Exception $e) {
                $this->__subscriptionLog('Exception in [transactionRegistrationPaymentLogs] for shop: ' . $shop_id . ' and user id: ' . $user_id . ":" . $e->getMessage());
            }
        } else {
            //check for shop notification send count
            $send_count = $notifications_sent->getSendCount();
            $update_send_count = ((int) $send_count + 1);
            if ($update_send_count >= self::DAYS_COUNTER) {
                //block the shop if days counter is 8 
                $resp = $this->blockShop($shop_id);
                if (!$resp) {
                    //if shop is blocked already
                    try {
                        $dm->remove($notifications_sent);
                        $dm->flush();
                    } catch (\Exception $e) {
                        $this->__subscriptionLog('Exception in [transactionRegistrationPaymentLogs] for shop block: ' . $shop_id . ' and user id: ' . $user_id . ":" . $e->getMessage());
                    }
                    return true;
                }
            }
            //add new entry
            //$notifications_sent->setUpdatedDate($start_date);
            $notifications_sent->setSendCount($update_send_count);
            $notifications_sent->setNotificationType($msg_type);
            try {
                $dm->persist($notifications_sent);
                $dm->flush();
            } catch (\Exception $e) {
                $this->__subscriptionLog('Exception in [transactionRegistrationPaymentLogs] for notification update: ' . $shop_id . ' and user id: ' . $user_id . ":" . $e->getMessage());
            }
        }
        return true;
    }

    /**
     * Block Shop
     * @param int $shop_id
     */
    public function blockShop($shop_id) {
        $this->__subscriptionLog('Entering into class [RecurringShopPaymentService] and function [blockShop]', array());
        // get entity manager object
        $em = $this->em;
        $time = new \DateTime("now");
        $check_shops_registration = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $shop_id, 'isActive' => 1));
        if ($check_shops_registration) {
            //get shop status
            $shop_status = $check_shops_registration->getShopStatus();
            if ($shop_status == 0) {
                return false;
            }
            //block the shop
            $check_shops_registration->setShopStatus(0);
            $check_shops_registration->setUpdatedAt($time); //set the update time..
            $em->persist($check_shops_registration);
            $em->flush();
            $this->__subscriptionLog('Exiting from class [RecurringShopPaymentService] and function [blockShop]', array());
            return true;
        } else {
            $this->__subscriptionLog('Shop record does not exist into [Store] table for shopid: ' . $shop_id, array());
        }
        $this->__subscriptionLog('Exiting from class [RecurringShopPaymentService] and function [blockShop]', array());
        return true;
    }

    /**
     * Registration pending payment success logs remove(will not sent furthernotifications)
     * @param int $user_id
     * @param int $shop_id
     * @return boolean 
     */
    public function transactionRegistrationPaymentSuccessLogs($user_id, $shop_id) {
        $user_id = (int) $user_id;
        $shop_id = (int) $shop_id;
        $dm = $this->dm;
        //check notification sent
        $notifications_sent = $dm
                ->getRepository('UtilityApplaneIntegrationBundle:TransactionNotificationLog')
                ->checkNotificationSent($shop_id);
        if ($notifications_sent) { //if record exists we will remove.
            $dm->remove($notifications_sent);
            try {
                $dm->flush();
            } catch (\Exception $ex) {
                $this->__subscriptionLog('Exception in [transactionRegistrationPaymentSuccessLogs] for shop block: ' . $shop_id . ' and user id: ' . $user_id . ":" . $e->getMessage());
            }
        }
        return true;
    }

    /**
     * Unsubscribe the shop
     * @param int $shop_id
     */
    private function __unsubscribeshop($shop_id, $user_id) {
        $this->__subscriptionLog('Entering into [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [__unsubscribeshop]', array());
        $subscribed_array = array(self::SUBSCRIBED, self::PENDING);
        //unsubscribe
        $em = $this->em;
        //check contract is already exist
        $subscription_obj = $em->getRepository('CardManagementBundle:ShopSubscription')
                ->checkIfSubscribed($shop_id, $user_id, $subscribed_array);

        if (!$subscription_obj) {
            $this->__subscriptionLog('Subscription record does not exists in table [ShopSubscription] for shop: ' . $shop_id, array());
            return true;
        }
        $subscription_obj->setStatus(self::UNSUBSCRIBED);
        try {
            $em->persist($subscription_obj);
            $em->flush();
            $this->__subscriptionLog('Shop Subscription is unsubscribed in table [ShopSubscription] for shop: ' . $shop_id . ' with status: ' . self::UNSUBSCRIBED, array());
        } catch (\Exception $e) {
            
        }
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [__unsubscribeshop]', array());
        return true;
    }

    /**
     * Update shop subscription in store table
     * @param type $shop_id
     * @return boolean
     */
    private function __updatShopSubscription($shop_id, $status) {
        $this->__subscriptionLog('Entering into class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [__updatShopSubscription]', array());
        $em = $this->em;
        $store_obj = $em->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $shop_id));
        if ($store_obj) {
            $store_obj->setIsSubscribed($status);
            try {
                $em->persist($store_obj);
                $em->flush();
                $this->__subscriptionLog('Shop status is unsubscribed in [Store] table for shop: ' . $shop_id, array());
            } catch (\Exception $e) {
                
            }
        } else {
            $this->__transactionPaymentLogssubscriptionLog('Shop Record(store record) does not exists for shop: ' . $shop_id, array());
        }
        $this->__subscriptionLog('Exiting from class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [__updatShopSubscription]', array());
        return true;
    }

    /**
     * Update Shop Transaction for subscription if a shop subscription is unsubscribed
     * @param int $shop_id
     * @param int $user_id
     */
    public function updateShopTransactionSubscription($shop_id, $user_id) {
        $this->__subscriptionLog('Entering in [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [updateShopTransactionSubscription] for shop: ' . $shop_id . ' and user: ' . $user_id, array());
        $em = $this->em;
        //check if invoice exist
        $check_subscription = $em->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                ->findOneBy(array('shopId' => $shop_id, 'type' => 'S', 'status' => 0));
        if (!$check_subscription) {
            $this->__subscriptionLog('No record found in  [ShopTransactions] table for shop: ' . $shop_id . ' and user: ' . $user_id, array());
            return true;
        }
        //check subscription, Make the susbcription as 2
        $check_subscription->setStatus(2);
        try {
            $em->persist($check_subscription);
            $em->flush();
        } catch (\Exception $e) {
            $this->__subscriptionLog('Exception in [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [updateShopTransactionSubscription] for shop: ' . $shop_id . ' and user: ' . $user_id, 'Exception is:' . $e->getMessage());
        }
        $this->__subscriptionLog('Exiting from [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [updateShopTransactionSubscription] for shop: ' . $shop_id . ' and user: ' . $user_id, array());
        return true;
    }

    /**
     * Get shop object
     * @param int $shop_id
     */
    public function getShopObject($shop_id) {
        $shop_status = 0;
        $em = $this->em;
        $shops_obj = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $shop_id, 'isActive' => 1));
        if ($shops_obj) {
            $shop_status = $shops_obj->getShopStatus();
        }
        return $shop_status;
    }

    /**
     * Subscription waiver
     */
    public function subscriptionWaiver() {
        $this->__subscriptionLog('Exiting from Class[Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] for Function [subscriptionWaiver] no waiver found', array());
        $em = $this->em;
        //check for waiver setting
        try {
            $waiver_type = self::SHOP_RECURRING_SUBSCRIPTION_FEE_WAIVER;
            $waiver_value = $this->container->getParameter($waiver_type);
        } catch (\Exception $ex) {
            $waiver_value = 0;
        }
        if ($waiver_value == 0) {
            $this->__subscriptionLog('Exiting from Class[Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] for Function [subscriptionWaiver] no waiver found', array());
            return true; //no setting found
        }
        //get all subscribed users with status 0 from ShopTransactions
        $pay_subscription_fees = $em
                ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                ->findBy(array('type' => 'S', 'status' => 0));
        if (!$pay_subscription_fees) {
            $this->__subscriptionLog('Exiting from Class[Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] for Function [subscriptionWaiver] Message: No subscription transaction found with status 0', array());
            return true;
        }
        $date = new \DateTime("now");
        $waiver_service = $this->container->get('card_management.waiver');
        $waiver_obj = $waiver_service->getWaiverStatus(self::SHOP_RECURRING_SUBSCRIPTION_FEE_WAIVER, $date);
        if (!$waiver_obj) {
            $this->__subscriptionLog('Exiting From class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [subscriptionWaiver] With message: No options found for type ' . self::SHOP_RECURRING_SUBSCRIPTION_FEE_WAIVER, array());
            return true; //no waiver options found
        }
        try {
            $subscription_status = $waiver_obj['status'];
            $waiver_id = $waiver_obj['id'];

            //prepare transaction ids array
            foreach ($pay_subscription_fees as $pay_subscription_fee) {
                $transaction_ids[] = $pay_subscription_fee->getId();
                $shop_ids[] = $pay_subscription_fee->getShopId();
            }
            $user_object = $this->container->get('user_object.service');
            $user_object_service = $user_object->getShopsOwnerIds($shop_ids, array(), true);
            $shop_ids_users = $user_object_service['owner_ids']; //userid,shop_owner_id associated array
            //set transaction as status 1
            $transactions = $em
                    ->getRepository('UtilityApplaneIntegrationBundle:ShopTransactions')
                    ->updateWaiverSubscription($transaction_ids, self::SUBSCRIPTION_WAIVER_STATUS);

            if ($transactions) {
                //renew subscription
                $sub_status = self::CONFIRMED;
                foreach ($shop_ids as $shop_id) {
                    $this->updateShopSubscription($shop_id, $sub_status); //update shop subscription
                    //Update subscription log
                    $user_id = (isset($shop_ids_users[$shop_id]) ? $shop_ids_users[$shop_id] : 0); //store owner id.
                    $this->subscriptionPaymentSuccessLogs($user_id, $shop_id); //mark the notification success
                }
                //update shop as subscribed
                $store_obj = $em
                        ->getRepository('StoreManagerStoreBundle:Store')
                        ->updateWaiverSubscription($shop_ids);

                $waiver_service->addMultipleWaiver($waiver_id, $shop_ids, self::WAIVER_TYPE); //Update multiple Waiver
                //remove notification
            }
        } catch (\Exception $ex) {
            $this->__subscriptionLog('Exiting From class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [subscriptionWaiver] With Exception :' . $ex->getMessage());
        }
        $this->__subscriptionLog('Exiting From class [Utility\ApplaneIntegrationBundle\Services\RecurringShopPaymentService] function [subscriptionWaiver]', array());
        return true;
    }

}
