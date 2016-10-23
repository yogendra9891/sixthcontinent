<?php

namespace Media\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UserManager\Sonata\UserBundle\Document\GroupAlbumRating;
use UserManager\Sonata\UserBundle\Document\GroupMediaRating;
use Post\PostBundle\Document\PostRating;
use Post\PostBundle\Document\Post;
use Post\PostBundle\Document\CommentRating;
use Post\PostBundle\Document\Comments;

abstract class ShopClubRatingController extends Controller
{

    abstract function getAppData(Request $request);
    abstract function checkParamsAction($chk_params, $object_info);
    abstract function decodeData($req_obj);
    abstract function returnResponse($data_array);
    abstract function updateEditRateCount($vote_count, $vote_sum, $post_id, $current_user_vote, $rate);
    abstract function updateAddRate($total_user_count, $total_rate, $rate);
    abstract function updateDeleteRate($total_user_count, $total_rate, $rate);
    abstract function roundNumber($number);
    abstract function sendMail($mail_sub, $mail_body, $thumb_path, $link, $from_id, $to_id);
    abstract function getUserManager();
    abstract function saveUserNotification($user_id, $fid, $item_id, $msgtype, $msg);
    abstract function removeNotification($item_owner_id, $user_id, $item_id, $message_type);

    protected $club_post_rating = "CLUB_POST_RATE";
    protected $club_post_comment_rating = "CLUB_POST_COMMENT_RATE";
    protected $club_album_rating = "CLUB_ALBUM_RATE";
    protected $club_album_photo_rating = "CLUB_ALBUM_PHOTO_RATE";
    protected $shop_post_rating = "SHOP_POST_RATE";
    protected $shop_post_comment_rating = "SHOP_POST_COMMENT_RATE";
    protected $shop_album_rating = "SHOP_ALBUM_RATE";
    protected $shop_album_photo_rating = "SHOP_ALBUM_PHOTO_RATE";

    /* club ratting start */

