<?php

namespace Payment\PaymentProcessBundle\Controller;

use Symfony\Component\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Payment\PaymentProcessBundle\Services\TransactionManagerService;
use Payment\PaymentProcessBundle\Entity\PaymentProcessCredit;
use Payment\PaymentProcessBundle\Entity\ShopGiftCards;

class GiftCardController extends Controller {
   protected $gift_card_media_path = '/uploads/giftcard/';
    /**
     * Purchase Gift Card.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */
    public function postPurchasegiftcardsAction(Request $request) {

        //call the service for getting the request object.
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'shop_id', 'gift_card_amount');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($data);
        }
        //extract parameters.
        $user_id = $object_info->user_id;
        $shop_id = $object_info->shop_id;
        $amount  = $object_info->gift_card_amount;
        
        $gift_cards = $this->container->getParameter('gift_cards_amount');
        $gift_cards_amount = explode('/', $gift_cards);
        if (!in_array($amount, $gift_cards_amount)) {
            $data = array('code' => 79, 'message' => 'INVALID_GIFT_CARD_AMOUNT', 'data' => $data);
            $this->returnResponse($data);
        }
        $new_gc_amount = $this->getConvertAmount($amount);
        //get citizen income
        $citizen_income_object = $this->getCitizenIncome($user_id);
        $citizen_income = $citizen_income_object->getCitizenIncome();
        if ($citizen_income < $new_gc_amount) {
            $data = array('code' => 78, 'message' => 'CITIZEN_INCOME_NOT_ENOUGH', 'data' => $data);
            $this->returnResponse($data);
        }
        //make entry for gift card purchase.
        $gc_id = $this->purchaseGiftCard($user_id, $shop_id, $new_gc_amount);
        if ($gc_id) {
            //update citizen income and gift cards value.
            $this->updateGiftCards($user_id, $shop_id, $new_gc_amount);
            $data = array('code'=>101, 'message'=>'SUCCESS', 'data'=>array());
        } else {
            $data = array('code'=>100, 'message'=>'ERROR_OCCURED', 'data'=>array());
        }
        $this->returnResponse($data);
    }

    
    /**
     * Get available gift cards
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return 
     */
   public function postGetavailablegiftcardsAction(Request $request)
   {
        //get request objects
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'shop_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($data);
        }
        $user_id = $object_info->user_id;
        $shop_id = $object_info->shop_id;
        //get citizen income
        $citizen_income_objects = $this->getCitizenIncome($user_id);
        $citizen_income = $citizen_income_objects->getCitizenIncome();
        $available_card = array();
        //to purchase the gift card, citizen income must be greater than or equal to minimum gift card purchased
        $gift_cards = $this->container->getParameter('gift_cards_amount');
        $gift_cards_amount = explode('/', $gift_cards);
        $minimum_gc_amoumt = min($gift_cards_amount); //get minimum gift card amount
        $minimum_gc_amoumt_m = $this->getConvertAmount($minimum_gc_amoumt);
        //only if citizen income is greater than minimum GC
        if($citizen_income < $minimum_gc_amoumt_m) {
          $data = array('code' => 78, 'message' => 'CITIZEN_INCOME_NOT_ENOUGH', 'data' => $data);
          $this->returnResponse($data); 
        }
        
        //get shop object
        $user_service = $this->get('user_object.service');
        $shop_objects = $user_service->getStoreObjectService($shop_id);
        $shop_data = array();
        if($shop_objects){
            $shop_data = $shop_objects;
        }
        //check the citizen income
        foreach($gift_cards_amount as $gc){
            $gcn = $this->getConvertAmount($gc);
            if($citizen_income >= $gcn){
                //get image path
                $image_path = $this->getBaseUri().$this->gift_card_media_path.$gc.".png";
                //check if image exist
                
                $document_root = $request->server->get('DOCUMENT_ROOT');
                $BasePath = $request->getBasePath();
                $file_location = $document_root . $BasePath; // getting sample directory path
                $image_path_directory = $file_location . $this->gift_card_media_path.$gc.".png";
                
                //check if image exist
                if(file_exists($image_path_directory)){
                    $image_path_exist = $image_path;
                }else{
                    $image_path_exist = '';
                }
                $available_card[] = array('card_value' => (int)$gc, 'card_logo' => $image_path_exist, 'user_id'=>$user_id, 'shop'=>$shop_data);
            }
        }
        
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $available_card);
        $this->returnResponse($data); 
    }
   
   
  /**
   * Get cicitzen income
   * @param int $user_id
   * @return array
   */
   public function getCitizenIncome($user_id)
   {
        $data = array();
        $em   = $this->get('doctrine')->getManager();
        $available_citizen_income_objects = $em
                        ->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                        ->findOneBy(array('userId' => $user_id));
        if(!$available_citizen_income_objects){
            $data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($data);
        }
        return $available_citizen_income_objects;
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
     * return the response.
     * @param type $data_array
     */
    private function returnResponse($data_array) {
        echo json_encode($data_array);
        exit;
    }
    
    /**
     * convert amount
     * @param int $amount
     * @return type
     */
    private function getConvertAmount($amount) {
      return ($amount * 1000000);   
    }

    /**
     * save the data for purchased gift card.
     * @param int $user_id
     * @param int $shop_id
     * @param int $gc_amount
     */
    private function purchaseGiftCard($user_id, $shop_id, $gc_amount) {
        //get entity manger object.
        $em = $this->container->get('doctrine')->getManager();
        $date = new \DateTime('now');
        $shop_gift_card = new ShopGiftCards();
        $shop_gift_card->setUserId($user_id);
        $shop_gift_card->setShopId($shop_id);
        $shop_gift_card->setGiftCardAmount($gc_amount);
        $shop_gift_card->setDate($date);
        $shop_gift_card->setIsUsed(0);
        try {
            $em->persist($shop_gift_card);
            $em->flush();
            $transaction_id = ($shop_gift_card->getId()? $shop_gift_card->getId() : null);
            return $transaction_id;        
        } catch (\Exception $ex) {
            return null;
        }
    }
    
    /**
     * update gift cards and citizen income.
     * @param int $user_id
     * @param int $shop_id
     * @param int $new_gc_amount
     */
    private function updateGiftCards($user_id, $shop_id, $new_gc_amount) {
        //get entity manger object.
        $em = $this->container->get('doctrine')->getManager();
        $shop_user_credit = $em->getRepository('WalletManagementWalletBundle:UserShopCredit')
                                 ->findOneBy(array('userId' => $user_id, 'shopId' => $shop_id));
        
        if ($shop_user_credit) {
            $total_gift_card   = $shop_user_credit->getTotalGiftCard();
            $balance_gift_card = $shop_user_credit->getBalanceGiftCard();
            $new_total_gift_card   = $total_gift_card + $new_gc_amount;
            $new_balance_gc_amount = $balance_gift_card + $new_gc_amount;
            $shop_user_credit->setTotalGiftcard($new_total_gift_card);
            $shop_user_credit->setBalanceGiftCard($new_balance_gc_amount);
            try {
               $em->persist($shop_user_credit);
               $em->flush();
            } catch (\Exception $ex) {
                $data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
                $this->returnResponse($data);                
            }
        }
        
        //finding the citizen income.
        $user_discount_position = $this->getCitizenIncome($user_id);
        if ($user_discount_position) {
            $old_citizen_income = $user_discount_position->getCitizenIncome();
            $new_citizen_income = ($old_citizen_income-$new_gc_amount);
            $user_discount_position->setCitizenIncome($new_citizen_income);
            try {
                $em->persist($user_discount_position);
                $em->flush();                
            } catch (\Exception $ex) {
                $data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
                $this->returnResponse($data);
            }
        }
        return true;
    }
    
    /**
     * Function to retrieve current applications base URI 
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        //return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl();
    }
}