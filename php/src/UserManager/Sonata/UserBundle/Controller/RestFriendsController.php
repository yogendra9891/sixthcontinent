<?php

namespace UserManager\Sonata\UserBundle\Controller;

use UserManager\Sonata\UserBundle\Entity\UserConnection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;
use Newsletter\NewsletterBundle\Entity\Newslettertrack;
use Newsletter\NewsletterBundle\Entity\Template;
use StoreManager\StoreBundle\Controller\ShoppingplusController;
use Notification\NotificationBundle\Document\UserNotifications;
use UserManager\Sonata\UserBundle\Entity\UserFollowers;
use UserManager\Sonata\UserBundle\Document\Group;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use SixthContinent\SixthContinentConnectBundle\Model\SixthcontinentConnectConstentInterface;

class RestFriendsController extends FOSRestController implements SixthcontinentConnectConstentInterface {

    protected $store_media_path = '/uploads/documents/stores/gallery/';
    protected $request_type_val = 1;

    /**
     * Search the all users of the app
     * @param Request $request
     * @return array;
     */
    public function postSearchusersAction(Request $request) {
        //initilise the array
        $users_array = array();
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

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        //parameter check end
        //end to get request object
        //get user id
        $user_id = $de_serialize['user_id'];

        if ($user_id == "") {
            $res_data = array('code' => 111, 'message' => 'USER_ID_REQUIRED', 'data' => array());
            return $res_data;
        }
        //check parameter friend name
        $friend_name = "";
        if (isset($de_serialize['friend_name'])) {
            $friend_name = $de_serialize['friend_name'];
        }
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
            $offset = $de_serialize['limit_start'];
            $limit = $de_serialize['limit_size'];

            //set dafault limit
            if ($limit == "") {
                $limit = 20;
            }

            //set default offset
            if ($offset == "") {
                $offset = 0;
            }
        } else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get entity manager object
        $dm = $this->getDoctrine()->getManager();

        //fire the query in User Repository
        $results = $dm
                ->getRepository('UserManagerSonataUserBundle:User')
                ->searchByUsername($friend_name, $offset, $limit,$user_id);

        $user_service = $this->get('user_object.service');
        $user_info = array();
        foreach ($results as $result) {
            $user_id = $result->getId();
            $user_name = $result->getUsername();
            $user_email = $result->getEmail();
            $user_info = $user_service->UserObjectService($user_id);
            $users_array[] = array('user_id' => $user_id, 'user_info' => $user_info, 'user_name' => $user_name, 'user_email' => $user_email);
        }