    /**
     * add the rate for club posts.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    public function addClubPostRate($item_type, $item_id, $rate, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_res = $dm->getRepository('PostPostBundle:Post')
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
        $club_post_rating = new PostRating();
        $time = new \DateTime("now");

        //calculate the total, average rate.
        $total_user_count = $post_res->getVoteCount();
        $total_rate = $post_res->getVoteSum();

        $club_post_user_id = $post_owner_id =  $post_res->getPostAuthor(); //get post owner id
        $post_gid = $post_res->getPostGid();
        $updated_rate_result = $this->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count

        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate = $updated_rate_result['avg_rate'];

        //set the object.
        $post_res->setVoteCount($new_user_count);
        $post_res->setVoteSum($new_total_rate);
        $post_res->setAvgRating($avg_rate);

        //set object for dashboard post rating
        $club_post_rating->setUserId($user_id);
        $club_post_rating->setRate($rate);
        $club_post_rating->setItemId($item_id);
        $club_post_rating->setType('club_post');
        $club_post_rating->setCreatedAt($time);
        $club_post_rating->setUpdatedAt($time);

        $post_res->addRate($club_post_rating);
        try {
            $dm->persist($post_res); //storing the post data.
            $dm->flush();
            if ($user_id != $post_owner_id) {
                //prepare the mail template for dashboard post rating.
                $email_template_service = $this->container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
                //$club_post_url     = $this->container->getParameter('club_post_url'); //dashboard post url
                $to_id   = $club_post_user_id;
                $from_id = $user_id;
                $post_id = $item_id;
                //get the local parameters in parameters file.
                $locale = $this->container->getParameter('locale');
                $language_const_array = $this->container->getParameter($locale);

                $postService = $this->get('post_detail.service');
                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->club_post_rating;
                $notification_id = $this->saveUserNotification($user_id, $post_owner_id, $item_id, $msgtype, $rate);
                $postService->sendUserNotifications($user_id, $post_owner_id, $msgtype, 'rate', $item_id, false, true, $sender_name, 'CITIZEN', array("club_id"=>$post_gid));
                //end to send social notification

                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);

                $href = $postService->getStoreClubUrl(array('clubId'=>$post_gid, 'postId'=>$post_id), 'club');
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $mail_sub  = sprintf($lang_array['CLUB_POST_RATE_SUBJECT'], $sender_name);
                $mail_body = sprintf($lang_array['CLUB_POST_RATE_BODY'], ucwords($sender_name));
                $mail_text = sprintf($lang_array['CLUB_POST_RATE_TEXT'], $sender_name, $rate);
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
     * Edit Club Post rate
     * @param int $post_id
     * @param int $user_id
     */
    public function editClubPostRate($item_type, $post_id, $rate, $user_id) {
        $arrayPostRate = array();
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('PostPostBundle:Post')
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
            $rating_response = $dm->getRepository('PostPostBundle:Post')
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
     * delete the rate for club posts.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    public function deleteClubPostRate($item_type, $item_id, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post_res = $dm->getRepository('PostPostBundle:Post')
                ->find($item_id);
        //if posts is not exist.
        if (!$post_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->club_post_rating;
        $post_owner_id = $post_res->getPostAuthor();
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
     * List dashboard rated users
     * @param int $item_type
     * @param int $item_id
     */
    public function getClubPostRateUsers($item_type, $item_id) {
        $data = array();
        $users = array();
        $count = 0;
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //get rating object
        $post = $dm->getRepository('PostPostBundle:Post')
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
        $user_service = $this->get('user_object.service');
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
     * add the rate for club posts Comments.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    public function addClubPostCommentRate($item_type, $item_id, $rate, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('PostPostBundle:Comments')
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

        $club_comment_rating = new CommentRating();
        $time = new \DateTime("now");

        //calculate the total, average rate.
        $total_user_count = $comment_res->getVoteCount();
        $total_rate = $comment_res->getVoteSum();

        $club_post_comment_user_id = $comment_owner_id = $comment_res->getCommentAuthor(); //get post owner id
        $postId = $comment_res->getPostId();
        $updated_rate_result = $this->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count

        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $comment_res->setVoteCount($new_user_count);
        $comment_res->setVoteSum($new_total_rate);
        $comment_res->setAvgRating($avg_rate);

        //set object for dashboard post rating
        $club_comment_rating->setUserId($user_id);
        $club_comment_rating->setRate($rate);
        $club_comment_rating->setItemId($item_id);
        $club_comment_rating->setType('club_post_comment');
        $club_comment_rating->setCreatedAt($time);
        $club_comment_rating->setUpdatedAt($time);

        $comment_res->addRate($club_comment_rating);
        try {
            $dm->persist($comment_res); //storing the post data.
            $dm->flush();

            if ($user_id != $comment_owner_id) {
                //prepare the mail template for dashboard post rating.
                $email_template_service = $this->container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
                //$club_post_url    = $this->container->getParameter('club_profile_url'); //dashboard post url
                $to_id   = $club_post_comment_user_id;
                $from_id = $user_id;
                $post_id = $item_id;
                $_postData = $dm->getRepository('PostPostBundle:Post')
                            ->find($postId);
                $postService = $this->get('post_detail.service');
                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->club_post_comment_rating;
                $notification_id = $this->saveUserNotification($user_id, $comment_owner_id, $item_id, $msgtype, $rate);
                $postService->sendUserNotifications($user_id, $comment_owner_id, $msgtype, 'rate', $item_id, false, true, $sender_name, 'CITIZEN', array('ref_id'=>$postId));
                //end to send social notification

                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);

                $href = $postService->getStoreClubUrl(array('clubId'=>$_postData->getPostGid(), 'postId'=>$_postData->getId()), 'club');
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $mail_sub  = sprintf($lang_array['CLUB_POST_COMMENT_RATE_SUBJECT'], $sender_name);
                $mail_body = sprintf($lang_array['CLUB_POST_COMMENT_RATE_BODY'], ucwords($sender_name));
                $mail_text = sprintf($lang_array['CLUB_POST_COMMENT_RATE_TEXT'], $sender_name, $rate);
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
    public function editClubPostCommentRate($item_type, $comment_id, $rate, $user_id)
    {
        $arrayCommentRate = array();
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $comment   = $dm->getRepository('PostPostBundle:Comments')
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
        $rating_response = $dm->getRepository('PostPostBundle:Comments')
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
     * delete the rate for club post comments.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    public function deleteClubPostCommentRate($item_type, $item_id, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $comment_res = $dm->getRepository('PostPostBundle:Comments')
                ->find($item_id);
        //if posts is not exist.
        if (!$comment_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->club_post_comment_rating;
        $comment_owner_id = $comment_res->getCommentAuthor();
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
     * add the rate for club album.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    public function addClubAlbumRate($item_type, $item_id, $rate, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $album_res = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
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

        $club_album_rating = new GroupAlbumRating();
        $time = new \DateTime("now");

        //calculate the total, average rate.
        $total_user_count = $album_res->getVoteCount();
        $total_rate = $album_res->getVoteSum();

        //get album owner Id
        $club_id =  $album_res->getGroupId();
        $club_details = $dm->getRepository('UserManagerSonataUserBundle:Group')
                ->find($club_id);
        if(!$club_details){
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            echo json_encode($response_data);
            exit;
        } else {
            $album_user_id = $club_details->getOwnerId(); //get post owner id
            $club_id = $club_details->getId(); //get club id
            $clubStatus = $club_details->getGroupStatus();
        }

        $updated_rate_result = $this->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count

        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $album_res->setVoteCount($new_user_count);
        $album_res->setVoteSum($new_total_rate);
        $album_res->setAvgRating($avg_rate);

        //set object for dashboard post rating
        $club_album_rating->setUserId($user_id);
        $club_album_rating->setRate($rate);
        $club_album_rating->setItemId($item_id);
        $club_album_rating->setType('club_album');
        $club_album_rating->setCreatedAt($time);
        $club_album_rating->setUpdatedAt($time);

        $album_res->addRate($club_album_rating);
        try {
            $dm->persist($album_res); //storing the post data.
            $dm->flush();

            if ($user_id != $album_user_id) {
                //prepare the mail template for dashboard post rating.
                $email_template_service = $this->container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
                $club_album_url     = $this->container->getParameter('club_album_url'); //dashboard post url
                $to_id   = $album_user_id;
                $from_id = $user_id;
                $album_id = $item_id;
                $album_name = $album_res->getAlbumName();

                $postService = $this->get('post_detail.service');
                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->club_album_rating;
                $postService->sendUserNotifications($user_id, $album_user_id, $msgtype, $rate, $item_id, true, true, $sender_name, 'CITIZEN', array("club_id"=>$club_id, "msg_code"=>'rate'), 'U', array("club_id"=>$club_id));
                //end to send social notification

                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);

                $href = $angular_app_hostname . $club_album_url . '/' . $club_id. '/'.$album_id. '/'.$clubStatus.'/'.$album_name;
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $mail_sub  = sprintf($lang_array['CLUB_ALBUM_RATE_SUBJECT'], $sender_name);
                $mail_body = sprintf($lang_array['CLUB_ALBUM_RATE_BODY'], ucwords($sender_name));
                $mail_text = sprintf($lang_array['CLUB_ALBUM_RATE_TEXT'], $sender_name, $rate);
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
     * Edit Club Album rate
     * @param int $comment_id
     * @param int $user_id
     */
    public function editClubAlbumRate($item_type, $item_id, $rate, $user_id)
    {
        $arrayAlbumRate = array();
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $album   = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
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
        $rating_response = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
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
     * delete the rate from club album.
     * @param string $item_type
     * @param string $item_id
     * @param int $user_id
     * @return json
     */
    public function deleteClubAlbumRate($item_type, $item_id, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $album_res = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                ->find($item_id);
        //if posts is not exist.
        if (!$album_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->club_album_rating;

        //get album owner Id
        $club_id =  $album_res->getGroupId();
        $club_details = $dm->getRepository('UserManagerSonataUserBundle:Group')
                ->find($club_id);
        if(!$club_details){
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            echo json_encode($response_data);
            exit;
        } else {
            $album_owner_id = $club_details->getOwnerId(); //get post owner id
        }

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
     * add the rate for club album image.
     * @param string $item_type
     * @param string $item_id
     * @param int $rate
     * @param int $user_id
     * @return json
     */
    public function addClubAlbumImageRate($item_type, $item_id, $rate, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $image_res = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
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


        $club_album_image_rating = new GroupMediaRating();
        $time = new \DateTime("now");

        //calculate the total, average rate.
        $total_user_count = $image_res->getVoteCount();
        $total_rate = $image_res->getVoteSum();

        $club_id =  $image_res->getGroupId();
        $club_details = $dm->getRepository('UserManagerSonataUserBundle:Group')
                ->find($club_id);
        if(!$club_details){
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            echo json_encode($response_data);
            exit;
        } else {
            $image_user_id = $club_details->getOwnerId();//get image owner id
            $clubStatus = $club_details->getGroupStatus();
        }

        $updated_rate_result = $this->updateAddRate($total_user_count, $total_rate, $rate); //calculate the new rate, total user count

        $new_user_count = $updated_rate_result['new_user_count'];
        $new_total_rate = $updated_rate_result['new_total_rate'];
        $avg_rate       = $updated_rate_result['avg_rate'];

        //set the object.
        $image_res->setVoteCount($new_user_count);
        $image_res->setVoteSum($new_total_rate);
        $image_res->setAvgRating($avg_rate);

        //set object for dashboard post rating
        $club_album_image_rating->setUserId($user_id);
        $club_album_image_rating->setRate($rate);
        $club_album_image_rating->setItemId($item_id);
        $club_album_image_rating->setType('club_album_image');
        $club_album_image_rating->setCreatedAt($time);
        $club_album_image_rating->setUpdatedAt($time);

        $image_res->addRate($club_album_image_rating);
        try {
            $dm->persist($image_res); //storing the post data.
            $dm->flush();

            if ($user_id != $image_user_id) {
                //prepare the mail template for dashboard post rating.
                $email_template_service = $this->container->get('email_template.service'); //email template service.
                $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
                $club_album_url     = $this->container->getParameter('club_album_url'); //dashboard post url
                $to_id   = $image_user_id;
                $from_id = $user_id;
                //getting media details
                $album_id = $image_res->getAlbumid();
                $album_name = "";
                if($album_id)
                {
                    $album_detail = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')->find($album_id);
                    $album_name = $album_detail->getAlbumName();
                }

                $postService = $this->get('post_detail.service');
                $sender = $postService->getUserData($from_id);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                //send social notification
                $msgtype = $this->club_album_photo_rating;

                $postService->sendUserNotifications($user_id, $image_user_id, $msgtype, $rate, $item_id, true, true, $sender_name, 'CITIZEN', array("club_id"=>$club_id, 'album_id'=>$album_id, 'msg_code'=>'rate'), 'U', array("club_id"=>$club_id, 'album_id'=>$album_id));
                //end to send social notification

                $receiver = $postService->getUserData($to_id, true);
                //get locale
                $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);

                //$href = $angular_app_hostname . $club_album_url .'/' . $club_id. '/'.$album_id. '/'.$clubStatus.'/'.$album_name;
                $href = $email_template_service->getPageUrl(array('supportId'=>$image_user_id, 'parentId'=> $album_id, 'mediaId'=>$image_res->getId(), 'albumType'=>'club'),'single_image_page');
                $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
                $mail_sub  = sprintf($lang_array['USER_ALBUM_PHOTO_RATE_SUBJECT'], $sender_name);
                $mail_body = sprintf($lang_array['USER_ALBUM_PHOTO_RATE_BODY'], ucwords($sender_name));
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
    public function editClubAlbumImageRate($item_type, $item_id, $rate, $user_id)
    {
        $arrayImageRate = array();
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

        //get rating object
        $image = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
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
        $rating_response = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
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
    public function deleteClubAlbumImageRate($item_type, $item_id, $user_id) {
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $image_res = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                ->find($item_id);
        //if posts is not exist.
        if (!$image_res) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => $data);
            $this->returnResponse($res_data);
        }

        $message_type  = $this->club_album_photo_rating;
        $club_id =  $image_res->getGroupId();
        $club_details = $dm->getRepository('UserManagerSonataUserBundle:Group')
                ->find($club_id);
        if(!$club_details){
            $response_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            echo json_encode($response_data);
            exit;
        } else {
            $image_owner_id = $club_details->getOwnerId();//get image owner id
        }

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
}
