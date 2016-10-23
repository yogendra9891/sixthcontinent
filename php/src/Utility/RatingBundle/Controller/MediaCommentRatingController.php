<?php

namespace Utility\RatingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Notification\NotificationBundle\Document\UserNotifications;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Media\MediaBundle\Document\AlbumMediaCommentRating;


/**
 * class for handing the rating system
 */
class MediaCommentRatingController extends Controller
{
    protected $rating_type = array('user_album_media_comment','club_album_media_comment','dashboard_post_media_comment','shop_album_media_comment',
                                   'club_post_media_comment', 'shop_post_media_comment');
    protected $rating_stars = array(1,2,3,4,5);
    protected $user_album_media_rating = "USER_ALBUM_MEDIA_RATE";
    protected $club_album_media_rating = "CLUB_ALBUM_MEDIA_RATE";
    protected $shop_album_media_rating = "SHOP_ALBUM_MEDIA_RATE";
    protected $user_album_media_comment_rating = "USER_ALBUM_MEDIA_COMMENT_RATE";
    protected $dashboardpost_media_rating = "DASHBOARD_POST_MEDIA_RATE";
    protected $dashboardpost_media_comment_rating = "DASHBOARD_POST_MEDIA_COMMENT_RATE";

        /**
     * add rate for a type
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postAddmediacommentratesAction(Request $request) {
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
        $required_parameter = array('user_id', 'comment_id', 'type', 'type_id', 'rate');
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
        $comment_id = $object_info->comment_id;

        //check for rating type
        if (!in_array($item_type, $this->rating_type)) {
            return array('code' => 81, 'message' => 'RATING_TYPE_NOT_SUPPPORTED', 'data' => $data);
        }
        //check for star count
        if (!in_array($object_info->rate, $this->rating_stars)) {
            return array('code' => 80, 'message' => 'RATING_VALUE_NOT_SUPPPORTED', 'data' => $data);
        }

        switch ($item_type) {
            case 'user_album_media_comment':
                $this->addUserAlbumMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id);

            case 'club_album_media_comment':
                //get club rating class object
                $club_rating = new ClubRatingController();
                $club_rating->addClubAlbumMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id);
                break;

            case 'shop_album_media_comment':
                 //get store rating object
                $storerating = new StoreRatingController();
                $storerating->addStoreAlbumMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id);
                break;
            case 'dashboard_post_media_comment':
                //get dashboardpost media rating object
                $this->addDashboardPostMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id);
                break;
            case 'club_post_media_comment':
                //get club rating class object
                $club_rating = new ClubRatingController();
                $club_rating->addClubWallPostMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id);
                break;
            case 'shop_post_media_comment':
                $shopRating = new StoreRatingController();
                $shopRating->addStorePostMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id);
                break;
        }
        exit;
    }

    /**
     * Edit Rate
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postEditmediacommentratesAction(Request $request) {
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

        $required_parameter = array('user_id', 'comment_id', 'type', 'type_id', 'rate');
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
        $comment_id = $object_info->comment_id;

        switch ($type)
        {
            case 'user_album_media_comment':
                $this->editUserAlbumMediaCommentRate($type, $comment_id, $type_id, $rate, $user_id);
                break;

            case 'club_album_media_comment':
                $club_rating = new ClubRatingController();
                $club_rating->editClubAlbumMediaCommentRate($type, $comment_id, $type_id, $rate, $user_id);
                break;
            case 'dashboard_post_media_comment':
                //get dashboardpost media rating object
                $this->editDashboardPostMediaCommentRate($type, $comment_id, $type_id, $rate, $user_id);
                break;
            case 'club_post_media_comment':
                //get club rating class object
                $club_rating = new ClubRatingController();
                $club_rating->editClubWallPostMediaCommentRate($type, $comment_id, $type_id, $rate, $user_id);
                break;
            case 'shop_post_media_comment':
                $shopRate = new StoreRatingController();
                $shopRate->editStorePostMediaCommentRate($type, $comment_id, $type_id, $rate, $user_id);
                break;
            case 'shop_album_media_comment':
                $storerating = new StoreRatingController();
                $storerating->editStoreAlbumMediaCommentRate($type, $comment_id, $type_id, $rate, $user_id);
                break;
        }
        exit;
    }

    /**
     * remove rate for a type
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postDeletemediacommentratesAction(Request $request) {
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
        $required_parameter = array('user_id', 'comment_id', 'type', 'type_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        //extract variables.
        $item_type = $object_info->type;
        $item_id = $object_info->type_id;
        $user_id = $object_info->user_id;
        $comment_id = $object_info->comment_id;

        if (!in_array($item_type, $this->rating_type)) {
            return array('code' => 81, 'message' => 'RATING_TYPE_NOT_SUPPPORTED', 'data' => $data);
        }

        switch ($item_type) {
            case 'user_album_media_comment':
                $this->deleteUserAlbumMediaCommentRate($item_type, $comment_id, $item_id, $user_id);
                break;

            case 'club_album_media_comment':
                $club_rating = new ClubRatingController();
                $club_rating->deleteClubAlbumMediaCommentRate($item_type, $comment_id, $item_id, $user_id);
                break;
            case 'dashboard_post_media_comment':
                //get dashboardpost media rating object
                $this->deleteDashboardPostMediaCommentRate($item_type, $comment_id, $item_id, $user_id);
                break;
            case 'club_post_media_comment':
                //get club rating class object
                $club_rating = new ClubRatingController();
                $club_rating->deleteClubWallPostMediaCommentRate($item_type, $comment_id, $item_id, $user_id);
                break;
            case 'shop_post_media_comment':
                $shopRate = new StoreRatingController();
                $shopRate->deleteStorePostMediaCommentRate($item_type, $comment_id, $item_id, $user_id);
                break;
            case 'shop_album_media_comment':
                $storerating = new StoreRatingController();
                $storerating->deleteStoreAlbumMediaCommentRate($item_type, $comment_id, $item_id, $user_id);
                break;
        }
        exit;
    }


    /**
     * add the rating to user album Media comments
    */

