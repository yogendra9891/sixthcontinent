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
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Dashboard\DashboardManagerBundle\Document\DashboardPost;
use Dashboard\DashboardManagerBundle\Document\DashboardPostRating;
use Notification\NotificationBundle\Document\UserNotifications;
use Dashboard\DashboardManagerBundle\Document\DashboardComments;
use Dashboard\DashboardManagerBundle\Document\DashboardCommentRating;
use Notification\NotificationBundle\NManagerNotificationBundle;
use UserManager\Sonata\UserBundle\Document\Group;
use UserManager\Sonata\UserBundle\Document\GroupAlbum;
use UserManager\Sonata\UserBundle\Document\ClubRating;
use Media\MediaBundle\Document\ClubAlbumCommentRating;
use UserManager\Sonata\UserBundle\Controller\RestGroupController;
use Media\MediaBundle\Document\AlbumMediaCommentRating;
use Utility\RatingBundle\Controller\MediaCommentRatingController;
/**
 * class for handing the rating system
 */
class ClubRatingController extends Controller
{
    protected $club_rating = "CLUB_RATE";
    protected $club_album_comment_rating = "CLUB_ALBUM_COMMENT_RATE";
    protected $club_album_media_comment_rating = "CLUB_ALBUM_MEDIA_COMMENT_RATE";
    protected $club_post_media_comment_rating = "CLUB_POST_MEDIA_COMMENT_RATE";
    /**
     * add the rating for the club
     */
    public function addClubRate($item_type, $item_id, $rate, $user_id)
    {
        $data = array();

        //get container object
        $container = NManagerNotificationBundle::getContainer();

          //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $club_res = $dm->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $item_id)); //@TODO Add group owner id in AND clause.

        //check if club exist or not
        if (!$club_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $club_rating = new ClubRating();
        $time = new \DateTime("now");

        $club_rating_res   = $club_res->getRate();
        //check if a user already rate on post.
        foreach ($club_rating_res as $club_rate) {
            if ($club_rate->getUserId() == $user_id) {
                $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                $this->returnResponse($res_data);
            }
        }

        $group_id = $club_res->getId();
        $group_type = $club_res->getGroupStatus();
        $group_name = $club_res->getTitle();
        //calculate the total, average rate.
        $total_user_count = $club_res->getVoteCount();
        $total_rate = $club_res->getVoteSum();
        //calculate the new rate ,total user count
        $updated_rate_result=$calculaterate->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count

        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $club_res->setVoteCount($new_user_count);
        $club_res->setVoteSum($new_total_rate);
        $club_res->setAvgRating($avg_rate);
        $club_owner_id = $club_res->getOwnerId(); //get club owner id

        //set object for dashboard post rating
        $club_rating->setUserId($user_id);
        $club_rating->setRate($rate);
        $club_rating->setItemId($item_id);
        $club_rating->setType('club');
        $club_rating->setCreatedAt($time);
        $club_rating->setUpdatedAt($time);




        $club_res->addRate($club_rating);
        try {
            $dm->persist($club_res); //storing the post data.
            $dm->flush();

            $from_id = $user_id;
            $to_id   = $club_owner_id;
            $postService = $container->get('post_detail.service');
            $userService = $container->get('user_object.service');
             // Roles : 2 for admin, 3 for friend
            $groupAdminMembers = $userService->groupMembersByGroupRole($group_id, 2, array($from_id));
            if($to_id!=$from_id and !key_exists($to_id, $groupAdminMembers)){
                $groupAdminMembers[$to_id] = $postService->getUserData($to_id);
            }
            $group_member_array = array_keys($groupAdminMembers);

            if (!empty($group_member_array))
            {
                $email_template_service = $container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $container->getParameter('angular_app_hostname'); //angular app host
                $club_profile_url     = $container->getParameter('club_profile_url'); //dashboard post url
                $post_id = $item_id;
                //get the local parameters in parameters file.

                $locale = $container->getParameter('locale');
                $language_const_array = $container->getParameter($locale);

                $sender = $postService->getUserData($from_id);
                $sender_name = trim($sender['first_name'].' '.$sender['last_name']);

                //send social notification
                $msgtype = $this->club_rating;
                $this->saveMultiUserNotification($group_member_array, $from_id, $item_id, $msgtype, $rate);
                $postService->sendUserNotifications($from_id, $group_member_array, $msgtype, 'rate', $item_id, false, true, array($sender_name, $club_res->getTitle()), 'CITIZEN', array('group_status'=>$group_type));

                $recieverByLanguage = $postService->getUsersByLanguage($groupAdminMembers);
                $emailResponse = '';
                foreach($recieverByLanguage as $lng=>$reciever){

                    $locale = $lng===0 ? $container->getParameter('locale') : $lng;
                    $lang_array = $container->getParameter($locale);

                    $href = $angular_app_hostname.$club_profile_url.'/'.$group_id.'/'.$group_type; //href for club profile;
                    $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                    $mail_sub  = sprintf($lang_array['CLUB_RATE_SUBJECT'], ucwords($sender_name));
                    $mail_body = sprintf($lang_array['CLUB_RATE_BODY'], ucwords($sender_name), ucwords($group_name));
                    $mail_text = sprintf($lang_array['CLUB_RATE_TEXT'], ucwords($sender_name), ucwords($group_name), $rate);
                    $bodyData      = $mail_text."<br><br>".$link;

                    // HOTFIX NO NOTIFY MAIL
                    //$emailResponse = $email_template_service->sendMail($reciever, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'RATING_NOTIFICATION');
                }


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
        $user_ids = is_array($user_ids) ? $user_ids : (array)$user_ids;
        foreach($user_ids as $user_id){
        //notification will not be send to the user who is rating
            if($user_id != $fid){
                $notification = new UserNotifications();
                $notification->setFrom($fid);
                $notification->setTo($user_id);
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
        return true;
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
     * Edit club rate
     * @param int $type_id
     * @param int $user_id
     */
    public function editClubRate($type, $type_id, $rate, $user_id) {

        $arrayPostRate = array();
        $data = array();
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $club_res = $dm->getRepository('UserManagerSonataUserBundle:Group')
                    ->findOneBy(array('id' => $type_id)); //@TODO Add group owner id in AND clause.
        //if post not exist
        if (!$club_res)
        {
             $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
              echo json_encode($res_data);
              exit;
        }
        $votes = $club_res->getRate();
        $total_user_count = $club_res->getVoteCount();
        $total_rate = $club_res->getVoteSum(); //old total rate

        //get current time
        $time = new \DateTime("now");
        foreach ($votes as $vote)
        {
            $voter_id = $vote->getUserId();
            //check if current user is voter of post
            if ($user_id == $voter_id)
            {
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
            $rating_response = $dm->getRepository('UserManagerSonataUserBundle:Group')
                    ->editPostRate($rate_id, $arrayPostRate, $type_id);
        }
        if (!$rating_response) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');
        //Update the rate count
        $resp = $calculaterate->updateEditRateCount($total_user_count, $total_rate, $type_id, $current_user_rate, $rate);
        $new_total_rate = $resp['total_rate'];
        $avg_rate = $resp['avg_rate'];

        $club_res->setVoteSum($new_total_rate);
        $club_res->setAvgRating($avg_rate);

        try {
            $dm->persist($club_res); //storing the post data.
            $dm->flush();
            //set response parameter
            $avg_rate_round = $this->roundNumber($avg_rate);
            $data = array('avg_rate' => $avg_rate_round, 'current_user_rate' => $rate, 'no_of_votes' => $total_user_count);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }

        $res_data = array('code' => 101, 'me'
            . 'ssage' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }


    /**
     * delete the rate for club posts.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    public function deleteClubPostRate($item_type, $item_id, $user_id) {
        $data = array();

         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $club_res = $dm->getRepository('UserManagerSonataUserBundle:Group')
                    ->findOneBy(array('id' => $item_id)); //@TODO Add group owner id in AND clause.
        //if post not exist
        if (!$club_res)
        {
             $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
              echo json_encode($res_data);
              exit;
        }

        $message_type  = $this->club_rating;
        $post_owner_id = $club_res->getOwnerId();
        $post_rating   = $club_res->getRate();
        $count = 0;
        foreach ($post_rating as $post_rate)
        {
            if ($post_rate->getUserId() == $user_id)
            {
                $count = 1;
                $user_rate = $post_rate->getRate();
                $club_res->removeRate($post_rate); // remove the rate post object.
                $total_user_count = $club_res->getVoteCount();
                $total_rate = $club_res->getVoteSum();

                //load the calculate_rate_service
                $calculaterate=$container->get('calculate_rate_service');

                $updated_rate_result = $calculaterate->updateDeleteRate($total_user_count, $total_rate, $user_rate);
                //calculate the updated rating, user count.

                $new_user_count = $updated_rate_result['new_user_count'];
                $new_total_rate = $updated_rate_result['new_total_rate'];
                $avg_rate = $updated_rate_result['avg_rate'];
                //set the object.
                $club_res->setVoteCount($new_user_count);
                $club_res->setVoteSum($new_total_rate);
                $club_res->setAvgRating($avg_rate);
                break;
            }
        }

        //set object for store post for updated rating..
        try
        {
            $dm->persist($club_res); //storing the post data.
            $dm->flush();
            if ($count > 0) {
                 //calling rating notification service
                $notification_obj=$container->get('rating_notification_service');
                //remove notification if post owner doent read the rate notiication.
                $notification_obj->removeNotification($post_owner_id, $user_id, $item_id, $message_type);
                $data = array('avg_rate' => $this->roundNumber($avg_rate), 'no_of_votes' => $new_user_count);
            } else { //if record does not exists.
                $data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
                $this->returnResponse($data);
            }
        }
        catch (\Doctrine\DBAL\DBALException $e)
        {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($response_data);
    }

    /**
     * add the rating for the club album comment
     */
    public function addClubAlbumCommentRate($item_type, $item_id, $rate, $user_id)
    {
        $data = array();
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //fetching album id
        $comment_res = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                          ->find($item_id);
        if (!$comment_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $album_id = $comment_res->getAlbumId();

        // fetching club id
        $club_album = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')->findOneBy(array('id' => $album_id));
        //get group album object and fetch group id
        $club_id = $club_album->getGroupId();
        $album_name = $club_album->getAlbumName();

        //get group  object and fetch group owner id
        $club = $dm->getRepository('UserManagerSonataUserBundle:Group')->find($club_id);
        $club_owner_id = $club ->getOwnerId();
        $club_name = $club->getTitle();
        $clubStatus = $club->getGroupStatus();

        $comment_rating   = $comment_res->getRate();
        //check if a user already rate on post comment.
        foreach ($comment_rating as $comment_rate) {
            if ($comment_rate->getUserId() == $user_id) {
                $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                $this->returnResponse($res_data);
            }
        }

        $clubalbum_comment_rating = new ClubAlbumCommentRating();
        $time = new \DateTime("now");

        //calculate the total, average rate.
        $total_user_count = $comment_res->getVoteCount();
        $total_rate = $comment_res->getVoteSum();

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');
        $store_album_comment_user_id = $comment_owner_id = $comment_res->getCommentAuthor(); //get comment owner id


        $updated_rate_result = $calculaterate->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count
        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $comment_res->setVoteCount($new_user_count);
        $comment_res->setVoteSum($new_total_rate);
        $comment_res->setAvgRating($avg_rate);

        //set object for dashboard post rating
        $clubalbum_comment_rating->setUserId($user_id);
        $clubalbum_comment_rating->setRate($rate);
        $clubalbum_comment_rating->setItemId($item_id);
        $clubalbum_comment_rating->setType('club_album_comment');
        $clubalbum_comment_rating->setCreatedAt($time);
        $clubalbum_comment_rating->setUpdatedAt($time);

        $comment_res->addRate($clubalbum_comment_rating);
        try {
            $dm->persist($comment_res); //storing the rating data.
            $dm->flush();

            if ($user_id != $club_owner_id) {
                //send social notification
                $msgtype = $this->club_album_comment_rating;

                //prepare the mail template for dashboard post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $container->getParameter('angular_app_hostname'); //angular app host
                $club_album_url     = $container->getParameter('club_album_url'); //dashboard post url
                $to_id   = $store_album_comment_user_id;
                $from_id = $user_id;
                $post_id = $item_id;

                $postService = $container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                $sender = $postService->getUserData($from_id);
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $container->getParameter('locale');
                $language_const_array = $container->getParameter($locale);

                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, $rate, $album_id, true, true, array($sender_name, $club_name), 'CITIZEN', array('msg_code'=>'rate', 'club_id'=>$club_id), 'U', array('comment_id'=>$item_id, 'club_id'=>$club_id));

                $href = $angular_app_hostname . $club_album_url . '/' . $club_id. '/'.$album_id. '/'.$clubStatus.'/'.$album_name;
                $link = $email_template_service->getLinkForMail($href); //making the link html from service
                $mail_sub  = sprintf($language_const_array['CLUB_ALBUM_COMMENT_RATE_SUBJECT'], ($sender_name), $club_name);
                $mail_body = sprintf($language_const_array['CLUB_ALBUM_COMMENT_RATE_BODY'], ($sender_name), $club_name);
                $mail_text = sprintf($language_const_array['CLUB_ALBUM_COMMENT_RATE_TEXT'], ($sender_name), $club_name, $rate);
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
     * delete the rate for store posts comment.
     * @param string $item_type
     * @param string $comment_id
     * @param int $user_id
     * @return json
     */
    public function deleteClubAlbumCommentRate($item_type, $comment_id, $user_id) {

        $data = array();

        //getting the container object
        $container=  NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                        ->find($comment_id);

        //if club album comment is not exist.
        if (!$comment_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->club_album_comment_rating;
        $post_owner_id = $comment_res->getCommentAuthor();
        $post_rating   = $comment_res->getRate();
        $count = 0;

        foreach ($post_rating as $post_rate) {
            if ($post_rate->getUserId() == $user_id) {
                $count = 1;
                $user_rate = $post_rate->getRate();
                $comment_res->removeRate($post_rate); // remove the rate post object.
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
                break;
            }
        }

        //set object for store album for updated rating..
        try {
            $dm->persist($comment_res); //storing the comment data.
            $dm->flush();
            if ($count > 0) {
                //calling rating notification service
                $notification_obj=$container->get('rating_notification_service');

                //remove notification if post owner doent read the rate notiication.
                $notification_obj->removeNotification($post_owner_id, $user_id, $comment_id, $message_type);

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
     * Edit Club Album Comment rate
     * @param int $comment_id
     * @param int $user_id
     */
    public function editClubAlbumCommentRate($item_type, $comment_id, $rate, $user_id)
    {
        $arrayCommentRate = array();
        $data = array();
        //getting the container object
        $container=  NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $comment   = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
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
        $rating_response = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                               ->editClubAlbumCommentRate($rate_id, $arrayCommentRate, $comment_id);

        }
        if(!$rating_response){
         $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
         $this->returnResponse($res_data);
        }

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        //Update the rate count
        $resp = $calculaterate->updateEditRateCount($total_user_count, $total_rate, $comment_id, $current_user_rate, $rate);
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
     * List Store rated users
     * @param int $item_type
     * @param int $item_id
     */
    public function getClubAlbumCommentRateUsers($item_type, $item_id) {
        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('MediaMediaBundle:ClubAlbumComment')
                ->find($item_id);

        //if post not exist
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $post->getRate();
        $voters_array = array();

        foreach($votes as $vote){
            $voter_id = $vote->getUserId();
            $rate = $vote->getRate();
            $voters_array[] = $vote->getUserId();
            //define rate by user
            $rate_users[$voter_id] = $rate;
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
            $count = $count + 1; //get total count
        }

        $data = array('type_id' => $item_id, 'type' => $item_type, 'total_users' => $count, 'users_rated' => $users);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }

    /**
     *
     * @param type $item_type
     * @param type $comment_id
     * @param type $item_id
     * @param type $rate
     * @param type $user_id
     */

    public function addClubAlbumMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id)
    {
        $data = array();

        //get container object
        $container = NManagerNotificationBundle::getContainer();

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $media_res = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                ->findOneBy(array('id' => $item_id)); //@TODO Add group owner id in AND clause.

        //check if club exist or not
        if (!$media_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        //get group id
        $club_id = $media_res->getGroupId();
        //get album id
        $album_id = $media_res->getAlbumid();
        $mediarating = new AlbumMediaCommentRating();
        $time = new \DateTime("now");


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
                $msgtype = $this->club_album_media_comment_rating;

                /*Send Email Notification using send grid function*/
                //prepare the mail template for dashboard post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $container->getParameter('angular_app_hostname'); //angular app host
                $dashboard_post_url     = $container->getParameter('club_album_url'); //dashboard post url
                $to_id   = $comment_owner_id;
                $from_id = $user_id;
                $comment_id = $item_id;

                $postService = $container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $container->getParameter('locale');
                $lang_array = $container->getParameter($locale);

                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, $rate, $item_id, true, true, array($sender_name), 'CITIZEN', array('msg_code'=>'rate','club_id'=>$club_id,'album_id'=>$album_id), 'U', array('comment_id'=>$comment_id));

                $href = $angular_app_hostname . $dashboard_post_url . '/' . $comment_id;
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $subject  = sprintf($lang_array['CLUB_ALBUM_MEDIA_COMMENT_RATE_SUBJECT'], ucwords($sender_name));
                $mail_body = sprintf($lang_array['CLUB_ALBUM_MEDIA_COMMENT_RATE_SUBJECT'], ucwords($sender_name));
                $mail_text = sprintf($lang_array['CLUB_ALBUM_MEDIA_COMMENT_RATE_TEXT'], ucwords($sender_name), $rate);
                $bodyData      = $mail_text."<br><br>".$link;

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $subject, $sender['profile_image_thumb'], 'CLUB_ALBUM_MEDIA_COMMENT_RATING_NOTIFICATION');
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
    public function editClubAlbumMediaCommentRate($item_type, $comment_id, $type_id, $rate, $user_id) {
       // $arrayCommentRate = array();
        $data = array();

        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $media_res = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')->find($type_id);

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
     * delete the rate for club album media comments rate.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    public function deleteClubAlbumMediaCommentRate($item_type, $comment_id, $item_id, $user_id) {
        $data = array();

        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $media_res = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')->find($item_id);
        //if group media does not exists.
        if (!$media_res) {
            $res_data = array('code' => 302, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

//        $mediarating = new AlbumMediaCommentRating();
//        $time = new \DateTime("now");
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

        $message_type  = $this->club_album_media_comment_rating;
        $comment_owner_id = $comment_res->getCommentAuthor();

        //set object for club album media cpmmant for updated rating..
        try {
            $dm->persist($comment_res); //storing the comment data.
            $dm->flush();
            if ($rate_exists) {
                //calling rating notification service
                $notification_obj=$container->get('rating_notification_service');
                //remove notification if post owner doent read the rate notiication.
                $notification_obj->removeNotification($comment_owner_id, $user_id, $item_id, $message_type);
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
     * List Club rated users
     * @param int $item_type
     * @param int $item_id
     */
    public function getClubPostRateUsers($item_type, $item_id, $limit_start, $limit_size) {
        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $club_res = $dm->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $item_id)); //@TODO Add group owner id in AND clause.

        //if post not exist
        if (!$club_res) {
             $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        $votes = $club_res->getRate();
        $voters_array = array();

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

        $data = array('type_id' => $item_id, 'type' => $item_type, 'total_users' => $count, 'users_rated' => $users);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }

    /**
     * List rated users on clum album media comment
     * @param int $item_type
     * @param int $item_id
     */
    public function getClubAlbumMediaCommentRateUsers($item_type, $item_id,$comment_id, $limit_start, $limit_size) {

        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $media_res = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
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
                    $total_user_count = $comment->getVoteCount();
                   // $total_rate = $comment->getVoteSum();
                    //comment
                    $votes = $comment->getRate();
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
    /*********************************/

    public function addClubWallPostMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id){
        $data = array();
       //get container object
        $container = NManagerNotificationBundle::getContainer();
          //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $image_res = $dm->getRepository('PostPostBundle:PostMedia')->find($item_id);

        if (!$image_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $post_id = $image_res->getPostId();
        $club_res = $dm->getRepository('PostPostBundle:Post')->find($post_id);
        $club_id = $club_res->getPostGid();
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

        $comment_owner_id = $comment_res->getCommentAuthor(); //get comment owner id

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
                $msgtype = $this->club_post_media_comment_rating;

                //prepare the mail template for dashboard post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $container->getParameter('angular_app_hostname'); //angular app host
                $club_post_url          = $container->getParameter('club_post_url');
                $to_id   = $comment_owner_id;
                $from_id = $user_id;

                $postService = $container->get('post_detail.service');
                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                $receiver = $postService->getUserData($to_id);
                $locale = !empty($receiver['current_language']) ? $receiver['current_language'] : $container->getParameter('locale');
                $language_const_array = $container->getParameter($locale);

                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, $rate, $post_id, true, true, $sender_name, 'CITIZEN', array('club_id'=>$club_id, 'msg_code'=>'rate'), 'U', array('comment_id'=>$comment_id, 'club_id'=>$club_id));
                $href = $postService->getStoreClubUrl(array('clubId'=>$club_id,'postId'=>$post_id),'club');
                $link = $email_template_service->getLinkForMail($href, $locale); //making the link html from service
                $mail_sub  = sprintf($language_const_array['CLUB_POST_MEDIA_COMMENT_RATE_SUBJECT']);
                $mail_body = sprintf($language_const_array['CLUB_POST_MEDIA_COMMENT_RATE_BODY'], ucwords($sender_name), $rate);
                $mail_text = sprintf($language_const_array['CLUB_POST_MEDIA_COMMENT_RATE_TEXT'],ucwords($sender_name), $rate);
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
     * edit rating for club wall post media comment
     * @param type $item_type
     * @param type $comment_id
     * @param type $item_id
     * @param type $rate
     * @param type $user_id
     */
    public function editClubWallPostMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id){
        $arrayCommentRate = array();
        $data = array();

        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $media_res = $dm->getRepository('PostPostBundle:PostMedia')->find($item_id);

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
     * get dtail of rated users for this comment
     * @param type $item_type
     * @param type $item_id
     * @param type $comment_id
     * @param type $limit_start
     * @param type $limit_size
     */

    public function getClubWallPostMediaCommentRateUsers($item_type, $item_id, $comment_id, $limit_start, $limit_size){
        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $media_res = $dm->getRepository('PostPostBundle:PostMedia')
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
     * Delete rating for club post media comment
     * @param type $item_type
     * @param type $comment_id
     * @param type $item_id
     * @param type $user_id
     */
    public function deleteClubWallPostMediaCommentRate($item_type, $comment_id, $item_id, $user_id){

        $data = array();
        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $media_res = $dm->getRepository('PostPostBundle:PostMedia')->find($item_id);
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

        $message_type  = $this->club_post_media_comment_rating;
        $comment_owner_id = $comment_res->getCommentAuthor();

        //set object for dashboard post for updated rating..
        try {
            $dm->persist($comment_res); //storing the post data.
            $dm->flush();
            if ($rate_exists) {
                //remove notification if post owner doent read the rate notiication.
               // $this->removeNotification($comment_owner_id, $user_id, $item_id, $message_type);
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

}
