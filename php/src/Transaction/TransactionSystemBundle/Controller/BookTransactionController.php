<?php
namespace Transaction\TransactionSystemBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Transaction\TransactionSystemBundle\Entity\BookTransaction;
use Transaction\TransactionSystemBundle\Entity\TransactionType;
Use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;

class BookTransactionController extends Controller
{   
    protected $store_media_path = '/uploads/documents/stores/gallery/';
    protected $profile_image_path = '/uploads/users/media/thumb/'; 

    public function initbookingAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        $bucket_path = $this->getS3BaseUri();

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $data = array(); 
        $required_parameter = array('status', 'buyer_id', 'seller_id', 'do_transaction');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        }

        $em = $this->get('doctrine')->getEntityManager();
        
        /* Check for already pending transaction */
        $checkData = $em->getRepository('TransactionSystemBundle:BookTransaction')
           ->checkBuyerWithoutCreditPendingTransaction(array('status' => '0', 'buyer_id' => $de_serialize['buyer_id'], 'seller_id' => $de_serialize['seller_id'], 'with_credit' => 0));

        if(count($checkData) > 0 && $de_serialize['do_transaction'] == 'without_credit') {
            $data = array('code'=>1029, 'message'=>'FAILURE', 'response' => array('result' => 'ALREADY_PENDING_TRANSACTION_EXISTS'));
            echo json_encode($data);
            exit();
        }
        
        /* Check for wallet writing status */
        $walletStatus = $em->getRepository('WalletBundle:WalletCitizen')
                                        ->getWalletData($de_serialize['buyer_id']);
        
        if($de_serialize['do_transaction'] == 'with_credit') {
            if(!empty($walletStatus)  && $walletStatus[0]->getwritingStatus() == '1') {
                $data = array('code'=>1029, 'message'=>'FAILURE', 'response' => array('result' => 'ALREADY_PENDING_TRANSACTION_EXISTS'));
                echo json_encode($data);
                exit();
            }
            
            if(empty($walletStatus)) {
                $data = array('code'=>1029, 'message'=>'FAILURE', 'response' => array('result' => 'USER_NOT_ABLE_TO_PERFORM_TRANSACTION_WITH_CREDIT'));
                echo json_encode($data);
                exit();
            }
        }

        $buyer_id  = $de_serialize['buyer_id'];
        $store_id  = $de_serialize['seller_id'];

        /* Get Store Detail */
        $store_detail = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findBy(array('id' => $store_id));

         if(!empty($store_detail)) { 
                $store_detail = $store_detail[0];
            	if($de_serialize['do_transaction'] == 'without_credit') {
            		$withCredit = 0;
            	} else {
            		$withCredit = 1;
            	}     

                $TrManager = $this->get('transaction_manager');
                $time = new \DateTime('now');
                $timestamp = strtotime(date('Y-m-d H:i:s'));

                /* Process Book Transaction Request */
                $post_data = new BookTransaction();
                $post_data->setStatus(($de_serialize['status'] == 'INIT') ? 0 : '');
                $post_data->setTimeInitH($time);
                $post_data->setTimeUpdateStatusH(NULL);
                $post_data->setTimeInit($timestamp);
                $post_data->setTimeUpdateStatus(NULL);
                $post_data->SetBuyerId($de_serialize['buyer_id']);
                $post_data->setSellerId($de_serialize['seller_id']);
                $post_data->setTransactionId(NULL);
                $post_data->setWithCredit($withCredit);

                $em->persist($post_data);
                $em->flush();
                $em->clear();
                $BookId = $post_data->getId();
                
                /* Update wallet writing status */
                if($BookId && $de_serialize['do_transaction'] == 'with_credit') {
                    $updateWallet = $em->getRepository('WalletBundle:WalletCitizen')
                                                    ->updateWalletCitizenWritingStatus(array('buyer_id' => $de_serialize['buyer_id'], 'writing_status' => 1));
                }
                
                /*get store images */       
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
                            $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/coverphoto/' . $album_id .'/'. $image_name;
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
                $store_data['profile_image_original'] = $store_profile_image_path;
                $store_data['profile_image_thumb'] = $store_profile_image_thumb_path;
                $store_data['cover_image_path'] = $store_profile_image_cover_thumb_path;

                /* Get Buyer Detail */
                $buyer_data = $em
                           ->getRepository('UserManagerSonataUserBundle:User')
                           ->findBy(array('id' => $de_serialize['buyer_id']));
                $buyer_data = $buyer_data[0];
                $buyerProfilePic = (!empty($buyer_data->getProfileImagename())) ? $this->getS3BaseUri() . $this->profile_image_path . $buyer_id. '/' . $buyer_data->getProfileImagename() : '';

                $responseData = array(
                        'booking_id'              => $BookId,
                        'buyer_id'                 => $buyer_id,
                        'buyer_data'             => array(
                                                                        'firstname' => $buyer_data->getfirstname(),
                                                                        'lastname'  => $buyer_data->getlastname(),
                                                                        'profile_pic' => $buyerProfilePic
                                                                ),
                    'status'                        => ($de_serialize['status'] == 0) ? 'INIT' : '',
                    'date'                           => $time,
                    'date_format'               => date('h:i A d M Y'),
                    'do_transaction'            => $de_serialize['do_transaction'],
                    'seller_id'                      => $store_id,
	    'shop_id'                       => $store_id,
	    'store_data'                  => array(
                                                                'business_name' => $store_detail->getbusinessName(),
                                                                'business_type' => $store_detail->getbusinessType(),
                                                                'business_country' => $store_detail->getbusinessName(),
                                                                'business_region' => $store_detail->getbusinessName(),
                                                                'business_city' => $store_detail->getbusinessName(),
                                                                'business_address' => $store_detail->getbusinessName(),
                                                                'description' => $store_detail->getdescription(),
                                                                'zip' => $store_detail->getzip(),
                                                                'province' => $store_detail->getprovince()
                                                        ),
	                    'store_images'          => $store_data
	                );
	            
                if($BookId) {
                    $data = array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $responseData));
                    echo json_encode($data);
                } else {
                    $data = array('code'=>1029, 'message'=>'FAILURE');
                    echo json_encode($data);
                }
            } else {
                    $data = array('code'=>1029, 'message'=>'FAILURE','response' => array('result' => 'INVALID_SHOP'));
                    echo json_encode($data);
            }
        exit();
    }

    public function getcitizenpendingbookingAction(Request $request) {
    	$freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        $bucket_path = $this->getS3BaseUri();

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $data = array(); 
        $required_parameter = array('status', 'buyer_id');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        }

        $em = $this->get('doctrine')->getEntityManager();

        $PendingTransactions = $em->getRepository('TransactionSystemBundle:BookTransaction')
	               ->checkBuyerPendingTransaction(array('status' => '0', 'buyer_id' => $de_serialize['buyer_id']));

        if(!empty($PendingTransactions)) {
                foreach($PendingTransactions as $val) {
            
            $store_id  = $val->getSellerId();
            $buyer_id  = $de_serialize['buyer_id'];

            /* Get Store Detail */
            $store_detail = $em
                    ->getRepository('StoreManagerStoreBundle:Store')
                    ->findBy(array('id' => $store_id));
            $store_detail = $store_detail[0];

            /*get store images */       
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
                        $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/coverphoto/' . $album_id .'/'. $image_name;
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
            $store_data['profile_image_original'] = $store_profile_image_path;
            $store_data['profile_image_thumb'] = $store_profile_image_thumb_path;
            $store_data['cover_image_path'] = $store_profile_image_cover_thumb_path;

            /* Get Buyer Detail */
            $buyer_data = $em
                           ->getRepository('UserManagerSonataUserBundle:User')
                           ->findBy(array('id' => $de_serialize['buyer_id']));
            $buyer_data = $buyer_data[0];
            $buyerProfilePic = (!empty($buyer_data->getProfileImagename())) ? $this->getS3BaseUri() . $this->profile_image_path . $buyer_id. '/' . $buyer_data->getProfileImagename() : '';

            $dateObj = $val->gettimeInitH();

            $responseData[] = array(
                    'booking_id'        => $val->getId(),
            		'status' 			=> $val->getstatus(),
					'status_label' 		=> ($val->getstatus() == 0) ? 'Pending' : 'INIT',
                    'buyer_id'          => $val->getBuyerId(),
                    'buyer_data'        => array(
            								'firstname' => $buyer_data->getfirstname(),
            								'lastname'  => $buyer_data->getlastname(),
            								'profile_pic' => $buyerProfilePic
            							),
                    'date'					=> $dateObj->format('Y-m-d H:i:s'),
                    'date_format'			=> date('h:i A d M Y', strtotime($dateObj->format('Y-m-d H:i:s'))),
                    'do_transaction'        => ($val->getWithCredit() == 1) ? 'with_credit' : 'without_credit',
                    'seller_id'             => $store_id,
                    'shop_id'               => $store_id,
                    'store_data'            => array(
                    								'business_name' => $store_detail->getbusinessName(),
                    								'business_type' => $store_detail->getbusinessType(),
                    								'business_country' => $store_detail->getbusinessName(),
                    								'business_region' => $store_detail->getbusinessName(),
                    								'business_city' => $store_detail->getbusinessName(),
                    								'business_address' => $store_detail->getbusinessName(),
                    								'description' => $store_detail->getdescription(),
                    								'zip' => $store_detail->getzip(),
                    								'province' => $store_detail->getprovince()
                    							),
                    'store_images'          => $store_data
                );
            }
        }
        
        if(!empty($responseData)) {
            $data = array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $responseData));
            echo json_encode($data);
        } else {
    	$data = array('code'=>1029, 'message'=>'FAILURE', 'response' => array('result' => 'NO_PENDING_TRANSACTION'));
	 echo json_encode($data);
        }
        exit();
    }

    public function getcitizenstatsontransactionAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        $bucket_path = $this->getS3BaseUri();
        $TrManager = $this->get('transaction_manager');

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $data = array(); 
        $required_parameter = array('buyer_id', 'seller_id');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        }

        $em = $this->get('doctrine')->getEntityManager();

        /* Get citizen wallet data */
        $walletData = $em->getRepository('WalletBundle:WalletCitizen')
                         ->getWalletData($de_serialize['buyer_id']);
 
         $shopturnoverData = $em->getRepository('TransactionSystemBundle:Transaction')
                         ->getTotalTurnOverShop($de_serialize['seller_id']);
      
        if(!empty($walletData)) {
  
            $postData = array(
                            'wallet_citizen_id' => (!empty($walletData)) ? $walletData[0]->getId() : '',
                            'buyer_id' => $de_serialize['buyer_id'], 
                            'seller_id' => $de_serialize['seller_id']
                        );
            /* Get card data */
            $cardData = $em->getRepository('WalletBundle:Card')
                            ->getCitizenSellerCard($postData);

            $shoppingCardData = $em->getRepository('WalletBundle:ShoppingCard')
                            ->getCitizenSellerShoppingCard($postData);

            if(!empty($shoppingCardData)) {
                $shoppingCardBal = $shoppingCardData[0]['totalBalance'];
            } else {
                $shoppingCardBal = 0;
            }

            if(!empty($cardData)) {
                $cardBalance = $cardData[0]['totalCitizenCards'] + $shoppingCardBal;
            } else {
                $cardBalance = $shoppingCardBal;
            } 

            /* Get coupon data */
            $couponData = $em->getRepository('WalletBundle:Coupon')
                            ->getSellerCoupon($postData);

            /* Get credit position data */
            $premiumCreditData = $em->getRepository('WalletBundle:CreditPosition')
                                    ->getUsageCredits($postData);

            $responseData = array(
                 'currency' => $TrManager->getBuyerCurrency($de_serialize['buyer_id']),
                 'currency_symbol' => $TrManager->getCurrencyCode($TrManager->getBuyerCurrency($de_serialize['buyer_id'])),
                 'credit_available' => (!empty($walletData)) ? number_format($TrManager->getOrigPrice($walletData[0]->getcitizenIncomeAvailable()), 2, '.', '') : '0.00',
                 'cards_balance' => (!empty($cardData)) ? number_format($TrManager->getOrigPrice($cardBalance), 2, '.', '') : '0.00',
                 'coupons_balance' => (!empty($couponData)) ? number_format($TrManager->getOrigPrice($couponData[0]['couponAvailable']), 2, '.', '') : '0.00',
                 'premium_credits' => (!empty($premiumCreditData)) ? number_format($TrManager->getOrigPrice($premiumCreditData['available_amount']), 2, '.', '') : '0.00',
                 'revenue' => $shopturnoverData[0]['revenue']
              );
            echo json_encode(array('code' => 1029, 'message' => 'SUCCESS', 'response' => array('result' => $responseData)), JSON_UNESCAPED_UNICODE);
         } else {
            echo json_encode(array('code' => 1029, 'message' => 'FAILURE')); 
        }
        exit();
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
		return  $json;
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