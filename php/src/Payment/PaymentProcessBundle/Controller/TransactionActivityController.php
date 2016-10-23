<?php

namespace Payment\PaymentProcessBundle\Controller;

use Symfony\Component\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Payment\PaymentProcessBundle\Document\TransactionComment;
use StoreManager\PostBundle\Document\ItemRating;
use Payment\PaymentProcessBundle\Entity\PaymentProcessCredit;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;

class TransactionActivityController extends Controller {

    protected $rating_stars = array(1, 2, 3, 4, 5);
    protected $miss_param = '';

    /**
     *  function for adding the transiction comment and rating
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postAddtransactioncommentsAction(Request $request) {
        //call the service for getting the request object.
        $info = array();
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);
        $object_info = (object) $de_serialize; //convert an array into object.
 
        $required_parameter = array('user_id', 'shop_id', 'transaction_id', 'rating','invoice_id');
        $data = array();
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($data);
        }

        //check for star count
        if (!in_array($object_info->rating, $this->rating_stars)) {
            return array('code' => 80, 'message' => 'RATING_VALUE_NOT_SUPPPORTED', 'data' => $data);
        }
      
        //get parameters from the request
        $user_id = $object_info->user_id;
        $store_id = $object_info->shop_id;
        $transaction_id = $object_info->transaction_id;
        $comment = (isset($object_info->comment) ? $object_info->comment : '');
        $rating = $object_info->rating;
        $invoice_id = (string)$object_info->invoice_id;

        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        //    
        //add data related to a transaction object
        $comment_status = $this->addTransactionComment($store_id, $user_id, $transaction_id, $comment, $rating,$invoice_id);

        if (!$comment_status) {
            $res_data = array('code' => 305, 'message' => 'ERROR_IN_ADDING_TRANSACTION_COMMENT', 'data' => $data);
            $this->returnResponse($res_data);
        } else {
            $avg_rating = 0;
            $vote_count = 0;
            $shop_rating = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                    ->findBy(array('item_id' => (string) $store_id, 'item_type' => 'shop_rating'));
            //check if shop transaction is rated first time
            if (count($shop_rating) == 0) {
                $avg_rating = $rating;
                $vote_count = 1;
                $shop_rating = new ItemRating();
                $shop_rating->setItemId($store_id);
                $shop_rating->setItemType('shop_rating');
                $shop_rating->setAvgRating($rating);
                $shop_rating->setVoteCount(1);
                $shop_rating->setVoteSum($rating);
                $dm->persist($shop_rating);
                $dm->flush();
            } else {
                $shop_rating = $shop_rating[0];
                $vote_count = $shop_rating->getVoteCount();
                $avg_rating = $shop_rating->getAvgRating();
                $vote_sum = $shop_rating->getVoteSum();
                $vote_data = $this->updateAddRate($vote_count, $vote_sum, $rating);
                $avg_rating = $vote_data['avg_rate'];
                $vote_count = $vote_data['new_user_count'];
                $shop_rating->setAvgRating($vote_data['avg_rate']);
                $shop_rating->setVoteCount($vote_data['new_user_count']);
                $shop_rating->setVoteSum($vote_data['new_total_rate']);
                $dm->persist($shop_rating);
                $dm->flush();
            }
            //get store object
            $store_data = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->find((int)$store_id);
            if(count($store_data) > 0) {
                $store_data->setAvgRating($avg_rating);
                $store_data->setVoteCount($vote_count);
                $em->persist($store_data);
                $em->flush();
            }
            //updating the transaction object and set user status to Approved(payment credit process table)
//            $transaction_object->setCitizenStatus('APPROVED');
//            $em->persist($transaction_object);
//            $em->flush();
//            //get the service for transaction object..
//            $transaction_obj = $this->container->get('payment_payment_process.transaction_manager');
//            $transaction_data_response = $transaction_obj->getTransactionObject($transaction_id);
//            $data = $transaction_data_response;
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            
        //send notification type Notification_Acl = 5 to shop owner
        //get shop owner
        /** get entity manager object **/
        $em   = $this->get('doctrine')->getManager();
        $push_object_service = $this->container->get('push_notification.service');
        $shop_owners = $em
                ->getRepository('StoreManagerStoreBundle:UserToStore')
                ->getShopOwnerById((int)$store_id);
        $user_service = $this->get('user_object.service');
        $user_info = $user_service->UserObjectService($user_id);
        $username = isset($user_info['username']) ? $user_info['username'] : '';
        $shop_owners_id = $shop_owners['userId'];  
        $locale = $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        //get language constant
        $push_subject = $lang_array['TRANSACTION_CUSTOMER_FEEDBACK_PUSH_BODY'];
        $push_subject = sprintf($push_subject, $username);
        //get user devices
        $device_array = $push_object_service->getReceiverDeviceInfo($shop_owners_id);
        $from_id = $user_id;
        $to_id = array($shop_owners_id);
        $ref_type = 'TXN';
        $msg_code = 'TXN_CUST_RATING';
//        $msg = $push_subject;
//        $ref_id = $invoice_id;
//        $notification_role = 5;
//        $client_type = "SHOP";
//        $info['_id'] = $transaction_id;
//        $info['store_owner_id'] = $shop_owners_id;
//        $info['store_id'] = $store_id;
//        $info['citizen_id'] = $user_id;
//        $info['message_status'] = 'T';
//        $info['txn_id'] = $invoice_id;
        
