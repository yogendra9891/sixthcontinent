<?php

namespace Affiliation\AffiliationManagerBundle\Controller;

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

class AffiliationV1Controller extends Controller
{
    
     protected $store_media_path = '/uploads/documents/stores/gallery/';
     CONST MAIL_SENT = 1;
     CONST MAIL_NOT_SENT = 0;
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
     * send affiliation link
     * @param request object
     * @return json
     */
    public function postSendaffiliationlinksAction(Request $request) { 
        $data = array();
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        //get locale
        $locale = $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        //get object of email template service
        $email_template_service = $this->container->get('email_template.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'to_emails','affiliation_type','url');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $to_emails = $object_info->to_emails;
        $affiliation_type = $object_info->affiliation_type;
        $url_to_send = $object_info->url;
        $affiliation_category = ''; //sendgrid mail category.
        
        //get User
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        $from_email_id = '';
        if(!$sender_user){
            return array('code'=>100, 'message'=>'USER_ID_IS_INVALID','data'=>$data); 
        }else{
            $from_email_id = $sender_user->getEmail();
            $current_language = $sender_user->getCurrentLanguage();
        }
        
        $locale = $current_language ? $current_language : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);

        //get remote url url
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        
        $invitation_link = "<a href='$url_to_send'>$url_to_send</a>";
        $click_here = "<a href='$url_to_send'>{$lang_array['CLICK_HERE']}</a>";
        $affiliation_type_arr = array(1,2,3);
      
        if(!in_array($affiliation_type,$affiliation_type_arr)){
            return array('code'=>100, 'message'=>'AFFILIATION_TYPE_IS_INVALID','data'=>$data); 
        }
        
        //send invitation for registeration
        
        /*get info realted to the sender*/
        $user_service = $this->get('user_object.service');
        $user_info = $user_service->UserObjectService($sender_user->getId());
        if($user_info['profile_image_thumb']) {
            $user_thumb = $user_info['profile_image_thumb'];
        }else {
            $user_thumb = '';
        }
        
        //get emails that are altrady registerd
        $affliation_service = $this->get('affiliation_affiliation_manager.user');
        $registerd_emails = $affliation_service->checkRegisteredEmails($to_emails);
        $data_email = array();
        if(count($registerd_emails) > 0 ){
            foreach($registerd_emails as $registerd_email){
            $data_email[] = array('email' => $registerd_email, 'status' => self::MAIL_NOT_SENT); //already registerd and mail not sent
            }
        }
        $to_emails = array_diff($to_emails, $registerd_emails);  //send mail to user that are not registerd
        
        if(count($to_emails) > 0 ){
            foreach($to_emails as $to_email){
            $data_email[] = array('email' => $to_email, 'status' => self::MAIL_SENT); //mail sent
            }
        }
        $data = $data_email;
        if($affiliation_type == 1) {
            $sender_name = ucfirst($user_info['first_name']).' '.ucfirst($user_info['last_name']);       
            $mail_sub = sprintf($lang_array['AFFILIATION_INVITATION_SUBJECT'],$sender_name);
            $mail_body = sprintf($lang_array['AFFILIATION_INVITATION_BODY'],$sender_name);        
            $bodyData = $lang_array['AFFILIATION_INVITATION_LINK'];
            $bodyData .= "<br><br>$click_here";
            $affiliation_category = 'INVITE_CITIZEN';
        }else if($affiliation_type == 3) {
            $sender_name = ucfirst($user_info['first_name']).' '.ucfirst($user_info['last_name']);       
            $mail_sub = sprintf($lang_array['AFFILIATION_INVITATION_SUBJECT_SHOP'],$sender_name);
            $mail_body = sprintf($lang_array['AFFILIATION_INVITATION_BODY_SHOP'],$sender_name);    
            //echo $mail_body;exit;
            $bodyData = $lang_array['AFFILIATION_INVITATION_LINK_SHOP'];
            $bodyData .= "<br><br>".$email_template_service->getLinkForMail($url_to_send, $locale);
            //$link .= "<br><br>$click_here";
            $affiliation_category = 'INVITE_SHOP';
        }
        
        
        /*send affiliation invitation link to all email addresses*/
        if(!empty($to_emails)){
            $emailResponse = $email_template_service->sendMail($to_emails, $bodyData, $mail_body, $mail_sub, $user_thumb, $affiliation_category, null, 2, 1);
        }
        
        return array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);        
        
    }
    
   /**
    * Get linked citizens
    * @return string
    */
    public function linkedcitizenAction(Request $request)
    {
        $data = array();
        $results = array();
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
        $order_by = "asc";
        $limit_start = 0;
        $limit_size = 50;
        
        if(isset($object_info->order_by)){
        $order_by = $object_info->order_by;
        }
        if(isset($object_info->limit_start)){
        $limit_start = $object_info->limit_start;
        }
        if(isset($object_info->limit_size)){
        $limit_size = $object_info->limit_size;
        }
        
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        
        //fire the query in User Repository
        $results = $em
                ->getRepository('AffiliationAffiliationManagerBundle:AffiliationCitizen')
                ->getTopLinkedCitizens($order_by, $limit_start, $limit_size);
        
        $total_count = count($results);
        $pagination_array = array_slice($results, $limit_start, $limit_size, true);
       
        //get user service
        $user_service  = $this->get('user_object.service');
        
        foreach($pagination_array as $key=>$value){
           $user_object  = $user_service->UserObjectService($key);
           $affiliation_count = $value;
           $data[] = array('user_info' => $user_object,'affiliation_count' => $value);
        }
        $response = array('data'=>$data, 'size'=>$total_count);
        $resp_data = array('code'=>101,'message'=>'SUCCESS','data'=>$response);
        echo json_encode($resp_data);
        exit;
    }
       
    /**
     * Finding the afiliated citizen users of a user
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function postCitizenaffiliationsAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj      = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data      = array();
        $user_data = array();
        $limit     = (int)(isset($de_serialize['limit_size'])? $de_serialize['limit_size']:50);
        $offset    = (int)(isset($de_serialize['limit_start'])? $de_serialize['limit_start']:0);

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $user_affiliates_count = 0; //initialize the count by 0.
        
        //finding the entity manager object.
        $em = $this->getDoctrine()->getManager();
        
        //find user affiliates
        $user_affiliates = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationCitizen')                   
                               ->findCitizenAffiliationUsers($user_id, $offset, $limit);

        //call the user service.
        $user_service = $this->get('user_object.service');
        foreach ($user_affiliates as $user) {
            $user_profile = $user_service->UserObjectService($user['toId']);
            $user_data[] = $user_profile;
        }
        $data['affiliates'] = $user_data;
        
        //if affiliates users are 0 then count is also zero
        if (count($data['affiliates']) > 0) {
            //count of user affiliates.
            $user_affiliates_count = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationCitizen')                   
                                        ->findCitizenAffiliationUsersCount($user_id);
        }
        $data['count']      = $user_affiliates_count;
        $final_data =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit;
    }
    
    /**
     * Finding the count of afiliated citizen users of a user
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function postCitizenaffiliationcountsAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj      = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data      = array();
        
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        
        //finding the entity manager object.
        $em = $this->getDoctrine()->getManager();
        
        //count of user affiliates.
        $user_affiliates_count = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationCitizen')                   
                                    ->findCitizenAffiliationUsersCount($user_id);
        $data['count'] = $user_affiliates_count;
        $final_count =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_count);
        exit;
    }
    
    /**
     * Finding the afiliated broker users of a user
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function postBrokeraffiliationsAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj      = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data      = array();
        $user_data = array();
        $limit     = (int)(isset($de_serialize['limit_size'])? $de_serialize['limit_size']:50);
        $offset    = (int)(isset($de_serialize['limit_start'])? $de_serialize['limit_start']:0);

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $user_affiliates_count = 0; //initialize the count by 0.
        
        //finding the entity manager object.
        $em = $this->getDoctrine()->getManager();
        
        //find user affiliates
        $user_affiliates = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationBroker')                   
                               ->findBrokerAffiliationUsers($user_id, $offset, $limit);

        //call the user service.
        $user_service = $this->get('user_object.service');
        foreach ($user_affiliates as $user) {
            $user_profile = $user_service->UserObjectService($user['toId']);
            $user_data[] = $user_profile;
        }
        $data['affiliates'] = $user_data;
        
        //if affiliates users are 0 then count is also zero
        if (count($data['affiliates']) > 0) {
            //count of user affiliates.
            $user_affiliates_count = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationBroker')                   
                                        ->findBrokerAffiliationUsersCount($user_id);
        }
        $data['count']      = $user_affiliates_count;
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit;
    }
    
    /**
     * Finding the count of afiliated broker users of a user
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function postBrokeraffiliationcountsAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj      = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data      = array();
        
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        
        //finding the entity manager object.
        $em = $this->getDoctrine()->getManager();
        
        //count of user affiliates.
        $user_affiliates_count = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationBroker')                   
                                    ->findBrokerAffiliationUsersCount($user_id);
        $data['count'] = $user_affiliates_count;
        $final_count = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_count);
        exit;
    }
    
    /**
     * Finding the afiliated for a shop done by users.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function postShopaffiliationsAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj      = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data      = array();
        $shop_affiliates_array = array();
        $limit     = (int)(isset($de_serialize['limit_size'])? $de_serialize['limit_size']:50);
        $offset    = (int)(isset($de_serialize['limit_start'])? $de_serialize['limit_start']:0);

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $shop_affiliates_count = 0; //initialize the count by 0.
        
        //finding the entity manager object.
        $em = $this->getDoctrine()->getManager();
        
        //find shop affiliates
        $shop_affiliates = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationShop')                   
                              ->findUserAffiliationShops($user_id, $offset, $limit);

        //making the shop object....
        foreach ($shop_affiliates as $shop) {
            $store_detail = $em->getRepository('StoreManagerStoreBundle:Store')
                               ->findOneBy(array('id' => $shop['shopId']));
            $store_id = $store_detail->getId();
            //prepare store info array
            $store_data = array(
                'id'=>$store_id,
                'name'=>$store_detail->getName(),
                'business_name'=>$store_detail->getBusinessName(),
                'email'=>$store_detail->getEmail(),
                'description'=>$store_detail->getDescription(),
                'phone'=>$store_detail->getPhone(),
                'legal_status'=>$store_detail->getLegalStatus(),
                'business_type'=>$store_detail->getBusinessType(),
                'business_country'=>$store_detail->getBusinessCountry(),
                'business_region'=>$store_detail->getBusinessRegion(),
                'business_city'=>$store_detail->getBusinessCity(),
                'business_address'=>$store_detail->getBusinessAddress(),
                'zip'=>$store_detail->getZip(),
                'province'=>$store_detail->getProvince(),
                'vat_number'=>$store_detail->getVatNumber(),
                'iban'=>$store_detail->getIban(),
                'map_place'=>$store_detail->getMapPlace(),
                'latitude'=>$store_detail->getLatitude(),
                'longitude'=>$store_detail->getLongitude(),
                'parent_store_id'=>$store_detail->getParentStoreId(), //for parent store
                'is_active'=>(int)$store_detail->getIsActive(),
                'is_allowed'=>(int)$store_detail->getIsAllowed(),
                'created_at'=>$store_detail->getCreatedAt(),
            );
            $current_store_profile_image_id = $store_detail->getStoreImage();
            $store_profile_image_path = '';
            $store_profile_image_thumb_path = '';
            $store_profile_image_cover_thumb_path = '';
            if (!empty($current_store_profile_image_id)) {
                    $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                                         ->find($current_store_profile_image_id);
                    if ($store_profile_image) {
                        $album_id   = $store_profile_image->getalbumId();
                        $image_name = $store_profile_image->getimageName();
                        if (!empty($album_id)) {
                            $store_profile_image_path             = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $album_id . '/' . $image_name;
                            $store_profile_image_thumb_path       = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/'. $album_id . '/'. $image_name;
                            $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/'. $album_id . '/coverphoto/'. $image_name;
                        } else {
                            $store_profile_image_path             = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $image_name;
                            $store_profile_image_thumb_path       = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/'. $image_name;
                            $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/coverphoto/'. $image_name;
                        }
                    }
            }
            $store_data['profile_image_original'] = $store_profile_image_path;
            $store_data['profile_image_thumb']    = $store_profile_image_thumb_path;
            $store_data['cover_image_path']       = $store_profile_image_cover_thumb_path;
            $shop_affiliates_array[]              = $store_data;
        }
        $data['affiliates'] = $shop_affiliates_array;
        
        //if affiliates shop are 0 then count is also zero
        if (count($data['affiliates']) > 0) {
            //count of shop affiliates.
            $shop_affiliates_count = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationShop')                   
                                        ->findUserAffiliationShopsCount($user_id);
        }
        $data['count']      = $shop_affiliates_count;
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data);
        exit;
    }

    /**
     * Finding the count of affiliated for a shop done by users.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function postShopaffiliationcountsAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj      = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data      = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $shop_affiliates_count = 0; //initialize the count by 0.
        
        //finding the entity manager object.
        $em = $this->getDoctrine()->getManager();

        //count of shop affiliates.
        $shop_affiliates_count = $em->getRepository('AffiliationAffiliationManagerBundle:AffiliationShop')                   
                                    ->findUserAffiliationShopsCount($user_id);
        $data['count']         = $shop_affiliates_count;
        $final_data_count =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($final_data_count);
        exit;
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
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path.'/'.$aws_bucket;
        return $full_path;
    }
    /**
     * temp fosusertemp table
     */
    public function postTempfosusersAction(Request $request) {
        exit;
        //get entity manager object
        $dm = $this->getDoctrine()->getManager();
        $limit = 49999;
        //fire the query in User Repository
        $results = $dm
                ->getRepository('UserManagerSonataUserBundle:User')
                ->getUserForTemp($limit);
        $user_service = $this->get('user_object.service');
        $users_array = array();
        if($results){
            foreach ($results as $result) {
                $user_id = $result['id']; 
                $user_email = $result['email'];
                $firstname = $result['firstname'];
                $lastname = $result['lastname'];
                $random_num = substr(number_format(time() * rand(),0,'',''),0,6);
                $md5pass = md5($random_num);
                $sql = "INSERT INTO temp_fos_user (email, plainpassword,password,user_id,firstname,lastname) VALUES ('$user_email', '$random_num','$md5pass','$user_id','$firstname','$lastname')";
                $stmt = $dm->getConnection()->prepare($sql);
                $result = $stmt->execute();
            }
        }
        
        $data_res =array("status"=>"success");
        echo json_encode($data_res);
        exit;
    }
    /**
     * temp change value in fos_user_user
     */
    public function postChangfosuserusersAction(Request $request) {
        exit;
        //get entity manager object
        $dm = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM temp_fos_user";
        $stmt = $dm->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        if($results){
            foreach($results as $result) {
               $userManager = $this->container->get('fos_user.user_manager');
               $user_id = $result['user_id'];
               $email = $result['email'];
               $plainpassword = $result['plainpassword'];
               $password = $result['password'];
               $firstname = $result['firstname'];
               $lastname = $result['lastname'];
               $user_result = $dm
                        ->getRepository('UserManagerSonataUserBundle:User')
                        ->findOneBy(array('id' => $user_id));
               if($user_result){
                   $user_result->setPassword($password);
                   $userManager->updateUser($user_result);
                   $sql = "UPDATE fos_user_user SET salt= '' WHERE id='$user_id'";
                   $stmt = $dm->getConnection()->prepare($sql);
                   $result = $stmt->execute();
               }
            }
        }
        $data_res =array("status"=>"success");
        echo json_encode($data_res);
        exit;
       
    }
    
    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    
    public function postSendmailusersAction(Request $request){
        exit;
        $dm = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM temp_fos_user";
        $stmt = $dm->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        if($results){
            foreach($results as $result) {
                $user_id = $result['user_id'];
                $email = $result['email'];
                $password = $result['plainpassword'];
                $sixthcontinent_admin_email = $this->container->getParameter('sixthcontinent_admin_email');
                $mail_sub = "Cambio Password";
                $mail_body = "La tua password Ã¨ stata modificata da SixthContinent con la seguente .<br />Password : $password<br/> Ti chiediamo di accedere al sito con la seguente password e cambiarla per ragioni di sicurezza.";
                $body = file_get_contents("https://www.sixthcontinent.com/mail/noreply/mail.html");
                $body =str_replace('%body%',$mail_body,$body);
                
                $sixthcontinent_admin_email =  array(
                   'smtp@sixthcontinent.org' => 'SixthContinent'
                );
               
                $notification_msg = \Swift_Message::newInstance()
                    ->setSubject($mail_sub)
                    ->setFrom($sixthcontinent_admin_email)
                    ->setTo(array($email))
                    ->setBody($body, 'text/html');
                $this->container->get('mailer')->send($notification_msg);
                $sql1 = "INSERT INTO check_email (user_id) VALUES ('$user_id')";
                $stmt1 = $dm->getConnection()->prepare($sql1);
                $result1 = $stmt1->execute();
            }
        }
        $data_res =  array("status"=>"success");
        echo json_encode($data_res);
        exit;
    }
    
    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postResetpasscitizenusersAction(Request $request){
        exit;
        $dm = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM temp_fos_user";
        $stmt = $dm->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $arr_not_sccess = array();
        if($results){
            foreach($results as $result) {
                $user_email = $result['email'];
                $user_password = $result['password'];
                $firstname = $result['firstname'];
                $lastname = $result['lastname'];
                $user_id = $result['user_id'];
                 try{
//                        $user_email = 'ankur1914@yahoo.com';
//                        $user_password = 'e10adc3949ba59abbe56e057f20f883e';
//                        $firstname = 'sunil';
//                        $lastname = 'thakur';
//                        $user_id = 23928;
//                        
                        
                        $curl_obj = $this->container->get("store_manager_store.curl");

                        $env = $this->container->getParameter('kernel.environment');
//                        //if($env == 'dev'){ 
//                            $url = $this->container->getParameter('shopping_plus_get_client_url_test');
//                            $shopping_plus_username = $this->container->getParameter('social_bees_username_test');
//                            $shopping_plus_password =$this->container->getParameter('social_bees_password_test');

                        //} else {
                            $url = $this->container->getParameter('shopping_plus_get_client_url_prod');
                            $shopping_plus_username = $this->container->getParameter('social_bees_username_prod');
                            $shopping_plus_password =$this->container->getParameter('social_bees_password_prod');  
                        //}

                        $request_data = array('o'=>'CLIENTEUPDATE',
                            'u'=>$shopping_plus_username,
                            'p'=>$shopping_plus_password,
                            'V01'=> (int)$user_id,
                            'V02'=>$firstname,
                            'V03'=>$lastname,
                            'V04'=>$user_email,
                            'V05'=>'',
                            'V06'=>$user_email,
                            'V07'=>$user_password,
                            'V08'=>'',
                            'V09'=>'N'
                        );
                        $curl_obj->shoppingplusCitizenRemoteServer($request_data,$url);
                }catch (\Exception $e) {
                    $arr_not_sccess[] = $user_id;
                }
               
            }
        }
        
        $data_res =  array('status'=>'success','user_not_work'=>$arr_not_sccess);
        echo json_encode($data_res);
        exit;
        
    }
    
     /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postResetpassshopsusersAction(Request $request){
        exit;
         $dm = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM temp_fos_user";
        $stmt = $dm->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $arr_not_sccess = array();
        if($results){
            foreach($results as $result) {
                $user_email = $result['email'];
                $user_password = $result['password'];
                $firstname = $result['firstname'];
                $lastname = $result['lastname'];
                $user_id = $result['user_id'];
                 try{
                       
//                        
//                        $user_email = 'ankur1914@yahoo.com';
//                        $user_password = 'e10adc3949ba59abbe56e057f20f883e';
//                        $firstname = 'sunil';
//                        $lastname = 'thakur';
//                        $user_id = 23928;
//                        
                        
                        $curl_obj = $this->container->get("store_manager_store.curl");

                        $env = $this->container->getParameter('kernel.environment');
                        if($env == 'dev'){ 
                            $url = $this->container->getParameter('shopping_plus_get_client_url_test');
                            $shopping_plus_username = $this->container->getParameter('social_bees_username_test');
                            $shopping_plus_password =$this->container->getParameter('social_bees_password_test');

                        } else {
                            $url = $this->container->getParameter('shopping_plus_get_client_url_prod');
                            $shopping_plus_username = $this->container->getParameter('social_bees_username_prod');
                            $shopping_plus_password =$this->container->getParameter('social_bees_password_prod');  
                        }

                        $request_data = array('o'=>'CLIENTEUPDATE',
                            'u'=>$shopping_plus_username,
                            'p'=>$shopping_plus_password,
                            'V01'=> (int)$user_id,
                            'V02'=>$firstname,
                            'V03'=>$lastname,
                            'V04'=>$user_email,
                            'V05'=>'',
                            'V06'=>$user_email,
                            'V07'=>$user_password,
                            'V08'=>'',
                            'V09'=>'N'
                        );
                        $curl_obj->shoppingplusCitizenRemoteServer($request_data,$url);
                }catch (\Exception $e) {
                    $arr_not_sccess[] = $user_id;
                }
               
            }
        }
          $data_res = array('status'=>'success','user_not_work'=>$arr_not_sccess);
        echo json_encode($data_res);
        exit;
        
    }
    
    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postFillshoptemptablesAction(Request $request){
        exit;
        $dm = $this->getDoctrine()->getManager();
        $limit_shop = 49999;
        $sql = "SELECT * FROM Store WHERE id >$limit_shop";
        $stmt = $dm->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $arr_not_sccess = array();
        if($results){
            foreach($results as $result) {
                $store_id = $result['id'];
                $sql_get_pass = "SELECT password from fos_user_user where id=(select user_id from UserToStore where store_id='$store_id')";
                $stmt_get = $dm->getConnection()->prepare($sql_get_pass);
                $stmt_get->execute();
                $results_get = $stmt_get->fetchAll();
                $password = $results_get[0]['password'];
                $sql_insert = "INSERT INTO temp_store (store_id,description,email,phone,business_name,legal_status,business_type,business_country,business_region,business_city,business_address,zip,province,vat_number) SELECT id,description,email,phone,business_name,legal_status,business_type,business_country,business_region,business_city,business_address,zip,province,vat_number FROM Store where id ='$store_id' ";
                $stmt_insert = $dm->getConnection()->prepare($sql_insert);
                $result_insert = $stmt_insert->execute();
                
                $sql_update = "UPDATE temp_store set md5password = '$password' where store_id = '$store_id'";
                $stmt_update = $dm->getConnection()->prepare($sql_update);
                $result_update = $stmt_update->execute();
                
            }
        }
          $data_res =  array('status'=>'success');
        echo json_encode($data_res);
        exit;
    }
    
    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postMakeshopentriesAction(Request $request){
        exit;
        $dm = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM temp_store";
        $stmt = $dm->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $arr_not_sccess = array();
        if($results){
            foreach($results as $result) {
               
                $curl_obj = $this->container->get("store_manager_store.curl");            
                $env = $this->container->getParameter('kernel.environment');
//                if($env == 'dev'){ 
//                   $url = $this->container->getParameter('shopping_plus_get_client_url_test');
//                   $shopping_plus_username = $this->container->getParameter('social_bees_username_test');
//                   $shopping_plus_password =$this->container->getParameter('social_bees_password_test');
//
//                } else {
                    $url = $this->container->getParameter('shopping_plus_get_client_url_prod');
                    $shopping_plus_username = $this->container->getParameter('social_bees_username_prod');
                    $shopping_plus_password =$this->container->getParameter('social_bees_password_prod');  
               // }
             
                $request_data = array('o'=>'PDVUPDATE',
                        'u'=>$shopping_plus_username,
                        'p'=>$shopping_plus_password,
                        'V01'=>$result['store_id'],
                        'V02'=>$result['legal_status'],      
                        'V03'=>$result['business_address'],
                        'V04'=>$result['zip'],
                        'V05'=>$result['business_city'],
                        'V06'=>$result['province'],
                        'V07'=>$result['phone'],
                        'V08'=>$result['email'],
                        'V09'=>$result['description'],
                        'V10'=>$result['vat_number'],    //vat_number ( this should be unique)
                        'V11'=>$result['md5password'],
                        'V13'=>'N',
                        'V14'=>0
                    );
                    try{
                      $obj =  $curl_obj->shoppingplusCitizenRemoteServer($request_data,$url);  
                    } catch (\Exception $ex) {
                        $arr_not_sccess[] = $result['store_id'];
                    }
                     
            }
        }
        $data_res =  array('status'=>'success','store_not'=>$arr_not_sccess);
        echo json_encode($data_res);
        exit;
          
    }
    
     /**
     * check referral id is valid or not
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return json array
     */
    public function checkreferralidAction(Request $request) {
        $data = array();
        //Code start for getting the request
        $freq_obj      = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id','type');
        $data      = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        $user_id = $object_info->user_id;
        $type = $object_info->type;
        $type_arr = array(1,2,3);
        
        if(!in_array($type,$type_arr)){
            $data_res =  array('code'=>100, 'message'=>'FAILURE','data'=>$data);
            echo json_encode($data_res);
            exit;
        }
        
        //get entity manager object
        $em = $this->container->get('doctrine')->getManager();
        /*
        if($type == 1){
             $citizenuser = $em
                        ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                        ->checkActiveCitizen($user_id);
              $brokeruser = $em
                        ->getRepository('UserManagerSonataUserBundle:BrokerUser')
                        ->findOneBy(array('userId' => $user_id, 'isActive' => 1));
            if($citizenuser){
                $data_res =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($data_res);
                exit;
            }else if($brokeruser){
                $data_res =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($data_res);
                exit;
            }else{
                $data_res =  array('code'=>100, 'message'=>'FAILURE','data'=>$data);
                echo json_encode($data_res);
                exit;
            }
        }
        
        if($type == 2 || $type == 3){
            $brokeruser = $em
                        ->getRepository('UserManagerSonataUserBundle:BrokerUser')
                        ->findOneBy(array('userId' => $user_id, 'isActive' => 1));
            if($brokeruser){
                $data_res =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($data_res);
                exit;
            }else{
                $data_res =  array('code'=>100, 'message'=>'FAILURE','data'=>$data);
                echo json_encode($data_res);
                exit;
            }
        }
        */
        if($type == 1 || $type == 2 || $type == 3){
            $citizenuser = $em
                        ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                        ->checkActiveCitizen($user_id);
            if($citizenuser){
                $data_res =  array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($data_res);
                exit;
            }else{
                $data_res =  array('code'=>100, 'message'=>'FAILURE','data'=>$data);
                echo json_encode($data_res);
                exit;
            }
        }
    }
    
    /**
    * Get linked citizens
    * @return string
    */
    public function setusermediafieldAction()
    {
        set_time_limit(0);
        ini_set('memory_limit','512M');
        // get documen manager object
        $em = $this->getDoctrine()->getManager();  
        $users_res = $em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->findAll();
        if($users_res) {
            foreach($users_res as $user_record) {
                $profile_img_id = $user_record->getProfileImg();
                $user_id = $user_record->getId();
                if($profile_img_id != '') {
                       $dm = $this->get('doctrine.odm.mongodb.document_manager');
                      
                       $group_media_res = $dm
                                    ->getRepository('MediaMediaBundle:UserMedia')
                                    ->findUserMedia($profile_img_id);
                      
                       if($group_media_res) {
                           $user_media_name = $group_media_res[0]->getName();
                           $user_album_id = $group_media_res[0]->getAlbumid();
                           
                           if($group_media_res[0]->getAlbumid() != '') {
                           $user_album_id = $group_media_res[0]->getAlbumid();
                           } else {
                               $user_album_id = 0;
                           }
                           $user_media_name = str_replace("'", "\'", $user_media_name);
                           $user_update_res = $em
                                            ->getRepository('UserManagerSonataUserBundle:User')
                                            ->updateFieldsInFos($user_id,$user_media_name,$user_album_id);
                        
                       }
                }
            }
        }
        exit('Done');
        return new Response('ok');
    }
}