        //fire the query in User Repository
//        $results_count = $dm
//                ->getRepository('UserManagerSonataUserBundle:User')
//                ->searchByUsernameCount($friend_name);
        $results_count = 12;
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('users' => $users_array, 'count' => $results_count));

        echo json_encode($resp_data);
        exit();
    }

    /**
     * Search the all users of the app
     * @param Request $request
     * @return array;
     */
    public function postSearchallprofilesAction(Request $request) {
        //initilise the array
        $users_array = array();
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
        $required_parameter = array('user_id');
        $data = array();
        $post_feed_service = $this->container->get('post_feeds.postFeeds');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        //parameter check end
        //end to get request object
        //get user id
        $user_id = $de_serialize['user_id'];

        if ($user_id == "") {
            $res_data = array('code' => 111, 'message' => 'USER_ID_REQUIRED', 'data' => array());
            return $res_data;
        }
        //check parameter friend name
        $friend_name = "";
        if (isset($de_serialize['friend_name'])) {
            $friend_name = $de_serialize['friend_name'];
        }
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
            $offset = $de_serialize['limit_start'];
            $limit = $de_serialize['limit_size'];

            //set dafault limit
            if ($limit == "") {
                $limit = 16;
            }

            //set default offset
            if ($offset == "") {
                $offset = 0;
            }
        } else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        $aws_link = $this->container->getParameter('aws_base_path');
        $aws_bucket= $this->container->getParameter('aws_bucket');
        $aws_path  = $aws_link.'/'.$aws_bucket;
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        //serach group in mongo db
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $group_object = $dm->getRepository('UserManagerSonataUserBundle:Group')
                ->getSearchGroupAll($friend_name, 0, 4);

        //get social project for the search text
        $social_projects = $dm->getRepository('PostFeedsBundle:SocialProject')
                             ->getSearchedProject('', $friend_name, 0, 4, '', '' , 1);

        //get social project for the search text
        $applications= $em->getRepository('SixthContinentConnectBundle:Application')
                             ->searchApplicationByText($friend_name, 0, 4);

        // Get voucher
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $repo_commercial_offer = $em->getRepository("CommercialPromotionBundle:CommercialPromotion");
        $voucher_detail = $repo_commercial_offer->searchCommercialPromotion($friend_name , $dm);


       //get tamoil offers for the search text
        // $tamOfffers = $em->getRepository('SixthContinentConnectBundle:Offer')
        //                      ->searchByText($friend_name, 0, 4);


        //limit for user and store
        $limit_shop_user = $limit - (count($group_object) + count($social_projects) + count($applications)+ count($voucher_detail));

        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->searchByAllProfiles($friend_name, $offset, $limit_shop_user, $aws_path);


        //getting group ids
        $groupIds = array_map(function($o) {
            return $o->getId();
        }, $group_object);


        $membershipStatus = $dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->getMemberStatus($groupIds, $user_id);

        $group_images = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                ->getGroupMedia($groupIds);


        // get offer detail

        $result_group = array();
        $media_object;

        //loop for getting the the clubs information
        foreach ($group_object as $key => $value) {
            $result_group[$key]['id'] = $value->getId();
            $result_group[$key]['name'] = $value->getTitle();
            $result_group[$key]['business_name'] = '';
            $result_group[$key]['first_name'] = '';
            $result_group[$key]['last_name'] = '';
            $result_group[$key]['email'] = '';
            $result_group[$key]['status'] = $value->getGroupStatus();
            $result_group[$key]['album_id'] = '';
            $result_group[$key]['profile_image'] = '';
            $result_group[$key]['thumb_path'] = '';
            $result_group[$key]['is_member'] = $membershipStatus[$result_group[$key]['id']]['is_member'];
            foreach ($group_images as $group_image) {
                if ($group_image->getGroupId() == $result_group[$key]['id']) {
                    $result_group[$key]['album_id'] = $group_image->getAlbumid();
                    $album_id = '';
                    if($result_group[$key]['album_id'] != '') {
                        $album_id = '/'.$result_group[$key]['album_id'];
                    }
                    $result_group[$key]['profile_image'] = $group_image->getMediaName();
                    $result_group[$key]['thumb_path'] = 'https://s3.amazonaws.com/sixthcontinent/uploads/groups/thumb/'.$result_group[$key]['id'].$album_id.'/'.$group_image->getMediaName();
                }
            }
            $result_group[$key]['type'] = 'G';
        }

        //loop for getting the social project infoamtion
        $result_project = array();
        $cover_info = array();
        foreach($social_projects as $key => $project) {
            $cover_info = $post_feed_service->getCoverImageinfo($project->getCoverImg());
            $result_project[$key]['id'] = $project->getId();
            $result_project[$key]['name'] = $project->getTitle();
            $result_project[$key]['business_name'] = $project->getDescription();
            $result_project[$key]['first_name'] = '';
            $result_project[$key]['last_name'] = '';
            $result_project[$key]['email'] = $project->getEmail();
            $result_project[$key]['status'] = $project->getStatus();
            $result_project[$key]['album_id'] = '';
            $result_project[$key]['profile_image'] = isset($cover_info['ori_image']) ? $cover_info['ori_image'] : '';;
            $result_project[$key]['thumb_path'] = isset($cover_info['thum_image']) ? $cover_info['thum_image'] : '';
            $result_project[$key]['is_member'] = '';
            $result_project[$key]['type'] = 'SP';
        }

        //loop for getting the social project infoamtion
        $result_appliaction = array();
        foreach($applications as $key => $application) {
            $result_appliaction[$key]['id'] = $application['application_id'];
            $result_appliaction[$key]['name'] = $application['application_name'];
            $result_appliaction[$key]['business_name'] = $application['business_name'];
            $result_appliaction[$key]['first_name'] = '';
            $result_appliaction[$key]['last_name'] = '';
            $result_appliaction[$key]['email'] = '';
            $result_appliaction[$key]['status'] = '';
            $result_appliaction[$key]['album_id'] = '';
            $result_appliaction[$key]['profile_image'] = '';
            $result_appliaction[$key]['thumb_path'] = '';
            $result_appliaction[$key]['country'] = '';
            $result_appliaction[$key]['city'] = '';
            $result_appliaction[$key]['type'] = self::SEARCH_TYPE;
        }

        // $result_offers = array();
        // foreach($tamOfffers as $key => $_offer) {
        //     $result_offers[$key]['id'] = $_offer['id'];
        //     $result_offers[$key]['name'] = $_offer['name'];
        //     $result_offers[$key]['business_name'] = '';
        //     $result_offers[$key]['first_name'] = '';
        //     $result_offers[$key]['last_name'] = '';
        //     $result_offers[$key]['email'] = '';
        //     $result_offers[$key]['status'] = '';
        //     $result_offers[$key]['album_id'] = '';
        //     $result_offers[$key]['profile_image'] = '';
        //     $result_offers[$key]['thumb_path'] = !empty($_offer['imageThumb']) ? $_offer['imageThumb'] : '';
        //     $result_offers[$key]['country'] = '';
        //     $result_offers[$key]['city'] = '';
        //     $result_offers[$key]['type'] = self::SEARCH_OFFER_TAMOIL;
        // }

        $result_voucher = array();

        if(!empty($voucher_detail)){

            foreach($voucher_detail as $key => $voucher) {

                $result_voucher[$key]['id'] = $voucher['id'];
                $result_voucher[$key]['name'] = $voucher[0]['description'];
                $result_voucher[$key]['sellerid'] = $voucher['sellerId'];
                $result_voucher[$key]['business_name'] = '';
                $result_voucher[$key]['first_name'] = '';
                $result_voucher[$key]['last_name'] = '';
                $result_voucher[$key]['email'] = '';
                $result_voucher[$key]['status'] = '';
                $result_voucher[$key]['album_id'] = '';
                $result_voucher[$key]['profile_image'] = '';
                $result_voucher[$key]['thumb_path'] = $voucher[0]['defaultImg'];
                $result_voucher[$key]['country'] = '';
                $result_voucher[$key]['city'] = '';
                if($voucher[0]['promotionType'] == "voucher"){
                   $voucher[0]['promotionType'] = self::SEARCH_OFFER_TAMOIL;
                }
                $result_voucher[$key]['type'] = strtoupper($voucher[0]['promotionType']);
            }
        }

        //preparing the final array
        $final_search = array_merge($results, $result_group,$result_project,$result_appliaction,$result_voucher);
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $final_search);
        echo json_encode($resp_data);
        exit;
    }

    /**
     * Search the all users of the app
     * @param Request $request
     * @return array;
     */
    public function postGetallsearchrecordsAction(Request $request) {
        //initilise the array
        $users_array = array();
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

        $required_parameter = array('user_id','search_text','search_type');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $search_type_array=array('1','2','3','4','5','6','7');
        //search type:1:user,2:store,3:club,4:user+store+club


        //parameter check end
        //end to get request object
        //get user id
        $user_id = $de_serialize['user_id'];
        $search_text = $de_serialize['search_text'];
        $search_type = $de_serialize['search_type'];

         /** check limit start **/
        if(isset($object_info->limit_start) && $object_info->limit_start !='') {
            $limit_start   = $object_info->limit_start;
        }else{
            $limit_start = 0;
        }

         /** check limit size **/
        if(isset($object_info->limit_size) && $object_info->limit_size !='') {
            $limit_size   = $object_info->limit_size;
        }else {
            $limit_size   = 20;
        }


        //checking if serach type is valid or not
        if(!in_array($de_serialize['search_type'],$search_type_array))
        {
             return array('code' => 100, 'message' => 'SEARCH TYPE IS INVALID', 'data' => $data);
        }


        if ($user_id == "") {
            $res_data = array('code' => 111, 'message' => 'USER_ID_REQUIRED', 'data' => array());
            return $res_data;
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        $aws_link = $this->container->getParameter('aws_base_path');
        $aws_bucket= $this->container->getParameter('aws_bucket');
        $aws_path  = $aws_link.'/'.$aws_bucket;
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        //serach group in mongo db
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $post_feed_service = $this->container->get('post_feeds.postFeeds');

        $user_res=array();
        $store_res=array();
        $result_group=array();
        $result_project = array();
        $result_appliaction = array();
        $total_user_count=0;
        $total_store_count=0;
        $group_count=0;
        $social_project_count=0;
        $application_count=0;
        $tamOffersCount=0;
        $result_offers = array();

        switch($search_type){
            case 1:
                $user_res = $em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->searchAllUserProfiles($search_text, $limit_start, $limit_size, $aws_path);
                $total_user_count=$em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->countsearchUserProfiles($search_text, $aws_path);
                break;
            case 2:
                $store_res = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->searchallStores($search_text, $limit_start, $limit_size, $aws_path);
                $total_store_count = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->countallStoressearch($search_text, $aws_path);

                break;
            case 3:
                $group_object=$dm->getRepository('UserManagerSonataUserBundle:Group')
                    ->getSearchGroupAll($search_text,$limit_start,$limit_size);
                $group_count=$dm->getRepository('UserManagerSonataUserBundle:Group')
                    ->getSearchGroupAllcount($search_text);
                  //getting group ids
                $groupIds = array_map(function($o) {
                    return $o->getId();
                }, $group_object);
                $dm = $this->get('doctrine.odm.mongodb.document_manager');
                $group_images = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                ->getGroupMedia($groupIds);
                $result_group = array();
                $media_object;

                foreach ($group_object as $key => $value) {
                    $result_group[$key]['id'] = $value->getId();
                    $result_group[$key]['name'] = $value->getTitle();
                    $result_group[$key]['business_name'] = '';
                    $result_group[$key]['first_name'] = '';
                    $result_group[$key]['last_name'] = '';
                    $result_group[$key]['email'] = '';
                    $result_group[$key]['status'] = $value->getGroupStatus();
                    $result_group[$key]['album_id'] = '';
                    $result_group[$key]['profile_image'] = '';
                    $result_group[$key]['thumb_path'] = '';
                    foreach ($group_images as $group_image) {
                        if ($group_image->getGroupId() == $result_group[$key]['id']) {
                            $result_group[$key]['album_id'] = $group_image->getAlbumid();
                            $album_id = '';
                            if($result_group[$key]['album_id'] != '') {
                                $album_id = '/'.$result_group[$key]['album_id'];
                            }
                            $result_group[$key]['profile_image'] = $group_image->getMediaName();
                            $result_group[$key]['thumb_path'] = 'https://s3.amazonaws.com/sixthcontinent/uploads/groups/thumb/'.$result_group[$key]['id'].$album_id.'/'.$group_image->getMediaName();
                        }
                    }
                    $result_group[$key]['type'] = 'G';
                }
                break;
            case 4:
                $user_res = $em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->searchAllUserProfiles($search_text, $limit_start, $limit_size, $aws_path);
                $total_user_count=$em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->countsearchUserProfiles($search_text, $aws_path);

                 $store_res = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->searchallStores($search_text, $limit_start, $limit_size, $aws_path);
                 $total_store_count = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->countallStoressearch($search_text, $aws_path);



                 $group_object=$dm->getRepository('UserManagerSonataUserBundle:Group')
                    ->getSearchGroupAll($search_text,$limit_start,$limit_size);
                 $group_count=$dm->getRepository('UserManagerSonataUserBundle:Group')
                    ->getSearchGroupAllcount($search_text,$limit_start,$limit_size);


                  //getting group ids
                $groupIds = array_map(function($o) {
                    return $o->getId();
                }, $group_object);
                $dm = $this->get('doctrine.odm.mongodb.document_manager');
                $group_images = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                ->getGroupMedia($groupIds);
                $result_group = array();
                $media_object;

                foreach ($group_object as $key => $value) {
                    $result_group[$key]['id'] = $value->getId();
                    $result_group[$key]['name'] = $value->getTitle();
                    $result_group[$key]['business_name'] = '';
                    $result_group[$key]['first_name'] = '';
                    $result_group[$key]['last_name'] = '';
                    $result_group[$key]['email'] = '';
                    $result_group[$key]['status'] = $value->getGroupStatus();
                    $result_group[$key]['album_id'] = '';
                    $result_group[$key]['profile_image'] = '';
                    $result_group[$key]['thumb_path'] = '';
                    foreach ($group_images as $group_image) {
                        if ($group_image->getGroupId() == $result_group[$key]['id']) {
                            $result_group[$key]['album_id'] = $group_image->getAlbumid();
                            $album_id = '';
                            if($result_group[$key]['album_id'] != '') {
                                $album_id = '/'.$result_group[$key]['album_id'];
                            }
                            $result_group[$key]['profile_image'] = $group_image->getMediaName();
                            $result_group[$key]['thumb_path'] = 'https://s3.amazonaws.com/sixthcontinent/uploads/groups/thumb/'.$result_group[$key]['id'].$album_id.'/'.$group_image->getMediaName();
                        }
                    }
                    $result_group[$key]['type'] = 'G';
                }


                break;

             case 5:
                //getting the list of the socail project
                $social_projects = $dm->getRepository('PostFeedsBundle:SocialProject')
                        ->getSearchedProject('', $search_text, $limit_start, $limit_size, '', '', 1);
                $social_project_count = $dm->getRepository('PostFeedsBundle:SocialProject')
                        ->getSearchedProjectCount($search_text, '', '', '');
                //loop for getting the social project infoamtion
                $result_project = array();
                $cover_info = array();
                foreach ($social_projects as $key => $project) {
                    $cover_info = $post_feed_service->getCoverImageinfo($project->getCoverImg());
                    $result_project[$key]['id'] = $project->getId();
                    $result_project[$key]['name'] = $project->getTitle();
                    $result_project[$key]['business_name'] = $project->getDescription();
                    $result_project[$key]['first_name'] = '';
                    $result_project[$key]['last_name'] = '';
                    $result_project[$key]['email'] = $project->getEmail();
                    $result_project[$key]['status'] = $project->getStatus();
                    $result_project[$key]['album_id'] = '';
                    $result_project[$key]['profile_image'] = isset($cover_info['ori_image']) ? $cover_info['ori_image'] : '';
                    ;
                    $result_project[$key]['thumb_path'] = isset($cover_info['thum_image']) ? $cover_info['thum_image'] : '';
                    $result_project[$key]['is_member'] = '';
                    $result_project[$key]['type'] = 'SP';
                }
                break;

                case 6:
                //getting the list of the socail project
                //get social project for the search text
                $applications= $em->getRepository('SixthContinentConnectBundle:Application')
                             ->searchApplicationByText($search_text, $limit_start, $limit_size);

                $application_count = $em->getRepository('SixthContinentConnectBundle:Application')
                        ->searchApplicationByTextCount($search_text);

                //loop for getting the social project infoamtion
                $result_appliaction = array();
                foreach ($applications as $key => $application) {
                    $result_appliaction[$key]['id'] = $application['application_id'];
                    $result_appliaction[$key]['name'] = $application['application_name'];
                    $result_appliaction[$key]['business_name'] = $application['business_name'];
                    $result_appliaction[$key]['first_name'] = '';
                    $result_appliaction[$key]['last_name'] = '';
                    $result_appliaction[$key]['email'] = '';
                    $result_appliaction[$key]['status'] = '';
                    $result_appliaction[$key]['album_id'] = '';
                    $result_appliaction[$key]['profile_image'] = '';
                    $result_appliaction[$key]['thumb_path'] = '';
                    $result_appliaction[$key]['country'] = '';
                    $result_appliaction[$key]['city'] = '';
                    $result_appliaction[$key]['type'] = self::SEARCH_TYPE;
                }
                break;
                case 7:
                    $tamOffers= $em->getRepository('SixthContinentConnectBundle:Offer')
                                 ->searchByText($search_text, $limit_start, $limit_size);

                    $tamOffersCount = $em->getRepository('SixthContinentConnectBundle:Offer')
                            ->searchByTextCount($search_text);
                    //loop for getting the social project infoamtion
                    $result_offers = array();
                    foreach ($tamOffers as $key => $_offer) {
                        $result_offers[$key]['id'] = $_offer['id'];
                        $result_offers[$key]['name'] = $_offer['name'];
                        $result_offers[$key]['business_name'] = '';
                        $result_offers[$key]['first_name'] = '';
                        $result_offers[$key]['last_name'] = '';
                        $result_offers[$key]['email'] = '';
                        $result_offers[$key]['status'] = '';
                        $result_offers[$key]['album_id'] = '';
                        $result_offers[$key]['profile_image'] = '';
                        $result_offers[$key]['thumb_path'] = !empty($_offer['image']) ? $_offer['image'] : 'https://s3.amazonaws.com/sixthcontinent/uploads/logo/tamoil89x89.jpg';
                        $result_offers[$key]['country'] = '';
                        $result_offers[$key]['city'] = '';
                        $result_offers[$key]['type'] = self::SEARCH_OFFER_TAMOIL;
                    }
                break;
        }
        $total_count=$total_user_count+$total_store_count+$group_count + $social_project_count + $application_count + $tamOffersCount;
        $final_search['results'] = array_merge($user_res, $store_res, $result_group,$result_project,$result_appliaction, $result_offers);
        $final_search['total_count']=$total_count;
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $final_search);
        echo json_encode($resp_data);
        exit;
    }
    public function get_all_data($item2, $key) {
        echo "$key. $item2<br />\n";
        die;
    }

    /**
     * Search the all users of the app
     * @param Request $request
     * @return array;
     */
    public function postSearchfriendsAction(Request $request) {
        //initilise the array
        $users_array = array();
        $data = array();

        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        //parameter check end
        //get user id
        $user_id = $count_user_id = $de_serialize['user_id'];

        if ($de_serialize['friend_name'] == "") {
            $friend_name = "";
        } else {
            $friend_name = $de_serialize['friend_name'];
        }
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
            $offset = $de_serialize['limit_start'];
            $limit = $de_serialize['limit_size'];

            //set dafault limit
            if ($limit == "") {
                $limit = 20;
            }

            //set default offset
            if ($offset == "") {
                $offset = 0;
            }
        } else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get entity manager object
        $dm = $this->getDoctrine()->getManager();

        //fire the query in User Repository
        $response = $dm
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllFriendsType($user_id, $friend_name, $offset, $limit);

        $userIds = array();
        foreach($response as $_result){
            array_push($userIds, $_result['user_id']);
        }
        $friendsIds = array_unique($userIds);

        $user_service = $this->get('user_object.service');
        $results = array();
        if(!empty($friendsIds)){
            $results = $user_service->MultipleUserObjectService($friendsIds);
        }
        $user_info = array();
        foreach ($results as $result) {
            $personal = 0;
            $proffesional = 0;
            foreach($response as $res){
                if($result['id'] == $res['user_id']){
                    $personal = ($personal == 0 and $res['personal_status']) ? 1 : $personal;
                    $proffesional = ($proffesional==0 and $res['professional_status']) ? 1 : $proffesional;
                }
            }
            $users_array[] = array('user_id' => $result['id'], 'user_info' => $result, 'user_name' => $result['username'], 'user_email' => $result['email'],'personal' => $personal , 'professional' => $proffesional);
        }

        //fire the query in User Repository
        $results_count = $dm
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllUserFriendsCount($user_id, $friend_name);

        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('users' => $users_array, 'count' => $results_count));

        echo json_encode($resp_data);
        exit();
    }

    /**
     * Send friend request.
     * @param Request $request
     * @return multitype:number string multitype: |multitype:string multitype:
     */
    public function postSendfriendrequestsAction(Request $request) {
        //initilise the array
        $users_array = array();
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //end to get request object
        $connectFrom = $de_serialize['user_id'];
        if ($connectFrom == "") {
            $resp_data = array('code' => '111', 'message' => 'USER_ID_REQUIRED', 'data' => array());
            return $resp_data;
        }
        $connectTo = $de_serialize['friend_id'];
        if ($connectTo == "") {
            $resp_data = array('code' => '111', 'message' => 'FRIEND_ID_IS_REQUIRED', 'data' => array());
            return $resp_data;
        }
        $msg = $de_serialize['msg'];

        // Friend Request Type
        $requestType = $de_serialize['request_type'];
        if ($requestType == "") {
            $resp_data = array('code' => '111', 'message' => 'REQUEST_TYPE_IS_REQUIRED', 'data' => array());
            return $resp_data;
        }
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        // Get connenction request already sent status

        $reqTypeArr = array();
        $resultsData = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkFriendRequestStatus($connectFrom,$connectTo);
        if(!empty($resultsData)){
            $reqTypeArr['id'] = $resultsData[0]['id'];
            $reqTypeArr['professionalRequest'] = $resultsData[0]['professionalRequest'];
            $reqTypeArr['personalRequest']     = $resultsData[0]['personalRequest'];
            $reqTypeArr['professionalStatus']  = $resultsData[0]['professionalStatus'];
            $reqTypeArr['personalStatus']      = $resultsData[0]['personalStatus'];
        } else {
            $reqTypeArr['professionalRequest'] = 0;
            $reqTypeArr['personalRequest']     = 0;
            $reqTypeArr['professionalStatus']  = 0;
            $reqTypeArr['personalStatus']      = 0;
        }

        $rqsT = $requestType==2 ? 'professional' : 'personal';
        $_haveRequested = $em
            ->getRepository('UserManagerSonataUserBundle:UserConnection')
            ->checkPendingRequestStatus($connectTo, $connectFrom, $rqsT);
        if ($_haveRequested) {
            $res_data = array('code' => 100, 'message' => 'FRIEND_REQUEST_HAS_ALREADY_RECEIVED', 'data' => $data);
            return $res_data;
        }
        // Get global variable for Personal
        $personal = $this->container->getParameter('personal');
        if($personal == $requestType) {
           $reqTypeArr['personalRequest'] = $this->request_type_val;
        }

        // Get Professional variable
        $professional = $this->container->getParameter('professional');
        if($professional == $requestType) {
            $reqTypeArr['professionalRequest'] = $this->request_type_val;
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($connectFrom);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //check if friend request is already sent to same user
        $is_friend_request_sent_alreay = $this->checkFriendRequest($connectFrom, $connectTo, $reqTypeArr);
        if ($is_friend_request_sent_alreay) {
            $resp_data = array('code' => '109', 'message' => 'FRIEND_REQUEST_HAS_ALREADY_SENT', 'data' => array());
            return $resp_data;
        }
        //check if friend request is already received
        $is_friend_request_received_alreay = $this->checkReceivedFriendRequest($connectFrom, $connectTo, $reqTypeArr);
        if ($is_friend_request_received_alreay) {
//            $resp_data = array('code' => '109', 'message' => 'FRIEND_REQUEST_HAS_ALREADY_RECEIVED', 'data' => array());
//            return $resp_data;
        }

        // Check if friend request already sent either Personal OR Professional
        if(empty($resultsData)){
            //get entity object
            $userConnection = new UserConnection();
            $userConnection->setConnectFrom($connectFrom);
            $userConnection->setConnectTo($connectTo);
            $userConnection->setMsg($msg);
            $userConnection->setStatus(0);
            $userConnection->setPersonalStatus(0);
            // Set Personal
            $personal = $this->container->getParameter('personal');

            $userConnection->setPersonalRequest(0);
            if($personal == $requestType) {
                $userConnection->setPersonalRequest($this->request_type_val);
            }
            $userConnection->setProfessionalStatus(0);
            // Set Professional
            $userConnection->setProfessionalRequest(0);
            if($professional == $requestType) {
                $userConnection->setProfessionalRequest($this->request_type_val);
            }
            $time = new \DateTime("now");
            $userConnection->setCreated($time);

            $em->persist($userConnection);
            $em->flush();
        } else {
            // Update record for friend request already sent for either Personal/Profesional
            $resultsData = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->updateFriendRequestAnother($reqTypeArr['id'], $reqTypeArr);
        }

        //send mail start
        $from_id = $connectFrom;
        $to_id = $connectTo;

        $postService = $this->container->get('post_detail.service');
        $receiver = $postService->getUserData($to_id, true);
        //get locale
        $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);

        $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
        $friend_profile_url = $this->container->getParameter('friend_profile_url'); //friend profile url

        //for email template
        $email_template_service = $this->container->get('email_template.service');

        $sender = $postService->getUserData($from_id);
        $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

        $href = $angular_app_hostname . $friend_profile_url . '/' . $from_id;
        $link = $email_template_service->getLinkForMail($href,$locale);
        //$mail_sub = sprintf($language_const_array['SENT_FRIEND_REQUEST_SUBJECT']);
        $mail_sub  = sprintf($lang_array['FRIEND_REQUEST_SENT_BODY'], ucwords($sender_name));
        $mail_body = sprintf($lang_array['FRIEND_REQUEST_SENT_BODY'], ucwords($sender_name));
        $mail_text = sprintf($lang_array['FRIEND_REQUEST_MAIL_TEXT'], ucwords($sender_name));
        $bodyData      = $mail_text."<br><br>".$link;

        // HOTFIX NO NOTIFY MAIL
        //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'FRIEND_REQUEST');
        $postService->sendUserNotifications($from_id, $to_id, "FRIEND_REQUEST", "request", $from_id, false, true);
        $resp_data = array('code' => '101', 'message' => 'FRIEND_REQUEST_SENT', 'data' => array());

        echo json_encode($resp_data);
        exit();
    }

    /**
     * Check friend request if already sent to the same user from same user.
     * @param int $connectFrom
     * @param int $connectTo
     * @return boolean
     */
    public function checkFriendRequest($connectFrom, $connectTo, $reqTypeArr) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkFriendRequest($connectFrom, $connectTo, $reqTypeArr);

        if ($results > 0) {
            //friend request already sent
            return true;
        }

        //new friend request.
        return false;
    }

    /**
     * Check received friend request.
     * @param int $connectFrom
     * @param int $connectTo
     * @return boolean
     */
    public function checkReceivedFriendRequest($connectFrom, $connectTo, $reqTypeArr) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkReceivedFriendRequest($connectFrom, $connectTo, $reqTypeArr);

        if ($results > 0) {
            //friend request already sent
            return true;
        }

        //new friend request.
        return false;
    }

    /**
     * Check received friend request.
     * @param int $connectFrom
     * @param int $connectTo
     * @return boolean
     */
    public function checkReceivedFriendRequestStatus($connectFrom, $connectTo, $reqTypeArr) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in User Repository
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkReceivedFriendRequestStatus($connectFrom, $connectTo, $reqTypeArr);

        if ($results > 0) {
            //friend request already sent
            return true;
        }

        //new friend request.
        return false;
    }

    /**
     * Response friend request
     *
     */
    public function postResponsefriendrequestsAction(Request $request) {
        $media_id = '';
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //end to get request object
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('user_id', 'friend_id', 'action','request_type');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //parameter check end
        $user_id = $de_serialize['user_id'];
        $fid = $de_serialize['friend_id'];
        $rType = $de_serialize['request_type'];
        $action = $de_serialize['action']; //if 1 for accept, 0 for deny
        $media_id ='';
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //check for request parameter
        $allowed_action = array(0, 1);
        if (!in_array($action, $allowed_action)) {
            $resp_data = array('code' => '110', 'message' => 'INVALID_ACTION_PARAMETER', 'data' => array());
            return $resp_data;
        }
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        // Get Friend Connection Status
        $reqTypeArr = array();
        $personal = $this->container->getParameter('personal');
        $professional = $this->container->getParameter('professional');
        // For reject
        if ($action == 0) {
            $_checkAllRqType = $rType == $professional ? 'professional' : ($rType == $personal ? 'persional' : 'all');
            $resultsData = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkAllFriendRequestStatus($fid,$user_id, $_checkAllRqType);
            if(empty($resultsData))
            {
                $resp_data = array('code' => '113', 'message' => 'FRIEND_REQUEST_HAS_ALREADY_REMOVED', 'data' => array());
                return $resp_data;
            }
            $reqTypeArr['id'] = $resultsData[0]['id'];
            $reqTypeArr['professionalRequest'] = $resultsData[0]['professionalRequest'];
            $reqTypeArr['personalRequest']     = $resultsData[0]['personalRequest'];
            $reqTypeArr['professionalStatus']  = $resultsData[0]['professionalStatus'];
            $reqTypeArr['personalStatus']      = $resultsData[0]['personalStatus'];
            // Get Global Variable For Personal value
            if($personal == $rType) {
               $reqTypeArr['personalStatus'] = 0;
               $reqTypeArr['personalRequest'] = 0;
            }
            // Get Global Variable For Personal value
            if($professional == $rType) {
                $reqTypeArr['professionalStatus'] = 0;
                $reqTypeArr['professionalRequest'] = 0;
            }
            // Update request for Personal/Professional record
            $results = $em
                    ->getRepository('UserManagerSonataUserBundle:UserConnection')
                    ->responseFriendRequest($user_id, $fid, $action, $reqTypeArr);
            //check current friendship status
            $friendship_result = $em->getRepository('UserManagerSonataUserBundle:UserConnection')->checkFriendShip($user_id, $fid);

            //delete friendship from applane if users are not friends of any type.
            if ($friendship_result != 1) {
                $appalne_data = $de_serialize;
                $appalne_data['register_id'] = $user_id;
                $appalne_data['friend_id']   = $fid;
                //get dispatcher object
                $event = new FilterDataEvent($appalne_data);
                $dispatcher = $this->container->get('event_dispatcher');
                $dispatcher->dispatch('citizen.deletefriend', $event);

                //remove friendship from both side.
                $appalne_data['register_id'] = $fid;
                $appalne_data['friend_id']   = $user_id;
                //get dispatcher object
                $event1 = new FilterDataEvent($appalne_data);
                $dispatcher->dispatch('citizen.deletefriend', $event1);
            }
        }
        // For Accept
        if ($action == 1) {
            $resultsData = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkFriendRequestStatus($fid,$user_id);

            $reqTypeArr['id'] = $resultsData[0]['id'];
            $reqTypeArr['professionalRequest'] = $resultsData[0]['professionalRequest'];
            $reqTypeArr['personalRequest']     = $resultsData[0]['personalRequest'];
            $reqTypeArr['professionalStatus']  = $resultsData[0]['professionalStatus'];
            $reqTypeArr['personalStatus']      = $resultsData[0]['personalStatus'];

            // Get Global Variable For Personal value

            if($personal == $rType) {
               $reqTypeArr['personalStatus'] = $this->request_type_val;
               $notifMsg = 'personal';
            }

            // Set Professional
            if($professional == $rType) {
                $reqTypeArr['professionalStatus'] = $this->request_type_val;
                $notifMsg = 'professional';
            }

            //check if friend request is already received
            $is_friend_request_received_alreay = $this->checkReceivedFriendRequestStatus($user_id, $fid, $reqTypeArr);
            if ($is_friend_request_received_alreay) {
                $resp_data = array('code' => '109', 'message' => 'FRIEND_REQUEST_HAS_ALREADY_RECEIVED', 'data' => array());
                return $resp_data;
            }
            // Update request for Personal/Professional record
            $results = $em
                    ->getRepository('UserManagerSonataUserBundle:UserConnection')
                    ->responseFriendRequest($user_id, $fid, $action, $reqTypeArr);
            // Check if either Personal/Professional request is accepted
            //update in notification table
            $msgtype = 'friend';
            $msg = 'accepted_'.$notifMsg;
            $add_notification = $this->saveUserNotification($user_id, $fid, $msgtype, $msg);

            //send mail
            $from_id = $user_id;
            $to_id = $fid;

            $postService = $this->container->get('post_detail.service');
            $receiver = $postService->getUserData($to_id, true);
            //get locale
            $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);

            $email_template_service = $this->container->get('email_template.service'); //email template service.

            $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
            $friend_profile_url = $this->container->getParameter('friend_profile_url'); //friend profile url

            $sender = $postService->getUserData($from_id);
            $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

            $postService->sendUserNotifications($sender['id'], $fid, $msgtype, $msg, $sender['id'], false, true, $sender_name);

            $href =  $angular_app_hostname.$friend_profile_url.'/'.$from_id; //href for friend profile
            $link =  $email_template_service->getLinkForMail($href,$locale); //making the link html from service
            $mail_sub = sprintf($lang_array['FRIEND_REQUEST_ACCEPTED_SUBJECT']);
            $mail_body = sprintf($lang_array['FRIEND_REQUEST_ACCEPTED_BODY'], ucwords($sender_name));
            $mail_text = sprintf($lang_array['FRIEND_REQUEST_ACCEPTED_MAIL_TEXT'], ucwords($sender_name));
            $bodyData      = $mail_text."<br><br>".$link;

            // HOTFIX NO NOTIFY MAIL
            //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'FRIEND_REQUEST');

        // call applane service
        //from login user to friend
        $appalne_data = $de_serialize;
        $appalne_data['register_id'] = $user_id;
        $appalne_data['friend_data'] = $fid;
        //get dispatcher object
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('citizen.addfriend', $event);

        //from friend to login user
        $appalne_data['register_id'] = $fid;
        $appalne_data['friend_data'] = $user_id;
        //get dispatcher object
        $event = new FilterDataEvent($appalne_data);
        $dispatcher->dispatch('citizen.addfriend', $event);
        //end of applane service
        }
        $returnArr = array();
        $returnArr['user_id'] = $user_id;
        $returnArr['friend_id'] = $fid;
        $returnArr['action'] = $action;
        $returnArr['request_type'] = $rType;
        //friend request accepted or deny
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $returnArr);

        echo json_encode($resp_data);
        exit();
    }

    /**
     * Save user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveUserNotification($user_id, $fid, $msgtype, $msg) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($user_id);
        $notification->setTo($fid);
        $notification->setMessageType($msgtype);
        $notification->setMessage($msg);
        $notification->setItemId(0);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $dm->persist($notification);
        $dm->flush();
        return true;
    }

    /**
     * send email for notification on activation
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
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * View profile
     */
    public function postViewprofilesAction(Request $request) {
        //initilise the data array
        $data = array();
        $is_friend = 0;
        $is_sent = 0;
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'friend_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        //parameter check end
        //get friend id
        $friend_id = $de_serialize['friend_id'];

        if ($friend_id == "") {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => array());
            return $res_data;
        }

        //check if friend is active or not
        $friend_user_check_enable = $this->checkActiveUserProfile($friend_id);

        if ($friend_user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get login user id
        $user_id = $de_serialize['user_id'];

        if ($user_id == "") {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => array());
            return $res_data;
        }
        //get usermanager object
        $userManager = $this->container->get('fos_user.user_manager');

        $user = $userManager->findUserBy(array('id' => $friend_id));

        //get user data

        $fuser_name = $user->getUsername();
        $fuser_email = $user->getEmail();
        $fuser_group = $user->getGroupNames();

        //get entity manager object
        $em = $this->getDoctrine()->getManager();
         //fire the query in User Connection Repository
        $friend_check = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkIsFriend($user_id, $friend_id);

        if ($friend_check) {
            $is_friend = $friend_check;
        }

        //check friend request
        //fire the query in User Connection Repository
        $friend_request_check = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->checkFriendrequestsent($user_id, $friend_id);
        if ($friend_request_check) {
            $is_sent = $friend_request_check;
        }

        // checking personal and professional friend request
        $friend_request_type = 0;

        // when i send a friend request and the user who has received it wants to view my profile
        // in this case friend_id will be equal to $user_id and user_id will be equal to friend_id
        // since now freind will view user profile

        //check persoanl friend request
        $personal_friend_request_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                                             ->checkPersoanlFriendRequest($user_id, $friend_id);
        $personal_pending = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
           ->checkPendingRequestStatus($user_id, $friend_id, 'personal' );

        $professional_pending = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
           ->checkPendingRequestStatus($user_id, $friend_id, 'professional' );

        if($personal_friend_request_check){
           $friend_request_type = 1;
        }
        //check persoanl friend request
        $professional_friend_request_check = $em->getRepository('UserManagerSonataUserBundle:UserConnection')
                                                 ->checkProfessionalFriendRequest($user_id, $friend_id);
        if($professional_friend_request_check){
           $friend_request_type = 2 ;
        }

        if($personal_friend_request_check && $professional_friend_request_check){
           $friend_request_type = 3 ;
        }
       //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

