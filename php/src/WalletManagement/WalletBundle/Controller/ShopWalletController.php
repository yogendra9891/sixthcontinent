<?php

namespace WalletManagement\WalletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Controller for shop wallet
 */
class ShopWalletController extends Controller
{
    protected $base_six = 1000000;
    protected $miss_param = '';
    
    public function indexAction($name)
    {
        return $this->render('WalletManagementWalletBundle:Default:index.html.twig', array('name' => $name));
    }
    
    /**
     * getting the shop wallet.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postShopwalletsAction(Request $request) {
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

        $required_parameter = array('shop_id');
        $data = array();
        $result_data = array();
        $shop_wallet_purchased_final_data   = array();
        $shop_wallet_purchased_final_array  = array();
        $shop_wallet_purchased_count = 0;
        $shop_wallet_shots_count = 0;
        $shop_wallet_momosy_card_count = 0;
        $shop_wallet_shots_final_data = array();
        $shop_wallet_shots_final_array = array();
        $shop_wallet_momosy_card_final_data = array();
        $shop_wallet_momosy_card_final_array = array();
        $shop_wallet_discount_position_final_array = array();
        //checking for parameter missing.
        $chk_error = $this->checkParams($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $shop_id   = $object_info->shop_id;
        $shots_needed               = (isset($object_info->shots_needed)) ? $object_info->shots_needed : 1;
        $purchase_card_needed       = (isset($object_info->purchase_card_needed)) ? $object_info->purchase_card_needed : 1;
        $momosy_card_needed         = (isset($object_info->momosy_card_needed)) ? $object_info->momosy_card_needed : 1;
        $discount_position_needed   = (isset($object_info->discount_position_needed)) ? $object_info->discount_position_needed : 1;
        
        $purchase_card_limit_start  = (isset($object_info->purchase_card_limit_start)) ? $object_info->purchase_card_limit_start : 0;
        $purchase_card_limit_size   = (isset($object_info->purchase_card_limit_size)) ? $object_info->purchase_card_limit_size : 20;
        
        $shots_card_limit_start     = (isset($object_info->shots_card_limit_start)) ? $object_info->shots_card_limit_start : 0;
        $shots_card_limit_size      = (isset($object_info->shots_card_limit_size)) ? $object_info->shots_card_limit_size : 20;
        
        $momosy_card_limit_start    = (isset($object_info->momosy_card_limit_start)) ? $object_info->momosy_card_limit_start : 0;
        $momosy_card_limit_size     = (isset($object_info->momosy_card_limit_size)) ? $object_info->momosy_card_limit_size : 20;
        
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        //user service.
        $user_service   = $this->get('user_object.service');
        
        //finding the purchase card if needed
        if ($purchase_card_needed) {
            $shop_wallet_purchased_data = $em->getRepository('WalletManagementWalletBundle:UserShopCredit')
                                             ->getShopWalletPurchasedCard($shop_id, $purchase_card_limit_start, $purchase_card_limit_size);
            if (count($shop_wallet_purchased_data)) {
                $shop_wallet_purchased_count = $em->getRepository('WalletManagementWalletBundle:UserShopCredit')
                                                  ->getShopWalletPurchasedCardCount($shop_id);
                foreach ($shop_wallet_purchased_data as $purchase_data) {
                    
                    $user_info      = $user_service->UserObjectService($purchase_data['user_id']); //transaction user object
                    $shop_wallet_purchased_final_data[] = array(
                        'citizen_name'=>  ucwords($user_info['first_name'].' '.$user_info['last_name']),
                        'user_info'   => $user_info,
                        'value'=> $this->convertCurrency($purchase_data['total_gift_card']),
                        'balance'=> $this->convertCurrency($purchase_data['balance_gift_card'])
                    );
                }
            }
            $shop_wallet_purchased_final_array = array('purchase_card'=>$shop_wallet_purchased_final_data, 'total'=>$shop_wallet_purchased_count);
        }
        
        //if shots needed
        if ($shots_needed) {               
            $shop_wallet_shots_data = $em->getRepository('WalletManagementWalletBundle:UserShopCredit')
                                         ->getShopWalletShots($shop_id, $shots_card_limit_start, $shots_card_limit_size);
            if (count($shop_wallet_shots_data)) {
                $shop_wallet_shots_count = $em->getRepository('WalletManagementWalletBundle:UserShopCredit')
                                              ->getShopWalletShotsCount($shop_id);      
                    foreach ($shop_wallet_shots_data as $shots_data) {
                    
                    $user_info      = $user_service->UserObjectService($shots_data['user_id']); //transaction user object
                    $shop_wallet_shots_final_data[] = array(
                        'citizen_name'=>  ucwords($user_info['first_name'].' '.$user_info['last_name']),
                        'user_info'   => $user_info,
                        'value'       => $this->convertCurrency($shots_data['total_shots']),
                        'balance'     => $this->convertCurrency($shots_data['balance_shots'])
                    );
                }
            }
            $shop_wallet_shots_final_array = array('shot'=>$shop_wallet_shots_final_data, 'total'=>$shop_wallet_shots_count);
            
        }
        //momosy card needed.
        if ($momosy_card_needed) {
            $shop_wallet_momosy_card_data   = $em->getRepository('WalletManagementWalletBundle:UserShopCredit')
                                                 ->getShopWalletMomosycard($shop_id, $momosy_card_limit_start, $momosy_card_limit_size);
            if (count($shop_wallet_momosy_card_data)) {
                $shop_wallet_momosy_card_count   = $em->getRepository('WalletManagementWalletBundle:UserShopCredit')
                                                       ->getShopWalletMomosycardCount($shop_id);
                foreach ($shop_wallet_momosy_card_data as $momosy_data) {

                    $user_info      = $user_service->UserObjectService($momosy_data['user_id']); //transaction user object
                    $shop_wallet_momosy_card_final_data[] = array(
                                'citizen_name'=>  ucwords($user_info['first_name'].' '.$user_info['last_name']),
                                'user_info'   => $user_info,
                                'value'       => $this->convertCurrency($momosy_data['total_momosy_card']),
                                'balance'     => $this->convertCurrency($momosy_data['balance_momosy_card'])
                    );                    
                } 
            }
            $shop_wallet_momosy_card_final_array = array('momosy_card'=>$shop_wallet_momosy_card_final_data, 'total'=>$shop_wallet_momosy_card_count);
        }
        
        //if discount poistion needed
        if ($discount_position_needed) {
            $shop_wallet_discount_position_data   = $em->getRepository('StoreManagerStoreBundle:Store')
                                                       ->find($shop_id);
            if (count($shop_wallet_discount_position_data)) {
                $tot_dp = (int)$shop_wallet_discount_position_data->getTotalDp();
                $bal_dp = (int)$shop_wallet_discount_position_data->getBalanceDp();
                $total_avail_dp = $this->convertCurrency($tot_dp);
                $balance_dp     = $this->convertCurrency($bal_dp);
                $shop_wallet_discount_position_final_array = array('total_discount_position'=>$total_avail_dp, 
                                                                   'balance_discount_position'=>$balance_dp);
            }
        }
        $data = array('purchase_cards'=>$shop_wallet_purchased_final_array, 'shots'=>$shop_wallet_shots_final_array,
                      'momosy_cards'=>$shop_wallet_momosy_card_final_array, 'discount_position'=>$shop_wallet_discount_position_final_array); 
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit;
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
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     * @return int
     */
    private function checkParams($chk_params, $object_info) {
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
     * Convert currency
     * @param int amount
     * @return float
     */
    public function convertCurrency($amount)
    {
        $final_amount = (float)$amount/$this->base_six;
        return $final_amount;
    }
}