        $post_data['_id'] = $transaction_id;
        $post_data['store_owner_id'] = $shop_owners_id;
        $post_data['store_id'] = $store_id;
        $post_data['citizen_id'] = $user_id;
        $post_data['message_status'] = 'T';
        $post_data['txn_id'] = $invoice_id;
        $post_data['from_id'] = $from_id;
        $post_data['to_id'] = $to_id;
        $post_data['ref_type'] = $ref_type;
        $post_data['message'] = 'TXN_CUST_RATING';
         //create rating on the transaction
        //update to applane
        $appalne_data = $de_serialize;
        $appalne_data['avg_rating'] = $avg_rating;
        $appalne_data['vote_count'] = $vote_count;
        //get dispatcher object
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('transaction.addrating', $event);
        
        $postService = $this->get('post_detail.service');
        
        $postService->sendPostNotificationEmail($post_data, 'TXN', true, false);
        
        $this->returnResponse($res_data);
        }
    }

    /**
     *  function for adding the transaction comment/rating and check if user have not vote for this transaction before
     * @param type $shopId
     * @param type $userId
     * @param type $transactionId
     * @param type $commet
     * @param type $rating
     * @return type
     */
    public function addTransactionComment($shopId, $userId, $transactionId, $commet, $rating,$invoice_id) {
        //get doctrine object
        $data = array();
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $transaction_comment = $dm->getRepository('PaymentPaymentProcessBundle:TransactionComment')
                ->findBy(array('user_id' => (int)$userId, 'transaction_id' => (string)$transactionId, 'shop_id' => (int)$shopId));
        if (count($transaction_comment) == 0) {
            $time = new \DateTime("now");
            $transaction_object = new TransactionComment();
            $transaction_object->setShopId($shopId);
            $transaction_object->setUserId($userId);
            $transaction_object->setTransactionId($transactionId);
            $transaction_object->setComment($commet);
            $transaction_object->setRating($rating);
            $transaction_object->setCreatedAt($time);
            $transaction_object->setInvoiceId($invoice_id);
            $dm->persist($transaction_object);
            $dm->flush();
            $transaction_comment_id = $transaction_object->getId();
            return $transaction_comment_id = ($transaction_comment_id ? true : false);
        } else {
            $res_data = array('code' => 78, 'message' => 'YOU_HAVE_ALREADY_VOTE', 'data' => $data);
            $this->returnResponse($res_data);
        }
    }

    /**
     *  function for listing the transaction for a shop
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type $transaction_comment
     * @return type
     */
    public function postListstoretransactioncommentsAction(Request $request) {
        //call the service for getting the request object.
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);
        $object_info = (object) $de_serialize; //convert an array into object.
        $data = array();
        $transaction_data = array();
        $comment_data = array();
        $required_parameter = array('shop_id');
        //get limit size
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
            $limit_size = (int) $de_serialize['limit_size'];
            if ($limit_size == "") {
                $limit_size = 20;
            }
            //get limit offset
            $limit_start = (int) $de_serialize['limit_start'];
            if ($limit_start == "") {
                $limit_start = 0;
            }
        } else {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }
        
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($data);
        }
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $store_id = $object_info->shop_id;
        $shop_rating = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                ->findBy(array('item_id' => (string) $store_id, 'item_type' => 'shop_rating'));
        if (count($shop_rating) > 0) {
            $transaction_data['avg_rating'] = $shop_rating[0]->getAvgRating();
            $transaction_data['no_of_votes'] = $shop_rating[0]->getVoteCount();

            //get transaction history for shop with limit
            $transaction_comments = $dm->getRepository('PaymentPaymentProcessBundle:TransactionComment')
                    ->findBy(array('shop_id' => (int)$store_id), array('created_at' => 'DESC'), $limit_size, $limit_start);

            //get all transaction comments
            $transaction_comments_all = $dm->getRepository('PaymentPaymentProcessBundle:TransactionComment')
                    ->findBy(array('shop_id' => (int)$store_id));
            //getting the users ids of the comments
            $users_ids = array_map(function($transaction_comment) {
                return "{$transaction_comment->getUserId()}";
            }, $transaction_comments);

            $users_array = array_unique($users_ids);
            //find user object service..
            $user_service = $this->get('user_object.service');
            //get user objects
            $users_object_array = $user_service->MultipleUserObjectService($users_array);
            //preparing the comment array
            $i = 0;
            foreach($transaction_comments as $transaction_comment) {
                $comment_data[$i]['user_id'] = $transaction_comment->getUserId();
                $comment_data[$i]['transaction_id'] = $transaction_comment->getTransactionId();
                $comment_data[$i]['comment'] = $transaction_comment->getComment();
                $comment_data[$i]['user_info'] = isset($users_object_array[$transaction_comment->getUserId()]) ? $users_object_array[$transaction_comment->getUserId()] : array();
                $comment_data[$i]['created_at'] = $transaction_comment->getCreatedAt();    
                $i++;
            }
            $transaction_data['comments'] = $comment_data;
            $transaction_data['count'] = count($transaction_comments_all);
        } else {
            $transaction_data['avg_rating'] = 0;
            $transaction_data['no_of_votes'] = 0;
            $transaction_data['comments'] = array();
            $transaction_data['count'] = 0;
        }
        
        $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $transaction_data);
        $this->returnResponse($data);
    }
    
    /**
     *  function for listing users transaction
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type $user_transaction
     * @return type
     */
     public function postListusertransactionhistoriesAction(Request $request) {
         //call the service for getting the request object.
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);
        $object_info = (object) $de_serialize; //convert an array into object.
        $data = array();
        $transaction_data = array();
        $comment_data = array();
        $required_parameter = array('user_id');
        //get limit size
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
            $limit_size = (int) $de_serialize['limit_size'];
            if ($limit_size == "") {
                $limit_size = 20;
            }
            //get limit offset
            $limit_start = (int) $de_serialize['limit_start'];
            if ($limit_start == "") {
                $limit_start = 0;
            }
        } else {
            return array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }
        
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            $data = array('code' => 300, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
            $this->returnResponse($data);
        }
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $user_id = $object_info->user_id;

            //get transaction history for shop with limit
            $user_transactions = $dm->getRepository('PaymentPaymentProcessBundle:TransactionComment')
                    ->findBy(array('user_id' => $user_id), array('created_at' => 'DESC'), $limit_size, $limit_start);
            //get all transaction comments
            $user_transactions_all = $dm->getRepository('PaymentPaymentProcessBundle:TransactionComment')
                    ->findBy(array('user_id' => $user_id));
            //getting the users ids of the comments
            $shops_ids = array_map(function($user_transaction) {
                return "{$user_transaction->getShopId()}";
            }, $user_transactions);

            
            $shops_array = array_unique($shops_ids);
            //find user object service..
            $store_service = $this->get('user_object.service');
            //get user objects
            $shop_object_array = $store_service->getMultiStoreObjectService($shops_array);
            //preparing the comment array
            $i = 0;
            foreach($user_transactions as $user_transaction) {
                $comment_data[$i]['shop_id'] = $user_transaction->getShopId();
                $comment_data[$i]['transaction_id'] = $user_transaction->getTransactionId();
                $comment_data[$i]['comment'] = $user_transaction->getComment();
                $comment_data[$i]['shop_info'] = isset($shop_object_array[$user_transaction->getShopId()]) ? $shop_object_array[$user_transaction->getShopId()] : array();
                $comment_data[$i]['created_at'] = $user_transaction->getCreatedAt();    
                $i++;
            }
            $transaction_data['comments'] = $comment_data;
            $transaction_data['count'] = count($user_transactions_all);
        
        $data = array('code' => 101, 'message' => 'SUCESS', 'data' => $transaction_data);
        $this->returnResponse($data);
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
     *  function for getting the user language country wise
     * @param array $users
     * @return array
     */
    public function getUsersByLanguage(array $users){
        $response = array();
        foreach($users as $k=>$v){
            $key = (isset($v['current_language']) and !empty($v['current_language'])) ? $v['current_language'] : 0;
            $response[$key][$k] = $v;
        }
        return $response;
    }

}
