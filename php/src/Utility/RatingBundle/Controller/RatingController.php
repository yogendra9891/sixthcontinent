<?php

namespace Utility\RatingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Dashboard\DashboardManagerBundle\Document\DashboardPost;
use Dashboard\DashboardManagerBundle\Document\DashboardPostRating;
use Notification\NotificationBundle\Document\UserNotifications;
use Dashboard\DashboardManagerBundle\Document\DashboardComments;
use Dashboard\DashboardManagerBundle\Document\DashboardCommentRating;
use Utility\RatingBundle\Controller\ClubRatingController;
use Utility\RatingBundle\Controller\StoreRatingController;
use Media\MediaBundle\Document\UserAlbumRating;
use Media\MediaBundle\Document\UserMediaRating;
use Media\MediaBundle\Controller\ShopClubRatingController;
use Media\MediaBundle\Document\AlbumCommentRating;

/**
 * class for handing the rating system
 */
class RatingController extends ShopClubRatingController
{

    protected $rating_type = array('dashboard_post','dashboard_post_comment','user_profile_album','user_profile_album_photo','club_post','club_post_comment','club_album','club_album_photo','store_post', 'club','store_post_comment',
        'store_album','store_media','user_album_comment','store_album_comment','club_album_comment', 'store_album_media_comment', 'shop_post_media');
    protected $rating_stars = array(1,2,3,4,5);
    protected $dashboard_post_rating = "DASHBOARD_POST_RATE";
    protected $dashboard_comment_rating = "DASHBOARD_COMMENT_RATE";
    protected $user_album_rating = "USER_ALBUM_RATE";
    protected $user_photo_rating = "USER_PHOTO_RATE";
    protected $useralbum_comment_rating = "USER_ALBUM_COMMENT_RATE";
    /**
     * add rate for a type
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postAddratesAction(Request $request) {
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
        $required_parameter = array('user_id', 'type', 'type_id', 'rate');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //extract parameters.
        $item_type = $object_info->type;
        $item_id   = $object_info->type_id;
        $rate    = $object_info->rate;
        $user_id = $object_info->user_id;

        //check for rating type
        if (!in_array($item_type, $this->rating_type)) {
            return array('code' => 81, 'message' => 'RATING_TYPE_NOT_SUPPPORTED', 'data' => $data);
        }
        //check for star count
        if (!in_array($object_info->rate, $this->rating_stars)) {
            return array('code' => 80, 'message' => 'RATING_VALUE_NOT_SUPPPORTED', 'data' => $data);
        }

        switch ($item_type) {
            case 'dashboard_post':
                $this->addDashboardPostRate($item_type, $item_id, $rate, $user_id);
                break;

            case 'dashboard_post_comment':
                $this->addDashboardPostCommentRate($item_type, $item_id, $rate, $user_id);
                break;

            case 'club':
                //get club rating class object
                $club_rating = new ClubRatingController();
                $club_rating->addClubRate($item_type, $item_id, $rate, $user_id);
                break;

            case 'store_post':
                //get store rating object
                $storerating = new StoreRatingController();
                $storerating->addStorePostRate($item_type, $item_id, $rate, $user_id);
                break;

            case 'store_post_comment':
                 //get store rating object
                $storerating = new StoreRatingController();
                $storerating->addStorePostCommentRate($item_type, $item_id, $rate, $user_id);
                break;


            case 'user_profile_album':
                $this->addUserProfileAlbumRate($item_type, $item_id, $rate, $user_id);
                break;

            case 'user_profile_album_photo':
                $this->addUserProfileAlbumImageRate($item_type, $item_id, $rate, $user_id);
                break;

            case 'club_post':
                $this->addClubPostRate($item_type, $item_id, $rate, $user_id);
                break;

            case 'club_post_comment':
                $this->addClubPostCommentRate($item_type, $item_id, $rate, $user_id);
                break;

            case 'club_album':
                $this->addClubAlbumRate($item_type, $item_id, $rate, $user_id);
                break;

            case 'club_album_photo':
                $this->addClubAlbumImageRate($item_type, $item_id, $rate, $user_id);
                break;

            case 'store_album':
                 //get store rating object
                $storerating = new StoreRatingController();
                $storerating->addStoreAlbumRate($item_type, $item_id, $rate, $user_id);
                break;
             case 'store_media':
                 //get store rating object
                $storerating = new StoreRatingController();
                $storerating->addStoreMediaRate($item_type, $item_id, $rate, $user_id);
                break;
            case 'user_album_comment':
                 //get store rating object
                $this->addUserAlbumCommentRate($item_type, $item_id, $rate, $user_id);
                break;
             case 'store_album_comment':
                 //get store rating object
                $storerating = new StoreRatingController();
                $storerating->addStoreAlbumCommentRate($item_type, $item_id, $rate, $user_id);
                break;

             case 'club_album_comment':
                 //get store rating object
                $storerating = new ClubRatingController();
                $storerating->addClubAlbumCommentRate($item_type, $item_id, $rate, $user_id);
                break;
            case 'shop_post_media':
                $shopRating = new StoreRatingController();
                $shopRating->addStorePostMediaRate($item_type, $item_id, $rate, $user_id);
                break;


        }
        exit;
    }

    /**
     * remove rate for a type
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postDeleteratesAction(Request $request) {
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


        $required_parameter = array('user_id', 'type', 'type_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //extract variables.
        $item_type = $object_info->type;
        $item_id = $object_info->type_id;
        $user_id = $object_info->user_id;

        if (!in_array($item_type, $this->rating_type)) {
            return array('code' => 81, 'message' => 'RATING_TYPE_NOT_SUPPPORTED', 'data' => $data);
        }

        switch ($item_type) {
            case 'dashboard_post':
                $this->deleteDashboardPostRate($item_type, $item_id, $user_id);
                break;
            case 'club':
                //get club rating class object
                $club_rating = new ClubRatingController();
                $club_rating->deleteClubPostRate($item_type, $item_id, $user_id);
                break;
            case 'store_post':
                 //get store rating object
                $storerating = new StoreRatingController();
                $storerating->deleteStorePostRate($item_type, $item_id, $user_id);
                break;
            case 'store_post_comment':
                //get store rating object
                $storerating = new StoreRatingController();
                $storerating->deleteStorePostCommentRate($item_type, $item_id, $user_id);
                break;
            case 'dashboard_post_comment':
                $this->deleteDashboardPostCommentRate($item_type, $item_id, $user_id);
                break;
            case 'user_profile_album':
                $this->deleteUserProfileAlbumRate($item_type, $item_id, $user_id);
                break;
            case 'user_profile_album_photo':
                $this->deleteUserProfileAlbumImageRate($item_type, $item_id, $user_id);
                break;

            case 'club_post':
                $this->deleteClubPostRate($item_type, $item_id, $user_id);
                break;

            case 'club_post_comment':
                $this->deleteClubPostCommentRate($item_type, $item_id, $user_id);
                break;

            case 'club_album':
                $this->deleteClubAlbumRate($item_type, $item_id, $user_id);
                break;

            case 'club_album_photo':
                $this->deleteClubAlbumImageRate($item_type, $item_id, $user_id);
                break;
            case 'store_album':
                 //get store rating object
                $storerating = new StoreRatingController();
                $storerating->deleteStoreAlbumRate($item_type, $item_id, $user_id);
                break;
            case 'store_media':
                 //get store rating object
                $storerating = new StoreRatingController();
                $storerating->deleteStoreMediaRate($item_type, $item_id, $user_id);
                break;
            case 'user_album_comment':
                $this->deleteUserAlbumCommentRate($item_type, $item_id, $user_id);
                break;
             case 'store_album_comment':
                 //get store rating object
                $storerating = new StoreRatingController();
                $storerating->deleteStoreAlbumCommentRate($item_type, $item_id, $user_id);
                break;
             case 'club_album_comment':
                 //get store rating object
                $storerating = new ClubRatingController();
                $storerating->deleteClubAlbumCommentRate($item_type, $item_id, $user_id);
                break;
            case 'shop_post_media':
                $shopRating = new StoreRatingController();
                $shopRating->deleteStorePostMediaRate($item_type, $item_id, $user_id);
                break;
        }
        exit;
    }

    /**
     * Edit Rate
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postEditratesAction(Request $request) {
        //initilise the array
        $data = array();
        $votes = array();

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

        $required_parameter = array('user_id', 'type', 'type_id', 'rate');
        $data = array();

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        if (!in_array($object_info->type, $this->rating_type)) {
            return array('code' => 81, 'message' => 'RATING_TYPE_NOT_SUPPPORTED', 'data' => $data);
        }

        if (!in_array($object_info->rate, $this->rating_stars)) {
            return array('code' => 80, 'message' => 'RATING_VALUE_NOT_SUPPPORTED', 'data' => $data);
        }

        $rate = $object_info->rate;
        $user_id = $object_info->user_id;
        $type = $object_info->type;
        $type_id = $object_info->type_id;
        switch ($type)
        {
            case 'dashboard_post':
                $this->editDashboardPostRate($type, $type_id, $rate, $user_id);
                break;
            case 'dashboard_post_comment':
                $this->editDashboardPostCommentRate($type, $type_id, $rate, $user_id);
                break;

            case 'club':
                //get club rating class object
                $club_rating = new ClubRatingController();
                $club_rating->editClubRate($type, $type_id, $rate, $user_id);
                break;

            case 'store_post':
                $storerating = new StoreRatingController();
                $storerating->editStorePostRate($type, $type_id, $rate, $user_id);
                break;

            case 'store_post_comment':
                //get store rating object
                $storerating = new StoreRatingController();
                $storerating->editStorePostCommentRate($type, $type_id, $rate, $user_id);
                break;

            case 'user_profile_album':
                $this->editUserProfileAlbumRate($type, $type_id, $rate, $user_id);
                break;

            case 'user_profile_album_photo':
                $this->editUserProfileAlbumImageRate($type, $type_id, $rate, $user_id);
                break;

            case 'club_post':
                $this->editClubPostRate($type, $type_id, $rate, $user_id);
                break;

            case 'club_post_comment':
                $this->editClubPostCommentRate($type, $type_id, $rate, $user_id);
                break;

            case 'club_album':
                $this->editClubAlbumRate($type, $type_id, $rate, $user_id);
                break;

            case 'club_album_photo':
                $this->editClubAlbumImageRate($type, $type_id, $rate, $user_id);
                break;
            case 'store_album':
                //get store rating object
                $storerating = new StoreRatingController();
                $storerating->editStoreAlbumRate($type, $type_id, $rate, $user_id);
                break;
            case 'store_media':
                //get store rating object
                $storerating = new StoreRatingController();
                $storerating->editStoreMediaRate($type, $type_id, $rate, $user_id);
                break;
            case 'user_album_comment':
                $this->editUserAlbumCommentRate($type, $type_id, $rate, $user_id);
                break;
            case 'store_album_comment':
                //get store rating object
                $storerating = new StoreRatingController();
                $storerating->editStoreAlbumCommentRate($type, $type_id, $rate, $user_id);
                break;
            case 'club_album_comment':
                //get store rating object
                $storerating = new ClubRatingController();
                $storerating->editClubAlbumCommentRate($type, $type_id, $rate, $user_id);
                break;
            case 'shop_post_media':
                $shopRating = new StoreRatingController();
                $shopRating->editStorePostMediaRate($type, $type_id, $rate, $user_id);
                break;
        }
        exit;
    }

    /**
     * Edit Dashboard rate
     * @param int $post_id
     * @param int $user_id
     */
    public function editDashboardPostRate($item_type, $post_id, $rate, $user_id) {
        $arrayPostRate = array();
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($post_id);

        //if post not exist
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $post->getRate();
        $total_user_count = $post->getVoteCount();
        $total_rate = $post->getVoteSum(); //old total rate
        //
        //get current time
        $time = new \DateTime("now");
        foreach ($votes as $vote) {
            $voter_id = $vote->getUserId();
            //check if current user is voter of post
            if ($user_id == $voter_id) {
                $rate_id = $vote->getId(); //get rate id
                $current_user_rate = $vote->getRate();
                //preapre the object
                $arrayPostRate = array(
                    "_id" => new \MongoId($rate_id),
                    "user_id" => $vote->getUserId(),
                    "rate" => (int) $rate,
                    "item_id" => $vote->getItemId(),
                    "type" => $vote->getType(),
                    "created_at" => $vote->getCreatedAt(),
                    "updated_at" => $time,
                );
            }
        }
        $rating_response = array();
        //edit rating object
        if (count($arrayPostRate) > 0) {
            $rating_response = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                    ->editPostRate($rate_id, $arrayPostRate, $post_id);
        }
        if (!$rating_response) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //Update the rate count
        $resp = $this->updateEditRateCount($total_user_count, $total_rate, $post_id, $current_user_rate, $rate);
        $new_total_rate = $resp['total_rate'];
        $avg_rate = $resp['avg_rate'];

        $post->setVoteSum($new_total_rate);
        $post->setAvgRating($avg_rate);

        try {
            $dm->persist($post); //storing the post data.
            $dm->flush();
            //set response parameter
            $avg_rate_round = $this->roundNumber($avg_rate);
            $data = array('avg_rate' => $avg_rate_round, 'current_user_rate' => $rate, 'no_of_votes' => $total_user_count);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }

    /**
     * Get Url content
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeData($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }

    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     */
    public function checkParamsAction($chk_params, $object_info) {
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
     * add the rate for dahboard posts.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    private function addDashboardPostRate($item_type, $item_id, $rate, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_res = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($item_id);
        if (!$post_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }
        $post_rating   = $post_res->getRate();
        //check if a user already rate on post.
        foreach ($post_rating as $post_rate) {
            if ($post_rate->getUserId() == $user_id) {
                $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                $this->returnResponse($res_data);
            }
        }
        $dashboard_rating = new DashboardPostRating();
        $time = new \DateTime("now");

        //calculate the total, average rate.
        $total_user_count = $post_res->getVoteCount();
        $total_rate = $post_res->getVoteSum();

        $dashboard_post_user_id = $post_owner_id =  $post_res->getUserId(); //get post owner id
        $updated_rate_result = $this->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count

        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate = $updated_rate_result['avg_rate'];

        //set the object.
        $post_res->setVoteCount($new_user_count);
        $post_res->setVoteSum($new_total_rate);
        $post_res->setAvgRating($avg_rate);

        //set object for dashboard post rating
        $dashboard_rating->setUserId($user_id);
        $dashboard_rating->setRate($rate);
        $dashboard_rating->setItemId($item_id);
        $dashboard_rating->setType('post');
        $dashboard_rating->setCreatedAt($time);
        $dashboard_rating->setUpdatedAt($time);

        $post_res->addRate($dashboard_rating);
        try {
            $dm->persist($post_res); //storing the post data.
            $dm->flush();
            if ($user_id != $post_owner_id)
                {

                //prepare the mail template for dashboard post rating.
                $email_template_service = $this->container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
                $dashboard_post_url     = $this->container->getParameter('dashboard_post_url'); //dashboard post url
                $to_id   = $dashboard_post_user_id;
                $from_id = $user_id;
                $post_id = $item_id;

                $postService = $this->container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);

                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->dashboard_post_rating;
                $notification_id = $this->saveUserNotification($user_id, $post_owner_id, $item_id, $msgtype, $rate);
                $postService->sendUserNotifications($user_id, $post_owner_id, $msgtype, 'rate', $item_id, false, true, $sender_name);
                //end to send social notification


                $href = $angular_app_hostname . $dashboard_post_url . '/' . $post_id;
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $subject  = sprintf($lang_array['DASHBOARD_POST_RATE_SUBJECT'], ucwords($sender_name));
                $mail_body = sprintf($lang_array['DASHBOARD_POST_RATE_BODY'], ucwords($sender_name));
                $mail_text = sprintf($lang_array['DASHBOARD_POST_RATE_TEXT'], ucwords($sender_name), $rate);
                $bodyData      = $mail_text."<br><br>".$link;

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $subject, $sender['profile_image_thumb'], 'RATING_NOTIFICATION');

            }
            $data = array('avg_rate' => $this->roundNumber($avg_rate), 'current_user_rate' => $rate, 'no_of_votes' => $new_user_count);

            } catch (\Doctrine\DBAL\DBALException $e) {
                $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
                $this->returnResponse($response_data);
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($response_data);
    }

    /**
     * delete the rate for dahboard posts.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    private function deleteDashboardPostRate($item_type, $item_id, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_res = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($item_id);
        //if posts is not exist.
        if (!$post_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->dashboard_post_rating;
        $post_owner_id = $post_res->getUserId();
        $post_rating   = $post_res->getRate();
        $count = 0;
        foreach ($post_rating as $post_rate) {
            if ($post_rate->getUserId() == $user_id) {
                $count = 1;
                $user_rate = $post_rate->getRate();
                $post_res->removeRate($post_rate); // remove the rate post object.
                $total_user_count = $post_res->getVoteCount();
                $total_rate = $post_res->getVoteSum();
                $updated_rate_result = $this->updateDeleteRate($total_user_count, $total_rate, $user_rate); //calculate the updated rating, user count.

                $new_user_count = $updated_rate_result['new_user_count'];
                $new_total_rate = $updated_rate_result['new_total_rate'];
                $avg_rate = $updated_rate_result['avg_rate'];
                //set the object.
                $post_res->setVoteCount($new_user_count);
                $post_res->setVoteSum($new_total_rate);
                $post_res->setAvgRating($avg_rate);
                break;
            }
        }

        //set object for dashboard post for updated rating..
        try {
            $dm->persist($post_res); //storing the post data.
            $dm->flush();
            if ($count > 0) {
                //remove notification if post owner doent read the rate notiication.
                $this->removeNotification($post_owner_id, $user_id, $item_id, $message_type);
                $data = array('avg_rate' => $this->roundNumber($avg_rate), 'no_of_votes' => $new_user_count);
            } else { //if record does not exists.
                $data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
                $this->returnResponse($data);
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($response_data);
    }

    /**
     * return the response.
     * @param type $data_array
     */
    public function returnResponse($data_array) {
        echo json_encode($data_array);
        exit;
    }

    /**
     * Update rate
     * @param int $post_id
     * @param int $current_user_vote
     * @return array
     */
    public function updateEditRateCount($vote_count, $vote_sum, $post_id, $current_user_vote, $rate) {

        //voter count will be the same
        $total_user_count = $vote_count; //get vote count

        $total_rate = $vote_sum; //old total rate
        //remove the old rate done by user
        $total_rate_exclude_cuser = $total_rate - $current_user_vote; //new total rate
        //add the new rate done by user
        $new_total_rate = $total_rate_exclude_cuser + $rate;

        $avg_rate = $new_total_rate / $total_user_count;

        return array('total_rate' => $new_total_rate, 'avg_rate' => $avg_rate);
    }

    /**
     * calculate the new added rate and new user count
     * @param int $total_user_count
     * @param int $total_rate
     * @param float $rate
     * @return array
     */
    public function updateAddRate($total_user_count, $total_rate, $rate) {
        $new_total_user_count = $total_user_count + 1;
        $new_total_rate = $total_rate + $rate;
        $avg_rate = $new_total_rate / $new_total_user_count;
        return array('new_user_count' => $new_total_user_count, 'new_total_rate' => $new_total_rate, 'avg_rate' => $avg_rate);
    }

    /**
     * calculate the new added rate and new user count at remove time of rating.
     * @param int $total_user_count
     * @param int $total_rate
     * @param float $rate
     * @return array
     */
    public function updateDeleteRate($total_user_count, $total_rate, $rate) {
        $new_total_user_count = $total_user_count - 1;
        $new_total_rate = $total_rate - $rate;
        if ($new_total_user_count > 0) {
            $avg_rate = $new_total_rate / $new_total_user_count;
        } else {
            $avg_rate = 0;
        }

        return array('new_user_count' => $new_total_user_count, 'new_total_rate' => $new_total_rate, 'avg_rate' => $avg_rate);
    }

    /**
     * round off a float number
     * @param float $number
     * @return float
     */
    public function roundNumber($number) {
        $rounded_number = round($number, 1);
        return $rounded_number;
    }

    /**
     * get rated user list for a item.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetratedusersAction(Request $request) {
        //call the service for getting the request object.
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('type', 'type_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $item_type = $object_info->type;
        $item_id = $object_info->type_id;
        $limit_size = isset($object_info->limit_size) ? $object_info->limit_size : 1000;
        $limit_start = isset($object_info->limit_start) ? $object_info->limit_start : 0;


        switch ($item_type) {
            case 'dashboard_post':
               // $this->getDashboardPostRateUsers($item_type, $item_id);
                break;
            case 'club':
                 //get club rating class object
                $club_rating = new ClubRatingController();
                $club_rating->getClubPostRateUsers($item_type, $item_id, $limit_start, $limit_size);
                break;
            case 'store_post':
                //get store rating object
                $storerating = new StoreRatingController();
                $storerating->getStorePostRateUsers($item_type, $item_id, $limit_start, $limit_size);
                break;
            case 'store_post_comment':
                //get store rating object
                $storerating = new StoreRatingController();
                $storerating->getStorePostCommentRateUsers($item_type, $item_id, $limit_start, $limit_size);
                break;
            case 'store_album':
                //get store rating object
                $storerating = new StoreRatingController();
                $storerating->getStoreAlbumRateUsers($item_type, $item_id, $limit_start, $limit_size);
                break;
            case 'store_media':
                //get store rating object
                $storerating = new StoreRatingController();
                $storerating->getStoreMediaRateUsers($item_type, $item_id, $limit_start, $limit_size);
                break;
//            case 'store_album_media_comment':
//                $storerating = new StoreRatingController();
//                $storerating->getStoreAlbumMediaCommentRateUsers($item_type, $item_id);
//                break;
            case 'shop_post_media':
                $shopRating = new StoreRatingController();
                $shopRating->getStorePostMediaRateUsers($item_type, $item_id);
                break;


        }
        // dashboard and club
        $this->getRattedUserDetails($item_type, $item_id, $limit_start, $limit_size);
        exit;
    }

    /**
     * List dashboard rated users
     * @param int $item_type
     * @param int $item_id
     */
    public function getRattedUserDetails($item_type, $item_id, $limit_start, $limit_size) {
        $data = array();
        $users = array();
        $count = 0;
        $ratted_item_detail = '';
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        switch ($item_type) {
            case 'dashboard_post':
                $ratted_item_detail = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($item_id);
                break;

            case 'dashboard_post_comment':
                $ratted_item_detail = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($item_id);
                break;

            case 'user_profile_album':
                $ratted_item_detail = $dm->getRepository('MediaMediaBundle:UserAlbum')
                ->find($item_id);
                break;

            case 'user_profile_album_photo':
                $ratted_item_detail = $dm->getRepository('MediaMediaBundle:UserMedia')
                ->find($item_id);
                break;

            case 'club_post':
                $ratted_item_detail = $dm->getRepository('PostPostBundle:Post')
                ->find($item_id);
                break;

            case 'club_post_comment':
                $ratted_item_detail = $dm->getRepository('PostPostBundle:Comments')
                ->find($item_id);
                break;

            case 'club_album':
                $ratted_item_detail = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                ->find($item_id);
                break;

            case 'club_album_photo':
                $ratted_item_detail = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                ->find($item_id);
                break;
            case 'user_album_comment':
                $ratted_item_detail = $dm->getRepository('MediaMediaBundle:AlbumComment')
                                        ->find($item_id);
                break;
            case 'club_album_comment':
                $ratted_item_detail = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                                        ->find($item_id);
                break;
            case 'store_album_comment':
                $ratted_item_detail = $dm->getRepository('MediaMediaBundle:StoreAlbumComment')
                                        ->find($item_id);
                break;
        }

        //if post not exist
        if (!$ratted_item_detail) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $ratted_item_detail->getRate();
        $voters_array = array();

        //get total votes count
        if($votes){
            $count = count($votes);
        } else {
            $count = 0;
        }

        $vote_count = $limit_size + $limit_start;
        foreach($votes as $i=>$vote){
            if($i >= $limit_start && $i < $vote_count ){
                $voter_id = $vote->getUserId();
                $rate = $vote->getRate();
                $voters_array[] = $vote->getUserId();
                //define rate by user
                $rate_users[$voter_id] = $rate;
            }
        }

        //get user object
        $user_service = $this->get('user_object.service');
        $user_objects_rated = $user_service->MultipleUserObjectService($voters_array);
        //preapare the users array with user object

        foreach($user_objects_rated as $user_objects_rated_single){
            //get voter id
            $voter_id = $user_objects_rated_single['id'];
            $user_rate = isset($rate_users[$voter_id])? $rate_users[$voter_id]: 0;
            $user_array = array('rate'=>$user_rate);
            $users[] = array_merge($user_objects_rated_single,$user_array);
        }

        $data = array('type_id' => $item_id, 'type' => $item_type, 'total_users' => $count, 'users_rated' => $users);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }

    /**
     *
     * @param string $mail_sub
     * @param string $mail_body
     * @param string $thumb_path
     * @param string $link
     * @param int $from_id
     * @param int $to_id
     */
    public function sendMail($mail_sub, $mail_body, $thumb_path, $link, $from_id, $to_id) {
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        //service for email template
        $email_body = $email_template_service->EmailTemplateService($mail_body, $thumb_path, $link, $to_id);
        $mail_notification = $email_template_service->sendEmailNotification($mail_sub, $from_id, $to_id, $email_body);
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    public function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * Save user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveUserNotification($user_id, $fid, $item_id, $msgtype, $msg) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($user_id);
        $notification->setTo($fid);
        $notification->setMessageType($msgtype);
        $notification->setMessage($msg);
        $notification->setItemId($item_id);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $notification->setMessageStatus('U');
        $dm->persist($notification);
        $dm->flush();
        return $notification->getId();
    }


    /**
     * remove rate notification if a user not read yet and user remove rate.
     * @param int $item_owner_id
     * @param int $user_id
     * @param string $item_id
     * @param string $message_type
     */
    public function removeNotification($item_owner_id, $user_id, $item_id, $message_type) {
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $user_notifications = $dm->getRepository('NManagerNotificationBundle:UserNotifications')
                                ->findBy(array('from'=>"{$user_id}", 'to'=>"{$item_owner_id}", 'message_type'=>$message_type, 'item_id'=>"{$item_id}", 'is_read'=>'0'), null, 1, 0);

        if (count($user_notifications)) {
            $user_notification = $user_notifications[0];
            $dm->remove($user_notification);
            $dm->flush();
        }
        return true;
    }

      /**
     * add the rate for dahboard posts Comments.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    private function addDashboardPostCommentRate($item_type, $item_id, $rate, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($item_id);
        if (!$comment_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $comment_rating   = $comment_res->getRate();
        //check if a user already rate on post comment.
        foreach ($comment_rating as $comment_rate) {
            if ($comment_rate->getUserId() == $user_id) {
                $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                $this->returnResponse($res_data);
            }
        }

        $dashboard_comment_rating = new DashboardCommentRating();
        $time = new \DateTime("now");
         $_postId = $comment_res->getPostId();
        //calculate the total, average rate.
        $total_user_count = $comment_res->getVoteCount();
        $total_rate = $comment_res->getVoteSum();

        $dashboard_post_comment_user_id = $comment_owner_id = $comment_res->getUserId(); //get post owner id
        $updated_rate_result = $this->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count

        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $comment_res->setVoteCount($new_user_count);
        $comment_res->setVoteSum($new_total_rate);
        $comment_res->setAvgRating($avg_rate);

        //set object for dashboard post rating
        $dashboard_comment_rating->setUserId($user_id);
        $dashboard_comment_rating->setRate($rate);
        $dashboard_comment_rating->setItemId($item_id);
        $dashboard_comment_rating->setType('post_comment');
        $dashboard_comment_rating->setCreatedAt($time);
        $dashboard_comment_rating->setUpdatedAt($time);

        $comment_res->addRate($dashboard_comment_rating);
        try {
            $dm->persist($comment_res); //storing the post data.
            $dm->flush();

            if ($user_id != $comment_owner_id) {
                $email_template_service = $this->container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
                $dashboard_post_url     = $this->container->getParameter('dashboard_post_url'); //dashboard post url
                $to_id   = $dashboard_post_comment_user_id;
                $from_id = $user_id;
                $post_id = $item_id;

                $postService = $this->container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);

                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->dashboard_comment_rating;
                $notification_id = $this->saveUserNotification($user_id, $comment_owner_id, $item_id, $msgtype, $rate);
                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, 'rate', $item_id, false, true, $sender_name, 'CITIZEN', array('ref_id'=>$_postId));
                //end to send social notification

                //prepare the mail template for dashboard post rating.

                $href = $angular_app_hostname . $dashboard_post_url . '/' . $_postId;
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $mail_sub  = sprintf($lang_array['DASHBOARD_POST_COMMENT_RATE_SUBJECT'], $sender_name);
                $mail_body = sprintf($lang_array['DASHBOARD_POST_COMMENT_RATE_BODY'], ucwords($sender_name));
                $mail_text = sprintf($lang_array['DASHBOARD_POST_COMMENT_RATE_TEXT'], $sender_name, $rate, 5);
                $bodyData      = $mail_text."<br><br>".$link;

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'RATING_NOTIFICATION');
            }

            $data = array('avg_rate'=>$this->roundNumber($avg_rate), 'current_user_rate'=>$rate, 'no_of_votes'=>$new_user_count);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            echo json_encode($response_data);
            exit;
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($response_data);
        exit;
    }

    /**
     * Edit Dashboard Comment rate
     * @param int $comment_id
     * @param int $user_id
     */
    public function editDashboardPostCommentRate($item_type, $comment_id, $rate, $user_id)
    {
        $arrayCommentRate = array();
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $comment   = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                          ->find($comment_id);

        //if post not exist
        if(!$comment){
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $comment->getRate();
        $total_user_count = $comment->getVoteCount();
        $total_rate = $comment->getVoteSum();//old total rate

        //get current time
        $time = new \DateTime("now");
        foreach ($votes as $vote) {
            $voter_id = $vote->getUserId();
            //check if current user is voter of post
            if($user_id == $voter_id){
            $rate_id = $vote->getId(); //get rate id
            $current_user_rate = $vote->getRate();
            //preapre the object
            $arrayCommentRate = array(
            "_id" => new \MongoId($rate_id),
            "user_id" => $vote->getUserId(),
            "rate" => (int)$rate,
            "item_id" => $vote->getItemId(),
            "type" => $vote->getType(),
            "created_at" =>$vote->getCreatedAt(),
            "updated_at" =>$time,
            );
            }
        }
       $rating_response = array();
        //edit rating object
        if(count($arrayCommentRate)>0){
        $rating_response = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                           ->editCommentRate($rate_id, $arrayCommentRate, $comment_id);

        }
        if(!$rating_response){
         $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
         $this->returnResponse($res_data);
        }

        //Update the rate count
        $resp = $this->updateEditRateCount($total_user_count, $total_rate, $comment_id, $current_user_rate, $rate);
        $new_total_rate = $resp['total_rate'];
        $avg_rate = $resp['avg_rate'];

        $comment->setVoteSum($new_total_rate);
        $comment->setAvgRating($avg_rate);

        try {
            $dm->persist($comment); //storing the post data.
            $dm->flush();
            //set response parameter
            $avg_rate_round = $this->roundNumber($avg_rate);
            $data = array('avg_rate'=>$avg_rate_round, 'current_user_rate'=>$rate, 'no_of_votes'=>$total_user_count);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);

    }

    /**
     * delete the rate for dahboard post comments.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    private function deleteDashboardPostCommentRate($item_type, $item_id, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('DashboardManagerBundle:DashboardComments')
                ->find($item_id);
        //if posts is not exist.
        if (!$comment_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->dashboard_comment_rating;
        $comment_owner_id = $comment_res->getUserId();
        $comment_rating   = $comment_res->getRate();
        $count = 0;
        foreach ($comment_rating as $comment_rate) {
            if ($comment_rate->getUserId() == $user_id) {
                $count = 1;
                $user_rate = $comment_rate->getRate();
                $comment_res->removeRate($comment_rate); // remove the rate post object.
                $total_user_count = $comment_res->getVoteCount();
                $total_rate = $comment_res->getVoteSum();
                $updated_rate_result = $this->updateDeleteRate($total_user_count, $total_rate, $user_rate); //calculate the updated rating, user count.

                $new_user_count = $updated_rate_result['new_user_count'];
                $new_total_rate = $updated_rate_result['new_total_rate'];
                $avg_rate = $updated_rate_result['avg_rate'];
                //set the object.
                $comment_res->setVoteCount($new_user_count);
                $comment_res->setVoteSum($new_total_rate);
                $comment_res->setAvgRating($avg_rate);
                break;
            }
        }

        //set object for dashboard post for updated rating..
        try {
            $dm->persist($comment_res); //storing the post data.
            $dm->flush();
            if ($count > 0) {
                //remove notification if post owner doent read the rate notiication.
                $this->removeNotification($comment_owner_id, $user_id, $item_id, $message_type);
                $data = array('avg_rate' => $this->roundNumber($avg_rate), 'no_of_votes' => $new_user_count);
            } else { //if record does not exists.
                $data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
                $this->returnResponse($data);
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($response_data);
    }


     /**
     * add the rate for user profile album.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    private function addUserProfileAlbumRate($item_type, $item_id, $rate, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $album_res = $dm->getRepository('MediaMediaBundle:UserAlbum')
                ->find($item_id);
        if (!$album_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $album_rating   = $album_res->getRate();
        //check if a user already rate on album.
        foreach ($album_rating as $album_rate) {
            if ($album_rate->getUserId() == $user_id) {
                $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                $this->returnResponse($res_data);
            }
        }

        $user_album_rating = new UserAlbumRating();
        $time = new \DateTime("now");

        //calculate the total, average rate.
        $total_user_count = $album_res->getVoteCount();
        $total_rate = $album_res->getVoteSum();

        $album_user_id =  $album_res->getUserId(); //get post owner id
        $updated_rate_result = $this->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count

        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $album_res->setVoteCount($new_user_count);
        $album_res->setVoteSum($new_total_rate);
        $album_res->setAvgRating($avg_rate);

        //set object for dashboard post rating
        $user_album_rating->setUserId($user_id);
        $user_album_rating->setRate($rate);
        $user_album_rating->setItemId($item_id);
        $user_album_rating->setType('album');
        $user_album_rating->setCreatedAt($time);
        $user_album_rating->setUpdatedAt($time);

        $album_res->addRate($user_album_rating);
        try {
            $dm->persist($album_res); //storing the post data.
            $dm->flush();

            if ($user_id != $album_user_id) {
                //prepare the mail template for dashboard post rating.
                $email_template_service = $this->container->get('email_template.service'); //email template service.

                $to_id   = $album_user_id;
                $from_id = $user_id;
                $album_id = $item_id;
                $album_name = $album_res->getAlbumName();

                $postService = $this->container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);

                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->user_album_rating;
                $notification_id = $this->saveUserNotification($user_id, $album_user_id, $item_id, $msgtype, $rate);
                $postService->sendUserNotifications($user_id, $album_user_id, $msgtype, 'rate', $item_id, false, true, $sender_name);
                //end to send social notification

                $href = $email_template_service->getDashboardAlbumUrl(array('friendId'=>$album_user_id, 'albumId'=> $album_id, 'albumName'=> $album_name));
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $mail_sub  = sprintf($lang_array['USER_ALBUM_RATE_SUBJECT'], $sender_name);
                $mail_body = sprintf($lang_array['USER_ALBUM_RATE_BODY'], $sender_name);
                $mail_text = sprintf($lang_array['USER_ALBUM_RATE_TEXT'], $sender_name, $rate);
                $bodyData      = $mail_text."<br><br>".$link;

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'RATING_NOTIFICATION');
            }

            $data = array('avg_rate'=>$this->roundNumber($avg_rate), 'current_user_rate'=>$rate, 'no_of_votes'=>$new_user_count);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            echo json_encode($response_data);
            exit;
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($response_data);
        exit;
    }

    /**
     * Edit User Album rate
     * @param int $comment_id
     * @param int $user_id
     */
    public function editUserProfileAlbumRate($item_type, $item_id, $rate, $user_id)
    {
        $arrayAlbumRate = array();
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $album   = $dm->getRepository('MediaMediaBundle:UserAlbum')
                          ->find($item_id);

        //if post not exist
        if(!$album){
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $album->getRate();
        $total_user_count = $album->getVoteCount();
        $total_rate = $album->getVoteSum();//old total rate

        //get current time
        $time = new \DateTime("now");
        foreach ($votes as $vote) {
            $voter_id = $vote->getUserId();
            //check if current user is voter of post
            if($user_id == $voter_id){
            $rate_id = $vote->getId(); //get rate id
            $current_user_rate = $vote->getRate();
            //preapre the object
            $arrayAlbumRate = array(
            "_id" => new \MongoId($rate_id),
            "user_id" => $vote->getUserId(),
            "rate" => (int)$rate,
            "item_id" => $vote->getItemId(),
            "type" => $vote->getType(),
            "created_at" =>$vote->getCreatedAt(),
            "updated_at" =>$time,
            );
            }
        }
       $rating_response = array();
        //edit rating object
        if(count($arrayAlbumRate)>0){
        $rating_response = $dm->getRepository('MediaMediaBundle:UserAlbum')
                           ->editAlbumRate($rate_id, $arrayAlbumRate, $item_id);

        }
        if(!$rating_response){
         $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
         $this->returnResponse($res_data);
        }

        //Update the rate count
        $resp = $this->updateEditRateCount($total_user_count, $total_rate, $item_id, $current_user_rate, $rate);
        $new_total_rate = $resp['total_rate'];
        $avg_rate = $resp['avg_rate'];

        $album->setVoteSum($new_total_rate);
        $album->setAvgRating($avg_rate);

        try {
            $dm->persist($album); //storing the post data.
            $dm->flush();
            //set response parameter
            $avg_rate_round = $this->roundNumber($avg_rate);
            $data = array('avg_rate'=>$avg_rate_round, 'current_user_rate'=>$rate, 'no_of_votes'=>$total_user_count);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);

    }

    /**
     * delete the rate for user profile album.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    private function deleteUserProfileAlbumRate($item_type, $item_id, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $album_res = $dm->getRepository('MediaMediaBundle:UserAlbum')
                ->find($item_id);
        //if posts is not exist.
        if (!$album_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->user_album_rating;
        $album_owner_id = $album_res->getUserId();
        $album_rating   = $album_res->getRate();
        $count = 0;
        foreach ($album_rating as $album_rate) {
            if ($album_rate->getUserId() == $user_id) {
                $count = 1;
                $user_rate = $album_rate->getRate();
                $album_res->removeRate($album_rate); // remove the rate post object.
                $total_user_count = $album_res->getVoteCount();
                $total_rate = $album_res->getVoteSum();
                $updated_rate_result = $this->updateDeleteRate($total_user_count, $total_rate, $user_rate); //calculate the updated rating, user count.

                $new_user_count = $updated_rate_result['new_user_count'];
                $new_total_rate = $updated_rate_result['new_total_rate'];
                $avg_rate = $updated_rate_result['avg_rate'];
                //set the object.
                $album_res->setVoteCount($new_user_count);
                $album_res->setVoteSum($new_total_rate);
                $album_res->setAvgRating($avg_rate);
                break;
            }
        }

        //set object for dashboard post for updated rating..
        try {
            $dm->persist($album_res); //storing the post data.
            $dm->flush();
            if ($count > 0) {
                //remove notification if post owner doent read the rate notiication.
                $this->removeNotification($album_owner_id, $user_id, $item_id, $message_type);
                $data = array('avg_rate' => $this->roundNumber($avg_rate), 'no_of_votes' => $new_user_count);
            } else { //if record does not exists.
                $data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
                $this->returnResponse($data);
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($response_data);
    }


     /**
     * add the rate for user profile album image.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    private function addUserProfileAlbumImageRate($item_type, $item_id, $rate, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $image_res = $dm->getRepository('MediaMediaBundle:UserMedia')
                ->find($item_id);
        if (!$image_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $image_rating  = $image_res->getRate();
        //check if a user already rate on user album images.
        foreach ($image_rating as $image_rate) {
            if ($image_rate->getUserId() == $user_id) {
                $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                $this->returnResponse($res_data);
            }
        }

        $user_album_image_rating = new UserMediaRating();
        $time = new \DateTime("now");

        //calculate the total, average rate.
        $total_user_count = $image_res->getVoteCount();
        $total_rate = $image_res->getVoteSum();

        $image_user_id =  $image_res->getUserId(); //get image owner id
        $updated_rate_result = $this->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count

        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $image_res->setVoteCount($new_user_count);
        $image_res->setVoteSum($new_total_rate);
        $image_res->setAvgRating($avg_rate);

        //set object for dashboard post rating
        $user_album_image_rating->setUserId($user_id);
        $user_album_image_rating->setRate($rate);
        $user_album_image_rating->setItemId($item_id);
        $user_album_image_rating->setType('profile_album_image');
        $user_album_image_rating->setCreatedAt($time);
        $user_album_image_rating->setUpdatedAt($time);

        $image_res->addRate($user_album_image_rating);
        try {
            $dm->persist($image_res); //storing the post data.
            $dm->flush();

            if ($user_id != $image_user_id) {
                //prepare the mail template for dashboard post rating.
                $email_template_service = $this->container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
                $dashboard_album_url     = $this->container->getParameter('user_album_url'); //dashboard post url
                $to_id   = $image_user_id;
                $from_id = $user_id;
                //getting media details
                $album_id = $image_res->getAlbumid();
                $album_name = "";
                if($album_id)
                {
                    $album_detail = $dm->getRepository('MediaMediaBundle:UserAlbum')->find($album_id);
                    $album_name = $album_detail->getAlbumName();
                    $album_user_id = $album_detail->getUserId();
                }

                $postService = $this->container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);


                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->user_photo_rating;
                $notification_id = $this->saveUserNotification($user_id, $image_user_id, $item_id, $msgtype, $rate);
                $postService->sendUserNotifications($user_id, $image_user_id, $msgtype, 'rate', $item_id, false, true, $sender_name, 'CITIZEN', array('owner'=>$image_user_id, 'album'=>$album_id));
                //end to send social notification


                $href = $email_template_service->getPageUrl(array('supportId'=>$image_user_id, 'parentId'=> $album_id, 'mediaId'=>$image_res->getId(), 'albumType'=>'user'),'single_image_page');
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $mail_sub  = sprintf($lang_array['USER_ALBUM_PHOTO_RATE_SUBJECT'], $sender_name);
                $mail_body = sprintf($lang_array['USER_ALBUM_PHOTO_RATE_BODY'], $sender_name);
                $mail_text = sprintf($lang_array['USER_ALBUM_PHOTO_RATE_TEXT'], $sender_name, $rate);
                $bodyData      = $mail_text."<br><br>".$link;

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'RATING_NOTIFICATION');
            }

            $data = array('avg_rate'=>$this->roundNumber($avg_rate), 'current_user_rate'=>$rate, 'no_of_votes'=>$new_user_count);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            echo json_encode($response_data);
            exit;
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($response_data);
        exit;
    }

    /**
     * Edit User Album Image rate
     * @param int $item_id
     * @param int $user_id
     */
    public function editUserProfileAlbumImageRate($item_type, $item_id, $rate, $user_id)
    {
        $arrayImageRate = array();
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $image = $dm->getRepository('MediaMediaBundle:UserMedia')
                          ->find($item_id);

        //if post not exist
        if(!$image){
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $image->getRate();
        $total_user_count = $image->getVoteCount();
        $total_rate = $image->getVoteSum();//old total rate

        //get current time
        $time = new \DateTime("now");
        foreach ($votes as $vote) {
            $voter_id = $vote->getUserId();
            //check if current user is voter of post
            if($user_id == $voter_id){
            $rate_id = $vote->getId(); //get rate id
            $current_user_rate = $vote->getRate();
            //preapre the object
            $arrayImageRate = array(
            "_id" => new \MongoId($rate_id),
            "user_id" => $vote->getUserId(),
            "rate" => (int)$rate,
            "item_id" => $vote->getItemId(),
            "type" => $vote->getType(),
            "created_at" =>$vote->getCreatedAt(),
            "updated_at" =>$time,
            );
            }
        }
       $rating_response = array();
        //edit rating object
        if(count($arrayImageRate)>0){
        $rating_response = $dm->getRepository('MediaMediaBundle:UserMedia')
                           ->editMediaRate($rate_id, $arrayImageRate, $item_id);

        }
        if(!$rating_response){
         $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
         $this->returnResponse($res_data);
        }

        //Update the rate count
        $resp = $this->updateEditRateCount($total_user_count, $total_rate, $item_id, $current_user_rate, $rate);
        $new_total_rate = $resp['total_rate'];
        $avg_rate = $resp['avg_rate'];

        $image->setVoteSum($new_total_rate);
        $image->setAvgRating($avg_rate);

        try {
            $dm->persist($image); //storing the post data.
            $dm->flush();
            //set response parameter
            $avg_rate_round = $this->roundNumber($avg_rate);
            $data = array('avg_rate'=>$avg_rate_round, 'current_user_rate'=>$rate, 'no_of_votes'=>$total_user_count);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);

    }

    /**
     * delete the user profile album image rate.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    private function deleteUserProfileAlbumImageRate($item_type, $item_id, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $image_res = $dm->getRepository('MediaMediaBundle:UserMedia')
                ->find($item_id);
        //if posts is not exist.
        if (!$image_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->user_photo_rating;
        $image_owner_id = $image_res->getUserId();
        $image_rating   = $image_res->getRate();
        $count = 0;
        foreach ($image_rating as $image_rate) {
            if ($image_rate->getUserId() == $user_id) {
                $count = 1;
                $user_rate = $image_rate->getRate();
                $image_res->removeRate($image_rate); // remove the rate post object.
                $total_user_count = $image_res->getVoteCount();
                $total_rate = $image_res->getVoteSum();
                $updated_rate_result = $this->updateDeleteRate($total_user_count, $total_rate, $user_rate); //calculate the updated rating, user count.

                $new_user_count = $updated_rate_result['new_user_count'];
                $new_total_rate = $updated_rate_result['new_total_rate'];
                $avg_rate = $updated_rate_result['avg_rate'];
                //set the object.
                $image_res->setVoteCount($new_user_count);
                $image_res->setVoteSum($new_total_rate);
                $image_res->setAvgRating($avg_rate);
                break;
            }
        }

        //set object for dashboard post for updated rating..
        try {
            $dm->persist($image_res); //storing the post data.
            $dm->flush();
            if ($count > 0) {
                //remove notification if post owner doent read the rate notiication.
                $this->removeNotification($image_owner_id, $user_id, $item_id, $message_type);
                $data = array('avg_rate' => $this->roundNumber($avg_rate), 'no_of_votes' => $new_user_count);
            } else { //if record does not exists.
                $data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
                $this->returnResponse($data);
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($response_data);
    }

    /**
     * add the rate for user album Comments.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    private function addUserAlbumCommentRate($item_type, $item_id, $rate, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('MediaMediaBundle:AlbumComment')
                          ->find($item_id);
        if (!$comment_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $comment_rating   = $comment_res->getRate();
        //check if a user already rate on post comment.
        foreach ($comment_rating as $comment_rate) {
            if ($comment_rate->getUserId() == $user_id) {
                $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                $this->returnResponse($res_data);
            }
        }

      //  $dashboard_comment_rating = new DashboardCommentRating();
        $useralbum_comment_rating = new AlbumCommentRating();
        $time = new \DateTime("now");
         $_albumId = $comment_res->getAlbumId();
        //calculate the total, average rate.
        $total_user_count = $comment_res->getVoteCount();
        $total_rate = $comment_res->getVoteSum();

        $user_album_comment_user_id = $comment_owner_id = $comment_res->getCommentAuthor(); //get comment owner id
        $updated_rate_result = $this->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count

        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $comment_res->setVoteCount($new_user_count);
        $comment_res->setVoteSum($new_total_rate);
        $comment_res->setAvgRating($avg_rate);

        //set object for dashboard post rating
        $useralbum_comment_rating->setUserId($user_id);
        $useralbum_comment_rating->setRate($rate);
        $useralbum_comment_rating->setItemId($item_id);
        $useralbum_comment_rating->setType('user_album_comment');
        $useralbum_comment_rating->setCreatedAt($time);
        $useralbum_comment_rating->setUpdatedAt($time);

        $comment_res->addRate($useralbum_comment_rating);
        try {
            $dm->persist($comment_res); //storing the rating data.
            $dm->flush();

            if ($user_id != $comment_owner_id) {
                $msgtype = $this->useralbum_comment_rating;
                //end to send social notification
                $album_res = $dm->getRepository('MediaMediaBundle:UserAlbum')
                ->find($_albumId);
                //prepare the mail template for dashboard post rating.
                $email_template_service = $this->container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
                $useralbum_url          =  $this->container->getParameter('user_album_url');
                $to_id   = $user_album_comment_user_id;
                $from_id = $user_id;
                $post_id = $item_id;
                //get the local parameters in parameters file.

                $postService = $this->container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                $sender = $postService->getUserData($from_id);
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
                $language_const_array = $this->container->getParameter($locale);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                $albumName = $album_res->getAlbumName();
                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, $rate, $_albumId, true, true, array($sender_name, ucwords($albumName)), 'CITIZEN', array('msg_code'=>'rate'), 'U', array('comment_id'=>$item_id));
                $href = $angular_app_hostname . $useralbum_url . '/' . $_albumId.'/'.$albumName;
                $link = $email_template_service->getLinkForMail($href); //making the link html from service
                $mail_sub  = sprintf($language_const_array['USER_ALBUM_COMMENT_RATE_SUBJECT'], $sender_name, ucwords($albumName));
                $mail_body = sprintf($language_const_array['USER_ALBUM_COMMENT_RATE_BODY'], ucwords($sender_name), ucwords($albumName));
                $mail_text = sprintf($language_const_array['USER_ALBUM_COMMENT_RATE_TEXT'], $sender_name, ucwords($albumName), $rate);
                $bodyData      = $mail_text."<br><br>".$link;

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'RATING_NOTIFICATION');
            }

            $data = array('avg_rate'=>$this->roundNumber($avg_rate), 'current_user_rate'=>$rate, 'no_of_votes'=>$new_user_count);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            echo json_encode($response_data);
            exit;
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($response_data);
        exit;
    }
    /**
     * delete the rate for dahboard post comments.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    private function deleteUserAlbumCommentRate($item_type, $item_id, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('MediaMediaBundle:AlbumComment')
                               ->find($item_id);
        //if posts is not exist.
        if (!$comment_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->useralbum_comment_rating;
        $comment_owner_id = $comment_res->getCommentAuthor();

        $comment_rating   = $comment_res->getRate();
        $count = 0;
        foreach ($comment_rating as $comment_rate) {

            if ($comment_rate->getUserId() == $user_id) {  // only that user can remove rate who has rated it.

                $count = 1;
                $user_rate = $comment_rate->getRate();
                $comment_res->removeRate($comment_rate); // remove the rate post object.
                $total_user_count = $comment_res->getVoteCount();
                $total_rate = $comment_res->getVoteSum();
                $updated_rate_result = $this->updateDeleteRate($total_user_count, $total_rate, $user_rate); //calculate the updated rating, user count.

                $new_user_count = $updated_rate_result['new_user_count'];
                $new_total_rate = $updated_rate_result['new_total_rate'];
                $avg_rate = $updated_rate_result['avg_rate'];
                //set the object.
                $comment_res->setVoteCount($new_user_count);
                $comment_res->setVoteSum($new_total_rate);
                $comment_res->setAvgRating($avg_rate);
                break;
            }
        }

        //set object for user album comment for updated rating..
        try {

            $dm->persist($comment_res); //storing the post data.
            $dm->flush();
            if ($count > 0) {
                //remove notification if post owner doent read the rate notiication.
                $this->removeNotification($comment_owner_id, $user_id, $item_id, $message_type);
                $data = array('avg_rate' => $this->roundNumber($avg_rate), 'no_of_votes' => $new_user_count);
            } else { //if record does not exists.
                $data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
                $this->returnResponse($data);
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($response_data);
    }

    /**
     * Edit User album Comment rate
     * @param int $comment_id
     * @param int $user_id
     */
    public function editUserAlbumCommentRate($item_type, $comment_id, $rate, $user_id)
    {
        $arrayCommentRate = array();
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $comment   = $dm->getRepository('MediaMediaBundle:AlbumComment')
                          ->findOneBy(array('id'=>$comment_id));


        //if post not exist
        if(!$comment){
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $comment->getRate();
        $total_user_count = $comment->getVoteCount();
        $total_rate = $comment->getVoteSum();//old total rate

        //get current time
        $time = new \DateTime("now");
        foreach ($votes as $vote) {

            $voter_id = $vote->getUserId();
            //check if current user is voter of post
            if($user_id == $voter_id){
            $rate_id = $vote->getId(); //get rate id
            $current_user_rate = $vote->getRate();
            //preapre the object
            $arrayCommentRate = array(
                                "_id" => new \MongoId($rate_id),
                                "user_id" => $vote->getUserId(),
                                "rate" => (int)$rate,
                                "item_id" => $vote->getItemId(),
                                "type" => $vote->getType(),
                                "created_at" =>$vote->getCreatedAt(),
                                "updated_at" =>$time,
                            );
            }
        }
       $rating_response = array();
        //edit rating object
        if(count($arrayCommentRate)>0){
        $rating_response = $dm->getRepository('MediaMediaBundle:AlbumComment')
                           ->editCommentRate($rate_id, $arrayCommentRate, $comment_id);

        }
        if(!$rating_response){
         $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
         $this->returnResponse($res_data);
        }

        //Update the rate count
        $resp = $this->updateEditRateCount($total_user_count, $total_rate, $comment_id, $current_user_rate, $rate);
        $new_total_rate = $resp['total_rate'];
        $avg_rate = $resp['avg_rate'];

        $comment->setVoteSum($new_total_rate);
        $comment->setAvgRating($avg_rate);

        try {
            $dm->persist($comment); //storing the post data.
            $dm->flush();
            //set response parameter
            $avg_rate_round = $this->roundNumber($avg_rate);
            $data = array('avg_rate'=>$avg_rate_round, 'current_user_rate'=>$rate, 'no_of_votes'=>$total_user_count);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);

    }
}
