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
use StoreManager\PostBundle\Document\StorePosts;
use StoreManager\PostBundle\Document\StorePostsRating;
use Notification\NotificationBundle\Document\UserNotifications;
use Notification\NotificationBundle\NManagerNotificationBundle;
use StoreManager\PostBundle\Document\StoreComments;
use StoreManager\PostBundle\Document\StoreCommentRating;
use StoreManager\PostBundle\Document\ItemRating;
use StoreManager\PostBundle\Document\ItemRatingRate;
use Media\MediaBundle\Document\StoreAlbumCommentRating;
use Media\MediaBundle\Document\AlbumMediaCommentRating;
use Media\MediaBundle\Document\UserMediaRating;


/**
 * class for handing the store rating system
 */
class StoreRatingController extends Controller
{

    protected $store_post_rating = "STORE_POST_RATE";
    protected $store_post_comment_rating = "STORE_POST_COMMENT_RATE";
    protected $store_album_rating='STORE_ALBUM_RATE';
    protected $store_media_rating='STORE_MEDIA_RATE';
    protected $store_album_comment_rating='STORE_ALBUM_COMMENT_RATE';
    protected $store_media_comment_rating='STORE_MEDIA_COMMENT_RATE';
    protected $storepost_media_comment_rating = "STORE_POST_MEDIA_COMMENT_RATE";
    protected $storepost_media_rating = "STORE_POST_MEDIA_RATE";

    /**
     * add the rate for store posts.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    public function addStorePostRate($item_type, $item_id, $rate, $user_id) {

        $data = array();

        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_res = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($item_id);
        if (!$post_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }
        $post_rating   = $post_res->getRate();
        $store_id=$post_res->getStoreId();
        //check if a user already rate on post.
        foreach ($post_rating as $post_rate) {
            if ($post_rate->getUserId() == $user_id) {
                $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                $this->returnResponse($res_data);
            }
        }
        $store_rating=new StorePostsRating();//creating object of storeposts document
        $time = new \DateTime("now");

        //calculate the total, average rate.
        $total_user_count = $post_res->getVoteCount();
        $total_rate = $post_res->getVoteSum();

        $store_post_user_id = $post_owner_id =  $post_res->getStorePostAuthor(); //get post owner id

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        //calculate the new rate ,total user count
        $updated_rate_result=$calculaterate->updateAddRate($total_user_count, $total_rate, $rate);


        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate = $updated_rate_result['avg_rate'];

        //set the object.
        $post_res->setVoteCount($new_user_count);
        $post_res->setVoteSum($new_total_rate);
        $post_res->setAvgRating($avg_rate);

        //set object for store post rating
        $store_rating->setUserId($user_id);
        $store_rating->setRate($rate);
        $store_rating->setItemId($item_id);
        $store_rating->setType('post');
        $store_rating->setCreatedAt($time);
        $store_rating->setUpdatedAt($time);

        $post_res->addRate($store_rating);
        try {
            $dm->persist($post_res); //storing the post data.
            $dm->flush();
            if ($user_id != $post_owner_id) {
                //prepare the mail template for store post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.

                $angular_app_hostname   = $container->getParameter('angular_app_hostname'); //angular app host
                //$store_post_url     = $container->getParameter('store_post_url'); //store post url
                $store_post_shop_part =$container->getParameter('store_post_shop_part_url'); //store post shop part url
                $store_post_post_part =$container->getParameter('store_post_post_part_url');    //store post post part url
                $to_id   = $store_post_user_id;
                $from_id = $user_id;
                $post_id = $item_id;

                $postService = $container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $container->getParameter('locale');
                $lang_array = $container->getParameter($locale);

                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->store_post_rating;
                //calling rating notification service
                $notification_obj=$container->get('rating_notification_service');

                $notification_id=$notification_obj->saveUserNotification($user_id, $post_owner_id, $item_id, $msgtype, $rate);
                $postService->sendUserNotifications($user_id, $post_owner_id, $msgtype, 'rate', $item_id, false, true, $sender_name, 'CITIZEN', array('store_id'=>$store_id));
                //end to send social notification


                $href = $angular_app_hostname . $store_post_shop_part.'/'.$store_id. '/'.$store_post_post_part. '/' . $post_id;
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $mail_sub  = sprintf($lang_array['STORE_POST_RATE_SUBJECT'], ucwords($sender_name));
                $mail_body = sprintf($lang_array['STORE_POST_RATE_BODY'], ucwords($sender_name));
                $mail_text = sprintf($lang_array['STORE_POST_RATE_TEXT'], ucwords($sender_name), $rate);
                $bodyData      = $mail_text."<br><br>".$link;

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'RATING_NOTIFICATION');


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
     * delete the rate for store posts.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    public function deleteStorePostRate($item_type, $item_id, $user_id) {
        $data = array();

         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_res = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($item_id);

        //if posts is not exist.
        if (!$post_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->store_post_rating;
        $post_owner_id = $post_res->getStorePostAuthor();
        $post_rating   = $post_res->getRate();
        $count = 0;
        foreach ($post_rating as $post_rate) {
            if ($post_rate->getUserId() == $user_id) {
                $count = 1;
                $user_rate = $post_rate->getRate();
                $post_res->removeRate($post_rate); // remove the rate post object.
                $total_user_count = $post_res->getVoteCount();
                $total_rate = $post_res->getVoteSum();

                //load the calculate_rate_service
                $calculaterate=$container->get('calculate_rate_service');

                $updated_rate_result = $calculaterate->updateDeleteRate($total_user_count, $total_rate, $user_rate);
                //calculate the updated rating, user count.

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

        //set object for store post for updated rating..
        try {
            $dm->persist($post_res); //storing the post data.
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
        } catch (\Doctrine\DBAL\DBALException $e) {
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($response_data);
        }
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($response_data);
    }

    /**
     * Edit store rate
     * @param int $post_id
     * @param int $user_id
     */
    public function editStorePostRate($item_type, $post_id, $rate, $user_id) {
        $arrayPostRate = array();
        $data = array();

         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($post_id);

        //if post not exist
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $post->getRate();
        $total_user_count = $post->getVoteCount();
        $total_rate = $post->getVoteSum(); //old total rate

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
            $rating_response = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                    ->editPostRate($rate_id, $arrayPostRate, $post_id);
        }
        if (!$rating_response) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');
        //Update the rate count
        $resp = $calculaterate->updateEditRateCount($total_user_count, $total_rate, $post_id, $current_user_rate, $rate);
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