    public function addUserAlbumMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id)
    {
        $data = array();

        //get container object
        $container = NManagerNotificationBundle::getContainer();

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $media_res = $dm->getRepository('MediaMediaBundle:UserMedia')
                ->findOneBy(array('id' => $item_id)); //@TODO Add group owner id in AND clause.

        //check if club exist or not
        if (!$media_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $album_id = $media_res->getAlbumid();
        $mediarating = new AlbumMediaCommentRating();
        $time = new \DateTime("now");


//        $club_rating_res   = $media_res->getRate();
//        //check if a user already rate on post.
//        foreach ($club_rating_res as $club_rate) {
//            if ($club_rate->getUserId() == $user_id) {
//                $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
//                $this->returnResponse($res_data);
//            }
//        }


        $media_comments = $media_res->getComment();
        $comment_exists = false;
        if($media_comments){
            //check comments one by one.
            foreach ($media_comments as $comment) {
                if ($comment->getId() == $comment_id) {
                    $comment_exists = true;
                    $comment_res = $comment;
                    //comment
                    $comment_rates = $comment->getRate();
                    foreach($comment_rates as $comment_rate){
                        if($comment_rate->getUserId() == $user_id){
                            $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                            $this->returnResponse($res_data);
                        }

                    }
                }
            }
        } else {
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //check if comment exists
        if(!$comment_exists){
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //calculate the total, average rate.
        $total_user_count = $comment_res->getVoteCount();
        $total_rate = $comment_res->getVoteSum();

        //calculate the new rate ,total user count
        $updated_rate_result=$calculaterate->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count


        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $comment_res->setVoteCount($new_user_count);
        $comment_res->setVoteSum($new_total_rate);
        $comment_res->setAvgRating($avg_rate);

        $comment_owner_id = $comment_res->getCommentAuthor(); //get club owner id

        //set object for media comment rating
        $mediarating->setUserId($user_id);
        $mediarating->setRate($rate);
        $mediarating->setItemId($item_id);
        $mediarating->setCreatedAt($time);
        $mediarating->setUpdatedAt($time);

        $comment_res->addRate($mediarating);
        try {
            $dm->persist($comment_res); //storing the post data.
            $dm->flush();

            if ($user_id != $comment_owner_id)
            {
                //send social notification
                $msgtype = $this->user_album_media_comment_rating;

                /*Send Email Notification using send grid function*/
                //prepare the mail template for dashboard post rating.
                $email_template_service = $this->container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
                $dashboard_post_url     = $this->container->getParameter('user_album_url'); //dashboard post url
                $to_id   = $comment_owner_id;
                $from_id = $user_id;
              //  $comment_id = $item_id;

                $postService = $this->container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);

                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, $rate, $item_id, true, true, array($sender_name), 'CITIZEN', array('msg_code'=>'rate','album_id'=>$album_id), 'U', array('comment_id'=>$comment_id));

                $href = $angular_app_hostname . $dashboard_post_url . '/' . $item_id;
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $subject  = sprintf($lang_array['USER_ALBUM_MEDIA_COMMENT_RATE_SUBJECT'], ucwords($sender_name));
                $mail_body = sprintf($lang_array['USER_ALBUM_MEDIA_COMMENT_RATE_SUBJECT'], ucwords($sender_name));
                $mail_text = sprintf($lang_array['USER_ALBUM_MEDIA_COMMENT_RATE_TEXT'], ucwords($sender_name), $rate);
                $bodyData      = $mail_text."<br><br>".$link;

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $subject, $sender['profile_image_thumb'], 'USER_ALBUM_MEDIA_RATING_NOTIFICATION');
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
     * Edit user album media comment rate
     * @param int $post_id
     * @param int $user_id
     */
    public function editUserAlbumMediaCommentRate($item_type, $comment_id, $type_id, $rate, $user_id) {
        $arrayCommentRate = array();
        $data = array();

        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $media_res = $dm->getRepository('MediaMediaBundle:UserMedia')->find($type_id);

        //if post not exist
        if (!$media_res) {
            $res_data = array('code' => 302, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $mediarating = new AlbumMediaCommentRating();
        $time = new \DateTime("now");

        $media_comments = $media_res->getComment();
        $comment_exists = false;
        $rate_exists = false;
        if($media_comments){
            //check comments one by one.
            foreach ($media_comments as $comment) {
                if ($comment->getId() == $comment_id) {
                    $comment_exists = true;
                    $comment_res = $comment;
                    $total_user_count = $comment->getVoteCount();
                    $total_rate = $comment->getVoteSum();

                    //comment
                    $comment_rates = $comment->getRate();
                    foreach($comment_rates as $comment_rate){
                        if($comment_rate->getUserId() == $user_id){
                            $rate_exists = true;

                            $rate_id = $comment_rate->getId(); //get rate id
                            $current_user_rate = $comment_rate->getRate();
                            //set new rate
                            $comment_rate->setRate((int) $rate);
                            $comment_rate->setUpdatedAt($time);
                        }

                    }
                }
            }
        } else {
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //check if comment exists
        if(!$comment_exists){
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //check if vote exists
        if(!$rate_exists){
            $res_data = array('code' => 302, 'message' => 'VOTE_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');
        //Update the rate count
        $resp = $calculaterate->updateEditRateCount($total_user_count, $total_rate, $comment_id, $current_user_rate, $rate);
        $new_total_rate = $resp['total_rate'];
        $avg_rate = $resp['avg_rate'];

        $comment_res->setVoteSum($new_total_rate);
        $comment_res->setAvgRating($avg_rate);

        try {

            $dm->persist($comment_rate); //storing the post data.
            $dm->persist($comment_res); //storing the post data.
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
     * delete the rate for user media comments.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    private function deleteUserAlbumMediaCommentRate($item_type, $comment_id, $item_id, $user_id) {
        $data = array();

        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $media_res = $dm->getRepository('MediaMediaBundle:UserMedia')->find($item_id);
        //if posts is not exist.
        if (!$media_res) {
            $res_data = array('code' => 302, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $mediarating = new AlbumMediaCommentRating();
        $time = new \DateTime("now");

        $media_comments = $media_res->getComment();
        $comment_exists = false;
        $rate_exists = false;
        if($media_comments){
            //check comments one by one.
            foreach ($media_comments as $comment) {
                if ($comment->getId() == $comment_id) {
                    $comment_exists = true;
                    $comment_res = $comment;
                    $total_user_count = $comment->getVoteCount();
                    $total_rate = $comment->getVoteSum();

                    //comment
                    $comment_rates = $comment->getRate();
                    foreach($comment_rates as $comment_rate){
                        if($comment_rate->getUserId() == $user_id){
                            $rate_exists = true;

                            $user_rate = $comment_rate->getRate();
                            $comment_res->removeRate($comment_rate); // remove the rate post object.
                            $total_user_count = $comment_res->getVoteCount();
                            $total_rate = $comment_res->getVoteSum();
                            //load the calculate_rate_service
                            $calculaterate=$container->get('calculate_rate_service');
                            $updated_rate_result = $calculaterate->updateDeleteRate($total_user_count, $total_rate, $user_rate); //calculate the updated rating, user count.

                            $new_user_count = $updated_rate_result['new_user_count'];
                            $new_total_rate = $updated_rate_result['new_total_rate'];
                            $avg_rate = $updated_rate_result['avg_rate'];
                            //set the object.
                            $comment_res->setVoteCount($new_user_count);
                            $comment_res->setVoteSum($new_total_rate);
                            $comment_res->setAvgRating($avg_rate);

                        }

                    }
                }
            }
        } else {
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //check if comment exists
        if(!$comment_exists){
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //check if vote exists
        if(!$rate_exists){
            $res_data = array('code' => 302, 'message' => 'VOTE_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->user_album_media_rating;
        $comment_owner_id = $comment_res->getCommentAuthor();

        //set object for dashboard post for updated rating..
        try {
            $dm->persist($comment_res); //storing the post data.
            $dm->flush();
            if ($rate_exists) {
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
     * Save Multi user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveMultiUserNotification($user_ids, $fid, $item_id, $msgtype, $msg) {
        //get container object
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager');
        foreach($user_ids as $user_id){
        //notification will not be send to the user who is rating
        if($user_id != $fid){
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
        }
        }
        $dm->flush();
        return $notification->getId();
    }

     /**
     * round off a float number
     * @param float $number
     * @return float
     */
    private function roundNumber($number) {
        $rounded_number = round($number, 1);
        return $rounded_number;
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
     * get rated user list for a item.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postGetratedusersofmediacommentsAction(Request $request) {
        //call the service for getting the request object.
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);

        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('type', 'item_id','comment_id');
        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }

        $item_type = $object_info->type;
        $item_id = $object_info->item_id;
        $comment_id = $object_info->comment_id;
        $limit_size = isset($object_info->limit_size) ? $object_info->limit_size : 1000;
        $limit_start = isset($object_info->limit_start) ? $object_info->limit_start : 0;


        switch ($item_type) {
            case 'user_album_media_comment':
                 //get user rating class object
                 $this->getUserAlbumMediaCommentRateUsers($item_type, $item_id, $comment_id, $limit_start, $limit_size);
            case 'club_album_media_comment':
                 //get club rating class object
                $club_rating = new ClubRatingController();
                $club_rating->getClubAlbumMediaCommentRateUsers($item_type, $item_id, $comment_id, $limit_start, $limit_size);
                break;
            case 'dashboard_post_media_comment':
                $this->getDashboardPostMediaCommentRateUsers($item_type, $item_id, $comment_id, $limit_start, $limit_size);
                break;
            case 'club_post_media_comment':
                //get club rating class object
                $club_rating = new ClubRatingController();
                $club_rating->getClubWallPostMediaCommentRateUsers($item_type, $item_id, $comment_id, $limit_start, $limit_size);
                break;
            case 'shop_post_media_comment':
                $shopRate = new StoreRatingController();
                $shopRate->getStorePostMediaCommentRateUsers($item_type, $item_id, $comment_id, $limit_start, $limit_size);
                break;
            case 'shop_album_media_comment':
                $storerating = new StoreRatingController();
                $storerating->getStoreAlbumMediaCommentRateUsers($item_type, $item_id, $comment_id, $limit_start, $limit_size);
                break;

        }
        exit;
    }
    /**
     * List rated users on clum album media comment
     * @param int $item_type
     * @param int $item_id
     */
    public function getUserAlbumMediaCommentRateUsers($item_type, $item_id,$comment_id, $limit_start, $limit_size) {

        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $media_res = $dm->getRepository('MediaMediaBundle:UserMedia')
                ->findOneBy(array('id' => $item_id)); //@TODO Add group owner id in AND clause.

        //if post not exist
        if (!$media_res) {
             $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $media_comments = $media_res->getComment();
        $comment_exists = false;
        $voters_array = array();
        if($media_comments){
            //check comments one by one.
            foreach ($media_comments as $comment) {
                if ($comment->getId() == $comment_id) {
                    $comment_exists = true;
                    $comment_res = $comment;
                    $total_user_count = $comment_res->getVoteCount();
                    $votes = $comment_res->getRate();
                    //get total votes count
                    if($votes){
                        $count = count($votes);
                    } else {
                        $count = 0;
                    }

                    $vote_count = $limit_size + $limit_start;
                    foreach($votes as $i=>$vote)
                    {
                        if($i >= $limit_start && $i < $vote_count ){
                            $voter_id = $vote->getUserId();
                            $rate = $vote->getRate();
                            $voters_array[] = $vote->getUserId();
                            //define rate by user
                            $rate_users[$voter_id] = $rate;
                        }
                   }
                }
            }
        } else {
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }
        //get user object
        $user_service = $container->get('user_object.service');
        $user_objects_rated = $user_service->MultipleUserObjectService($voters_array);
        //preapare the users array with user object

        foreach($user_objects_rated as $user_objects_rated_single){
            //get voter id
            $voter_id = $user_objects_rated_single['id'];
            $user_rate = isset($rate_users[$voter_id])? $rate_users[$voter_id]: 0;
            $user_array = array('rate'=>$user_rate);
            $users[] = array_merge($user_objects_rated_single,$user_array);
        }

        $data = array('type_id' => $item_id, 'type' => $item_type, 'total_users' => $total_user_count, 'users_rated' => $users);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }

    /**
     * Rating on dashboard post media
     * @param type $item_type
     * @param type $comment_id
     * @param type $item_id
     * @param type $rate
     * @param type $user_id
     */
    public function addDashboardPostMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id){
        $data = array();
       //get container object
        $container = NManagerNotificationBundle::getContainer();
          //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $image_res = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                         ->find($item_id);
        if (!$image_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $mediarating = new AlbumMediaCommentRating();
        $time = new \DateTime("now");

        $media_comments = $image_res->getComment();
        $comment_exists = false;
        if($media_comments){
            //check comments one by one.
            foreach ($media_comments as $comment) {
                if ($comment->getId() == $comment_id) {
                    $comment_exists = true;
                    $comment_res = $comment;
                    //comment
                    $comment_rates = $comment->getRate();
                    foreach($comment_rates as $comment_rate){
                        if($comment_rate->getUserId() == $user_id){
                            $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                            $this->returnResponse($res_data);
                        }

                    }
                }
            }
        } else {
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //check if comment exists
        if(!$comment_exists){
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //calculate the total, average rate.
        $total_user_count = $comment_res->getVoteCount();
        $total_rate = $comment_res->getVoteSum();

        //calculate the new rate ,total user count
        $updated_rate_result=$calculaterate->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count


        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $comment_res->setVoteCount($new_user_count);
        $comment_res->setVoteSum($new_total_rate);
        $comment_res->setAvgRating($avg_rate);

        $comment_owner_id = $comment_res->getCommentAuthor(); //get club owner id

        //set object for media comment rating
        $mediarating->setUserId($user_id);
        $mediarating->setRate($rate);
        $mediarating->setItemId($item_id);
        $mediarating->setCreatedAt($time);
        $mediarating->setUpdatedAt($time);

        $comment_res->addRate($mediarating);
        try {
            $dm->persist($comment_res); //storing the rating data.
            $dm->flush();

            if ($user_id != $comment_owner_id) {
                //send social notification
                $msgtype = $this->dashboardpost_media_comment_rating;

                $notification_obj = $container->get('rating_notification_service');
                $notification_id  = $notification_obj->saveUserNotification($user_id, $comment_owner_id, $item_id, $msgtype, $rate);
                //end to send social notification

                //prepare the mail template for dashboard post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $container->getParameter('angular_app_hostname'); //angular app host
               // $dashboard_post_url     = $this->container->getParameter('dashboard_post_url'); //dashboard post url
                $storealbum_url          = $container->getParameter('dashboard_post_url');
                $to_id   = $comment_owner_id;
                $from_id = $user_id;
                $post_id = $item_id;

                $postService = $container->get('post_detail.service');
                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                $receiver = $postService->getUserData($to_id);
                $locale = !empty($receiver['current_language']) ? $receiver['current_language'] : $container->getParameter('locale');
                $language_const_array = $container->getParameter($locale);

                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, $rate, $post_id, true, true, $sender_name, 'CITIZEN', array('msg_code'=>'rate'), 'U', array('comment_id'=>$comment_id));
                $href = $angular_app_hostname . $storealbum_url . '/' . $post_id;
                $link = $email_template_service->getLinkForMail($href); //making the link html from service
                $mail_sub  = sprintf($language_const_array['DASHBOARD_POST_MEDIA_COMMENT_RATE_SUBJECT'], $sender_name);
                $mail_body = sprintf($language_const_array['DASHBOARD_POST_MEDIA_COMMENT_RATE_BODY'], ucwords($sender_name), $rate);
                $mail_text = sprintf($language_const_array['DASHBOARD_POST_MEDIA_COMMENT_RATE_TEXT'],ucwords($sender_name), $rate);
                $bodyData      = $mail_text."<br><br>".$link;


                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $email_template_service->sendMail(array($receiver), $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'RATING_NOTIFICATION');
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
     * edit raing for dashboard post media comment
     * @param type $item_type
     * @param type $comment_id
     * @param type $item_id
     * @param type $rate
     * @param type $user_id
     */
    public function editDashboardPostMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id){
        $arrayCommentRate = array();
        $data = array();

        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $media_res = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')->find($item_id);

        //if post not exist
        if (!$media_res) {
            $res_data = array('code' => 302, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $mediarating = new AlbumMediaCommentRating();
        $time = new \DateTime("now");

        $media_comments = $media_res->getComment();
        $comment_exists = false;
        $rate_exists = false;
        if($media_comments){
            //check comments one by one.
            foreach ($media_comments as $comment) {
                if ($comment->getId() == $comment_id) {
                    $comment_exists = true;
                    $comment_res = $comment;
                    $total_user_count = $comment->getVoteCount();
                    $total_rate = $comment->getVoteSum();

                    //comment
                    $comment_rates = $comment->getRate();
                    foreach($comment_rates as $comment_rate){
                        if($comment_rate->getUserId() == $user_id){
                            $rate_exists = true;

                            $rate_id = $comment_rate->getId(); //get rate id
                            $current_user_rate = $comment_rate->getRate();
                            //set new rate
                            $comment_rate->setRate((int) $rate);
                            $comment_rate->setUpdatedAt($time);
                        }

                    }
                }
            }
        } else {
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //check if comment exists
        if(!$comment_exists){
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //check if vote exists
        if(!$rate_exists){
            $res_data = array('code' => 302, 'message' => 'VOTE_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');
        //Update the rate count
        $resp = $calculaterate->updateEditRateCount($total_user_count, $total_rate, $comment_id, $current_user_rate, $rate);
        $new_total_rate = $resp['total_rate'];
        $avg_rate = $resp['avg_rate'];

        $comment_res->setVoteSum($new_total_rate);
        $comment_res->setAvgRating($avg_rate);

        try {

            $dm->persist($comment_rate); //storing the post data.
            $dm->persist($comment_res); //storing the post data.
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
     * delete rating on dashboard post media comment rating
     * @param type $item_type
     * @param type $comment_id
     * @param type $item_id
     * @param type $user_id
     */
    public function deleteDashboardPostMediaCommentRate($item_type, $comment_id, $item_id, $user_id){
        $data = array();
        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $media_res = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')->find($item_id);
        //if posts is not exist.
        if (!$media_res) {
            $res_data = array('code' => 302, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $mediarating = new AlbumMediaCommentRating();
        $time = new \DateTime("now");

        $media_comments = $media_res->getComment();
        $comment_exists = false;
        $rate_exists = false;
        if($media_comments){
            //check comments one by one.
            foreach ($media_comments as $comment) {
                if ($comment->getId() == $comment_id) {
                    $comment_exists = true;
                    $comment_res = $comment;
                    $total_user_count = $comment->getVoteCount();
                    $total_rate = $comment->getVoteSum();

                    //comment
                    $comment_rates = $comment->getRate();
                    foreach($comment_rates as $comment_rate){
                        if($comment_rate->getUserId() == $user_id){
                            $rate_exists = true;

                            $user_rate = $comment_rate->getRate();
                            $comment_res->removeRate($comment_rate); // remove the rate post object.
                            $total_user_count = $comment_res->getVoteCount();
                            $total_rate = $comment_res->getVoteSum();
                            //load the calculate_rate_service
                            $calculaterate=$container->get('calculate_rate_service');
                            $updated_rate_result = $calculaterate->updateDeleteRate($total_user_count, $total_rate, $user_rate); //calculate the updated rating, user count.

                            $new_user_count = $updated_rate_result['new_user_count'];
                            $new_total_rate = $updated_rate_result['new_total_rate'];
                            $avg_rate = $updated_rate_result['avg_rate'];
                            //set the object.
                            $comment_res->setVoteCount($new_user_count);
                            $comment_res->setVoteSum($new_total_rate);
                            $comment_res->setAvgRating($avg_rate);

                        }

                    }
                }
            }
        } else {
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //check if comment exists
        if(!$comment_exists){
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //check if vote exists
        if(!$rate_exists){
            $res_data = array('code' => 302, 'message' => 'VOTE_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->dashboardpost_media_rating;
        $comment_owner_id = $comment_res->getCommentAuthor();

        //set object for dashboard post for updated rating..
        try {
            $dm->persist($comment_res); //storing the post data.
            $dm->flush();
            if ($rate_exists) {
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
     * details of all rated users
     * @param type $item_type
     * @param type $item_id
     * @param type $comment_id
     * @param type $limit_start
     * @param type $limit_size
     */
    public function getDashboardPostMediaCommentRateUsers($item_type, $item_id, $comment_id, $limit_start, $limit_size){
        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $media_res = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                ->findOneBy(array('id' => $item_id)); //@TODO Add group owner id in AND clause.

        //if post not exist
        if (!$media_res) {
             $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $media_comments = $media_res->getComment();
        $comment_exists = false;
        $voters_array = array();
        if($media_comments){
            //check comments one by one.
            foreach ($media_comments as $comment) {
                if ($comment->getId() == $comment_id) {
                    $comment_exists = true;
                    $comment_res = $comment;
                    $total_user_count = $comment_res->getVoteCount();
                    $votes = $comment_res->getRate();
                    //get total votes count
                    if($votes){
                        $count = count($votes);
                    } else {
                        $count = 0;
                    }

                    $vote_count = $limit_size + $limit_start;
                    foreach($votes as $i=>$vote)
                    {
                        if($i >= $limit_start && $i < $vote_count ){
                            $voter_id = $vote->getUserId();
                            $rate = $vote->getRate();
                            $voters_array[] = $vote->getUserId();
                            //define rate by user
                            $rate_users[$voter_id] = $rate;
                        }
                   }
                }
            }
        } else {
            $res_data = array('code' => 302, 'message' => 'COMMENT_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }
        //get user object
        $user_service = $container->get('user_object.service');
        $user_objects_rated = $user_service->MultipleUserObjectService($voters_array);
        //preapare the users array with user object

        foreach($user_objects_rated as $user_objects_rated_single){
            //get voter id
            $voter_id = $user_objects_rated_single['id'];
            $user_rate = isset($rate_users[$voter_id])? $rate_users[$voter_id]: 0;
            $user_array = array('rate'=>$user_rate);
            $users[] = array_merge($user_objects_rated_single,$user_array);
        }

        $data = array('type_id' => $item_id, 'type' => $item_type, 'total_users' => $total_user_count, 'users_rated' => $users);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }

}