//        $user_service = $this->get('user_object.service');
//        $user_info_detail = array();
//        $user_info_detail = $user_service->UserObjectService($friend_id);

        if (!array_key_exists('lang_code', $de_serialize)) {
            $de_serialize['lang_code'] = 'en';
            $lang_code = $de_serialize['lang_code'];
        } else {
            $lang_code = $de_serialize['lang_code'];
        }

        $user_service = $this->get('user_friend.service');
        $user_info_detail = array();
        $user_info_detail = $user_service->UserObjectService($user_id, $friend_id, $lang_code);


        //get entity object
        $userfollow = new UserFollowers();
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //check if usser has already connected
        $user_con = $em
                ->getRepository('UserManagerSonataUserBundle:UserFollowers')
                ->findOneBy(array('senderId' => $user_id, 'toId' => $friend_id));
        $is_follow = 0;
        if ($user_con) {
            $is_follow = 1;
        }

        //adding new parametr for checking friend request type
        $user_info = array('user_id' => $friend_id, 'user_info' => $user_info_detail, 'user_email' => $fuser_email,
                           'user_name' => $fuser_name, 'user_group' => $fuser_group, 'is_friend' => $is_friend, 'is_sent' => $is_sent,
                           'is_follow' => $is_follow,'friend_request_type'=>$friend_request_type);

        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $user_info);

        echo json_encode($resp_data);
        exit();
    }

    /**
     * Get pending friend request
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function postPendingfriendrequestsAction(Request $request) {

        //initilise the data array
        $users_array = array();
        //get request object
        //$request = $this->getRequest();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        //parameter check end
        //get login user id
        $user_id = $de_serialize['user_id'];

        //check if user is active or not
//        $user_check_enable = $this->checkActiveUserProfile($user_id);
//
//        if ($user_check_enable == false) {
//            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
//            return $res_data;
//        }

        $offset = isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0;
        $limit = isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20;

        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getAllPendingFriendRequests($user_id, $offset, $limit);
        $resultsCount = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->countAllPendingFriendRequests($user_id);

        $user_service = $this->get('user_object.service');
        $users_array = array();
        try{
            $_userIds=array();
            foreach ($results as $result) {
                $_userIds[] = $result['connect_from'];
            }
            $_userIds = array_unique($_userIds);
            $_usersInfo = $user_service->MultipleUserObjectService($_userIds);
            foreach ($results as $result) {
                $user_connect_from = $result['connect_from'];
                $user_info = $_usersInfo[$user_connect_from];
                $users_array[] = array(
                    'request_id' => $result['id'],
                    'professional' => $result['professional_request'],
                    'personal' => $result['personal_request'],
                    'friend_id' => $user_connect_from,
                    'user_info' => $user_info
                );
            }
        }catch(\Exception $e){

        }
        // Get professional pending requests
//        $results = $em
//                ->getRepository('UserManagerSonataUserBundle:UserConnection')
//                ->pendingFriendrequestProfessional($user_id, $offset, $limit);
//        $user_service = $this->get('user_object.service');
//
//        $_userIds=array();
//        foreach ($results as $result) {
//            $_userIds[] = $result->getConnectFrom();
//        }
//
//
//        // Get personal pending requests
//        $resultsPer = $em
//                ->getRepository('UserManagerSonataUserBundle:UserConnection')
//                ->pendingFriendrequestPersonal($user_id, $offset, $limit);
//
//        foreach ($resultsPer as $result) {
//            $_userIds[] = $result->getConnectFrom();
//        }
//        $_userIds = array_unique($_userIds);
//        $_usersInfo = $user_service->MultipleUserObjectService($_userIds);
//
//        $user_info = array();
//        foreach ($results as $result) {
//            $requset_id = $result->getId();
//            $prof_request = $result->getProfessionalRequest();
//            $user_connect_from = $result->getConnectFrom();
////            $user_info = $user_service->UserObjectService($user_connect_from);
//            $user_info = $_usersInfo[$user_connect_from];
//            $users_array[] = array('request_id' => $requset_id, 'professional' => $prof_request, 'personal' => 0, 'friend_id' => $user_connect_from, 'user_info' => $user_info);
//        }
//
//        $user_info = array();
//        foreach ($resultsPer as $result) {
//            $requset_id = $result->getId();
//            $per_request = $result->getPersonalRequest();
//            $user_connect_from = $result->getConnectFrom();
////            $user_info = $user_service->UserObjectService($user_connect_from);
//            $user_info = $_usersInfo[$user_connect_from];
//            $users_array[] = array('request_id' => $requset_id, 'professional' => 0, 'personal' => $per_request, 'friend_id' => $user_connect_from, 'user_info' => $user_info);
//        }
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array('requests'=>$users_array,'size'=>$resultsCount));
        echo json_encode($resp_data);
        exit();
    }

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
     * Check for enabled user
     * @param string $username
     * @return boolean
     */
    public function checkActiveUserProfile($uid) {
        //get user manager
        $um = $this->container->get('fos_user.user_manager');

        //get user detail
        $user = $um->findUserBy(array('id' => $uid));
        if (!$user) {
            return false;
        }
        $user_check_enable = $user->isEnabled();

        return $user_check_enable;
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
     *
     * @param type $trackid
     */
    public function trackemailAction($trackid) {
        $em = $this->getDoctrine()->getManager();
        $template_res = $em
                ->getRepository('NewsletterNewsletterBundle:Newslettertrack')
                ->findOneByToken($trackid);

        $template_res->setOpenStatus(1);
        $em->flush();
        return $this->redirect($this->generateUrl('newsletter_newsletter_status'));
    }

    /**
     * Get All connected profiles for that user id.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetconnectedprofilesAction(Request $request) {
        //initilise the data array
        $users_array = array();
        $shop_array = array();
        $citizen_data = array();
        $broker_data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $user_id = $object_info->user_id;

        $user_service = $this->get('user_object.service');
        $user_object = $user_service->UserObjectService($user_id);

        //get stores of the user
        //fire the query in User Connection Repository
        $em = $this->getDoctrine()->getManager();

        //get citizen info
        $citizen_profile = $em
                ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                ->getExternalProfileCitizen($user_id);

        if (count($citizen_profile) == 0) {
            $resp_data = array('code' => '100', 'message' => 'USER_NOT_EXIST', 'data' => array());
            return $resp_data;
        }

        $stores = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->getExternalProfileStores($user_id);

        foreach ($stores as $shop) {
            $store_detail = $em->getRepository('StoreManagerStoreBundle:Store')
                    ->findOneBy(array('id' => $shop['id']));
            $store_id = $store_detail->getId();
            //prepare store info array
            $store_data = array(
                'id' => $store_id,
                'business_name' => $store_detail->getBusinessName(),
                'email' => $store_detail->getEmail(),
                'description' => $store_detail->getDescription(),
                'phone' => $store_detail->getPhone(),
                'legal_status' => $store_detail->getLegalStatus(),
                'business_type' => $store_detail->getBusinessType(),
                'business_country' => $store_detail->getBusinessCountry(),
                'business_region' => $store_detail->getBusinessRegion(),
                'business_city' => $store_detail->getBusinessCity(),
                'business_address' => $store_detail->getBusinessAddress(),
                'zip' => $store_detail->getZip(),
                'province' => $store_detail->getProvince(),
                'vat_number' => $store_detail->getVatNumber(),
                'iban' => $store_detail->getIban(),
                'map_place' => $store_detail->getMapPlace(),
                'latitude' => $store_detail->getLatitude(),
                'longitude' => $store_detail->getLongitude(),
                'parent_store_id' => $store_detail->getParentStoreId(), //for parent store
                'is_active' => (int) $store_detail->getIsActive(),
                'is_allowed' => (int) $store_detail->getIsAllowed(),
                'created_at' => $store_detail->getCreatedAt(),
            );
            $current_store_profile_image_id = $store_detail->getStoreImage();
            $store_profile_image_path = '';
            $store_profile_image_thumb_path = '';
            $store_profile_image_cover_thumb_path = '';
            if (!empty($current_store_profile_image_id)) {
                $store_profile_image = $em->getRepository('StoreManagerStoreBundle:StoreMedia')
                        ->find($current_store_profile_image_id);
                if ($store_profile_image) {
                    $album_id = $store_profile_image->getalbumId();
                    $image_name = $store_profile_image->getimageName();
                    if (!empty($album_id)) {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $album_id . '/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/' . $image_name;
                        $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $album_id . '/coverphoto/' . $image_name;
                    } else {
                        $store_profile_image_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/original/' . $image_name;
                        $store_profile_image_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/' . $image_name;
                        $store_profile_image_cover_thumb_path = $this->getS3BaseUri() . $this->store_media_path . $store_id . '/thumb/coverphoto/' . $image_name;
                    }
                }
            }
            $store_data['profile_image_original'] = $store_profile_image_path;
            $store_data['profile_image_thumb'] = $store_profile_image_thumb_path;
            $store_data['cover_image_path'] = $store_profile_image_cover_thumb_path;

            //get store revenue
            $stores_revenue = $em
                    ->getRepository('StoreManagerStoreBundle:Transactionshop')
                    ->getShopsRevenue($store_id);
            $store_data['revenue'] = $stores_revenue;
            $shop_array[] = $store_data;
        }

        //get the applane service for total citizen income, credit.
        $applane_service      = $this->container->get('appalne_integration.callapplaneservice');
        $citizen_income_data = $applane_service->getCitizenIncome($user_id);
        $stato = $saldoc = $saldorc = $saldorm = $total_income = $total_credit_available = 0; //intialize the variables.
        $descrizione = '';
        $user_type = 1;
        $total_income = $citizen_income_data['citizen_income'];
        $total_credit_available = $citizen_income_data['credit'];

        $citizen_data = array('citizen' => $citizen_profile, 'stato' => $stato,
            'description' => $descrizione, 'saldoc' => $saldoc, 'saldorc' => $saldorc, 'saldorm' => $saldorm,
            'total_income' => $total_income, 'total_credit_available' => $total_credit_available, 'shopping_plus_user' => $user_type);

        //get broker info
        $broker_profile = $em
                ->getRepository('UserManagerSonataUserBundle:BrokerUser')
                ->getExternalProfileBroker($user_id);
        if (count($broker_profile) > 0) {
            $broker_data = array('broker' => $broker_profile, 'total_income' => $total_income, 'total_credit_available' => $total_credit_available);
        }

        $combine_data = array('store_profile' => $shop_array, 'citizen_profile' => $citizen_data, 'broker_profile' => $broker_data, 'base_profile' => $user_object);

        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $combine_data);

        echo json_encode($resp_data);
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

    /**
     * Function to retrieve current applications base URI
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        // return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl();
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
     * Get Relation Type
     * @param Request $request
     * @return array;
     */
    public function getrelationtypeAction(Request $request) {
        // check empty search
        $em = $this->getDoctrine()->getManager();
        $relationTypeData = $em
                ->getRepository('UserManagerSonataUserBundle:RelationType')
                ->findAll();
        // Set data for search
        $searche_result = array();
        if(!empty($relationTypeData)){
            foreach ($relationTypeData as $key => $value) {
                $searche_result['relations'][] = array(
                    'id' => $value->getId(),
                    'name' => $value->getName()
                    );
            }
        }
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $searche_result);
        echo json_encode($final_data);
        exit;
    }


     /**
     * Insert Relation Type
     * @param Request $request
     * @return array;
     */
    public function postInsertrelationtypesAction(Request $request) {

        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $em = $this->getDoctrine()->getManager();
        $relations = $em->getRepository('UserManagerSonataUserBundle:RelationType');
        $InsertRelationType = $relations->InsertRelationType($de_serialize['data']);

        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
    }

     /**
     * Insert Relation Type
     * @param Request $request
     * @return array;
     */
    public function updatepersonalAction(Request $request) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        // Get Friend Connection Status
        $reqTypeArr = array();
        $resultsData = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->findAll();
        foreach ($resultsData as $value) {
            $reqId = $value->getId();
            //if($reqId == 235 || $reqId == 275){
                $reqStatus = $value->getStatus();
                if($reqStatus == 0){
                    $personalreg = 1;
                    $resultsData = $em
                    ->getRepository('UserManagerSonataUserBundle:UserConnection')
                    ->updateUserConnectionStatusPersonal($reqId,$personalreg);
                } elseif ($reqStatus == 1) {
                    $resultsData = $em
                    ->getRepository('UserManagerSonataUserBundle:UserConnection')
                    ->updateUserConnectionStatus($reqId,$reqStatus);
                }
            //}
        }

    }
    /*********************************************************/
    /**
     * Get all pending friend request
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function postAllpendingfriendrequestsAction(Request $request) {
        //initilise the data array
        $users_array = array();
        //get request object
        //$request = $this->getRequest();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        //parameter check end
        //get login user id
        $user_id = $de_serialize['user_id'];

                /* Limit Set with Notification list*/
         $limit = (int)(isset($de_serialize['limit_size'])? $de_serialize['limit_size']:10);
        $offset = (int)(isset($de_serialize['limit_start'])? $de_serialize['limit_start']:0);
        /*End here notification list*/
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => array());
            return $res_data;
        }


        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        // Get professional pending requests
        /*$results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->pendingFriendrequestProfessional($user_id, $offset, $limit);*/
        $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->allPendingFriendrequestProfessional($user_id, $offset, $limit);
        $user_service = $this->get('user_object.service');
        $user_info = array();
        foreach ($results as $result) {
            $requset_id = $result->getId();
            $prof_request = $result->getProfessionalRequest();
            $user_connect_from = $result->getConnectFrom();
            $user_info = $user_service->UserObjectService($user_connect_from);
            $users_array['professional'][] = array('request_id' => $requset_id, 'professional' => $prof_request, 'friend_id' => $user_connect_from, 'user_info' => $user_info);
        }
        // Get personal pending requests
        /*$resultsPer = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->pendingFriendrequestPersonal($user_id, $offset, $limit);*/
        $resultsPer = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->allPendingFriendrequestPersonal($user_id, $offset, $limit);
        $user_service = $this->get('user_object.service');
        $user_info = array();
        foreach ($resultsPer as $result) {
            $requset_id = $result->getId();
            $per_request = $result->getPersonalRequest();
            $user_connect_from = $result->getConnectFrom();
            $user_info = $user_service->UserObjectService($user_connect_from);
            $users_array['personal'][] = array('request_id' => $requset_id, 'personal' => $per_request, 'friend_id' => $user_connect_from, 'user_info' => $user_info);
        }
        //echo "<pre>";
        //print_r($users_array);
        //exit;
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $users_array,'count'=>count($users_array));
        echo json_encode($resp_data);
        exit();
    }
    /*********************************************************/

    public function postGetallfriendsAction(Request $request){
        $aws_link = $this->container->getParameter('aws_base_path');
        $aws_bucket= $this->container->getParameter('aws_bucket');
        $aws_path  = $aws_link.'/'.$aws_bucket;
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        $results = array();

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

        $required_parameter = array('user_id', 'search_keyword', 'search_type');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $results);
        }

        $userId = trim($de_serialize['user_id']);
        $searchKeyword = $de_serialize['search_keyword'];
        $searchType = $de_serialize['search_type'];
        $clubId = isset($de_serialize['club_id']) ? $de_serialize['club_id'] : '';
        $limit = $de_serialize['limit_size']>0 ? (int)$de_serialize['limit_size'] : 10;
        $offset = $de_serialize['limit_start']>0 ? (int)$de_serialize['limit_start'] : 0;


        if (empty($userId)) {
            $res_data = array('code' => 111, 'message' => 'USER_ID_REQUIRED', 'data' => array());
            return $res_data;
        }

        //check if user is active or not
        $isUserActive = $this->checkActiveUserProfile($userId);

        if ($isUserActive == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $results);
            return $res_data;
        }

        // get club (group) details from mongodb
        $groupResults = $this->getAllGroupsByKeyword($searchKeyword, 0, 4, $aws_path);
        // limit for friends and shops
        $limit = $limit - count($groupResults);

        switch($searchType){
            case 'all_friends':
                $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getFriendsAndShops($userId, $searchKeyword, $searchType, $offset, $limit, $aws_path);
                if(!empty($clubId)){
                    $limit = ($limit - count($results))>0 ? ($limit - count($results)) : 2;
                    $_results = $this->getAllClubMembers($userId, $searchKeyword, $clubId, $offset, $limit, $aws_path);
                    $results = array_merge($results, $_results);
                }
                break;
            case 'personal_friends':
            case 'professional_friends':
                $results = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getFriendsAndShops($userId, $searchKeyword, $searchType, $offset, $limit, $aws_path);
                break;
            case 'club_members':
                if (empty($clubId)) {
                    return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_CLUB_ID', 'data' => $results);
                }
                $results = $this->getAllClubMembers($userId, $searchKeyword, $clubId, $offset, $limit, $aws_path);
                break;

        }
        $results = array_merge($results, $groupResults);
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $results);
        echo json_encode($resp_data);
        exit;
    }

    private function getAllClubMembers($userId, $searchKeyword, $clubId, $offset=0, $limit=10, $aws_path=''){
        $em = $this->getDoctrine()->getManager();
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $results = array();

        $group_members = $dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->findGroupMemberUser(array($clubId));
        $users = array();
        if(!empty($group_members)){
            foreach ($group_members[$clubId] as $member){
                array_push($users, $member->getUserId());
            }
        }
        // exclude self user id
        $users = array_diff($users, array($userId));
        if(!empty($users)){
            $results = $em
                    ->getRepository('UserManagerSonataUserBundle:UserConnection')
                    ->getClubMembersAndShops($users, $searchKeyword, $offset, $limit, $aws_path);
        }
        return $results;
    }

    private function getAllGroupsByKeyword($keyword, $offset=0, $limit=10, $aws_path=''){
        $result_group = array();
        //serach group in mongo db
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $group_object = $dm->getRepository('UserManagerSonataUserBundle:Group')
                ->getSearchGroupAll($keyword,$offset, $limit);

        if(empty($group_object)){
            return $result_group;
        }
        //getting group ids
        $groupIds = array_map(function($o) {
            return $o->getId();
        }, $group_object);


        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $group_images = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                ->getGroupMedia($groupIds);


        foreach ($group_object as $key => $value) {
            $result_group[$key]['id'] = $value->getId();
            $result_group[$key]['name'] = $value->getTitle();
            $result_group[$key]['business_name'] = '';
            $result_group[$key]['first_name'] = '';
            $result_group[$key]['last_name'] = '';
            $result_group[$key]['email'] = '';
            $result_group[$key]['status'] = $value->getGroupStatus();
            $result_group[$key]['album_id'] = '';
            $result_group[$key]['profile_image'] = '';
            $result_group[$key]['thumb_path'] = '';
            if(!empty($group_images)){
                foreach ($group_images as $group_image) {
                    if ($group_image->getGroupId() == $result_group[$key]['id']) {
                        $result_group[$key]['album_id'] = $group_image->getAlbumid();
                        $album_id = '';
                        if($result_group[$key]['album_id'] != '') {
                            $album_id = '/'.$result_group[$key]['album_id'];
                        }
                        $result_group[$key]['profile_image'] = $group_image->getMediaName();
                        $result_group[$key]['thumb_path'] = $aws_path.'/uploads/groups/thumb/'.$result_group[$key]['id'].$album_id.'/'.$group_image->getMediaName();
                    }
                }
            }
            $result_group[$key]['type'] = 'G';
        }

        return $result_group;
    }

    public function postFriendrequestdetailsAction(Request $request){
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'friend_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $friend_id = $de_serialize['friend_id'];
        $user_id = $de_serialize['user_id'];

        if (empty($friend_id) or empty($user_id)) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => array());
            echo json_encode($res_data);
            exit;
        }

        //check if friend is active or not
        $friend_user_check_enable = $this->checkActiveUserProfile($friend_id);

        if ($friend_user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        //get entity manager object
        $em = $this->getDoctrine()->getManager();
         //fire the query in User Connection Repository
        $friends = $em
                ->getRepository('UserManagerSonataUserBundle:UserConnection')
                ->getFriendRequestStatus($user_id, $friend_id);
        $response = array();
        $response = array('personal'=>array(
                            'is_friend' => 0,
                            'is_sent' => 0,
                            'is_respond'=> 0
                        ),
                        'professional'=>array(
                            'is_friend' => 0,
                            'is_sent' => 0,
                            'is_respond'=> 0
                        )
                );
        try{
            if(!empty($friends['personal'])){
                $_from = $friends['personal']->getConnectFrom();
                $_to = $friends['personal']->getConnectTo();
                $_isFriend = $friends['personal']->getPersonalStatus()==1  ? 1 : 0;
                $_isSent = ($_isFriend) ? 0 : ($_from==$user_id ? 1 : 0);
                $_isRespond = ($_isFriend) ? 0 : ($_from==$friend_id ? 1 : 0);
                $response['personal'] = array(
                    'is_friend' => $_isFriend,
                    'is_sent' => $_isSent,
                    'is_respond'=> $_isRespond
                );
            }

            if(!empty($friends['professional'])){
                $_from = $friends['professional']->getConnectFrom();
                $_to = $friends['professional']->getConnectTo();
                $_isFriend = $friends['professional']->getProfessionalStatus()==1 ? 1 : 0;
                $_isSent = ($_isFriend) ? 0 : ($_from==$user_id ? 1 : 0);
                $_isRespond = ($_isFriend) ? 0 : ($_from==$friend_id ? 1 : 0);
                $response['professional']= array(
                                'is_friend' =>$_isFriend,
                                'is_sent' => $_isSent,
                                'is_respond'=> $_isRespond
                            );
            }
        }catch(\Exception $e){

        }
        $result = array('code' => 101, 'message' => 'success', 'data' => $response);
        echo json_encode($result);
        exit;
    }

    public function postGetclubmembersAction(Request $request){
        $em = $this->getDoctrine()->getManager();

        $results = array();

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

        $required_parameter = array('user_id', 'club_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => array('members'=>$results, 'size'=>0));
        }

        $userId = trim($de_serialize['user_id']);
        $searchKeyword = isset($de_serialize['keyword']) ? $de_serialize['keyword'] : '';
        $clubId = isset($de_serialize['club_id']) ? $de_serialize['club_id'] : '';
        $limit = $de_serialize['limit_size']>0 ? (int)$de_serialize['limit_size'] : 10;
        $offset = $de_serialize['limit_start']>0 ? (int)$de_serialize['limit_start'] : 0;

        $results = $this->getClubMembers($clubId, $offset, $limit, $searchKeyword);

        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $results);
        echo json_encode($resp_data);
        exit;
    }

    private function getClubMembers($clubId, $offset, $limit, $searchKeyword){
        $em = $this->getDoctrine()->getManager();
        $dm = $this->get('doctrine.odm.mongodb.document_manager');

        $group_members = $dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->findClubMembers($clubId);

        $users = array();
        if(!empty($group_members)){
            foreach ($group_members as $member){
                array_push($users, $member->getUserId());
            }
        }

        $user_service = $this->get('user_object.service');
        $response = array();
        if(!empty($users)){
            $results = $user_service->getUsersByIdsAndKeyword($users, $searchKeyword,  $offset, $limit);
            $response = array_values($results);
        }
        $group_members_count = $em->getRepository('UserManagerSonataUserBundle:User')->getUsersByIdsAndKeywordCount($users, $searchKeyword);
        return array('members'=>$response, 'size'=>$group_members_count);
    }
}
