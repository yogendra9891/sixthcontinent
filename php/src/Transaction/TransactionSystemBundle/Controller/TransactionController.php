<?php

namespace Transaction\TransactionSystemBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Transaction\TransactionSystemBundle\Entity\Transaction;
use Transaction\TransactionSystemBundle\Entity\BookTransaction;
use Transaction\TransactionSystemBundle\Entity\TransactionType;
use Transaction\WalletBundle\Entity\ShoppingCard;
use Transaction\WalletBundle\Entity\Card;
Use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;
use Transaction\CitizenIncomeBundle\Controller\RedistributionController;

class TransactionController extends Controller {
    protected $card_percentage = 10;
    protected $pay_key = 'pay_key';
    protected $status = 'status';
    protected $id = 'id';
    protected $ipn_notification_url = 'webapi/ipncallbackresponse';
    protected $chained_payment_fee_payer = 'CHAINED_PAYMENT_FEE_PAYER';
    protected $ci_return_fee_payer = 'CI_RETURN_FEE_PAYER';
    protected $item_type_shop = 'SHOP';
    protected $miss_param = '';
    protected $store_media_path = '/uploads/documents/stores/gallery/';
    protected $profile_image_path = '/uploads/users/media/thumb/';
    private $_CIMaxUsageInitPrice = 50;

