<?php
namespace Transaction\WalletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
Use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;

class WalletBusinessController extends Controller
{  
    protected $store_media_path = '/uploads/documents/stores/gallery/';
    protected $profile_image_path = '/uploads/users/media/thumb/';
    
    public function getbusinesswalletsaleAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $walletService = $this->get('wallet_manager');
        
        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $data = array(); 
        $required_parameter = array('user_id', 'seller_id');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        }
        
        $em = $this->getDoctrine()->getManager();
        $walletSale = $em->getRepository('WalletBundle:WalletBusiness')
                                    ->getBusinessWalletSale($de_serialize);
        echo json_encode(array('code' => 100, 'message' => 'SUCCESS', 'response' => array('result' => $walletSale)), JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    public function getbusinesswallethistoryAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        $de_serialize['from_date'] = '';
        $de_serialize['to_date'] = '';
        $walletService = $this->get('wallet_manager');
        
        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $data = array(); 
        $required_parameter = array('shop_id');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        }
        
        $em = $this->getDoctrine()->getManager();
        $shopHistory = $em->getRepository('WalletBundle:WalletBusiness')
                                       ->getbusinesswallethistory(array('seller_id' => $de_serialize['shop_id'], 'limit' => $de_serialize['limit'],'offset' => $de_serialize['skip'], 'from_date' => $de_serialize['from_date'], 'to_date' => $de_serialize['to_date']));
        
        $historyCount['historyCount'] = 0;
        $historyCount['hasNext'] = false;
        $resultArr = array();
        $TrtypeArr = array();
        
        if(!empty($shopHistory)) {
            $historyCount = $em->getRepository('WalletBundle:WalletBusiness')
                                       ->getcountofwallethistory(array('seller_id' => $de_serialize['shop_id'], 'limit' => $de_serialize['limit'],'offset' => $de_serialize['skip']));
            
            $buyer_data = array();
            $buyer_data[0] = array();
            $scPurchase = array();
            $scData = array();
            $disArr = array();
            
            foreach($shopHistory as $val) {
                $scPurchase = $em->getRepository('TransactionSystemBundle:TransactionPaymentInformation')
                                             ->getTransactionDetail(array('transaction_id' => $val->getId()));
                /* Check for shopping card purchase */
                if(!empty($scPurchase)) {
                    $scData = $em->getRepository('WalletBundle:ShoppingCard')
                                            ->getPurchasedShoppingCard($val->getsixcTransactionId());
                    $disArr = array(
                        'type' => array(
                            '_id' => (!empty($scData)) ? $scData->getsixcTransactionId() : '',
                            'name' => 'Shopping Card Upto 100%'
                        ),
                        'amount' => (!empty($scData)) ? number_format($scData->getinitAmount()/100, 2, '.', '') : '0.00',
                        'card_id' => array(
                            '_id' => (!empty($scData)) ? $scData->getshoppingCardId(): '',
                            'card_no' => (!empty($scData)) ? $scData->getshoppingCardId(): ''
                        )
                    );
                } else {
                     $disArr = array(
                        'type' => array(
                            '_id' => $val->getsixcTransactionId(),
                            'name' => '6% Debit'
                        ),
                        'amount' => number_format($val->getsixcAmountPc()/100, 2, '.', ''),
                    );
                }
                
                 /* Get Buyer Detail */
                $buyer_data = $em->getRepository('UserManagerSonataUserBundle:User')
                                                ->findBy(array('id' => $val->getbuyerId()));

                $buyerProfilePic = (!empty($buyer_data[0]->getProfileImagename())) ? $this->getS3BaseUri() . $this->profile_image_path . $val->getbuyerId() . '/' . $buyer_data[0]->getProfileImagename() : '';
                $time = $val->gettimeInitH();
                $resultArr[] = array(
                    '_id' => $val->getsixcTransactionId(),
                    'citizen_id' => array(
                        '_id' => (!empty($buyer_data)) ? $buyer_data[0]->getId() : '',
                        'address_l1' => '',
                        'address_l2' => '',
                        'country' => array(
                            '_id' => '',
                            'countryname' => '' 
                        ),
                        'name' => (!empty($buyer_data)) ? ucfirst($buyer_data[0]->getfirstname().' '.$buyer_data[0]->getlastname()): '',
                        'latitude' => '',
                        'longitude' => '',
                        'user_thumbnail_image' => (!empty($buyer_data[0]->getProfileImagename())) ? $buyerProfilePic : '',
                        'city' => (!empty($buyer_data)) ? $buyer_data[0]->getcityBorn() : ''
                    ),
                    'date' => $time->format('Y-m-d H:i:s'),
                    'discount_details' => $disArr
                );
                
                /* Transaction type id array */
                $TrtypeArr[] = $val->gettransactionTypeId();
            }
        }
        
        /* Creating return response */
        $returnArr = array(
            'response' => array(
                'shop_wallet_history' => array(
                    'result' => $resultArr,
                    'dataInfo' => array(
                        'hasNext' => $historyCount['hasNext']
                    )
                 ),
                'shop_wallet_history_count' => array(
                    'result' => array(
                        '_id' => NULL,
                        'count' => $historyCount['historyCount']
                    ),
                    'dataInfo' => array(
                        'hasNext' => $historyCount['hasNext']
                    )
                )
             ),
            'status' => 'ok',
            'code' => 200,
            'serverTime' => '',
            'query' => array(
                'shop_wallet_history' => array(
                    '$collection' => 'Transaction',
                    '$fields' => array(
                        'date' => 1,
                        'discount_details.amount' => 1,
                        'discount_details.type' => 1,
                        'discount_details.card_id' =>1,
                        'citizen_id' => 1
                    ),
                    '$filter' => array(
                        'discount_details.card_no' => array(
                            '$exists' => true
                        ),
                        'status' => 'Approved',
                        'transaction_type_id' => array(
                            '$in' => $TrtypeArr
                        ),
                        'shop_id' => $de_serialize['shop_id']
                    ),
                    '$unwind' => array('discount_details'),
                    '$sort' => array(
                        'date' => 1
                    ),
                    '$limit' => $de_serialize['limit'],
                    '$skip' => $de_serialize['skip']
                ),
                'shop_wallet_history_count' => array(
                    '$collection' => 'Transaction',
                    '$group' => array(
                        '$count' => array(
                            '$sum' => $historyCount['historyCount']
                        ),
                        '_id' => NULL,
                        '$fields' => false
                    ),
                    '$filter' => array(
                        'discount_details.card_no' => array(
                            '$exists' => true
                        ),
                        'status' => 'Approved',
                        'transaction_type_id' => array(
                            '$in' => $TrtypeArr
                        ),
                        'shop_id' => $de_serialize['shop_id']
                    ),
                    '$unwind' => array('discount_details'),
                )
            ),
            'serviceLogId' => ''
        );
        echo json_encode($returnArr);
        exit();
    }
    
    public function getbusinesswallethistorysaleAction(Request $request) {
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        $de_serialize['from_date'] = '';
        $de_serialize['to_date'] = '';
        $walletService = $this->get('wallet_manager');
        
        /* check required parameters */
        $object_info = (object) $de_serialize;
        $data = array(); 
        $required_parameter = array('shop_id');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        }
        
        $em = $this->getDoctrine()->getManager();
        $shopHistory = $em->getRepository('WalletBundle:WalletBusiness')
                                       ->getbusinesswallethistory(array('seller_id' => $de_serialize['shop_id'], 'limit' => $de_serialize['limit'],'offset' => $de_serialize['skip'], 'from_date' => $de_serialize['from_date'], 'to_date' => $de_serialize['to_date']));

        $historyCount['historyCount'] = 0;
        $historyCount['hasNext'] = false;
        $discountArr = array();
        $resultArr = array();
        $TrtypeArr = array();
        
        if(!empty($shopHistory)) {
            $historyCount = $em->getRepository('WalletBundle:WalletBusiness')
                                       ->getcountofwallethistory(array('seller_id' => $de_serialize['shop_id'], 'limit' => $de_serialize['limit'],'offset' => $de_serialize['skip']));
            
            $buyer_data = array();
            $buyer_data[0] = array();
   
            foreach($shopHistory as $val) {
                $serializeData = unserialize($val->gettransactionSerialize());
                /* Get Buyer Detail */
                $buyer_data = $em->getRepository('UserManagerSonataUserBundle:User')
                                                ->findBy(array('id' => $val->getbuyerId()));

                $buyerProfilePic = (!empty($buyer_data[0]->getProfileImagename())) ? $this->getS3BaseUri() . $this->profile_image_path . $val->getbuyerId() . '/' . $buyer_data[0]->getProfileImagename() : '';
                $time = $val->gettimeInitH();
                
                /* Discount response */
                if(!empty($serializeData['card_data']) && !empty($serializeData['card_usage'])) {
                    /* Get used card detail */
                    $cardData = $em->getRepository('WalletBundle:Card')
                                               ->findby(array('id' => $serializeData['card_data']['id']));
                    $discountArr[] = array(
                            'type' => array(
                                '_id' => (!empty($cardData)) ? $cardData[0]->getcardId() : '',
                                'name' => 'Shopping Card Upto 50%'
                            ),
                            'amount' => (!empty($cardData)) ? number_format($cardData[0]->getinitAmount()/100, 2, '.', '') : '',
                            'card_id' => array(
                                '_id' => (!empty($cardData)) ? $cardData[0]->getcardId() : '',
                                'card_no' => (!empty($cardData)) ? $cardData[0]->getcardId() : ''
                            ),
                            'used_amount' => ($serializeData['card_usage']['amount_used'] > 0) ? number_format($serializeData['card_usage']['amount_used']/100, 2, '.', '') : '0.00',
                            'balance_amount' => ($cardData[0]->getavailableAmount() > 0) ? number_format($cardData[0]->getavailableAmount()/100, 2, '.', '') : '0.00',
                            '_id' => (!empty($cardData)) ? $cardData[0]->getcardId() : ''
                        );
                }
             
                 /* Shopping card usage response */
                 if(!empty($serializeData['shopping_card_data']) && !empty($serializeData['shopping_card_usage'])) {
                     foreach($serializeData['shopping_card_usage'] as $scval) {
                         $scData = $em->getRepository('WalletBundle:ShoppingCard')
                                               ->findby(array('id' => $scval['id']));

                         $discountArr[] = array(
                                'type' => array(
                                    '_id' => (!empty($scData)) ? $scData[0]->getshoppingCardId() : '',
                                    'name' => 'Shopping Card Upto 100%'
                                ),
                                'amount' => (!empty($scData)) ? number_format($scData[0]->getinitAmount()/100, 2, '.', '') : '',
                                'card_id' => array(
                                    '_id' => (!empty($scData)) ? $scData[0]->getshoppingCardId() : '',
                                    'card_no' => (!empty($scData)) ? $scData[0]->getshoppingCardId() : ''
                                ),
                                'used_amount' => ($scval['used_data']['amount_used'] > 0) ? number_format($scval['used_data']['amount_used']/100, 2, '.', '') : '0.00',
                                'balance_amount' => ($scData[0]->getavailableAmount() > 0) ? number_format($scData[0]->getavailableAmount()/100, 2, '.', '') : '0.00',
                                 '_id' => (!empty($scData)) ? $scData[0]->getshoppingCardId() : ''
                             );
                     }
                 }
             
                 /* citizen income usage response */
                 if(!empty($serializeData['new_card_usage'])) {
                     foreach($serializeData['new_card_usage'] as $newCard) {
                         /* Get used new card detail */
                         $newCardData = $em->getRepository('WalletBundle:Card')
                                                   ->findby(array('id' => $newCard['id']));
                         
                         $discountArr[] = array(
                                'type' => array(
                                    '_id' => (!empty($newCardData)) ? $newCardData[0]->getcardId() : '',
                                    'name' => 'Shopping Card Upto 50%'
                                ),
                                'amount' => (!empty($newCardData)) ? number_format($newCardData[0]->getinitAmount()/100, 2, '.', '') : '',
                                'card_id' => array(
                                    '_id' => (!empty($newCardData)) ? $newCardData[0]->getcardId() : '',
                                    'card_no' => (!empty($newCardData)) ? $newCardData[0]->getcardId() : ''
                                ),
                                'used_amount' => ($newCard['amount_used'] > 0) ? number_format($newCard['amount_used']/100, 2, '.', '') : '0.00',
                                'balance_amount' => (!empty($newCardData) AND $newCardData[0]->getavailableAmount() > 0) ? number_format($newCardData[0]->getavailableAmount()/100, 2, '.', '') : '0.00',
                                '_id' => (!empty($newCardData)) ? $newCardData[0]->getcardId() : '',
                             );
                     }
                 }
                 
                 /* Premium position usage response */
                 if(!empty($serializeData['new_credit_position_data']) && $serializeData['credit_position_usage']) {
                     $creditPositionData = $em->getRepository('WalletBundle:CreditPosition')
                                                            ->findby(array('id' => $serializeData['new_credit_position_data']['id']));

                     $discountArr[] = array(
                                'amount' => (!empty($creditPositionData)) ? number_format($creditPositionData[0]->getamount()/100, 2, '.', '') : '',
                                'used_amount' => ($serializeData['new_credit_position_data']['amount_used'] > 0) ? number_format($serializeData['new_credit_position_data']['amount_used']/100, 2, '.', '') : '0.00',
                                'balance_amount' =>  (!empty($creditPositionData)) ?  number_format(($creditPositionData[0]->getamount() - $serializeData['new_credit_position_data']['amount_used'])/100, 2, '.', '') : '0.00',
                                'type' => array(
                                     '_id' => (!empty($creditPositionData)) ? $creditPositionData[0]->getpremiumId() : '',
                                    'name' => '6% Debit'
                                ),
                                'card_no' => '6% Debit',
                                 '_id' => (!empty($creditPositionData)) ? $creditPositionData[0]->getpremiumId() : ''
                         );
                 }
                 
                /* Result response */
                $resultArr[] = array(
                    '_id' => $val->getsixcTransactionId(),
                    'citizen_id' => array(
                        '_id' => (!empty($buyer_data)) ? $buyer_data[0]->getId() : '',
                        'address_l1' => '',
                        'address_l2' => '',
                        'country' => array(
                            '_id' => '',
                            'countryname' => '' 
                        ),
                        'name' => (!empty($buyer_data)) ? ucfirst($buyer_data[0]->getfirstname().' '.$buyer_data[0]->getlastname()): '',
                        'latitude' => '',
                        'longitude' => '',
                        'user_thumbnail_image' => (!empty($buyer_data[0]->getProfileImagename())) ? $buyerProfilePic : '',
                        'city' => (!empty($buyer_data)) ? $buyer_data[0]->getcityBorn() : ''
                    ),
                    'date' => $time->format('Y-m-d H:i:s'),
                    'transaction_value' => number_format($val->getinitPrice()/100, 2, '.', ''),
                    'payble_value' =>  number_format($val->getfinalPrice()/100, 2, '.', ''),
                    'total_discountvalue_used' => number_format($val->getdiscountUsed()/100, 2, '.', ''),
                    'total_cardvalue_used' => number_format($val->getshoppingCardUsed()/100, 2, '.', ''),
                    'discount_details' => (isset($discountArr)) ? $discountArr : ''
                );
                if(isset($discountArr)) {
                    unset($discountArr);
                }
                /* Transaction type id array */
                $TrtypeArr[] = $val->gettransactionTypeId();
            }
        }
        
        /* Create return response */
        $returnArr = array(
            'response' => array(
                'sale_history' => array(
                    'result' => $resultArr,
                    'dataInfo' => array(
                        'hasNext' => $historyCount['hasNext']
                    )
                ),
                'sale_history_count' => array(
                    'result' => array(
                        '_id' => NULL,
                        'count' => $historyCount['historyCount']
                    ),
                    'dataInfo' => array(
                        'hasNext' => $historyCount['hasNext']
                    )
                )
            ),
            'status' => 'ok',
            'code' => 200,
            'serverTime' => '',
            'query' => array(
                'sale_history' => array(
                    '$collection' => 'Transaction',
                    '$fields' => array(
                        'date' => '',
                        'total_discountvalue_used' => '',
                        'total_cardvalue_used' => '',
                        'transaction_value' => '',
                        'payble_value' => '',
                        'citizen_id' => '',
                        'discount_details' => ''
                    ),
                    '$filter' => array(
                        'shop_id._id' => $de_serialize['shop_id'],
                        'status' => 'Approved',
                        'transaction_type_id' => array(
                            '$in' => $TrtypeArr
                        )
                    ),
                    '$sort' => array(
                        'date' => -1,
                    ),
                    '$limit' => $de_serialize['limit'],
                    '$skip' => $de_serialize['skip']
                ),
                'sale_history_count' => array(
                    '$collection' => 'Transaction',
                    '$group' => array(
                        'count' => array(
                            '$sum' => $historyCount['historyCount']
                        ),
                        '_id' => NULL,
                        '$fields' => false
                    ),
                    '$filter' => array(
                        'shop_id._id' => $de_serialize['shop_id'],
                        'status' => 'Approved',
                        'transaction_type_id' => array(
                            '$in' => $TrtypeArr
                        )
                    )
                )
            ),
            'serviceLogId' => ''
        );
        echo json_encode($returnArr);
        exit();
    }


    /**
     * GEt top 50 shop Revenues
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function gettopshoprevenuesAction(Request $request) {
   
        $freq_obj = $request->get('reqObj');
   
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $walletService = $this->get('wallet_manager');

        /* check required parameters*/
        $object_info = (object) $de_serialize;
        $data = array();
        $required_parameter = array('limit');
        
        /* checking for parameter missing */
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
             $resp = array('code' => 1029, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param));
             echo json_encode($resp);
             exit();
        } 

        $em = $this->getDoctrine()->getManager();

        $shop_img_path = $this->getS3BaseUri().$this->store_media_path;

        if(isset($de_serialize['seller_id'])){
            $sid = $de_serialize['seller_id'];
        } else {
            $sid = "";
        }

        /* Get top 50 citizens */
        
         $wallet_b_repo= $em
                        ->getRepository('WalletBundle:WalletBusiness');
         $shopRevenueData = $wallet_b_repo->getTopShopRevenue($de_serialize['limit'],$shop_img_path,$sid);

            /* Get images for shop and category */   

            // Get Store thumb images 
             
             $storeimages = $em
                ->getRepository('StoreManagerStoreBundle:StoreMedia');

             // Get Category thumb image

             $storeprofileimages = $em->getRepository('UserManagerSonataUserBundle:BusinessCategory');
                
             foreach($shopRevenueData  as $getdata ) { 

                $store_images  =  $storeimages->findBy(array('storeId' => $getdata['id']));

                if (empty($store_images)) {
                 
                 $store_profile_images = $storeprofileimages->getCategoryImageFromStoreIds($getdata['id']);
                  $img = $store_profile_images[$getdata['id']]['thumb_image'];
                 if($img == ""){
                    $img =  $this->getS3BaseUri().'/uploads/businesscategory/thumb/default_store.png';
                  }
               }
               
                else 
                {
                  $img = $this->getS3BaseUri() . "/uploads/documents/stores/gallery/" . $getdata['id'] . "/thumb/" . $store_images[0]->getimageName();
                }    
               
               /* End get path for shop and category */

                $new_data = array(
                   'profile_image_thumb' => $img,
                   'name' => $getdata['name'],
                   'id' => $getdata['id'],
                   'revenue' => number_format($getdata['totalRevenue']/100,'2','.',''),
                   'shop_status' => true 
                );

                $topshop[] = $new_data; 
         }


        $res_data = array('status' => 'ok', 'code' => '101', 'data' => $topshop);

        echo json_encode($res_data);
        exit();
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

    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
        return $full_path;
    }
}