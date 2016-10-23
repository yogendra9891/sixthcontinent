<?php

namespace Paypal\PaypalIntegrationBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\Session\Session;
use Paypal\PaypalIntegrationBundle\Entity\PaymentTransaction;
use Paypal\PaypalIntegrationBundle\Model\PaypalConstentInterface;
use Utility\UtilityBundle\Utils\Utility;

// validate the data.like iban, vatnumber etc
class PaymentTransactionService {

    protected $em;
    protected $dm;
    protected $container;
    protected $valid_fee_type = array('CI_RETURN_FEE_PAYER', 'CHAINED_PAYMENT_FEE_PAYER');
    protected $valid_item_type = array('SHOP');
    private $valid_paypal_payers = array('PRIMARYRECEIVER', 'EACHRECEIVER', 'SECONDARYONLY', 'SENDER');

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
    }

    public function addPaymentTransaction($data) {
        $em = $this->em;
        $id = 0;
        $time = new \DateTime('now');
        $payment_transaction = new PaymentTransaction();
        $payment_transaction->setItemId($data['item_id']);
        $payment_transaction->setReason($data['reason']);
        $payment_transaction->setCitizenId($data['citizen_id']);
        $payment_transaction->setShopId($data['shop_id']);
        $payment_transaction->setPaymentVia($data['payment_via']);
        $payment_transaction->setPaymentStatus($data['payment_status']);
        $payment_transaction->setErrorCode($data['error_code']);
        $payment_transaction->setErrorDescription($data['error_description']);
        $payment_transaction->setTransactionReference($data['transaction_reference']);
        $payment_transaction->setDate($time);
        $payment_transaction->setTransactionValue($data['transaction_value']);
        $payment_transaction->setVatAmount($data['vat_amount']);
        $payment_transaction->setContractId($data['contract_id']);
        $payment_transaction->setPaypalId($data['paypal_id']);
        $payment_transaction_id = (isset($data['transaction_id']) ? $data['transaction_id'] : ''); //for UPTO100 card transaction inner id filed
        $payment_transaction->setTransationId($payment_transaction_id);
        $ci_used = isset($data['ci_used']) ? $data['ci_used'] : 0;
        $payment_transaction->setCiUsed($ci_used);
        $order_id = isset($data['order_id']) ? $data['order_id'] : '';
        $payment_transaction->setOrderId($order_id);
        $product_name = isset($data['product_name']) ? $data['product_name'] : '';
        $payment_transaction->setProductName($product_name);
        try {
            $em->persist($payment_transaction);
            $em->flush();
            $id = $payment_transaction->getId();
        } catch (\Exception $ex) {
            
        }
        return $id;
    }

    /**
     *  function for getting the fee payer based on the fee_type and the $shop_id and type
     * @param type $fee_type
     * @param type $shop_id
     * @return type
     */
    public function getPaypalFeePayer($fee_type, $shop_id, $type) {
        try {
            $this->writeLogs('Entering into class [Paypal/PaypalIntegrationBundle/Services/PaymentTransactionService] function [getPaypalFeePayer] with fee_type:'.$fee_type.' and shop_id:'.$shop_id.' and type:'.$type, '');
            $fee_type = Utility::getUpperCaseString(trim($fee_type));
            $type = Utility::getUpperCaseString(trim($type));
            $valid_fee_type = $this->valid_fee_type;
            $valid_item_type = $this->valid_item_type;
            $valid_fee_payer = $this->valid_paypal_payers;
            $default_payer = PaypalConstentInterface::DEFAULT_CI_PAYER_SHOP;
            //check if users pass a valid fee type
            if (!in_array($fee_type, $valid_fee_type)) {
                $this->writeLogs('Entering into class [Paypal/PaypalIntegrationBundle/Services/PaymentTransactionService] function [getPaypalFeePayer] Invalid fee type', '');
                return PaypalConstentInterface::DEFAULT_CI_PAYER_SHOP;
            }

            //check if users pass a valid type
            if (!in_array($type, $valid_item_type)) {
                $this->writeLogs('Entering into class [Paypal/PaypalIntegrationBundle/Services/PaymentTransactionService] function [getPaypalFeePayer] Invalid type', '');
                return $default_payer;
            }
            $this->writeLogs('Leaving the class [Paypal/PaypalIntegrationBundle/Services/PaymentTransactionService] function [getPaypalFeePayer] for getFormatForFeePayer', '');
            $fee_payer_options = $this->getFormatForFeePayer($fee_type, $type);
            $this->writeLogs('Back in class [Paypal/PaypalIntegrationBundle/Services/PaymentTransactionService] function [getPaypalFeePayer] with data:'.  json_encode($fee_payer_options), '');
            //check if there is any records for the given parameters 
            if ($fee_payer_options) {
                
                $payer_option_array = json_decode($fee_payer_options, true);
                //get the default payer 
                $default_payer = $this->checkDefaultPayer($payer_option_array, $default_payer);
                $this->writeLogs('In class [Paypal/PaypalIntegrationBundle/Services/PaymentTransactionService] function [getPaypalFeePayer] default payer is:'.$default_payer, '');
                //get feepayer based on shop id
                $fee_payer = $this->getFeePayerBasedOnShopId($shop_id, $payer_option_array, $default_payer);
                $this->writeLogs('In class [Paypal/PaypalIntegrationBundle/Services/PaymentTransactionService] function [getPaypalFeePayer] final fee payer is:'.$fee_payer, '');
                return $fee_payer;
            } else {
                return $default_payer;
            }
        } catch (\Exception $ex) {
            return PaypalConstentInterface::DEFAULT_CI_PAYER_SHOP;
        }
    }

    /**
     *  function for checking the valid dafault payer 
     * @param type $payer_option_array
     * @param type $default_payer
     * @return type
     */
    private function checkDefaultPayer($payer_option_array, $default_payer) {
        try {
            $valid_fee_payer = $this->valid_paypal_payers;
            //check if we have valid value in DEFAULT_PAYER in DB
            if (isset($payer_option_array['DEFAULT_PAYER']) && in_array($payer_option_array['DEFAULT_PAYER'], $valid_fee_payer)) {
                
                $default_payer = $payer_option_array['DEFAULT_PAYER'];
                $this->writeLogs('In class [Paypal/PaypalIntegrationBundle/Services/PaymentTransactionService] function [checkDefaultPayer] Found valid default payer in DB:'.$default_payer, '');
            }
            
            return $default_payer;
        } catch (\Exception $ex) {
            return $default_payer;
        }
    }

    /**
     * function for getting the fee payer based on the shop id
     * @param type $shop_id
     * @param type $fee_payer_data
     * @param type $default_payer
     * @return type
     */
    private function getFeePayerBasedOnShopId($shop_id, $fee_payer_data, $default_payer) {
        try {
            if (isset($fee_payer_data['FEE_PAYER'])) {
                $payer_option = (array)$fee_payer_data['FEE_PAYER'];
                foreach ($payer_option as $pay_option => $shops_ids) {
                    $shops_ids = (array) $shops_ids;
                    if (in_array($shop_id, $shops_ids)) {
                        $this->writeLogs('In class [Paypal/PaypalIntegrationBundle/Services/PaymentTransactionService] function [getFeePayerBasedOnShopId] Shop:'.$shop_id.' found in DB under fee_payer:'.$pay_option, '');
                        $default_payer = $pay_option;
                        break;
                    }
                }
            }
            return $default_payer;
        } catch (\Exception $ex) {
            return $default_payer;
        }
    }
    
    /**
     * function for checking if the json is valid or not
     * @param type $payers_option
     */
    private function checkValidJson($payers_option) {
        try {
            $result = json_decode($payers_option);
            if (json_last_error() === JSON_ERROR_NONE) {
                return true;
            }
            return false;
        } catch (\Exception $ex) {
            return false;
        }
    }
    
    /**
     * function for getting the waiver option in the proper format
     * @param type $fee_type
     * @param type $type
     * @return boolean
     */
    private function getFormatForFeePayer($fee_type, $type) {
        try {
            $em = $this->em;
            //get waiver object for the fee type and item type
            $waiver_object = $em->getRepository('CardManagementBundle:WaiverOptions')
                    ->findOneBy(array('waiverType' => $fee_type, 'itemType' => $type, 'status' => 1)); //for finding the rules for the fee_type
            //check if there is any records for the given parameters 
            $this->writeLogs('Data from the database in the class [Paypal/PaypalIntegrationBundle/Services/PaymentTransactionService] function [getFormatForFeePayer] is:'. json_encode(print_r($waiver_object,TRUE)), '');
            if (count($waiver_object) > 0) {
                $payers_option = $waiver_object->getOptions();
                $json_check = $this->checkValidJson($payers_option);
                if (!$json_check) {
                    $this->writeLogs('In class [Paypal/PaypalIntegrationBundle/Services/PaymentTransactionService] function [getFormatForFeePayer] Invalid json stored in DB', '');
                    return false;
                }
                return $payers_option;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return fasle;
        }
    }
    
    
    /**
     * write logs for IPN notification
     * @param string $request
     * @param string $response
     * @return boolean
     */
    public function writeLogs($request, $response) {
        //die("mw");
        $handler = $this->container->get('monolog.logger.paypal_transaction_fee_payer_logs');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        try {
            $applane_service->writeAllLogs($handler, $request, $response);
        } catch (\Exception $ex) {
            
        }
        return true;
    }

}