    /**
     * Get Initilized Transaction On Business App and Web
     * @param Request $request
     */
    public function getinitilizedtransactionsAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        $TrManager = $this->get('transaction_manager');

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        /* check required parameters */
        $object_info = (object) $de_serialize;
        $data = array();
        $required_parameter = array('status', 'shop_id');

        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
            echo json_encode($resp);
            exit();
        }

        $em = $this->getDoctrine()->getManager();

        $InitData = $em->getRepository('TransactionSystemBundle:BookTransaction')
                ->getInitTransactions(array('status' => 0, 'seller_id' => $de_serialize['shop_id']));

        if ($InitData) {
            foreach ($InitData as $key => $val) {
                $buyer_id = $val->getbuyerId();

                /* Get Store Detail */
                $store_detail = $em
                        ->getRepository('StoreManagerStoreBundle:Store')
                        ->findBy(array('id' => $val->getsellerId()));
                $store_detail = $store_detail[0];

                /* Get Store Images */
                $store_data = $this->getstoreimages($store_detail);

                /* Get Buyer Detail */
                $buyer_data = $em
                        ->getRepository('UserManagerSonataUserBundle:User')
                        ->findBy(array('id' => $buyer_id));
                $buyer_data = $buyer_data[0];
                $buyerProfilePic = (!empty($buyer_data->getProfileImagename())) ? $this->getS3BaseUri() . $this->profile_image_path . $buyer_id . '/' . $buyer_data->getProfileImagename() : '';

                /* Response Data */
                $dateObj = $val->gettimeInitH();
                $responseData[] = array(
                    'booking_id' => $val->getId(),
                    'status' => ($val->getstatus() == 0) ? 'Initiated' : $de_serialize['status'],
                    'date' => date('h:i A d M Y', strtotime($dateObj->format('Y-m-d H:i:s'))),
                    'date_format' => date('h:i A d M Y', strtotime($dateObj->format('Y-m-d H:i:s'))),
                    'seller_id' => $val->getsellerId(),
                    'store_id' => $val->getsellerId(),
                    'do_transaction' => ($val->getWithCredit() == 1) ? 'with_credit' : 'without_credit',
                    'store_data' => array(
                        'id' => $store_detail->getId(),
                        'name' => $store_detail->getbusinessName(),
                        'description' => $store_detail->getdescription(),
                        'store_image' => $store_data
                    ),
                    'user_data' => array(
                        'id' => $buyer_data->getId(),
                        'firstname' => $buyer_data->getfirstname(),
                        'lastname' => $buyer_data->getlastname(),
                        'profile_pic' => $buyerProfilePic
                    )
                );
                unset($store_data);
                unset($buyer_data);
            }

            if ($responseData) {
                $data = array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $responseData));
                echo json_encode($data);
            }
        } else {
            $data = array('code' => 1029, 'message' => 'FAILURE');
            echo json_encode($data);
        }
        exit();
    }

    /**
     * Get Initilized Booking Detail On Business App and Web
     * @param Request $request
     */
    public function getinitbookingdetailAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        $TrManager = $this->get('transaction_manager');

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        /* check required parameters */
        $object_info = (object) $de_serialize;
        $data = array();
        $required_parameter = array('booking_id');

        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
            echo json_encode($resp);
            exit();
        }

        /* Get Booking Data */
        $em = $this->getDoctrine()->getManager();
        $BookingData = $em->getRepository('TransactionSystemBundle:BookTransaction')
                ->findBy(array('id' => $de_serialize['booking_id']));

        if ($BookingData) {
            $BookingData = $BookingData[0];

            /* Get Transaction Detail */
            if (!empty($BookingData->gettransactionId())) {
                $TrDetail = $em->getRepository('TransactionSystemBundle:Transaction')
                        ->find($BookingData->gettransactionId());

                if(!empty($TrDetail)) {
                    $actual_init_price = $TrDetail->getinitPrice() / 100;
                    $actual_final_price = $TrDetail->getinitPrice() / 100;

                    /* Get Transaction Type Information */
                    $TrType = $em->getRepository('TransactionSystemBundle:TransactionType')
                            ->findBy(array('id' => $TrDetail->gettransactionTypeId()));
                    
                    /* Check for comment rating */
                    $dm = $this->get('doctrine.odm.mongodb.document_manager');
                    $userId = $TrDetail->getbuyerId();
                    $shopId = $TrDetail->getsellerId();
                    $transactionId = $TrDetail->getId();

                    $transaction_comment = $dm->getRepository('PaymentPaymentProcessBundle:TransactionComment')
                            ->findBy(array('user_id' => (int)$userId, 'transaction_id' => (string)$transactionId, 'shop_id' => (int)$shopId));
                }
            }

            /* Get Store Detail */
            $store_detail = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findBy(array('id' => $BookingData->getsellerId()));
            $store_detail = $store_detail[0];

            /* Get Store Images */
            $buyer_id = $BookingData->getbuyerId();
            $store_data = $this->getstoreimages($store_detail);

            /* Get Buyer Detail */
            $buyer_data = $em
                    ->getRepository('UserManagerSonataUserBundle:User')
                    ->findBy(array('id' => $buyer_id));
            $buyer_data = $buyer_data[0];
            $buyerProfilePic = (!empty($buyer_data->getProfileImagename())) ? $this->getS3BaseUri() . $this->profile_image_path . $buyer_id . '/' . $buyer_data->getProfileImagename() : '';

            if ($BookingData->getstatus() == '0') {
                $bookingStatusLabel = 'Initiated';
            } elseif ($BookingData->getstatus() == '1') {
                $bookingStatusLabel = 'Canceled';
            } elseif ($BookingData->getstatus() == '2') {
                $bookingStatusLabel = 'Approved';
            }

            if (!empty($TrDetail)) {
                /* Get citizen wallet data */
                $walletData = $em->getRepository('WalletBundle:WalletCitizen')
                        ->getWalletData($buyer_id);

                $TrnsDetail = array(
                    'id' => $TrDetail->getId(),
                    'status' => $TrDetail->getstatus(),
                    'sixc_transaction_id' => $TrDetail->getsixcTransactionid(),
                    'seller_id' => $TrDetail->getsellerId(),
                    'buyer_currency' => $TrDetail->getbuyerCurrency(),
                    'seller_currency' => $TrDetail->getsellerCurrency(),
                    'b_over_s_currency_ration' => $TrDetail->getbOverSCurrencyRation(),
                    'init_price' => $TrDetail->getinitPrice(),
                    'final_price' => $TrDetail->getfinalPrice(),
                    'with_credit' => $TrDetail->getwithCredit(),
                    'discount_used' => $TrDetail->getdiscountUsed(),
                    'citizen_income_used' => $TrDetail->getcitizenincomeUsed(),
                    'time_init_h' => $TrDetail->gettimeInitH(),
                    'time_update_status_h' => $TrDetail->gettimeUpdateStatusH(),
                    'time_close_h' => $TrDetail->gettimeCloseH(),
                    'time_init' => $TrDetail->gettimeInit(),
                    'time_update_status' => $TrDetail->gettimeUpdateStatus(),
                    'time_close' => $TrDetail->gettimeClose(),
                    'buyer_id' => $TrDetail->getbuyerId(),
                    'transaction_fee' => $TrDetail->gettransactionFee(),
                    'sixc_amount_pc' => $TrDetail->getsixcAmountPc(),
                    'sixc_amount_pc_vat' => $TrDetail->getSixcAmountPCVat(),
                    'seller_pc' => $TrDetail->getsellerPc(),
                    'transaction_type_id' => $TrDetail->gettransactionTypeId(),
                    'redistribution_status' => $TrDetail->getredistributionStatus(),
                    'citizen_aff_charge' => $TrDetail->getcitizenAffCharge(),
                    'shop_aff_charge' => $TrDetail->getshopAffCharge(),
                    'friends_follower_charge' => $TrDetail->getfriendsFollowerCharge(),
                    'buyer_charge' => $TrDetail->getbuyerCharge(),
                    'sixc_charge' => $TrDetail->getsixcCharge(),
                    'all_country_charge' => $TrDetail->getallCountryCharge(),
                    'actual_init_price' => $actual_init_price,
                    'actual_final_price' => $actual_final_price
                );
                $shoppingCardBal = $this->getShoppingCardBalance(array('buyer_id' => $TrDetail->getbuyerId(), 'seller_id' => $TrDetail->getsellerId()));
                $amntData = array(
                    'total_amount' => number_format($TrDetail->getinitPrice() / 100, 2, '.', ''),
                    'coupon_used' => (!empty($TrDetail->getcouponUsed())) ? number_format($TrDetail->getcouponUsed() / 100, 2, '.', '') : '0.00',
                    'credit_payment' => (!empty($TrDetail->getcreditPayment())) ? number_format($TrDetail->getcreditPayment() / 100, 2, '.', '') : '0.00',
                    'discount' => (!empty($TrDetail->getdiscountUsed())) ? number_format($TrDetail->getdiscountUsed() / 100, 2, '.', '') : '0.00',
                    'after_discount' => (!empty($TrDetail->getdiscountUsed())) ? number_format(($TrDetail->getinitPrice() - $TrDetail->getdiscountUsed()) / 100, 2) : number_format($TrDetail->getinitPrice() / 100, 2, '.', ''),
                    'shopping_card_used' => (!empty($TrDetail->getshoppingCardUsed())) ? number_format($TrDetail->getshoppingCardUsed() / 100, 2, '.', '') : '0.00',
                    'cash_payment' => number_format($TrDetail->getfinalPrice() / 100, 2, '.', ''),
                    'shopping_card_balance' => ($shoppingCardBal > 0) ? number_format($shoppingCardBal, 2, '.', '') : '0.00'
                );
                $TrnsDetail['transaction_amount_data'] = $amntData;
            }
            
            if(!empty($TrDetail)) {
                if($TrDetail->getstatus() == 'PENDING' && $bookingStatusLabel == 'Approved') {
                    $statusLabel = 'Pending';
                } else {
                    $statusLabel = $bookingStatusLabel;
                }
            } else {
                $statusLabel = $bookingStatusLabel;
            }
            
            /* Response Data */
            $dateObj = $BookingData->gettimeInitH();
            $responseData = array(
                'booking_id' => $BookingData->getId(),
                'currency' => $TrManager->getBuyerCurrency($buyer_id),
                'currency_symbol' => $TrManager->getCurrencyCode($TrManager->getBuyerCurrency($buyer_id)),
                'status' => ($BookingData->getstatus() == '0') ? 'Initiated' : $BookingData->getstatus(),
                'status_label' => $statusLabel,
                'date' => $dateObj->format('Y-m-d H:i:s'),
                'date_format' => date('h:i A d M Y', strtotime($dateObj->format('Y-m-d H:i:s'))),
                'buyer_id' => $buyer_id,
                'seller_id' => $BookingData->getsellerId(),
                'store_id' => $BookingData->getsellerId(),
                'do_transaction' => ($BookingData->getWithCredit() == 1) ? 'with_credit' : 'without_credit',
                'txn_rating_by_customer' => (!empty($transaction_comment)) ? $transaction_comment[0]->getrating() : 0,
                'transaction_data_status' => (!empty($TrDetail)) ? 'True' : 'False',
                'transaction_data' => (!empty($TrDetail)) ? $TrnsDetail : '',
                'store_data' => array(
                    'id' => $store_detail->getId(),
                    'name' => $store_detail->getbusinessName(),
                    'description' => $store_detail->getdescription(),
                    'store_image' => $store_data
                ),
                'user_data' => array(
                    'id' => $buyer_data->getid(),
                    'firstname' => $buyer_data->getfirstname(),
                    'lastname' => $buyer_data->getlastname(),
                    'profile_pic' => $buyerProfilePic
                )
            );

            if ($responseData) {
                $data = array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $responseData));
                echo json_encode($data, JSON_UNESCAPED_UNICODE);
            } else {
                $data = array('code' => 1029, 'message' => 'FAILURE');
                echo json_encode($data);
            }
        } else {
            $data = array('code' => 1029, 'message' => 'FAILURE', 'response' => array('result' => 'MISSING_BOOKING_ID'));
            echo json_encode($data);
        }
        exit();
    }

    /**
     * Cancle Initilized Transaction On Business App and Web
     * @param Request $request
     */
    public function canceltransactionAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        $TrManager = $this->get('transaction_manager');

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        /* check required parameters */
        $object_info = (object) $de_serialize;
        $data = array();
        $required_parameter = array('booking_id', 'status');

        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
            echo json_encode($resp);
            exit();
        }

        /* Check for booking */
        $em = $this->getDoctrine()->getManager();
        $checkBooking = $em->getRepository('TransactionSystemBundle:BookTransaction')
                ->findBy(array('id' => $de_serialize['booking_id']));

        if (!empty($checkBooking)) {
            $checkBooking = $checkBooking[0];

            if ($checkBooking->getstatus() != '1') {
                /* Update Booking Status */
                $time = date('Y-m-d H:i:s');
                $timestamp = strtotime(date('Y-m-d H:i:s'));

                unset($de_serialize['status']);
                $de_serialize['status'] = 1;
                $de_serialize['time_update_status_h'] = $time;
                $de_serialize['time_update_status'] = $timestamp;

                $updateBooking = $em->getRepository('TransactionSystemBundle:BookTransaction')
                        ->cancleBooking($de_serialize);

                /* Update WalletCitizen */
                if ($checkBooking->getwithCredit() == 1) {
                    $updateWallet = $em->getRepository('WalletBundle:WalletCitizen')
                            ->updateWalletCitizenWritingStatus(array('buyer_id' => $checkBooking->getbuyerId(), 'writing_status' => 0));
                }

                if ($updateBooking) {
                    $responseData = array(
                        'booking_id' => $de_serialize['booking_id'],
                        'status' => ($de_serialize['status'] == 1) ? 'Canceled' : ''
                    );

                    $data = array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $responseData));
                    echo json_encode($data);
                } else {
                    echo json_encode(array('code' => 1029, 'message' => 'ERROR', 'response' => array('result' => 'PLEASE_TRY_AGAIN')));
                }
            } else {
                $data = array('code' => 1029, 'message' => 'FAILURE', 'response' => array('result' => 'ALREADY_CANCELED'));
                echo json_encode($data);
            }
        } else {
            $data = array('code' => 1029, 'message' => 'FAILURE', 'response' => array('result' => 'INVALID_BOOKING_ID'));
            echo json_encode($data);
        }
        exit();
    }

    /**
     * Process Transaction With Credit OR Without Credit
     * @param Request $request
     */
    public function processTransactionAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        $TrManager = $this->get('transaction_manager');

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        /* check required parameters */
        $object_info = (object) $de_serialize;
        $data = array();
        
        if($de_serialize['do_transaction'] == 'paypal_once') {
            $required_parameter = array('buyer_id', 'seller_id', 'do_transaction', 'offer_id', 'cancel_url', 'return_url');
        } else {
            $required_parameter = array('booking_id', 'buyer_id', 'seller_id', 'do_transaction', 'status', 'amount');
        }
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
            echo json_encode($resp);
            exit();
        }
        
        /* Process this step if this transaction is not for shopping card purchase */
        if($de_serialize['do_transaction'] != 'paypal_once') {
            if ($de_serialize['status'] != 'INIT') {
                echo json_encode(array('code' => 1029, 'message' => 'FAILURE', 'response' => array('result' => 'ALREADY_CANCELED_TRANSACTION')));
                exit();
            }

            if ($de_serialize['amount'] < 1) {
                echo json_encode(array('code' => 1029, 'message' => 'FAILURE', 'response' => array('result' => 'TRANSACTION_AMOUNT_MUST_BE_GREATER_THAN_ONE')));
                exit();
            }
        }

        /*
         * Process transaction 
         * Type = with_credit OR without_credit OR SHOPPING_CARD_PURCHASE
         */
        $amntData = '';
        if ($de_serialize['do_transaction'] == 'with_credit') {
            $amntData = $this->creditcalculation($de_serialize);
            $responseData = $this->getProcessTransactionResponse($de_serialize, $amntData);
        } elseif ($de_serialize['do_transaction'] == 'without_credit') {
            $responseData = $this->getProcessTransactionResponse($de_serialize, $amntData);
        } elseif ($de_serialize['do_transaction'] == 'paypal_once') {
            $responseData = $this->shoppingCardPurchase($de_serialize);
        }

        /* Return Response Data */
        if ($responseData) {
            echo json_encode(array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $responseData)), JSON_UNESCAPED_UNICODE);
        } else {
            $data = array('code' => 1029, 'message' => 'FAILURE');
            echo json_encode($data);
        }
        exit();
    }
    
    /*
     * Generate response for transaction with or without credit
     */
    public function getProcessTransactionResponse($responseObj, $calcData = '') {
        $de_serialize = $responseObj;
        $em = $this->get('doctrine')->getEntityManager();

        $TrManager = $this->get('transaction_manager');
        $buyer_id = $de_serialize['buyer_id'];

        /* Get Store Detail */
        $store_detail = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findBy(array('id' => $de_serialize['seller_id']));
        $store_detail = $store_detail[0];

        /* Get Store Images */
        $store_data = $this->getstoreimages($store_detail);

        /* Get Buyer Detail */
        $buyer_data = $em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->findBy(array('id' => $buyer_id));
        $buyer_data = $buyer_data[0];
        $buyerProfilePic = (!empty($buyer_data->getProfileImagename())) ? $this->getS3BaseUri() . $this->profile_image_path . $buyer_id . '/' . $buyer_data->getProfileImagename() : '';
        
        /* Update transaction preference on entity */
        $TransactionId = $em->getRepository('TransactionSystemBundle:Transaction')
                                    ->getProcessTransaction($de_serialize, $calcData);
        
        if ($TransactionId) {
            /* Get Transaction Detail */
            $TrDetail = $em->getRepository('TransactionSystemBundle:Transaction')
                    ->findBy(array('id' => $TransactionId));
            $TrDetail = $TrDetail[0];

            $actual_init_price = $TrDetail->getinitPrice() / 100;
            $actual_final_price = $TrDetail->getinitPrice() / 100;

            if ($TrDetail->getstatus() == 'INIT') {
                $statusLabel = 'Initiated';
            } elseif ($TrDetail->getstatus() == 'PENDING') {
                $statusLabel = 'Pending';
            } elseif ($TrDetail->getstatus() == 'CANCELED') {
                $statusLabel = 'Canceled';
            } elseif ($TrDetail->getstatus() == 'COMPLETED') {
                $statusLabel = 'Completed';
            } elseif ($TrDetail->getstatus() == 'DENIED') {
                $statusLabel = 'Denied';
            } elseif ($TrDetail->getstatus() == 'APPROVED') {
                $statusLabel = 'Approved';
            }

            if (!empty($TrDetail)) {
                $TrnsDetail = array(
                    'id' => $TrDetail->getId(),
                    'status' => $TrDetail->getstatus(),
                    'sixc_transaction_id' => $TrDetail->getsixcTransactionid(),
                    'seller_id' => $TrDetail->getsellerId(),
                    'buyer_currency' => $TrDetail->getbuyerCurrency(),
                    'seller_currency' => $TrDetail->getsellerCurrency(),
                    'b_over_s_currency_ration' => $TrDetail->getbOverSCurrencyRation(),
                    'init_price' => $TrDetail->getinitPrice(),
                    'final_price' => $TrDetail->getfinalPrice(),
                    'with_credit' => $TrDetail->getwithCredit(),
                    'discount_used' => $TrDetail->getdiscountUsed(),
                    'citizen_income_used' => $TrDetail->getcitizenincomeUsed(),
                    'time_init_h' => $TrDetail->gettimeInitH(),
                    'time_update_status_h' => $TrDetail->gettimeUpdateStatusH(),
                    'time_close_h' => $TrDetail->gettimeCloseH(),
                    'time_init' => $TrDetail->gettimeInit(),
                    'time_update_status' => $TrDetail->gettimeUpdateStatus(),
                    'time_close' => $TrDetail->gettimeClose(),
                    'buyer_id' => $TrDetail->getbuyerId(),
                    'transaction_fee' => $TrDetail->gettransactionFee(),
                    'sixc_amount_pc' => $TrDetail->getsixcAmountPc(),
                    'sixc_amount_pc_vat' => $TrDetail->getSixcAmountPCVat(),
                    'seller_pc' => $TrDetail->getsellerPc(),
                    'transaction_type_id' => $TrDetail->gettransactionTypeId(),
                    'redistribution_status' => $TrDetail->getredistributionStatus(),
                    'citizen_aff_charge' => $TrDetail->getcitizenAffCharge(),
                    'shop_aff_charge' => $TrDetail->getshopAffCharge(),
                    'friends_follower_charge' => $TrDetail->getfriendsFollowerCharge(),
                    'buyer_charge' => $TrDetail->getbuyerCharge(),
                    'sixc_charge' => $TrDetail->getsixcCharge(),
                    'all_country_charge' => $TrDetail->getallCountryCharge(),
                    'actual_init_price' => $actual_init_price,
                    'actual_final_price' => $actual_final_price
                );

                $shoppingCardBal = $this->getShoppingCardBalance(array('buyer_id' => $TrDetail->getbuyerId(), 'seller_id' => $TrDetail->getsellerId()));
                if ($de_serialize['do_transaction'] == 'with_credit') {
                    $walletData = $em->getRepository('WalletBundle:WalletCitizen')
                            ->getWalletData($de_serialize['buyer_id']);
                    $amntData = array(
                        'total_amount' => number_format($TrDetail->getinitPrice() / 100, 2, '.', ''),
                        'coupon_used' => ($calcData['coupon_used'] > 0) ? number_format($TrManager->getOrigPrice($calcData['coupon_used']), 2, '.', '') : '0.00',
                        'credit_payment' => ($calcData['credit_position_used'] > 0) ? number_format($TrManager->getOrigPrice($calcData['credit_position_used']), 2, '.', '') : '0.00',
                        'discount' => ($calcData['discount'] > 0) ? number_format($TrManager->getOrigPrice($calcData['discount']), 2, '.', '') : '0.00',
                        'after_discount' => ($calcData['discount'] > 0) ? number_format($TrManager->getOrigPrice($TrDetail->getinitPrice() - $calcData['discount']), 2, '.', '') : number_format($TrManager->getOrigPrice($TrDetail->getinitPrice()), 2, '.', ''),
                        'shopping_card_used' => ($calcData['card_used'] > 0) ? number_format($TrManager->getOrigPrice($calcData['card_used']), 2, '.', '') : '0.00',
                        'cash_payment' => ($calcData['cashpayment'] > 0) ? number_format($TrManager->getOrigPrice($calcData['cashpayment']), 2, '.', '') : '0.00',
                        'shopping_card_balance' => ($shoppingCardBal > 0) ? number_format(($shoppingCardBal), 2, '.', '') : '0.00'
                    );
                } else {
                    $amntData = array(
                        'total_amount' => number_format($TrDetail->getfinalPrice() / 100, 2, '.', ''),
                        'coupon_used' => '0.00',
                        'credit_payment' => '0.00',
                        'discount' => '0.00',
                        'after_discount' => number_format($TrDetail->getfinalPrice() / 100, 2, '.', ''),
                        'shopping_card_used' => '0.00',
                        'cash_payment' => number_format($TrDetail->getfinalPrice() / 100, 2, '.', ''),
                        'shopping_card_balance' => '0.00'
                    );
                }
                $TrnsDetail['transaction_amount_data'] = $amntData;
            }

            $dateObj = $TrDetail->gettimeInitH();

            /* Response Data */
            $responseData = array(
                'transaction_id' => $TrDetail->getId(),
                'sixc_transaction_id' => $TrDetail->getsixcTransactionId(),
                'currency' => $TrManager->getBuyerCurrency($de_serialize['buyer_id']),
                'currency_symbol' => $TrManager->getCurrencyCode($TrManager->getBuyerCurrency($de_serialize['buyer_id'])),
                'booking_id' => (!empty($de_serialize['booking_id'])) ? $de_serialize['booking_id'] : NULL,
                'status' => $TrDetail->getstatus(),
                'status_label' => $statusLabel,
                'date' => $dateObj->format('Y-m-d H:i:s'),
                'date_format' => date('h:i A d M Y', strtotime($dateObj->format('Y-m-d H:i:s'))),
                'buyer_id' => $TrDetail->getbuyerId(),
                'seller_id' => $TrDetail->getsellerId(),
                'store_id' => $TrDetail->getsellerId(),
                'do_transaction' => $de_serialize['do_transaction'],
                'transaction_data' => $TrnsDetail,
                'store_data' => array(
                    'id' => $store_detail->getId(),
                    'name' => $store_detail->getbusinessName(),
                    'description' => $store_detail->getdescription(),
                    'store_image' => $store_data
                ),
                'buyer_data' => array(
                    'id' => $buyer_data->getId(),
                    'firstname' => $buyer_data->getfirstname(),
                    'lastname' => $buyer_data->getlastname(),
                    'profile_pic' => $buyerProfilePic
                )
            );
            return $responseData;
        }
    }

    public function creditcalculation($de_serialize) {
        $TrManager = $this->get('transaction_manager');

        $amount = $de_serialize['amount'] * 100;
        $em = $this->getDoctrine()->getManager();

        /* Get Citizen Wallet Information */
        $CitizenWalletData = $em->getRepository('WalletBundle:WalletCitizen')
                ->getWalletData($de_serialize['buyer_id']);

        $postObj = array(
            'wallet_citizen_id' => (!empty($CitizenWalletData)) ? $CitizenWalletData[0]->getId() : '',
            'buyer_id' => $de_serialize['buyer_id'],
            'seller_id' => $de_serialize['seller_id']
        );

        //First Step Init Price is always equal to Cash Payment
        $initAmount = $cashPayment = $amount;
        $amount_used = 0;
        $returnData = array();
        $returnData['init_amount'] = $initAmount;
        $returnData['coupon_used'] = 0;
        $returnData['shopping_card_used'] = 0;
        $returnData['credit_position_used'] = 0;
        $returnData['old_card_used'] = 0;
        $returnData['citizen_income_used'] = 0;
        $returnData['usable_citizen_income'] = 0;
        $returnData['transaction_serialize'] = 0;
        $coupon_usage = array();
        $credit_position_usage = array();
        $old_cards_usage = array();
        $shopping_card_usage = array();
        $shopping_card_usage_arr = array();
        $citizen_income_usage = array();
        $new_card_usage = array();
        $new_credit_position_data = array();

        //Start All credits usage= array();
        //1 Coupon
        $couponData = $em->getRepository('WalletBundle:Coupon')
                ->getUsageCredits($postObj, $returnData);

        if (!empty($couponData)) {
            $max_usage_init_price = $couponData['maxUsageInitPrice'];
            $available_amount = $couponData['availableAmount'];

            $coupon_usage = $this->useCreditManager($initAmount, $cashPayment, $max_usage_init_price, $available_amount);

            $cashPayment = $coupon_usage['cashpayment'];
            //Use only for the updated of credit method
            $amount_used = $coupon_usage['amount_used'];
            $returnData['coupon_used'] +=$amount_used;
        }

        //2 Credit Position
        $creditPositionData = $em->getRepository('WalletBundle:CreditPosition')
                ->getUsageCredits($postObj, $returnData);

        if (!empty($creditPositionData)) {
            $max_usage_init_price = $creditPositionData['maxUsageInitPrice'];
            $available_amount = $creditPositionData['available_amount'];

            $credit_position_usage = $this->useCreditManager($initAmount, $cashPayment, $max_usage_init_price, $available_amount);
            $cashPayment = $credit_position_usage['cashpayment'];
            $amount_used = $credit_position_usage['amount_used'];
            $returnData['credit_position_used'] +=$amount_used;
        }

        //Old Cards
        $cardData = $em->getRepository('WalletBundle:Card')
                ->getUsageCredits($postObj, $returnData);

        if (!empty($cardData)) {
            $max_usage_init_price = $cardData['maxUsageInitPrice'];
            $available_amount = $cardData['availableAmount'];

            $old_cards_usage = $this->useCreditManager($initAmount, $cashPayment, $max_usage_init_price, $available_amount);
            $cashPayment = $old_cards_usage['cashpayment'];
            $amount_used = $old_cards_usage['amount_used'];
            $returnData['old_card_used'] += $amount_used;
        }

        //Shopping Card
        $shoppingCardData = $em->getRepository('WalletBundle:ShoppingCard')
                ->getUsageCredits($postObj, $returnData);

        if (!empty($shoppingCardData)) {
            foreach ($shoppingCardData as $val) {
                $max_usage_init_price = $val['maxUsageInitPrice'];
                $available_amount = $val['availableAmount'];

                $shopping_card_usage = $this->useCreditManager($initAmount, $cashPayment, $max_usage_init_price, $available_amount);
                $shopping_card_usage_arr[] = array('id' => $val['id'], 'available_amount' => $available_amount, 'used_data' => $shopping_card_usage);
                $cashPayment = $shopping_card_usage['cashpayment'];
                $amount_used = $shopping_card_usage['amount_used'];
                $returnData['shopping_card_used'] += $amount_used;

                //I have not completly used my  $available_amount  card because is greater than the cash payment to pay
                if ($available_amount != $amount_used) {
                    break;
                }
            }
        } 

        //Citizen Income
        $walletData = $em->getRepository('WalletBundle:WalletCitizen')
                ->getUsageCredits($postObj, $returnData);

        if (!empty($walletData)) {
            $max_usage_init_price = $walletData['maxUsageInitPrice'];
            $available_amount = $walletData['availableAmount'];

            $citizen_income_usage = $this->useCreditManager($initAmount, $cashPayment, $max_usage_init_price, $available_amount);

            $cashPayment = $citizen_income_usage['cashpayment'];
            $amount_used = $citizen_income_usage['amount_used'];
            $returnData['usable_citizen_income'] = $available_amount;
            $returnData['citizen_income_used'] += $amount_used;
        }

        $returnData['cashpayment'] = $cashPayment;
        $returnData['discount'] = $returnData['coupon_used'] + $returnData['credit_position_used'];
        $returnData['card_used'] = $returnData['old_card_used'] + $returnData['shopping_card_used'] + $returnData['citizen_income_used'];

        $seriliazeData = array(
            'coupon_data' => $couponData,
            'coupon_usage' => $coupon_usage,
            'credit_position_data' => $creditPositionData,
            'credit_position_usage' => $credit_position_usage,
            'new_credit_position_data' => $new_credit_position_data,
            'card_data' => $cardData,
            'card_usage' => $old_cards_usage,
            'shopping_card_data' => $shoppingCardData,
            'shopping_card_usage' => $shopping_card_usage_arr,
            'citizen_income_data' => $walletData,
            'citizen_income_usage' => $citizen_income_usage,
            'new_card_usage' => $new_card_usage
        ); 
        $returnData['transaction_serialize'] = $seriliazeData;
        return $returnData;
    }

    public function useCreditManager($initAmount, $cashPayment, $max_usage_init_price, $available_amount) {
        $TrManager = $this->get('transaction_manager');
        $creditObj = array(
            'init_amount' => $initAmount,
            'cashpayment' => $cashPayment,
            'max_usage_init_price' => $max_usage_init_price,
            'available_amount' => $available_amount
        );
        return $TrManager->checkCreditUsage($creditObj);
    }

    /*
     * @param $request
     * update  process transaction CANCELED OR DENIED
     */

    public function updateprocesstransactionAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $TrManager = $this->get('transaction_manager');

        /* check required parameters */
        $object_info = (object) $de_serialize;
        $data = array();
        $required_parameter = array('booking_id', 'transaction_id', 'status');

        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
            echo json_encode($resp);
            exit();
        }
        $em = $this->get('doctrine')->getEntityManager();

        if (!empty($de_serialize)) {
            /* Get Transaction Detail */
            $TrDetail = $em->getRepository('TransactionSystemBundle:Transaction')
                    ->findBy(array('id' => $de_serialize['transaction_id']));
            $TrDetail = $TrDetail[0];

            /* Get citizen wallet data */
            $walletData = $em->getRepository('WalletBundle:WalletCitizen')
                    ->getWalletData($TrDetail->getbuyerId());

            if (!empty($TrDetail)) {
                $time = date('Y-m-d H:i:s');
                $timestamp = strtotime(date('Y-m-d H:i:s'));

                /* Update Transaction */
                $updateData = array(
                    'transaction_id' => $de_serialize['transaction_id'],
                    'status' => $de_serialize['status'],
                    'time_update_status_h' => $time,
                    'time_close_h' => $time,
                    'time_update_status' => $timestamp,
                    'time_close' => $timestamp
                );
                $updateRes = $em->getRepository('TransactionSystemBundle:Transaction')
                        ->updateProcessTransaction($updateData);

                /* Update wallet writing status */
                if ($TrDetail->getwithCredit() == 1) {
                    /* Update WalletCitizen */
                    $updateWallet = $em->getRepository('WalletBundle:WalletCitizen')
                            ->updateWalletCitizenWritingStatus(array('buyer_id' => $TrDetail->getbuyerId(), 'writing_status' => 0));
                }

                /* Update credits and citizen wallet for transaction with credits */
                if ($TrDetail->getwithCredit() == 1 && $de_serialize['status'] == 'COMPLETED') {
                    $this->updatecreditsafterconfirmation($de_serialize, $TrDetail);
                }

                if ($updateRes) {
                    $buyer_id = $TrDetail->getbuyerId();
                    $seller_id = $TrDetail->getsellerId();

                    /* Get booking detail */
                    $BookingData = $em->getRepository('TransactionSystemBundle:BookTransaction')
                            ->findBy(array('id' => $de_serialize['booking_id']));
                    $BookingData = $BookingData[0];

                    /* Get Store Detail */
                    $store_detail = $em
                            ->getRepository('StoreManagerStoreBundle:Store')
                            ->findBy(array('id' => $TrDetail->getsellerId()));
                    $store_detail = $store_detail[0];

                    /* Get Store Images */
                    $store_data = $this->getstoreimages($store_detail);
                    
                    /* Get Buyer Detail */
                    $buyer_data = $em
                            ->getRepository('UserManagerSonataUserBundle:User')
                            ->findBy(array('id' => $TrDetail->getbuyerId()));
                    $buyer_data = $buyer_data[0];
                    $buyerProfilePic = (!empty($buyer_data->getProfileImagename())) ? $this->getS3BaseUri() . $this->profile_image_path . $buyer_id . '/' . $buyer_data->getProfileImagename() : '';

                    /* Get updated transaction detail */
                    $TransactionDetail = $em->getRepository('TransactionSystemBundle:Transaction')
                            ->findBy(array('id' => $de_serialize['transaction_id']));
                    $TransactionDetail = $TransactionDetail[0];

                    if ($de_serialize['status'] == 'INIT') {
                        $statusLabel = 'Initiated';
                    } elseif ($de_serialize['status'] == 'PENDING') {
                        $statusLabel = 'Pending';
                    } elseif ($de_serialize['status'] == 'CANCELED') {
                        $statusLabel = 'Canceled';
                    } elseif ($de_serialize['status'] == 'COMPLETED') {
                        $statusLabel = 'Approved';
                    } elseif ($de_serialize['status'] == 'DENIED') {
                        $statusLabel = 'Denied';
                    } elseif ($de_serialize['status'] == 'APPROVED') {
                        $statusLabel = 'Approved';
                    }

                    if (!empty($TransactionDetail)) {
                        $actual_init_price = $TransactionDetail->getinitPrice() / 100;
                        $actual_final_price = $TransactionDetail->getinitPrice() / 100;

                        $TrnsDetail = array(
                            'id' => $TransactionDetail->getId(),
                            'status' => $de_serialize['status'],
                            'sixc_transaction_id' => $TransactionDetail->getsixcTransactionid(),
                            'seller_id' => $TransactionDetail->getsellerId(),
                            'buyer_currency' => $TransactionDetail->getbuyerCurrency(),
                            'seller_currency' => $TransactionDetail->getsellerCurrency(),
                            'b_over_s_currency_ration' => $TransactionDetail->getbOverSCurrencyRation(),
                            'init_price' => $TransactionDetail->getinitPrice(),
                            'final_price' => $TransactionDetail->getfinalPrice(),
                            'with_credit' => $TransactionDetail->getwithCredit(),
                            'discount_used' => $TransactionDetail->getdiscountUsed(),
                            'citizen_income_used' => $TransactionDetail->getcitizenincomeUsed(),
                            'time_init_h' => $TransactionDetail->gettimeInitH(),
                            'time_update_status_h' => $TransactionDetail->gettimeUpdateStatusH(),
                            'time_close_h' => $TransactionDetail->gettimeCloseH(),
                            'time_init' => $TransactionDetail->gettimeInit(),
                            'time_update_status' => $TransactionDetail->gettimeUpdateStatus(),
                            'time_close' => $TransactionDetail->gettimeClose(),
                            'buyer_id' => $TransactionDetail->getbuyerId(),
                            'transaction_fee' => $TransactionDetail->gettransactionFee(),
                            'sixc_amount_pc' => $TransactionDetail->getsixcAmountPc(),
                            'sixc_amount_pc_vat' => $TransactionDetail->getSixcAmountPCVat(),
                            'seller_pc' => $TransactionDetail->getsellerPc(),
                            'transaction_type_id' => $TransactionDetail->gettransactionTypeId(),
                            'redistribution_status' => $TransactionDetail->getredistributionStatus(),
                            'citizen_aff_charge' => $TransactionDetail->getcitizenAffCharge(),
                            'shop_aff_charge' => $TransactionDetail->getshopAffCharge(),
                            'friends_follower_charge' => $TransactionDetail->getfriendsFollowerCharge(),
                            'buyer_charge' => $TransactionDetail->getbuyerCharge(),
                            'sixc_charge' => $TransactionDetail->getsixcCharge(),
                            'all_country_charge' => $TransactionDetail->getallCountryCharge(),
                            'actual_init_price' => $actual_init_price,
                            'actual_final_price' => $actual_final_price
                        );

                        $shoppingCardBal = $this->getShoppingCardBalance(array('buyer_id' => $TransactionDetail->getbuyerId(), 'seller_id' => $TransactionDetail->getsellerId()));
                        $amntData = array(
                            'total_amount' => number_format($TransactionDetail->getinitPrice() / 100, 2, '.', ''),
                            'coupon_used' => (!empty($TransactionDetail->getcouponUsed())) ? number_format($TransactionDetail->getcouponUsed() / 100, 2, '.', '') : '0.00',
                            'credit_payment' => (!empty($TransactionDetail->getcreditPayment())) ? number_format($TransactionDetail->getcreditPayment() / 100, 2, '.', '') : '0.00',
                            'discount' => (!empty($TransactionDetail->getdiscountUsed())) ? number_format($TransactionDetail->getdiscountUsed() / 100, 2, '.', '') : '0.00',
                            'after_discount' => (!empty($TransactionDetail->getdiscountUsed())) ? number_format(($TransactionDetail->getinitPrice() - $TransactionDetail->getdiscountUsed()) / 100, 2, '.', '') : number_format($TransactionDetail->getinitPrice() / 100, 2, '.', ''),
                            'shopping_card_used' => (!empty($TransactionDetail->getshoppingCardUsed())) ? number_format($TransactionDetail->getshoppingCardUsed() / 100, 2, '.', '') : '0.00',
                            'cash_payment' => number_format($TransactionDetail->getfinalPrice() / 100, 2, '.', ''),
                            'shopping_card_balance' => ($shoppingCardBal > 0) ? number_format($shoppingCardBal, 2, '.', '') : '0.00'
                        );
                        $TrnsDetail['transaction_amount_data'] = $amntData;
                    }

                    $dateObj = $TransactionDetail->gettimeInitH();

                    /* Response Data */
                    $responseData = array(
                        'transaction_id' => $TransactionDetail->getId(),
                        'sixc_transaction_id' => $TransactionDetail->getsixcTransactionId(),
                        'booking_id' => $de_serialize['booking_id'],
                        'currency' => $TrManager->getBuyerCurrency($buyer_id),
                        'currency_symbol' => $TrManager->getCurrencyCode($TrManager->getBuyerCurrency($buyer_id)),
                        'status' => $de_serialize['status'],
                        'status_label' => $statusLabel,
                        'date' => $dateObj->format('Y-m-d H:i:s'),
                        'date_format' => date('h:i A d M Y', strtotime($dateObj->format('Y-m-d H:i:s'))),
                        'buyer_id' => $TransactionDetail->getbuyerId(),
                        'seller_id' => $TransactionDetail->getsellerId(),
                        'store_id' => $TransactionDetail->getsellerId(),
                        'do_transaction' => ($TransactionDetail->getWithCredit() == 1) ? 'with_credit' : 'without_credit',
                        'transaction_data' => $TrnsDetail,
                        'store_data' => array(
                            'id' => $store_detail->getId(),
                            'name' => $store_detail->getbusinessName(),
                            'description' => $store_detail->getdescription(),
                            'store_image' => $store_data
                        ),
                        'buyer_data' => array(
                            'id' => $buyer_data->getId(),
                            'firstname' => $buyer_data->getfirstname(),
                            'lastname' => $buyer_data->getlastname(),
                            'profile_pic' => $buyerProfilePic
                        )
                    );
                    
                    /* Process CI Redistribution and update total revenue of seller after confirmation of transaction */
                    if($de_serialize['status'] == 'COMPLETED') {
                        /* Update Total Revenue of seller */
                        $revenue = $TransactionDetail->getfinalPrice() + $TransactionDetail->getcitizenIncomeUsed();
                        $businessData = array(
                            'revenue' => $revenue,
                            'seller_id' => $TransactionDetail->getsellerId()
                        );
                        $em ->getRepository('WalletBundle:WalletBusiness')
                                ->updateSellerTotalRevenue($businessData);
                        
                        /* Process CI Redistribution */
                        $distributionObj = array(
                            'seller_id' =>$TransactionDetail->getsellerId(),
                            'transaction_id' => $TransactionDetail->getId(),
                            'transaction_type_id' => $TransactionDetail->gettransactionTypeId(),
                            'time_close' => $timestamp,
                            'currency' => $TransactionDetail->getbuyerCurrency()
                        ); 
                        $this->processCIRedistribution($distributionObj);
                        
                        /* Process Notification */
                        $storeOwner = $em
                                                    ->getRepository('TransactionSystemBundle:Transaction')
                                                    ->getStoreOwner(array('store_id' => $TransactionDetail->getsellerId()));
                     
                        $notificationArr = array(
                            'from_id' => $storeOwner,
                            'to_id' => $TransactionDetail->getbuyerId(),
                            'message_type' => 'TXN',
                            'message_code' => 'TXN_SHOP_APPROVE',
                            'item_id' => $de_serialize['booking_id'],
                            'info' => array(
                                '_id' => $TransactionDetail->getsixcTransactionId(),
                                'txn_id' => $de_serialize['booking_id'],
                                'store_owner_id' => $storeOwner,
                                'store_id' => $TransactionDetail->getsellerId(),
                                'txn_date' => $dateObj->format('Y-m-d'),
                                'citizen_id' => $TransactionDetail->getbuyerId()
                            )
                        ); 
                        $TrManager->sendNotification($notificationArr);
                    }
                    
                    $data = array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $responseData));
                    echo json_encode($data, JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(array('code' => 1029, 'message' => 'FAILURE', 'response' => array('result' => 'PLEASE_TRY_AGAIN')));
                }
            } else {
                echo json_encode(array('code' => 1029, 'message' => 'FAILURE', 'response' => array('result' => 'INVALID_TRANSACTION')));
            }
        } else {
            $data = array('code' => 1029, 'message' => 'FAILURE', 'response' => array('result' => 'PARAMETERS_MISSING'));
            echo json_encode($data);
        }
        exit();
    }
    
    /*
     * Process CI Redistribution after confirmation of PAY_IN_SHOP transaction
     * @param $distributionObj
     */
    public function processCIRedistribution($distributionObj) { 
        $validTransType = array('1', '4', '5');
        if(in_array($distributionObj['transaction_type_id'], $validTransType)) {
            $sellerId  =  $distributionObj['seller_id'];
            $id_transaction = $distributionObj['transaction_id'];
            $time_close = $distributionObj['time_close'];
            $currency = $distributionObj['currency'];
            
            $recurring_service =  $this->container->get('recurring_shop.payment');
            $recurring_service->paySingleRecurrinTransaction($sellerId , $id_transaction ,$time_close, $currency); 
        }
    }
    
    
    /*
     * Process CI Redistribution after confirmation of PAYPAL_ONCE transaction
     * @param $distributionObj
     */
    public function processPaypalCIRedistribution($distributionObj) { 
        $validTransType = array('1', '4', '5');
        if(in_array($distributionObj['transaction_type_id'], $validTransType)) {
            $sellerId  =  $distributionObj['seller_id'];
            $id_transaction = $distributionObj['transaction_id'];
            $time_close = $distributionObj['time_close'];
            $currency = $distributionObj['currency'];
            $redistribution_ci  = $this->container->get('redistribution_ci');
            $transactionGatewayReference  = "ALREADYPAID";
            $redistribution_ci->updateSuccessRecurring($sellerId, $id_transaction, $time_close , $transactionGatewayReference , false);
        }
    }
    
    /*
     * Update credits after confirmation of transaction
     */
    public function updatecreditsafterconfirmation($de_serialize, $TrDetail) { 
        $em = $this->get('doctrine')->getEntityManager();
        /* Initiliaze Variables */
        $coupon_used = 0;
        $shopping_card_used = 0;
        $old_card_used = 0;
        $credit_position_used = 0;
        $TrSerializeData = unserialize($TrDetail->gettransactionSerialize()); 
        $TrSerializeData['new_credit_position_data'] = array();
        $TrSerializeData['new_card_usage'] = array();

        /* Update Coupon Info */
        if (!empty($TrSerializeData['coupon_usage']) && !empty($TrSerializeData['coupon_data'])) {
            $coupon_available = $TrSerializeData['coupon_data']['availableAmount'];
            $coupon_used = $TrSerializeData['coupon_usage']['amount_used'];
            $updateCoupon = $em->getRepository('WalletBundle:Coupon')
                    ->updateCredits(array('id' => $TrSerializeData['coupon_data']['id'], 'available_amount' => $coupon_available, 'amount_used' => $coupon_used));
        }

        /* Update Credit position Info */
        if (!empty($TrSerializeData['credit_position_usage']) && !empty($TrSerializeData['credit_position_data'])) {
            $credit_position_available = $TrSerializeData['credit_position_data']['creditPositionAvailable'];
            $credit_position_used = $TrSerializeData['credit_position_usage']['amount_used'];
            $updateCreditPosition = $em->getRepository('WalletBundle:CreditPosition')
                    ->updateCredits(array('wallet_citizen_id' => $TrSerializeData['credit_position_data']['id'], 'buyer_id' => $TrDetail->getbuyerId(), 'seller_id' => $TrDetail->getsellerId(), 'available_amount' => $credit_position_available, 'amount_used' => $credit_position_used));

            if($updateCreditPosition) {
                 $TrSerializeData['new_credit_position_data'] = $updateCreditPosition;
            }
        }

        /* Update Old Cards Info */
        if (!empty($TrSerializeData['card_usage']) && !empty($TrSerializeData['card_data'])) {
            $card_available = $TrSerializeData['card_data']['availableAmount'];
            $old_card_used = $TrSerializeData['card_usage']['amount_used'];
            $updateCard = $em->getRepository('WalletBundle:Card')
                    ->updateCredits(array('id' => $TrSerializeData['card_data']['id'], 'available_amount' => $card_available, 'amount_used' => $old_card_used));
        }

        /* Update shopping card data */
        if (!empty($TrSerializeData['shopping_card_data']) && !empty($TrSerializeData['shopping_card_usage'])) {
            foreach ($TrSerializeData['shopping_card_usage'] as $val) {
                $avl_amount = $val['available_amount'];
                $shopping_card_used = $val['used_data']['amount_used'];
                $updateCard = $em->getRepository('WalletBundle:ShoppingCard')
                        ->updateCredits(array('id' => $val['id'], 'available_amount' => $avl_amount, 'amount_used' => $shopping_card_used));
            }
        }

        /* Update citizen income usage info */
        if (!empty($TrSerializeData['citizen_income_usage']) && !empty($TrSerializeData['citizen_income_data'])) {
            $ci_avl = $TrSerializeData['citizen_income_data']['availableAmount'];
            $ci_used = $TrSerializeData['citizen_income_usage']['amount_used'];
            $updateCitizenIncome = $em->getRepository('WalletBundle:WalletCitizen')
                    ->updateCredits(array('wallet_citizen_id' => $TrSerializeData['citizen_income_data']['id'], 'buyer_id' => $TrDetail->getbuyerId(), 'seller_id' => $TrDetail->getsellerId(), 'available_amount' => $ci_avl, 'amount_used' => $ci_used));

            /* Add new generated card infor in transaction serialize string */
            if($updateCitizenIncome) 
            {
                $TrSerializeData['new_card_usage'] = $updateCitizenIncome;
            }
        }

        /* Update transaction serialize string */
        $serializeData = array(
            'transaction_id' => $de_serialize['transaction_id'],
            'transaction_serialize' => serialize($TrSerializeData)
        );
        $updateRes = $em->getRepository('TransactionSystemBundle:Transaction')
                                     ->updateTransactionSerializeString($serializeData);

        /* Update Wallet Information */
        $walletUpdateObj = array(
            'buyer_id' => $TrDetail->getbuyerId(),
            'coupon_available' => $coupon_used,
            'shopping_card_available' => $shopping_card_used,
            'card_available' => $old_card_used,
            'credit_position_available' => $credit_position_used
        );

        $walletUpdate = $em->getRepository('WalletBundle:WalletCitizen')
                ->updateCitizenWallet($walletUpdateObj);
    }
    
    /*
     * Get store images
     */
    public function getstoreimages($store_detail) {
        if(!empty($store_detail)) {
            $em = $this->get('doctrine')->getEntityManager();
            $store_id = $store_detail->getId();
            $current_store_profile_image_id = $store_detail->getstoreImage();
            $store_profile_image_path = '';
            $store_profile_image_thumb_path = '';
            $store_profile_image_cover_thumb_path = '';
            $x = '';
            $y = '';
            if (!empty($current_store_profile_image_id)) {
                $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                        ->find($current_store_profile_image_id);
                if ($store_profile_image) {
                    $album_id = $store_profile_image->getalbumId();
                    $image_name = $store_profile_image->getimageName();
                    $x = $store_profile_image->getX();
                    $y = $store_profile_image->getY();
                    if (!empty($album_id)) {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $album_id . '/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $image_name;
                        $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/coverphoto/' . $album_id . '/' . $image_name;
                    } else {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $image_name;
                        $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/coverphoto/' . $image_name;
                        // $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb_cover_crop/' . $image_name;
                    }
                } else {
                    $store_profile_image_thumb_path = $store_profile_images[$store_id]['thumb_image'];
                    $store_profile_image_path = $store_profile_images[$store_id]['original_image'];
                    $store_profile_image_cover_thumb_path = $store_profile_images[$store_id]['original_image'];
                }
            } else {
                if (isset($store_profile_images[$store_id]['thumb_image']) && $store_profile_images[$store_id]['thumb_image'] != null) {
                    $store_profile_image_thumb_path = $store_profile_images[$store_id]['thumb_image'];
                    $store_profile_image_path = $store_profile_images[$store_id]['original_image'];
                    $store_profile_image_cover_thumb_path = $store_profile_images[$store_id]['original_image'];
                }
            }
            $store_data = array(
                'profile_image_original' => $store_profile_image_path,
                'profile_image_thumb' => $store_profile_image_thumb_path,
                'cover_image_path' => $store_profile_image_cover_thumb_path
             );
            return $store_data;
        }
    }
    
    public function getShoppingCardBalance($de_serialize) {
        $em = $this->getDoctrine()->getManager();
        $TrManager = $this->get('transaction_manager');
        /* Get citizen wallet data */
        $citizenWalletData = $em->getRepository('WalletBundle:WalletCitizen')
                ->getWalletData($de_serialize['buyer_id']);
        $postData = array(
            'wallet_citizen_id' => (!empty($citizenWalletData)) ? $citizenWalletData[0]->getId() : '',
            'buyer_id' => $de_serialize['buyer_id'],
            'seller_id' => $de_serialize['seller_id']
        );
        /* Get card data */
        $cardData = $em->getRepository('WalletBundle:Card')
                ->getCitizenSellerCard($postData);

        $shoppingCardData = $em->getRepository('WalletBundle:ShoppingCard')
                ->getCitizenSellerShoppingCard($postData);

        if (!empty($shoppingCardData)) {
            $shoppingCardBal = $shoppingCardData[0]['totalBalance'];
        } else {
            $shoppingCardBal = 0;
        }

        if (!empty($cardData)) {
            $cardBalance = $cardData[0]['totalCitizenCards'] + $shoppingCardBal;
        } else {
            $cardBalance = $shoppingCardBal;
        }
        return $TrManager->getOrigPrice($cardBalance);
    }

    /*
     * Get total economy shifted in sixthContinent
     */

    public function totaleconomyshiftedAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $TrManager = $this->get('transaction_manager');

        /* check required parameters */
        $object_info = (object) $de_serialize;
        $data = array();
        $required_parameter = array('idcard');

        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
            echo json_encode($resp);
            exit();
        }

        $em = $this->get('doctrine')->getEntityManager();
        $shiftedEcom = $em->getRepository('TransactionSystemBundle:EconomyShifted')
                ->getShiftedEconomy();

        $todayEcom = $em->getRepository('TransactionSystemBundle:Transaction')
                ->shiftedTodayEconomy();

         $todayEcom = $todayEcom[0]['fp'] + $todayEcom[0]['ci'];      

        if (!empty($shiftedEcom) || !empty($todayEcom)) {
         
            $totEc = (!empty($shiftedEcom[0]['totalEconomyAmount'])) ? $shiftedEcom[0]['totalEconomyAmount'] : '0';
            $todEc = (!empty($todayEcom)) ? $todayEcom : '0';
            $totalEconomy = ($totEc + $todEc)*(1.1) / 100;
            $totalShifted = number_format($totalEconomy, 2, '.',"");

            echo json_encode(array('code' => 101, 'message' => 'SUCCESS', 'data' => array('stato' => 0, 'descrizione' => '', 'economstot' => $totalShifted)));
        } else {
            echo json_encode(array('code' => 1029, 'message' => 'FAILURE'));
        }
        exit();
    }
    
    /*
     * Get transaction history in business app
     */
    public function getbusinesstransactionhistoryAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $TrManager = $this->get('transaction_manager');

        /* check required parameters */
        $object_info = (object) $de_serialize;
        $data = array();
        $required_parameter = array('seller_id');

        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
            echo json_encode($resp);
            exit();
        }

        $em = $this->get('doctrine')->getEntityManager();
        /* Get transaction history*/
        if(!empty($de_serialize['limit'])) {
            $limit = $de_serialize['limit'];
        } else {
            $limit = 10;
        }
        
         if(!empty($de_serialize['skip'])) {
            $skip = $de_serialize['skip'];
        } else {
            $skip = 0;
        }
        
        $TransactionHistory = array();
        $TrInfo = array();
        $TotalHistory = array();
        $TotalHistory['hasNext'] = false;
        $TransactionHistory = $em->getRepository('TransactionSystemBundle:BookTransaction')
                                                ->getBusinessTransactionHistory(array('seller_id' => $de_serialize['seller_id'], 'limit' => $limit, 'skip' => $skip));
        
        $TotalHistory = $em->getRepository('TransactionSystemBundle:BookTransaction')
                                       ->getTotalBusinessTransactionHistory(array('seller_id' => $de_serialize['seller_id'], 'limit' => $limit, 'skip' => $skip));
       
        if(!empty($TransactionHistory)) {
            foreach($TransactionHistory as $val) {
                /* Get transaction info */
                if(!empty($val->gettransactionId())) {
                    $TrInfo = $em->getRepository('TransactionSystemBundle:Transaction')
                                         ->find($val->gettransactionId());
                }
                
                $store_detail = $em
                                            ->getRepository('StoreManagerStoreBundle:Store')
                                            ->find($val->getsellerId());

                /* Get Store Images */
                $buyer_id = $val->getbuyerId();
                $store_data = $this->getstoreimages($store_detail);

                /* Get Buyer Detail */
                $buyer_data = $em
                        ->getRepository('UserManagerSonataUserBundle:User')
                        ->find($buyer_id);
                $buyerProfilePic = (!empty($buyer_data->getProfileImagename())) ? $this->getS3BaseUri() . $this->profile_image_path . $buyer_id . '/' . $buyer_data->getProfileImagename() : '';
                
                if ($val->getstatus() == '0') {
                    $statusLabel = 'Initiated';
                } elseif ($val->getstatus() == '1') {
                    $statusLabel = 'Canceled';
                } elseif ($val->getstatus() == '2') {
                    $statusLabel = 'Approved';
                }
            
                $dateObj = $val->gettimeInitH();
                $transaction_data_response[] = array(
                    'transaction_id' => (!empty($TrInfo)) ? $TrInfo->getsixcTransactionId() : '',
                    'booking_id' => $val->getId(),
                    'amount' => (!empty($TrInfo)) ? number_format($TrInfo->getinitPrice()/100, 2, '.', '') : '0.00',
                    'status' => $statusLabel,
                    'is_calculated' => ($val->getstatus() == 0) ? 1 : 0,
                    'user_id'=> $val->getbuyerId(),
                    'shop_id'=> $val->getsellerId(),
                    'date' => $val->gettimeInitH(),
                    'date_format' => date('h:i A d M Y', strtotime($dateObj->format('Y-m-d H:i:s'))),
                    'store_info' => array(
                        'id' => $store_detail->getId(),
                        'name' => $store_detail->getbusinessName(),
                        'description' => $store_detail->getdescription(),
                        'store_image' => $store_data
                    ),
                    'user_info' => array(
                        'id' => $buyer_data->getid(),
                        'firstname' => $buyer_data->getfirstname(),
                        'lastname' => $buyer_data->getlastname(),
                        'profile_pic' => $buyerProfilePic
                    )
                );
            }
            echo json_encode(array('code' => 100, 'message' => 'SUCCESS', 'data' => $transaction_data_response, 'dataInfo' => array('count' => $TotalHistory['totalRecords'], 'hasNext' => $TotalHistory['hasNext'])));
        } else {
            echo json_encode(array('code' => 1029, 'message' => 'FAILURE'));
        }
        exit();
    }
    
     /*
     * Shopping card purchase transaction
     * @param request obj $de_serialize
     */
    public function shoppingCardPurchase($de_serialize) {
        $em = $this->getDoctrine()->getManager();
        $TrManager = $this->get('transaction_manager');
        
        /* Get commercial Promotion information */
        $CpInfo = $em->getRepository('CommercialPromotionBundle:CommercialPromotion')
                               ->find($de_serialize['offer_id']);
        
        /* Checking for valid commercial promotion data */
        if(empty($CpInfo)) {
            echo json_encode(array('code' => 1029, 'message' => 'FAILURE', 'response' => array('data' => 'INVALID_OFFER_ID')));
            exit();
        }
        $commercialPromotion = array(
            'price' => $CpInfo->getprice(),
            'discountAmount' => $CpInfo->getdiscountAmount()
        );
        $CpTypeId = $CpInfo->getcommercialPromotionTypeId();
        $TrInfo = $em->getRepository('CommercialPromotionBundle:CommercialPromotion')
                              ->getPriceForMe($CPromotion = null, $wallet = null, $de_serialize['offer_id'], $de_serialize['buyer_id'], $CpTypeId, $commercialPromotion);
     
        $de_serialize['amount'] = $TrInfo['init_amount'];
        $de_serialize['discount_value'] = $TrInfo['discount_value'];
        $de_serialize['cashpayment'] = $TrInfo['cashpayment'];
        $de_serialize['sixthcontinent_contribution'] = $TrInfo['sixthcontinent_contribution'];
        $this->buyShoppingcard($de_serialize);
    }
    
    /**
     * buy 100% shopping cards.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * 
     */
    public function buyShoppingcard($de_serialize) {
        //initialise the array
        $data = array();
        
        $shop_id = $de_serialize['seller_id'];
        $user_id = $de_serialize['buyer_id'];
        $offer_id = $de_serialize['offer_id'];
        
         $object_info = (object) $de_serialize; //convert an array into object.
        //get doctring object
        $em = $this->getDoctrine()->getManager();
        $this->writePaypalLogs('Request data: ' . $this->toJson($object_info));
        //check the store is exist
        $store_info = $em->getRepository('StoreManagerStoreBundle:Store')
                ->find($shop_id);
        if (!$store_info) {
            $this->writePaypalLogs('Store does not exists shop_id:' . $shop_id);
            $data = array('code' => 1055, 'message' => 'SHOP_DOES_NOT_EXISTS', 'data' => array());
            $this->returnResponse($data);
        }
        if ($store_info->getShopStatus() == 0) { //if shop is blocked..
            $this->writePaypalLogs('Store is blocked shop_id:' . $shop_id);
            $data = array('code' => 1105, 'message' => 'SHOP_IS_BLOCKED', 'data' => array());
            $this->returnResponse($data);            
        }
        
        //find paypal account for shop
        $shop_paypal_info = $em->getRepository('PaypalIntegrationBundle:ShopPaypalInformation')
                ->findOneBy(array("shopId" => $shop_id, "status" => 'VERIFIED', 'isDefault' => 1));

        if (!$shop_paypal_info) {
            $this->writePaypalLogs('Store Paypal account does not exists shop_id:' . $shop_id);
            $data = array('code' => 1058, 'message' => 'SHOP_PAYPAL_DOES_NOT_EXISTS', 'data' => array());
            $this->returnResponse($data);
        }
        
        $paypal_id = $shop_paypal_info->getAccountId();
        $shop_paypal_email = $shop_paypal_info->getEmailId();
        //get parameters from the parameter.yml file
        $mode = $this->container->getParameter('paypal_mode');
        if ($mode == 'sandbox') {
            $paypal_authorize_url = $this->container->getParameter('paypal_authorize_url_sandbox');
            $paypal_sixthcontinent_email = $this->container->getParameter('paypal_sixthcontinent_email_sandbox');
        } else {
            $paypal_authorize_url = $this->container->getParameter('paypal_authorize_url_live');
            $paypal_sixthcontinent_email = $this->container->getParameter('paypal_sixthcontinent_email_live');
        }
        
        /* Initliize offer id in transaction_serialize for future updation */
        $de_serialize['transaction_serialize'] = array(
            'offer_id' => $offer_id
        );
        $transaction_system_data = $this->getProcessTransactionResponse($de_serialize);
        
        /* Reduce wallet available credits */
        $updateArr = array(
            'buyer_id' => $de_serialize['buyer_id'],
            'ci_used' => $de_serialize['sixthcontinent_contribution']
        );
        $updateWallet = $em->getRepository('WalletBundle:WalletCitizen')
                                         ->reduceWalletCitizenIncome($updateArr);
            
        if ($transaction_system_data['sixc_transaction_id'] == '') { //if transaction is not initiated in transaction system
            $this->writePaypalLogs('Transaction is not initiated on transaction system for shopId: ' . $shop_id.' offerId: '.$offer_id. ' and userId: '.$user_id);
            $data = array('code' => $transaction_system_data['code'], 'message' => $transaction_system_data['message'], 'data' => array());
            $this->returnResponse($data);
        }
        $calculated_data = $this->calculateShoppingCardAmount($de_serialize);
        $primary_user_amount  = $calculated_data['primary_user_amount'];
        $secondry_user_amount = $calculated_data['secondry_user_amount'];
        $calculated_data['ci_used'] = $calculated_data['ci_used'];
        $transaction_data = array(
                            'transaction_id' => $transaction_system_data['transaction_id'], 
                            'primary_user_paypal_email' => $shop_paypal_email,
                            'primary_user_amount' => $primary_user_amount,
                            'secondry_user_paypal_email' => $paypal_sixthcontinent_email, 
                            'secondry_user_amount' => $secondry_user_amount, 
                            'transaction_inner_id' => $transaction_system_data['sixc_transaction_id'],
                            'paypal_id' => $paypal_id
                      );
        
        $transaction_query = '?transaction_id=' . $transaction_data['transaction_id'] . '&shop_id=' . $shop_id;
        $cancel_url = urlencode($object_info->cancel_url . $transaction_query);
        $return_url = urlencode($object_info->return_url . $transaction_query);

        $paypal_response = $this->getPaypalResponse($transaction_data, $shop_id, $user_id, $cancel_url, $return_url);
        if (isset($paypal_response->responseEnvelope)) {
            if ($paypal_response->responseEnvelope->ack == 'Success') {
                $pay_key = $paypal_response->payKey;
                $reurn_data = array('link' => $paypal_authorize_url . $pay_key, 'cancel_url' => $cancel_url, 'return_url' => $return_url);
                $this->savePaymentTransactionRecord($paypal_response, $transaction_data);
                $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $reurn_data);
            } else {
                $this->returnCItoCitizen($updateArr);
                $data = array('code' => 1029, 'message' => 'FAILURE', 'data' => array());
            }
        } else {
            $this->returnCItoCitizen($updateArr);
            $data = array('code' => 1029, 'message' => 'FAILURE', 'data' => array());
        }
        $this->returnResponse($data);
    }
    
    /*
     * Return Wallet CI
     * @param $data
     */
    public function returnCItoCitizen($data) {
        $em = $this->getDoctrine()->getManager();
        $updateArr = array(
            'buyer_id' => $data['buyer_id'],
            'ci_used' => $data['ci_used']
        );
        $updateWallet = $em->getRepository('WalletBundle:WalletCitizen')
                                         ->returnWalletCitizenIncome($updateArr);
    }
    
    /**
     * save the data into payment transaction log
     * @param obj $paypal_response
     */
    public function savePaymentTransactionRecord($paypal_response, $transaction_data) {
        $transaction_data['payment_status'] = 'PENDING';
        $em = $this->getDoctrine()->getManager();
        $addData = $em->getRepository('TransactionSystemBundle:TransactionPaymentInformation')
                ->addtransactionRecord($paypal_response, $transaction_data);
        return true;
    }
    
     /**
     * paypal response and update the status
     * @param string $transaction_data
     * @param int $shop_id
     * @param int $citizen_id
     */
    public function getPaypalResponse($transaction_data, $shop_id, $citizen_id, $cancel_url, $return_url) {
        $result = array();
        //get parameters from the parameter.yml file
        $mode = $this->container->getParameter('paypal_mode');
        if ($mode == 'sandbox') {
            $paypal_acct_username = $this->container->getParameter('paypal_acct_username_sandbox');
            $paypal_acct_password = $this->container->getParameter('paypal_acct_password_sandbox');
            $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_sandbox');
            $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_sandbox');
            $paypal_end_point = $this->container->getParameter('paypal_end_point_sandbox');
            $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_sandbox');
            $paypal_acct_app_id = $this->container->getParameter('paypal_acct_appid_sandbox');
        } else {
            $paypal_acct_username = $this->container->getParameter('paypal_acct_username_live');
            $paypal_acct_password = $this->container->getParameter('paypal_acct_password_live');
            $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_live');
            $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_live');
            $paypal_end_point = $this->container->getParameter('paypal_end_point_live');
            $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_live');
            $paypal_acct_app_id = $this->container->getParameter('paypal_acct_appid_live');
        }
        $primary_reciever_paypal_email = $transaction_data['primary_user_paypal_email'];
        $primary_reciever_amount = $transaction_data['primary_user_amount'];
        $secondry_reciever_email = $transaction_data['secondry_user_paypal_email'];
        $secondry_reciever_amount = $transaction_data['secondry_user_amount'];
        $curreny_code = $this->container->getParameter('paypal_currency');
        $paypal_transaction_service = $this->container->get('paypal_integration.paypal_transaction_check');
        $paypal_service = $this->container->get('paypal_integration.payment_transaction');
        $type = $this->chained_payment_fee_payer;
        $item_type = $this->item_type_shop;
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        $feesPayerParam = '&feesPayer=';
        $final_fee_payer = $feesPayerParam.$fee_payer;
        $ipn_notification_url = urlencode($this->container->getParameter('symfony_base_url').$this->ipn_notification_url); //ipn notification url
       
        $headers = array(
            'X-PAYPAL-SECURITY-USERID: ' . $paypal_acct_username,
            'X-PAYPAL-SECURITY-PASSWORD: ' . $paypal_acct_password,
            'X-PAYPAL-SECURITY-SIGNATURE: ' . $paypal_acct_signature,
            'X-PAYPAL-REQUEST-DATA-FORMAT: NV',
            'X-PAYPAL-RESPONSE-DATA-FORMAT: JSON',
            'X-PAYPAL-APPLICATION-ID: ' . $paypal_acct_appid,
        );
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $payload = "actionType=PAY&ipnNotificationUrl=$ipn_notification_url&cancelUrl=$cancel_url&clientDetails.applicationId=$paypal_acct_app_id"
                . "&clientDetails.ipAddress=$ipAddress&currencyCode=$curreny_code" .
                "&receiverList.receiver(0).amount=$primary_reciever_amount&receiverList.receiver(0).email=$primary_reciever_paypal_email" .
                "&receiverList.receiver(0).primary=true&receiverList.receiver(1).amount=$secondry_reciever_amount" .
                "&receiverList.receiver(1).email=$secondry_reciever_email" .
                "&receiverList.receiver(1).primary=false" .
                "&requestEnvelope.errorLanguage=en_US" .
                "&returnUrl=$return_url".$final_fee_payer;
        $options = array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => false,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true
        );

        try {
            $curl = curl_init($paypal_end_point);
            if (!$curl) {
                throw new \Exception('Could not initialize curl');
            }
            if (!curl_setopt_array($curl, $options)) {
                throw new \Exception('Curl error:' . curl_error($curl));
            }
            $result = curl_exec($curl);
            if (!$result) {
                throw new \Exception('Curl error:' . curl_error($curl));
            }
            curl_close($curl);
            return json_decode($result);
        } catch (\Exception $e) {
            //$e->getMessage();
        }
    }
    
    /**
     * catch the ipn response for a transaction
     */
    public function ipncallbackresponseAction() {
        sleep(120);
        //get object of the transaction check service
        $paypal_transaction_service = $this->container->get('paypal_integration.paypal_transaction_check');
        // Instead, read raw POST data from the input stream. 
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);

        $myPost = array();
        $paypal_status = $pay_key = '';
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2)
                $myPost[$keyval[0]] = urldecode($keyval[1]);
        }

        // read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
        $req = 'cmd=_notify-validate';
        if (function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;       
        }       
        foreach ($myPost as $key => $value) { //prepare the query string for paypal ipn notiification validation.
            if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req .= "&$key=$value";
            if ($this->convertString($key) == $this->convertString($this->status)) { //get transaction status
                $paypal_status = $value;
            }
            if ($this->convertString($key) == $this->convertString($this->id)) { //get transaction status
                $paypal_transaction_id = $value;
            }
            if ($this->convertString($key) == $this->convertString($this->pay_key)) { //get pay key
                $pay_key = $value;
            }
        }
        //write logs for post data from ipn paypal
        $post_data = urldecode($req);

        $response = $this->verifyrequest($req); //verify it on ipn paypal again it a valid request from  ipn.
        $response = $this->convertString($response);
        $paypal_response = array();
        if (strcmp($response, "VERIFIED") == 0) {
            // The IPN is verified, process it
        } else if (strcmp($response, "INVALID") == 0) {
            // IPN invalid, log for manual investigation
            exit('INVALID respons from IPN');
        } else {
            exit('Unknown response from IPN');
        }

        $status = $this->convertString($paypal_status); //paypal ipn status convert to string
        //get doctring object
        $em = $this->getDoctrine()->getManager();
        if($pay_key) {
            /* get transaction payment information */
            $TrPayInfo = $em->getRepository('TransactionSystemBundle:TransactionPaymentInformation')
                                        ->getTransactionDetailBypayKey(array('pay_key' => $pay_key));
            
            /* Get transaction information */
            $TrInfo = $em->getRepository('TransactionSystemBundle:Transaction')
                                  ->find($TrPayInfo->gettransactionId());
            
            /* Update Payment Information */
            $updateArr = array(
                'transaction_id' => (!empty($TrPayInfo)) ? $TrPayInfo->gettransactionId() : '',
                'pay_key' => $pay_key,
                'status' => $paypal_status
            );
            $em->getRepository('TransactionSystemBundle:TransactionPaymentInformation')
                   ->updateTransactionFromIPN($updateArr);
            
             /* Update transaction entity */
            if($paypal_status == 'COMPLETED') {
                $em->getRepository('TransactionSystemBundle:Transaction')
                                               ->updateTransactionData($updateArr);
            }
            
            /* Return back citizen income if payment is failed */
            if($paypal_status == 'ERROR' || $paypal_status == 'REVERSALERROR') {
                $updateArr = array(
                    'buyer_id' => $TrInfo->getbuyerId(),
                    'ci_used' => $TrInfo->getcitizenIncomeUsed()
                );
                $updateWallet = $em->getRepository('WalletBundle:WalletCitizen')
                                                 ->returnWalletCitizenIncome($updateArr);
            }
        }
        exit('DONE');
    }
    
    /**
     * calculate the amount to be paid by user for purchasing the 100% shopping card
     * @param array $transaction_system_data
     * @return array $result
     */
    public function calculateShoppingCardAmount($de_serialize) {
        $TrManager = $this->get('transaction_manager');
        //get doctrine manager object
        $em = $this->getDoctrine()->getManager();
        $cash_amount  = $TrManager->getOrigPrice($de_serialize['cashpayment']);
        $discount  = $TrManager->getOrigPrice($de_serialize['discount_value']);
        $total_amount = $TrManager->getOrigPrice($de_serialize['amount']);
        $ci_value = $TrManager->getOrigPrice($de_serialize['sixthcontinent_contribution']);
        
        /* Secondary amount calculation */
        $vat = $TrManager->getCountryVat();
        $sellerPc = $em->getRepository('TransactionSystemBundle:Transaction')
                                 ->getSellerPC($de_serialize['seller_id']);
        
        $secondry_user_amount = $sellerPc['sellerPc'] * ($cash_amount + $ci_value) * $vat;
        $result = array(
                    'primary_user_amount' => number_format($cash_amount, 2, '.', ''), 
                    'secondry_user_amount' => number_format($secondry_user_amount, 2, '.', ''),
                    'ci_used' => number_format($ci_value, 2, '.', ''),
                    'discount_used' => number_format($discount, 2, '.', '')
            );
        return $result;
    }
    
    /*
     * Check process transaction
     */
    public function checkbuycardresponseAction(Request $request) {
        //initialise the array
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id', 'shop_id', 'transaction_id', 'type');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //extract variables.
        $user_id = $object_info->session_id;
        $shop_id = $object_info->shop_id;
        $transaction_id = $object_info->transaction_id;
        $TrManager = $this->get('transaction_manager');
        $em = $this->getDoctrine()->getManager();
        
        /* get transaction data */
        $TrData = $em->getRepository('TransactionSystemBundle:TransactionPaymentInformation')
                                ->getTransactionDetail(array('transaction_id' => $transaction_id));
        
        $TransactionData = $em->getRepository('TransactionSystemBundle:Transaction')
                                             ->find($transaction_id);
        
        /* get walletData */
        $WalletData = $em->getRepository('WalletBundle:WalletCitizen')
                                     ->getWalletData($user_id);
        
        $status = '';
        if(!empty($TrData)) {
            if($de_serialize['type'] == 'SUCCESS') {
                $data = $this->checkTransactionStatus($TrData->getpayKey());
                $status = $data->status;
            } else {
                $status = $de_serialize['type'];
            }
            /* Updating record */
            $updateArr = array(
                'transaction_id' => $transaction_id,
                'status' => $status,
                'payment_serialize' => ($de_serialize['type'] == 'SUCCESS') ? serialize($data) : NULL
            );
            $updateData = $em->getRepository('TransactionSystemBundle:TransactionPaymentInformation')
                                           ->updateTransactionData($updateArr);
            
            /* Update transaction data */
            if($status == 'COMPLETED') {
                /* Update Seller Total Revenue */
                $revenue = $TransactionData->getfinalPrice() + $TransactionData->getcitizenIncomeUsed();
                $businessData = array(
                    'revenue' => $revenue,
                    'seller_id' => $TransactionData->getsellerId()
                );
                $em ->getRepository('WalletBundle:WalletBusiness')
                        ->updateSellerTotalRevenue($businessData);
                        
                /* Update transaction data */
                $updateResponse = $em->getRepository('TransactionSystemBundle:Transaction')
                                               ->updateTransactionData($updateArr);
                
                /* Generate card upto50% of CI usage */
                $cardData = array(
                    'wallet_citizen_id' => (isset($WalletData)) ? $WalletData[0]->getId() : '',
                    'buyer_id' => $user_id,
                    'seller_id' => (isset($TransactionData)) ? $TransactionData->getsellerId() : '',
                    'init_price' => (isset($TransactionData)) ? $TransactionData->getcitizenIncomeUsed() : '',
                    'amount_used' => (isset($TransactionData)) ? $TransactionData->getcitizenIncomeUsed() : ''
                );
                $cardId = $this->generateCards($cardData);
                
                 /* Process CI Redistribution after confirmation of transaction */
                $distributionObj = array(
                    'seller_id' => $shop_id,
                    'transaction_id' => $transaction_id,
                    'transaction_type_id' => $TransactionData->gettransactionTypeId(),
                    'time_close' => $updateResponse['time_close'],
                    'currency' => $TransactionData->getbuyerCurrency()
                ); 
                $this->processPaypalCIRedistribution($distributionObj);
            }
            
            if($updateData && $status != 'CANCELED') {
                echo json_encode(array('code' => 100, 'message' => 'SUCCESS', 'data' => 'PAYMENT_PROCESSED'));
            } else {
                echo json_encode(array('code' => 101, 'message' => 'SUCCESS', 'data' => 'PAYMENT_CANCELED'));
            }
        } else {
            echo json_encode(array('code' => 1029, 'message' => 'FAILURE', 'data' => 'INVALID_TRANSACTION_ID'));
        }
        exit();
    }
    
    /*
     * Generate card upto 50%
     * @param
     */
    public function generateCards($data) {
        $returnData = array();
        $TrManager = $this->get('transaction_manager');
        $em = $this->getDoctrine()->getManager();
        $dateTime = new \DateTime('now');
        $Timestamp = strtotime(date('Y-m-d H:i:s'));

        /* Insert new generated card detail */
        $cardId = $TrManager->getTransactionIdToken(10);
        $cardPost = new Card();
        $cardPost->setcardId($cardId);
        $cardPost->setinitAmount($data['init_price']);
        $cardPost->setavailableAmount($data['init_price'] - $data['amount_used']);
        $cardPost->settimeCreatedH($dateTime);
        $cardPost->settimeUpdatedH($dateTime);
        $cardPost->settimeCreated($Timestamp);
        $cardPost->settimeUpdated($Timestamp);
        $cardPost->setcurrency($TrManager->getBuyerCurrency($data['buyer_id']));
        $cardPost->setmaxUsageInitPrice($this->_CIMaxUsageInitPrice);
        $cardPost->setsellerId($data['seller_id']);
        $cardPost->setwalletCitizenId($data['wallet_citizen_id']);

        $em->persist($cardPost);
        $em->flush();
        $em->clear();
        return $cardPost->getId();
    }
    
    /**
     * check the detail if transaction is completed on paypal.
     * @param string $transaction_pay_key
     */
    public function checkTransactionStatus($transaction_pay_key) {
        $result = array();
        //get parameters from the parameter.yml file
        $mode = $this->container->getParameter('paypal_mode');
        if ($mode == 'sandbox') {
            $paypal_acct_username = $this->container->getParameter('paypal_acct_username_sandbox');
            $paypal_acct_password = $this->container->getParameter('paypal_acct_password_sandbox');
            $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_sandbox');
            $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_sandbox');
            $paypal_end_point = $this->container->getParameter('paypal_detail_end_point_sandbox');
            $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_sandbox');
        } else {
            $paypal_acct_username = $this->container->getParameter('paypal_acct_username_live');
            $paypal_acct_password = $this->container->getParameter('paypal_acct_password_live');
            $paypal_acct_signature = $this->container->getParameter('paypal_acct_signature_live');
            $paypal_acct_appid = $this->container->getParameter('paypal_acct_appid_live');
            $paypal_end_point = $this->container->getParameter('paypal_detail_end_point_live');
            $paypal_acct_email_address = $this->container->getParameter('paypal_acct_email_address_live');
        }
        $headers = array(
            'X-PAYPAL-SECURITY-USERID: ' . $paypal_acct_username,
            'X-PAYPAL-SECURITY-PASSWORD: ' . $paypal_acct_password,
            'X-PAYPAL-SECURITY-SIGNATURE: ' . $paypal_acct_signature,
            'X-PAYPAL-REQUEST-DATA-FORMAT: NV',
            'X-PAYPAL-RESPONSE-DATA-FORMAT: JSON',
            'X-PAYPAL-APPLICATION-ID: ' . $paypal_acct_appid,
        );
        $payload = "payKey=" . $transaction_pay_key . "&requestEnvelope.errorLanguage=en_US";
        $options = array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => false,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true
        );
        
        try {
            $curl = curl_init($paypal_end_point);
            if (!$curl) {
                throw new \Exception('Could not initialize curl');
            }
            if (!curl_setopt_array($curl, $options)) {
                throw new \Exception('Curl error:' . curl_error($curl));
            }
            $result = curl_exec($curl);
            if (!$result) {
                throw new \Exception('Curl error:' . curl_error($curl));
            }
            curl_close($curl);
            return json_decode($result);
        } catch (\Exception $e) {
            //$e->getMessage();
        }
    }
    
    /**
     * return the response.
     * @param type $data_array
     */
    private function returnResponse($data_array) {
        echo json_encode($data_array, JSON_NUMERIC_CHECK);
        exit;
    }
    
    /**
     * convert the string into uppercase
     * @param string $string
     * @return string $final_string
     */
    public function convertString($string) {
        $final_string = strtoupper(trim($string));
        return $final_string;
    }

    /**
     * verify the status from paypal.
     * @param string $req
     * @return string $response
     */
    public function verifyrequest($req) {
        //get parameters from the parameter.yml file
        $mode = $this->container->getParameter('paypal_mode');
        if ($mode == 'sandbox') {
            $paypal_notify_end_point = $this->container->getParameter('paypal_notify_verify_end_point_sandbox');
        } else {
            $paypal_notify_end_point = $this->container->getParameter('paypal_notify_verify_end_point_live');
        }
        $this->writeLogs('Request to IPN for check transaction verification URL=> ' . $paypal_notify_end_point . ' AND Query string=> ' . $req, '');
        $res = '';
        $ch = curl_init($paypal_notify_end_point);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
        
        if (!($res = curl_exec($ch))) {
            curl_close($ch);
            exit('curl execution error');
        }
        curl_close($ch);
        return $res;
    }

    /**
     * write logs for IPN notification
     * @param string $request
     * @param string $response
     * @return boolean
     */
    public function writeLogs($request, $response) {
        return true;
    }

    /**
     * write logs paypal shopping
     * @param string $data
     * @return boolean
     */
    public function writePaypalLogs($data) {
        return true;
    }

    /**
     * convert to json
     * @param array/object $data
     */
    private function toJson($data) {
        return json_encode($data);
    }

     /**
     * convert the string into lowercase
     * @param string $string
     * @return string $final_string
     */
    public function convertStringToLower($string) {
        $final_string = strtolower(trim($string));
        return $final_string;
    }   
    
    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
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

    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
        return $full_path;
    }

    /**
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function decodeData($req_obj) {
        $req_obj = is_array($req_obj) ? json_encode($req_obj) : $req_obj;
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * Encode tha data
     * @param string $req_obj
     * @return array
     */
    public function encodeData($req_obj) {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array('json' => new JsonEncoder()));
        $json = $serializer->serialize($req_obj, 'json');
        return $json;
    }

    /**
     * Get Url content
     * @param type $request
     * @return type
     */
    public function getAppData(Request$request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeData($content);

        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
}