        $res_data = array('code' => 101, 'me'
            . 'ssage' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
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
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();
        return $container->get('fos_user.user_manager');
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
    private function sendMail($mail_sub, $mail_body, $thumb_path, $link, $from_id, $to_id) {

          //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $email_template_service = $container->get('email_template.service'); //email template service.
        //service for email template
        $email_body = $email_template_service->EmailTemplateService($mail_body, $thumb_path, $link, $to_id);
        $mail_notification = $email_template_service->sendEmailNotification($mail_sub, $from_id, $to_id, $email_body);
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
     * List Store rated users
     * @param int $item_type
     * @param int $item_id
     */
    public function getStorePostRateUsers($item_type, $item_id, $limit_start, $limit_size) {
        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($item_id);

        //if post not exist
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $post->getRate();
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
     * add the rate for Store posts Comments.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    public function addStorePostCommentRate($item_type, $item_id, $rate, $user_id) {

        $data = array();
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($item_id);
        if (!$comment_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $link_post_id=$comment_res->getPostId();
        $post_res = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($link_post_id);
        $store_id=$post_res->getStoreId();
        $store_comment_rating = new StoreCommentRating();
        $time = new \DateTime("now");

        //calculate the total, average rate.
        $total_user_count = $comment_res->getVoteCount();
        $total_rate = $comment_res->getVoteSum();



         //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        $store_comment_user_id = $comment_owner_id =  $comment_res->getCommentAuthor(); //get post owner id
        $updated_rate_result = $calculaterate->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count

        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $comment_res->setVoteCount($new_user_count);
        $comment_res->setVoteSum($new_total_rate);
        $comment_res->setAvgRating($avg_rate);

        //set object for store post comment rating
        $store_comment_rating->setUserId($user_id);
        $store_comment_rating->setRate($rate);
        $store_comment_rating->setItemId($item_id);
        $store_comment_rating->setType('post_comment');
        $store_comment_rating->setCreatedAt($time);
        $store_comment_rating->setUpdatedAt($time);

        $comment_res->addRate($store_comment_rating);
        try {
            $dm->persist($comment_res); //storing the post data.
            $dm->flush();
             if ($user_id != $comment_owner_id) {
                 //prepare the mail template for dashboard post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $container->getParameter('angular_app_hostname'); //angular app host
                $store_post_shop_part =$container->getParameter('store_post_shop_part_url'); //store post shop part url
                $store_post_post_part =$container->getParameter('store_post_post_part_url');    //store post post part url
                $to_id   = $store_comment_user_id;
                $from_id = $user_id;
                $post_id = $item_id;

                $postService = $container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $container->getParameter('locale');
                $lang_array = $container->getParameter($locale);

                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->store_post_comment_rating;

                 //calling rating notification service
                $notification_obj=$container->get('rating_notification_service');

                $notification_id = $notification_obj->saveUserNotification($user_id, $comment_owner_id, $item_id, $msgtype, $rate);
                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, 'rate', $item_id, false, true, $sender_name, 'CITIZEN', array('ref_id'=>$link_post_id, 'store_id'=>$store_id));
                //end to send social notification


                $href = $angular_app_hostname . $store_post_shop_part.'/'.$store_id. '/'.$store_post_post_part. '/' . $link_post_id;
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $mail_sub  = sprintf($lang_array['STORE_COMMENT_RATE_SUBJECT'], ucwords($sender_name));
                $mail_body = sprintf($lang_array['STORE_COMMENT_RATE_BODY'], ucwords($sender_name));
                $mail_text = sprintf($lang_array['STORE_COMMENT_RATE_TEXT'], ucwords($sender_name), $rate);
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
     * Edit Store Comment rate
     * @param int $comment_id
     * @param int $user_id
     */
    public function editStorePostCommentRate($item_type, $comment_id, $rate, $user_id)
    {
        $arrayCommentRate = array();
        $data = array();
        //getting the container object
        $container=  NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $comment   = $dm->getRepository('StoreManagerPostBundle:StoreComments')
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
        $rating_response = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                           ->editCommentRate($rate_id, $arrayCommentRate, $comment_id);

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
     * delete the rate for store posts comment.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    public function deleteStorePostCommentRate($item_type, $comment_id, $user_id) {
        $data = array();

        //getting the container object
        $container=  NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_res = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($comment_id);
        //if posts is not exist.
        if (!$post_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->store_post_comment_rating;
        $post_owner_id = $post_res->getCommentAuthor();
        $post_rating   = $post_res->getRate();
        $count = 0;

        foreach ($post_rating as $post_rate) {
            if ($post_rate->getUserId() == $user_id) {
                $count = 1;
                $user_rate = $post_rate->getRate();
                $post_res->removeRate($post_rate); // remove the rate post object.
                $total_user_count = $post_res->getVoteCount();
                $total_rate = $post_res->getVoteSum();
                //load the calculate_rate_service
                $calculaterate=$container->get('calculate_rate_service');

                $updated_rate_result = $calculaterate->updateDeleteRate($total_user_count, $total_rate, $user_rate); //calculate the updated rating, user count.

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
     * List Store rated users
     * @param int $item_type
     * @param int $item_id
     */
    public function getStorePostCommentRateUsers($item_type, $item_id, $limit_start, $limit_size) {
        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('StoreManagerPostBundle:StoreComments')
                ->find($item_id);

        //if post not exist
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $post->getRate();
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
     * add the rate for dahboard posts.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    public function addStoreAlbumRate($item_type, $item_id, $rate, $user_id) {
        $data = array();

         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();


        $em = $container->get('doctrine')->getManager(); //get entity manager object

        $store_album=$em->getRepository('StoreManagerStoreBundle:Storealbum')->find((string)$item_id);
        //fetching data for album of store from mysql table

        //checking if album exists or not
        if(!$store_album)
        {
         $res_data=array('code'=>302,'message'=>'RECORD_DOES_NOT_EXISTS','data' => $data);
         $this->returnResponse($res_data);
        }

        $store_id=$store_album->getStoreId();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //fetching album rating
        $album_rate_res = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findOneBy(array('item_id'=>(string)$item_id,'item_type'=>$item_type));


        $item_ratingrate = new ItemRatingRate();
        $time = new \DateTime("now");
        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        //if album rating not exits
        if(!$album_rate_res){
            //new rating object created
             $item_rating = new ItemRating();
            //calculate the new rate ,total user count
            $updated_rate_result = $calculaterate->updateAddRate(0, 0, $rate); //calculate the new rate,
            $new_user_count = $updated_rate_result['new_user_count'];
            $new_total_rate = $updated_rate_result['new_total_rate'];
            $avg_rate = $updated_rate_result['avg_rate'];
            $item_rating->setVoteCount($new_user_count);
            $item_rating->setItemId($item_id);
            $item_rating->setItemType($item_type);
            $item_rating->setVoteSum($new_total_rate);
            $item_rating->setAvgRating($avg_rate);
            //set object for store album rating
            $item_ratingrate->setUserId($user_id);
            $item_ratingrate->setRate($rate);
            $item_ratingrate->setItemId($item_id);
            $item_ratingrate->setType($item_type);
            $item_ratingrate->setCreatedAt($time);
            $item_ratingrate->setUpdatedAt($time);

            $item_rating->addRate($item_ratingrate);

        }else{

            $album_rating   = $album_rate_res->getRate();
            //check if a user already rate on post.
            foreach ($album_rating as $album_rate) {
                if ($album_rate->getUserId() == $user_id) {
                    $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                    $this->returnResponse($res_data);
                }
            }
             //calculate the total, average rate.
            $total_user_count = $album_rate_res->getVoteCount();
            $total_rate = $album_rate_res->getVoteSum();
            //calculate the new rate ,total user count
            $updated_rate_result = $calculaterate->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate,
            $new_user_count = $updated_rate_result['new_user_count'];
            $new_total_rate = $updated_rate_result['new_total_rate'];
            $avg_rate = $updated_rate_result['avg_rate'];
            //set the object.
            $album_rate_res->setVoteCount($new_user_count);
            $album_rate_res->setItemId($item_id);
            $album_rate_res->setItemType($item_type);
            $album_rate_res->setVoteSum($new_total_rate);
            $album_rate_res->setAvgRating($avg_rate);
             //set object for store album rating
            $item_ratingrate->setUserId($user_id);
            $item_ratingrate->setRate($rate);
            $item_ratingrate->setItemId($item_id);
            $item_ratingrate->setType($item_type);
            $item_ratingrate->setCreatedAt($time);
            $item_ratingrate->setUpdatedAt($time);

            $album_rate_res->addRate($item_ratingrate);

        }


        $store_album_user_id = $album_owner_id =$em->getRepository('StoreManagerStoreBundle:StoreAlbum')->getStoreOwnerId((string)$item_id);//get album owner id

        try {
            if(!$album_rate_res){
            $dm->persist($item_rating); //storing the album rate data.
            }else{
            $dm->persist($album_rate_res); //storing the album rate data.
            }
            $dm->flush();
            if ($user_id != $album_owner_id) {
                //prepare the mail template for dashboard post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.

                $angular_app_hostname   = $container->getParameter('angular_app_hostname'); //angular app host

                $store_album_url     = $container->getParameter('store_media_url'); //store album url
                $to_id   = $store_album_user_id;
                $from_id = $user_id;
                $post_id = $item_id;

                $postService = $container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $container->getParameter('locale');
                $lang_array = $container->getParameter($locale);


                $sender = $postService->getUserData($from_id);

                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->store_album_rating;

                //calling rating notification service
                $notification_obj=$container->get('rating_notification_service');

                $notification_id = $notification_obj->saveUserNotification($user_id, $album_owner_id, $item_id, $msgtype, $rate);
                $postService->sendUserNotifications($user_id, $album_owner_id, $msgtype, 'rate', $item_id, false, true, $sender_name, 'CITIZEN', array('store_id'=>$store_id));
                //end to send social notification


                $storeAlbumId = $store_album->getId();
                $storeAlbumTitle = $store_album->getStoreAlbumName();
                $href = $angular_app_hostname . $store_album_url . '/'. $storeAlbumId.'/'.$storeAlbumTitle.'/'. $store_id;
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $subject  = sprintf($lang_array['STORE_ALBUM_RATE_SUBJECT'], ucwords($sender_name));
                $mail_body = sprintf($lang_array['STORE_ALBUM_RATE_BODY'], ucwords($sender_name));
                $mail_text = sprintf($lang_array['STORE_ALBUM_RATE_TEXT'], ucwords($sender_name), $rate);
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
     * delete the rate for store album.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    public function deleteStorealbumRate($item_type, $item_id, $user_id) {
        $data = array();

         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $album_res = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findOneBy(array('item_id'=>(string)$item_id,'item_type'=>$item_type));

        //if posts is not exist.
        if (!$album_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->store_album_rating;
        $em = $container->get('doctrine')->getManager(); //get entity manager object

        $album_owner_id = $em->getRepository('StoreManagerStoreBundle:Storealbum')->getStoreOwnerId((string)$item_id);//get album owner id
        $store_album_rating  = $album_res->getRate();
        $count = 0;
        foreach ($store_album_rating as $post_rate) {
            if ($post_rate->getUserId() == $user_id) {
                $count = 1;
                $user_rate = $post_rate->getRate();
                $album_res->removeRate($post_rate); // remove the rate post object.
                $total_user_count = $album_res->getVoteCount();
                $total_rate = $album_res->getVoteSum();

                //load the calculate_rate_service
                $calculaterate=$container->get('calculate_rate_service');

                $updated_rate_result = $calculaterate->updateDeleteRate($total_user_count, $total_rate, $user_rate);
                //calculate the updated rating, user count.

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

        //set object for store post for updated rating..
        try {
            $dm->persist($album_res); //storing the post data.
            $dm->flush();
            if ($count > 0) {

                 //calling rating notification service
                $notification_obj=$container->get('rating_notification_service');

                //remove notification if post owner doent read the rate notiication.
                $notification_obj->removeNotification($album_owner_id, $user_id, $item_id, $message_type);

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
     * Edit store album rate
     * @param int $post_id
     * @param int $user_id
     */
    public function editStoreAlbumRate($item_type, $post_id, $rate, $user_id) {
        $arrayPostRate = array();
        $data = array();

         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findOneBy(array('item_id'=>(string)$post_id,'item_type'=>$item_type));

        //if post not exist
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $post->getRate();
        $total_user_count = $post->getVoteCount();
        $total_rate = $post->getVoteSum(); //old total rate

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
            $rating_response = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                    ->editPostRate($rate_id, $arrayPostRate, (string)$post_id);
        }
        if (!$rating_response) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');
        //Update the rate count
        $resp = $calculaterate->updateEditRateCount($total_user_count, $total_rate, (string)$post_id, $current_user_rate, $rate);
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
     * List Store album  rated users
     * @param int $item_type
     * @param int $item_id
     */
    public function getStoreAlbumRateUsers($item_type, $item_id, $limit_start, $limit_size) {
        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findOneBy(array('item_id'=>(string)$item_id,'item_type'=>$item_type));

        //if post not exist
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $post->getRate();
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
     * add the rate for store media.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    public function addStoreMediaRate($item_type, $item_id, $rate, $user_id) {
        $data = array();

         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();


        $em = $container->get('doctrine')->getManager(); //get entity manager object

        $store_media=$em->getRepository('StoreManagerStoreBundle:StoreMedia')->find((string)$item_id);
        //fetching data for album of store from mysql table

        //checking if album exists or not
        if(!$store_media)
        {
         $res_data=array('code'=>302,'message'=>'RECORD_DOES_NOT_EXISTS','data' => $data);
         $this->returnResponse($res_data);
        }

        $album_id = $store_media->getAlbumId();
        $store_id = $store_media->getStoreId();
        $store_album = $em->getRepository('StoreManagerStoreBundle:Storealbum')->find($album_id);
        if(!$store_album)
        {
         $res_data=array('code'=>302,'message'=>'RECORD_DOES_NOT_EXISTS','data' => $data);
         $this->returnResponse($res_data);
        }

        $album_name  = $store_album->getStoreAlbumName();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //fetching album rating
        $media_rate_res = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findOneBy(array('item_id'=>(string)$item_id,'item_type'=>$item_type));

        $item_ratingrate = new ItemRatingRate();
        $time = new \DateTime("now");
        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        //if album rating not exits
        if(!$media_rate_res){
            //new rating object created
             $item_rating = new ItemRating();
            //calculate the new rate ,total user count
            $updated_rate_result = $calculaterate->updateAddRate(0, 0, $rate); //calculate the new rate,
            $new_user_count = $updated_rate_result['new_user_count'];
            $new_total_rate = $updated_rate_result['new_total_rate'];
            $avg_rate = $updated_rate_result['avg_rate'];
            $item_rating->setVoteCount($new_user_count);
            $item_rating->setItemId($item_id);
            $item_rating->setItemType($item_type);
            $item_rating->setVoteSum($new_total_rate);
            $item_rating->setAvgRating($avg_rate);
            //set object for store album rating
            $item_ratingrate->setUserId($user_id);
            $item_ratingrate->setRate($rate);
            $item_ratingrate->setItemId($item_id);
            $item_ratingrate->setType($item_type);
            $item_ratingrate->setCreatedAt($time);
            $item_ratingrate->setUpdatedAt($time);

            $item_rating->addRate($item_ratingrate);

        }else{

            $media_rating   = $media_rate_res->getRate();
            //check if a user already rate on post.
            foreach ($media_rating as $media_rate) {
                if ($media_rate->getUserId() == $user_id) {
                    $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                    $this->returnResponse($res_data);
                }
            }
             //calculate the total, average rate.
            $total_user_count = $media_rate_res->getVoteCount();
            $total_rate = $media_rate_res->getVoteSum();
            //calculate the new rate ,total user count
            $updated_rate_result = $calculaterate->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate,
            $new_user_count = $updated_rate_result['new_user_count'];
            $new_total_rate = $updated_rate_result['new_total_rate'];
            $avg_rate = $updated_rate_result['avg_rate'];
            //set the object.
            $media_rate_res->setVoteCount($new_user_count);
            $media_rate_res->setItemId($item_id);
            $media_rate_res->setItemType($item_type);
            $media_rate_res->setVoteSum($new_total_rate);
            $media_rate_res->setAvgRating($avg_rate);
             //set object for store album rating
            $item_ratingrate->setUserId($user_id);
            $item_ratingrate->setRate($rate);
            $item_ratingrate->setItemId($item_id);
            $item_ratingrate->setType($item_type);
            $item_ratingrate->setCreatedAt($time);
            $item_ratingrate->setUpdatedAt($time);

            $media_rate_res->addRate($item_ratingrate);

        }


        $store_media_user_id = $media_owner_id =$em->getRepository('StoreManagerStoreBundle:StoreMedia')->getStoreOwnerId((string)$item_id);//get album owner id

        try {
            if(!$media_rate_res){
            $dm->persist($item_rating); //storing the album rate data.
            }else{
            $dm->persist($media_rate_res); //storing the album rate data.
            }
            $dm->flush();
            if ($user_id != $media_owner_id) {
                //prepare the mail template for dashboard post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.

                $angular_app_hostname   = $container->getParameter('angular_app_hostname'); //angular app host

                $store_media_url     = $container->getParameter('store_media_url'); //store album url
                $to_id   = $store_media_user_id;
                $from_id = $user_id;
                $post_id = $item_id;

                $postService = $container->get('post_detail.service');
                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $container->getParameter('locale');
                $lang_array = $container->getParameter($locale);

                $sender = $postService->getUserData($from_id);

                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->store_media_rating;

                //calling rating notification service
                $notification_obj=$container->get('rating_notification_service');

                $notification_id = $notification_obj->saveUserNotification($user_id, $media_owner_id, $item_id, $msgtype, $rate);
                $postService->sendUserNotifications($user_id, $media_owner_id, $msgtype, 'rate', $item_id, false, true, $sender_name, 'CITIZEN', array('owner'=>$media_owner_id, 'album'=>$album_id, 'store_id'=>$store_id));
                //end to send social notification

                //$href = $angular_app_hostname . $store_media_url . '/' . $album_id.'/'.$album_name.'/'.$store_id;
                $href = $email_template_service->getPageUrl(array('supportId'=>$store_id, 'parentId'=> $album_id, 'mediaId'=>$store_media->getId(), 'albumType'=>'shop'),'single_image_page');
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $subject  = sprintf($lang_array['STORE_ALBUM_PHOTO_RATE_SUBJECT'], ucwords($sender_name));
                $mail_body = sprintf($lang_array['STORE_ALBUM_PHOTO_RATE_BODY'], ucwords($sender_name));
                $mail_text = sprintf($lang_array['STORE_ALBUM_PHOTO_RATE_TEXT'], ucwords($sender_name), $rate);
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
     * delete the rate for store media.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    public function deleteStoreMediaRate($item_type, $item_id, $user_id) {
        $data = array();

         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $media_res = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findOneBy(array('item_id'=>(string)$item_id,'item_type'=>$item_type));

        //if posts is not exist.
        if (!$media_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->store_media_rating;
        $em = $container->get('doctrine')->getManager(); //get entity manager object

        $media_owner_id = $em->getRepository('StoreManagerStoreBundle:StoreMedia')->getStoreOwnerId((string)$item_id);//get media owner id
        $store_media_rating  = $media_res->getRate();
        $count = 0;
        foreach ($store_media_rating as $post_rate) {
            if ($post_rate->getUserId() == $user_id) {
                $count = 1;
                $user_rate = $post_rate->getRate();
                $media_res->removeRate($post_rate); // remove the rate post object.
                $total_user_count = $media_res->getVoteCount();
                $total_rate = $media_res->getVoteSum();

                //load the calculate_rate_service
                $calculaterate=$container->get('calculate_rate_service');

                $updated_rate_result = $calculaterate->updateDeleteRate($total_user_count, $total_rate, $user_rate);
                //calculate the updated rating, user count.

                $new_user_count = $updated_rate_result['new_user_count'];
                $new_total_rate = $updated_rate_result['new_total_rate'];
                $avg_rate = $updated_rate_result['avg_rate'];
                //set the object.
                $media_res->setVoteCount($new_user_count);
                $media_res->setVoteSum($new_total_rate);
                $media_res->setAvgRating($avg_rate);
                break;
            }
        }

        //set object for store post for updated rating..
        try {
            $dm->persist($media_res); //storing the post data.
            $dm->flush();
            if ($count > 0) {

                 //calling rating notification service
                $notification_obj=$container->get('rating_notification_service');

                //remove notification if post owner doent read the rate notiication.
                $notification_obj->removeNotification($media_owner_id, $user_id, (string)$item_id, $message_type);

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
     * Edit store media rate
     * @param int $post_id
     * @param int $user_id
     */
    public function editStoreMediaRate($item_type, $post_id, $rate, $user_id) {
        $arrayPostRate = array();
        $data = array();

         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findOneBy(array('item_id'=>(string)$post_id,'item_type'=>$item_type));

        //if post not exist
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $post->getRate();
        $total_user_count = $post->getVoteCount();
        $total_rate = $post->getVoteSum(); //old total rate

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
            $rating_response = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                    ->editPostRate($rate_id, $arrayPostRate, (string)$post_id);
        }
        if (!$rating_response) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');
        //Update the rate count
        $resp = $calculaterate->updateEditRateCount($total_user_count, $total_rate,(string) $post_id, $current_user_rate, $rate);
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
     * List Store media rated users
     * @param int $item_type
     * @param int $item_id
     */
    public function getStoreMediaRateUsers($item_type, $item_id, $limit_start, $limit_size) {
        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findOneBy(array('item_id'=>(string)$item_id,'item_type'=>$item_type));

        //if post not exist
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $post->getRate();
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
     * delete the rate for store posts comment.
     * @param string $item_type
     * @param string $comment_id
     * @param int $user_id
     * @return json
     */
    public function deleteStoreAlbumCommentRate($item_type, $comment_id, $user_id) {
        $data = array();

        //getting the container object
        $container=  NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('MediaMediaBundle:StoreAlbumComment')
                        ->find($comment_id);
        //if posts is not exist.
        if (!$comment_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->store_album_comment_rating;
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
     * Edit Store Album Comment rate
     * @param int $comment_id
     * @param int $user_id
     */
    public function editStoreAlbumCommentRate($item_type, $comment_id, $rate, $user_id)
    {
        $arrayCommentRate = array();
        $data = array();
        //getting the container object
        $container=  NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $comment   = $dm->getRepository('MediaMediaBundle:StoreAlbumComment')
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
        $rating_response = $dm->getRepository('MediaMediaBundle:StoreAlbumComment')
                               ->editStoreAlbumCommentRate($rate_id, $arrayCommentRate, $comment_id);

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
    public function getStoreAlbumCommentRateUsers($item_type, $item_id) {
        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('MediaMediaBundle:StoreAlbumComment')
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
     * delete the rate for store posts comment.
     * @param string $item_type
     * @param string $comment_id
     * @param int $user_id
     * @return json
     */
    public function deleteStoreAlbumMediaCommentRate($item_type, $comment_id, $item_id, $user_id) {
        $data = array();

        //getting the container object
        $container=  NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                        ->find($comment_id);
        //if posts is not exist.
        if (!$comment_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->store_media_comment_rating;
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
     * Edit Store Album Comment rate
     * @param int $comment_id
     * @param int $user_id
     */
    public function editStoreAlbumMediaCommentRate($type, $comment_id, $type_id, $rate, $user_id)
    {
        $arrayCommentRate = array();
        $data = array();
        //getting the container object
        $container=  NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $comment   = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
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
        $rating_response = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                               ->editCommentRate($rate_id, $arrayCommentRate, $comment_id);

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
    public function getStoreAlbumMediaCommentRateUsers($item_type, $item_id, $comment_id, $limit_start, $limit_size) {

        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                ->find($comment_id);

        //if post not exist
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $votes = $post->getRate();
        $voters_array = array();

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
        $total_count = count($votes)>0 ? count($votes) : 0;
        $data = array('type_id' => $item_id, 'type' => $item_type, 'total_users' => $total_count, 'users_rated' => $users);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }

    /**
     * Rating on Store post media
     * @param type $item_type
     * @param type $comment_id
     * @param type $item_id
     * @param type $rate
     * @param type $user_id
     */
    public function addStorePostMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id){
        $data = array();
       //get container object
        $container = NManagerNotificationBundle::getContainer();
          //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $image_res = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                         ->find($item_id);
        if (!$image_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $post_id = $image_res->getPostId();
        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                         ->find($post_id);
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $store_id = $post->getStoreId();
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
                    break;
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
                $msgtype = $this->storepost_media_comment_rating;

                //prepare the mail template for dashboard post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.
                $to_id   = $comment_owner_id;
                $from_id = $user_id;

                //get the local parameters in parameters file.
                $postService = $container->get('post_detail.service');
                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                $receiver = $postService->getUserData($to_id);
                $locale = !empty($receiver['current_language']) ? $receiver['current_language'] : $container->getParameter('locale');
                $language_const_array = $container->getParameter($locale);

                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, $rate, $post_id, true, false, $sender_name, 'CITIZEN', array('store_id'=>$store_id, 'msg_code'=>'rate'), 'U', array('comment_id'=>$comment_id, 'store_id'=>$store_id));
                $href = $link_url = $postService->getStoreClubUrl(array('storeId'=>$store_id, 'postId'=>$post_id), 'store');
                $link = $email_template_service->getLinkForMail($href, $locale); //making the link html from service
                $mail_sub  = sprintf($language_const_array['STORE_POST_MEDIA_COMMENT_RATE_SUBJECT'], $sender_name);
                $mail_body = sprintf($language_const_array['STORE_POST_MEDIA_COMMENT_RATE_BODY'], ucwords($sender_name));
                $mail_text = sprintf($language_const_array['STORE_POST_MEDIA_COMMENT_RATE_TEXT'],ucwords($sender_name), $rate);
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
    public function editStorePostMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id){
        $arrayCommentRate = array();
        $data = array();

        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $media_res = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')->find($item_id);

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
                    break;
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
    public function deleteStorePostMediaCommentRate($item_type, $comment_id, $item_id, $user_id){
        $data = array();
        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $media_res = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')->find($item_id);
        //if posts is not exist.
        if (!$media_res) {
            $res_data = array('code' => 302, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

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
                    break;
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

        //set object for dashboard post for updated rating..
        try {
            $dm->persist($comment_res); //storing the post data.
            $dm->flush();
            if ($rate_exists) {
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
    public function getStorePostMediaCommentRateUsers($item_type, $item_id, $comment_id, $limit_start, $limit_size){
        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $media_res = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
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
                   break;
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
     * Rating on Store post media
     * @param type $item_type
     * @param type $comment_id
     * @param type $item_id
     * @param type $rate
     * @param type $user_id
     */
    public function addStorePostMediaRate($item_type, $item_id, $rate, $user_id){
        $data = array();
       //get container object
        $container = NManagerNotificationBundle::getContainer();
          //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $image_res = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                         ->find($item_id);
        if (!$image_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $post_id = $image_res->getPostId();
        $post = $dm->getRepository('StoreManagerPostBundle:StorePosts')
                         ->find($post_id);
        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $store_id = $post->getStoreId();
        $mediarating = new UserMediaRating();
        $time = new \DateTime("now");

        $img_rates = $image_res->getRate();
        foreach($img_rates as $img_rate){
            if($img_rate->getUserId() == $user_id){
                $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                $this->returnResponse($res_data);
            }

        }

        //calculate the total, average rate.
        $total_user_count = $image_res->getVoteCount();
        $total_rate = $image_res->getVoteSum();

        //calculate the new rate ,total user count
        $updated_rate_result=$calculaterate->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count


        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $image_res->setVoteCount($new_user_count);
        $image_res->setVoteSum($new_total_rate);
        $image_res->setAvgRating($avg_rate);

        $owner_id = $post->getStorePostAuthor(); //get club owner id

        //set object for media comment rating
        $mediarating->setUserId($user_id);
        $mediarating->setRate($rate);
        $mediarating->setItemId($item_id);
        $mediarating->setCreatedAt($time);
        $mediarating->setUpdatedAt($time);

        $image_res->addRate($mediarating);
        try {
            $dm->persist($image_res); //storing the rating data.
            $dm->flush();

            if ($user_id != $owner_id) {
                //send social notification
                $msgtype = $this->storepost_media_rating;

                //prepare the mail template for dashboard post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.
                $to_id   = $owner_id;
                $from_id = $user_id;

                //get the local parameters in parameters file.
                $postService = $container->get('post_detail.service');
                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                $receiver = $postService->getUserData($to_id);
                $locale = !empty($receiver['current_language']) ? $receiver['current_language'] : $container->getParameter('locale');
                $language_const_array = $container->getParameter($locale);

                $postService->sendUserNotifications($user_id, $owner_id, $msgtype, $rate, $post_id, true, true, $sender_name, 'CITIZEN', array('store_id'=>$store_id, 'msg_code'=>'rate'), 'U', array('store_id'=>$store_id));
                $href = $link_url = $postService->getStoreClubUrl(array('storeId'=>$store_id, 'postId'=>$post_id), 'store');
                $link = $email_template_service->getLinkForMail($href, $locale); //making the link html from service
                $mail_sub  = sprintf($language_const_array['STORE_POST_MEDIA_RATE_SUBJECT'], $sender_name);
                $mail_body = sprintf($language_const_array['STORE_POST_MEDIA_RATE_BODY'], ucwords($sender_name));
                $mail_text = sprintf($language_const_array['STORE_POST_MEDIA_RATE_TEXT'],ucwords($sender_name), $rate);
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
    public function editStorePostMediaRate($item_type, $item_id, $rate, $user_id){
        $arrayCommentRate = array();
        $data = array();

        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $media_res = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')->find($item_id);

        //if post not exist
        if (!$media_res) {
            $res_data = array('code' => 302, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $time = new \DateTime("now");
        $rate_exists = false;
        $rates = $media_res->getRate();
        foreach($rates as $_rate){
            if($_rate->getUserId() == $user_id){
                $rate_exists = true;

                $current_user_rate = $_rate->getRate();
                //set new rate
                $_rate->setRate((int) $rate);
                $_rate->setUpdatedAt($time);
            }

        }

        //check if vote exists
        if(!$rate_exists){
            $res_data = array('code' => 302, 'message' => 'VOTE_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //calculate the total, average rate.
        $total_user_count = $media_res->getVoteCount();
        $total_rate = $media_res->getVoteSum();

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');
        //Update the rate count
        $resp = $calculaterate->updateEditRateCount($total_user_count, $total_rate, $media_res->getId(), $current_user_rate, $rate);
        $new_total_rate = $resp['total_rate'];
        $avg_rate = $resp['avg_rate'];

        $media_res->setVoteSum($new_total_rate);
        $media_res->setAvgRating($avg_rate);

        try {

            $dm->persist($media_res); //storing the post data.
            $dm->persist($media_res); //storing the post data.
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
    public function deleteStorePostMediaRate($item_type, $item_id, $user_id){
        $data = array();
        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        $media_res = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')->find($item_id);
        //if posts is not exist.
        if (!$media_res) {
            $res_data = array('code' => 302, 'message' => 'MEDIA_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $time = new \DateTime("now");

        $total_user_count = $media_res->getVoteCount();
        $total_rate = $media_res->getVoteSum();
        $rate_exists = false;
        //comment
        $comment_rates = $media_res->getRate();
        foreach($comment_rates as $comment_rate){
            if($comment_rate->getUserId() == $user_id){
                $rate_exists = true;

                $user_rate = $comment_rate->getRate();
                $media_res->removeRate($comment_rate); // remove the rate post object.
                $total_user_count = $media_res->getVoteCount();
                $total_rate = $media_res->getVoteSum();
                //load the calculate_rate_service
                $calculaterate=$container->get('calculate_rate_service');
                $updated_rate_result = $calculaterate->updateDeleteRate($total_user_count, $total_rate, $user_rate); //calculate the updated rating, user count.

                $new_user_count = $updated_rate_result['new_user_count'];
                $new_total_rate = $updated_rate_result['new_total_rate'];
                $avg_rate = $updated_rate_result['avg_rate'];
                //set the object.
                $media_res->setVoteCount($new_user_count);
                $media_res->setVoteSum($new_total_rate);
                $media_res->setAvgRating($avg_rate);

            }

        }

        //check if vote exists
        if(!$rate_exists){
            $res_data = array('code' => 302, 'message' => 'VOTE_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        //set object for dashboard post for updated rating..
        try {
            $dm->persist($media_res); //storing the post data.
            $dm->flush();
            if ($rate_exists) {
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
    public function getStorePostMediaRateUsers($item_type, $item_id, $limit_start, $limit_size){
        $data = array();
        $users = array();
        $count = 0;
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $media_res = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                ->findOneBy(array('id' => $item_id)); //@TODO Add group owner id in AND clause.

        //if post not exist
        if (!$media_res) {
             $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        $voters_array = array();
        $total_user_count = $media_res->getVoteCount();
        $votes = $media_res->getRate();
        //get total votes count
        if($votes){
            $count = count($votes);
        } else {
            $count = 0;
        }
        $rate_users = array();
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

        $data = array('type_id' => $item_id, 'type' => $item_type, 'total_users' => $total_user_count, 'users_rated' => $users);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        $this->returnResponse($res_data);
    }

    /**
     * add the rate for Store album Comments.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    public function addStoreAlbumCommentRate($item_type, $item_id, $rate, $user_id) {
        $data = array();
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $em = $container->get('doctrine')->getManager();
        $comment_res = $dm->getRepository('MediaMediaBundle:StoreAlbumComment')
                          ->find($item_id);

        if (!$comment_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        $comment_rating   = $comment_res->getRate();
        $album_id = $comment_res->getAlbumId();
        //check if a user already rate on post comment.
        foreach ($comment_rating as $comment_rate) {
            if ($comment_rate->getUserId() == $user_id) {
                $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
                $this->returnResponse($res_data);
            }
        }

      //  $dashboard_comment_rating = new DashboardCommentRating();
        $storealbum_comment_rating = new StoreAlbumCommentRating();
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
        $storealbum_comment_rating->setUserId($user_id);
        $storealbum_comment_rating->setRate($rate);
        $storealbum_comment_rating->setItemId($item_id);
        $storealbum_comment_rating->setType('store_album_comment');
        $storealbum_comment_rating->setCreatedAt($time);
        $storealbum_comment_rating->setUpdatedAt($time);

        $comment_res->addRate($storealbum_comment_rating);
        try {
            $dm->persist($comment_res); //storing the rating data.
            $dm->flush();

            if ($user_id != $comment_owner_id) {
                $storeMedia = $em->getRepository('StoreManagerStoreBundle:Storealbum')->find($album_id);
                $store_id = $storeMedia->getStoreId();
                $store = $em->getRepository('StoreManagerStoreBundle:Store')->find($store_id);
                $store_name = $store->getName();
                $store_name = !empty($store_name) ? $store_name : $store->getBusinessName();
                //send social notification
                $msgtype = $this->store_album_comment_rating;

                //prepare the mail template for dashboard post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $container->getParameter('angular_app_hostname'); //angular app host
               // $dashboard_post_url     = $this->container->getParameter('dashboard_post_url'); //dashboard post url
                $storealbum_url          = $container->getParameter('store_album_url');
                $to_id   = $store_album_comment_user_id;
                $from_id = $user_id;
                $post_id = $item_id;

                $postService = $container->get('post_detail.service');
                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                $receiver = $postService->getUserData($to_id);

                $locale = !empty($receiver['current_language']) ? $receiver['current_language'] : $container->getParameter('locale');
                $language_const_array = $container->getParameter($locale);

                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, $rate, $album_id, true, false, array($sender_name, $store_name), 'CITIZEN', array('store_id'=>$store_id, 'msg_code'=>'rate'), 'U', array('comment_id'=>$item_id, 'store_id'=>$store_id));

                $href = $angular_app_hostname . $storealbum_url . '/' . $post_id;
                $link = $email_template_service->getLinkForMail($href); //making the link html from service
                $mail_sub  = sprintf($language_const_array['STORE_ALBUM_COMMENT_RATE_SUBJECT'], $sender_name, $store_name);
                $mail_body = sprintf($language_const_array['STORE_ALBUM_COMMENT_RATE_BODY'], ucwords($sender_name), $store_name);
                $mail_text = sprintf($language_const_array['STORE_ALBUM_COMMENT_RATE_TEXT'], ucwords($sender_name), $store_name, $rate);
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
     * add the rate for Store album Comments.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    public function addStoreAlbumMediaCommentRate($item_type, $comment_id, $item_id, $rate, $user_id) {
        $data = array();
         //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();

        $dm = $container->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $em = $container->get('doctrine')->getManager();
        $comment_res = $dm->getRepository('MediaMediaBundle:StoreAlbumMediaComment')
                          ->find($comment_id);
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

        $storealbum_comment_rating = new AlbumMediaCommentRating();
        $time = new \DateTime("now");

        //calculate the total, average rate.
        $total_user_count = $comment_res->getVoteCount();
        $total_rate = $comment_res->getVoteSum();

        //load the calculate_rate_service
        $calculaterate=$container->get('calculate_rate_service');
        $store_album_comment_user_id = $comment_owner_id = $comment_res->getCommentAuthor(); //get comment owner id
        //media owner = store album owner = store owner
        $media_owner_id =$em->getRepository('StoreManagerStoreBundle:StoreMedia')->getStoreOwnerId((string)$item_id);

        $updated_rate_result = $calculaterate->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count
        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $comment_res->setVoteCount($new_user_count);
        $comment_res->setVoteSum($new_total_rate);
        $comment_res->setAvgRating($avg_rate);

        //set object for dashboard post rating
        $storealbum_comment_rating->setUserId($user_id);
        $storealbum_comment_rating->setRate($rate);
        $storealbum_comment_rating->setItemId($item_id);
        $storealbum_comment_rating->setType('store_album_comment');
        $storealbum_comment_rating->setCreatedAt($time);
        $storealbum_comment_rating->setUpdatedAt($time);

        $comment_res->addRate($storealbum_comment_rating);
        $media_id = $comment_res->getMediaId();
        $album_id = $comment_res->getAlbumId();

        try {
            $storeMedia = $em->getRepository('StoreManagerStoreBundle:StoreMedia')->find($media_id);
            $store_id = $storeMedia->getStoreId();
            $storeInfo = $em->getRepository('StoreManagerStoreBundle:UserToStore')
                    ->findOneBy(array('storeId'=>$store_id, 'role'=>15));
            $album_owner_id = $storeInfo ? $storeInfo->getUserId() : '';

            $dm->persist($comment_res); //storing the rating data.
            $dm->flush();

            if ($user_id != $comment_owner_id) {
                //send social notification
                $msgtype = $this->store_media_comment_rating;

                //prepare the mail template for dashboard post rating.
                $email_template_service = $container->get('email_template.service'); //email template service.
                $postService = $container->get('post_detail.service');
                $to_id   = $store_album_comment_user_id;
                $from_id = $user_id;
                $post_id = $item_id;
                $receiver = $postService->getUserData($to_id);

                $locale = !empty($receiver['current_language']) ? $receiver['current_language'] : $container->getParameter('locale');
                $language_const_array = $container->getParameter($locale);

                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, $rate, $media_id, true, true, $sender_name, 'CITIZEN', array('album_id'=>$album_id, 'store_id'=>$store_id, 'msg_code'=>'rate','owner'=> $media_owner_id), 'U', array('comment_id'=>$item_id, 'album_id'=>$album_id, 'store_id'=>$store_id));

                $href = $email_template_service->getPageUrl(array('supportId'=>$album_owner_id, 'parentId'=> $album_id, 'mediaId'=>$media_id, 'albumType'=>'shop'),'single_image_page');
                $link = $email_template_service->getLinkForMail($href); //making the link html from service
                $mail_sub  = sprintf($language_const_array['STORE_ALBUM_MEDIA_COMMENT_RATE_SUBJECT']);
                $mail_body = sprintf($language_const_array['STORE_ALBUM_MEDIA_COMMENT_RATE_BODY'], ucwords($sender_name), $rate);
                $mail_text = sprintf($language_const_array['STORE_ALBUM_MEDIA_COMMENT_RATE_TEXT'], ucwords($sender_name), $rate);
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

}
