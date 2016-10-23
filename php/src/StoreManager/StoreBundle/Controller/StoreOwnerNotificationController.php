<?php

namespace StoreManager\StoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use StoreManager\StoreBundle\Document\StoreOwnerNotification;

class StoreOwnerNotificationController extends Controller
{
    
    /**
     * Decode tha data
     * @param string $req_obj
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
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function encodeData($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->encode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     */
    private function checkParamsAction($chk_params, $object_info) {
        $converted_array = (array) $object_info;
        foreach ($chk_params as $param) {
            if (array_key_exists($param, $converted_array) && !empty($converted_array[$param])) {
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
    /**
     * Checking for file extension
     * @param $_FILE
     * @return int $file_error
     */
    private function checkFileType() {
        $file_error = 0;
        foreach ($_FILES['shop_offer_media']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['shop_offer_media']['name'][$key]);
            if (!empty($file_name)) {
                $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
                //for video and images.
                if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                        ($_FILES['shop_offer_media']['type'][$key] == 'image/jpeg' ||
                        $_FILES['shop_offer_media']['type'][$key] == 'image/jpg' ||
                        $_FILES['shop_offer_media']['type'][$key] == 'image/gif' ||
                        $_FILES['shop_offer_media']['type'][$key] == 'image/png'))) ||
                        (preg_match('/^.*\.(mp4|mov|mpg|mpeg|wmv|mkv)$/i', $file_name)))) {
                    $file_error = 1;
                    break;
                }
            }
        }
       return $file_error;
    }
    
    /**
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
        return $full_path;
    }
    
    /**
     * Send mail notification to store owner
     * @param Request $request
     */
    
    public function storeownernotificationsAction(Request $request){
        //initilise the array
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
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        // get all stores for which is_active filed is 1 and new_contract_status filed is 0
        $stores = $this->getDoctrine()->getRepository('StoreManagerStoreBundle:Store')
                                      ->findBy( array('isActive' => 1,'newContractStatus' => 0));
        //getting the store ids.
        $store_ids = array_map(function($store) {
                                    return "{$store->getId()}";
                                }, $stores);
       
        // fetch store owner informations
        $userService = $this->container->get('user_object.service');
        $email_template_service =  $this->container->get('email_template.service'); //email template service.   

        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        
        //get all user id from mongo db whose status for is_mail_send= 0
        $mongo_shop_owners=  $dm->getRepository('StoreManagerStoreBundle:StoreOwnerNotification')
                                 ->findBy(array('is_mail_send'=> 1));
        
//        $mongo_shop_owners_receive_mail = array_map(function($store) {
//                                                return "{$store->getStoreOwnerId()}";
//                                                }, $mongo_shop_owners);
                                                
        $mongo_shop_ids_mail_sent = array_map(function($store) {
                                                return "{$store->getStoreId()}";
                                                }, $mongo_shop_owners);
                                                
        //shops for which mail has to be sent
        $shops_id_mail_has_to_go = array_diff($store_ids, $mongo_shop_ids_mail_sent);
        //get shoponer details
        $shopOwners = $userService->getShopsOwnerIds($shops_id_mail_has_to_go,array(),true);
        $bunch_no = $this->container->getParameter('bunch_no');
        
        $bunches = $this->getUsersBunchForNewsletter($shopOwners['owner_ids'], $shopOwners['owner_details'], $bunch_no, 1);
        $locale = $this->container->getParameter('locale');
        $language_const_array = $this->container->getParameter($locale);
        $mail_sub  = sprintf($language_const_array['NEW_FEATURE_OPPORTUNITES_SUBJECT']); 
       
        $monoLog = $this->container->get('monolog.logger.storenotification_log');
        $monoLog->info('stoewoner newslettre initializing');
        $newsletterReceivers = 0;
        foreach ($bunches as $bunch){
            $newsletterReceivers += count($bunch);
            $emailResponse = $email_template_service->sendNewsLetters($bunch, $mail_sub, 'NEW_FEATURE_OPPORTUNITES_SUBJECT');  
        }
        $monoLog->info('Total number of user to get newsletter '.$newsletterReceivers);
        
        $time = new \DateTime("now");
        $shopOwnerIds = $shopOwners['owner_ids'];
       // echo "<pre>"; print_r($shopOwnerIds); // exit;
        foreach ($shopOwnerIds as $store_id => $shop_Owner_id){
            $store_owner_notification = new StoreOwnerNotification();
            $store_owner_notification->setStoreId($store_id);
            $store_owner_notification->setStoreOwnerId($shop_Owner_id);
            $store_owner_notification->setIsMailSend(1);
            $store_owner_notification->setMailFor('Edit Store Profile');
            $store_owner_notification->setMailSendOn($time);
            $dm->persist($store_owner_notification);
            $dm->flush(); 
        }
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
        
    }
    
    /**
     * Create a bunch of arrays so that not two element is repeated in same bunch
     * this is done bcz sendgrid do not allow same email id in one mail thread
     * @param type $shopDetails
     * @param type $shopOwnerDetails
     * @param type $usersInBunch
     * @param type $bunchNo
     * @return type
     */
    public function getUsersBunchForNewsletter($shopDetails, $shopOwnerDetails, $usersInBunch, $bunchNo=1){
        $emails = array();
        $shopEditProfilePage = $this->container->getParameter('shop_edit_url');
        $angular_site_url = $this->container->getParameter('angular_app_hostname');
        $nextBunch = false;
        $nextBunchEmails = array();
        $iteration=1;
        $bunchName = 'bunch-'.$bunchNo;
        foreach($shopDetails as $shopId=>$ownerId){
            if(!isset($emails[$bunchName]) or !key_exists($ownerId, $emails[$bunchName])){
                if(isset($shopOwnerDetails[$ownerId])){
                    $emails[$bunchName][$ownerId] = $shopOwnerDetails[$ownerId];
                    $emails[$bunchName][$ownerId]['shop_profile_link'] = $angular_site_url.$shopEditProfilePage.'/'.$shopId;
                }
                unset($shopDetails[$shopId]);
                $iteration++;
            }
            if($iteration==$usersInBunch){
               // $nextBunch=true;
               // $bunchNo++;
                break;
            }
            
        }
        if(count($shopDetails)>0){
            $bunchNo++;
            $nextBunchEmails = $this->getUsersBunchForNewsletter($shopDetails, $shopOwnerDetails, $usersInBunch, $bunchNo);
        }
        
        return array_merge($nextBunchEmails, $emails);
    }
    
    /**
     * upload shop offer images
     * @param Request $request
     */
    
    public function postUploadstoreofferimagesAction(Request $request) {
       
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        $device_request_type = $freq_obj['device_request_type'];

        if ($device_request_type == 'mobile') {  //for mobile if images are uploading.
            $de_serialize = $freq_obj;
        } else { //this handling for with out image.
            if (isset($fde_serialize)) {
                $de_serialize = $fde_serialize;
            } else {
                $de_serialize = $this->getAppData($request);
            }
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('shop_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $file_error = $this->checkFileType(); //checking the file type extension.
        if ($file_error) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
            
        }

        if (!isset($_FILES['shop_offer_media'])) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
        $original_media_name = @$_FILES['shop_offer_media']['name'];
        $shop_id  = $object_info->shop_id;
        if (empty($original_media_name)) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_AN_IMAGE', 'data' => $data);
        }
        
        //get the image name clean service..
        $clean_name = $this->get('clean_name_object.service');
        $image_upload = $this->get('amazan_upload_object.service');
        foreach ($_FILES['shop_offer_media']['tmp_name'] as $key => $tmp_name) {
                $original_media_name = $_FILES['shop_offer_media']['name'][$key];
                if ($original_media_name) {                   
                    if ($_FILES['shop_offer_media']['name'][$key] != "") {
                        $file_name = time() . strtolower(str_replace(' ', '', $_FILES['shop_offer_media']['name'][$key]));
                        $file_name = $clean_name->cleanString($file_name); //rename the file name, clean the image name.
                        $pre_upload_media_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('shop_offer_image_path').$shop_id.'/original/';
                        $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('shop_offer_image_path') .$shop_id.'/original/';
                        $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('shop_offer_image_path') .$shop_id.'/thumb/';
                        $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_album_media_path') .$shop_id.'/thumb/';
                        $s3_offer_media_path = $this->container->getParameter('s3_shop_offer_image_path'). $shop_id . "/original";
                        $s3_offer_media_path_thumb = $this->container->getParameter('s3_shop_offer_image_path'). $shop_id . "/thumb";
                        $image_upload->shopofferimageUploadService($_FILES['shop_offer_media'],$key,'shop_offer_media',$file_name,$pre_upload_media_dir,$media_original_path,$thumb_dir,$thumb_crop_dir,$s3_offer_media_path,$s3_offer_media_path_thumb);
                    }
                }
        }

        $shop_offer_image_path = $this->getS3BaseUri() . $this->container->getParameter('shop_offer_image_path').$shop_id.'/original/'.$file_name ;
        $shop_offer_image_thumb_path = $this->getS3BaseUri() . $this->container->getParameter('shop_offer_image_path') .$shop_id.'/thumb/'.$file_name ;

        //sending the current media and post data.
        $data = array(
            'media_link' => $shop_offer_image_path,
            'media_thumb_link' => $shop_offer_image_thumb_path,
        );

        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($resp_data);
        exit();
    }
    
    /**
     * 
     * @param Request $request
     * @return type
     */
    public function postDeleteshopoffermediasAction(Request $request){
        
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('media_path');
        $media_path = $object_info->media_path;
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //code for aws s3 server path
         $aws_base_path  = $this->container->getParameter('aws_base_path');
         $aws_bucket    = $this->container->getParameter('aws_bucket');
         $aws_path = $aws_base_path.'/'.$aws_bucket.'/';
         $relative_offer_media_path =$this->strafter($media_path, $aws_path);
         $image_upload = $this->get('amazan_upload_object.service');
         $media_deleted = $image_upload->deleteS3media($relative_offer_media_path);
         if($media_deleted){
          $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
          echo json_encode($resp_data);
          exit();   
         }else{
            $resp_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            echo json_encode($resp_data);
            exit();    
         }
    }
    
    /**
     * 
     * @param type $string
     * @param type $substring
     * @return type
     */
    function strafter($string, $substring) {
        $pos = strpos($string, $substring);
        if ($pos === false)
         return $string;
        else 
         return(substr($string, $pos+strlen($substring)));
    }
}
