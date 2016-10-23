<?php

namespace Utility\ApplaneIntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Payment\PaymentProcessBundle\Document\TransactionComment;
use StoreManager\PostBundle\Document\ItemRating;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Dashboard\DashboardManagerBundle\Document\DashboardPost;
use Dashboard\DashboardManagerBundle\Document\DashboardPostMedia;
use Notification\NotificationBundle\Document\UserNotifications;

class TransactionEventListner extends Event implements ApplaneConstentInterface {

    protected $em;
    protected $dm;
    protected $container;

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
        //$this->request   = $request;
    }

    /**
     *  Add transaction rating 
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onTransactionRating(Event $event) {
        $data = $event->getData();
        $data = $this->prepareApplaneDataTransactionRating($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, self::TRANSACTION_COLLECTION, $action_update);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status) ? $appalne_decode_resp->status : 'error';
    }

    /**
     *  Add transaction rating 
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onTransactionRatingSharing(Event $event) {
        $data = $event->getData();
        $data = $this->prepareApplaneDataTransactionPostSharing($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_update = self::ACTION_UPDATE;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, self::TRANSACTION_COLLECTION, $action_update);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status) ? $appalne_decode_resp->status : 'error';
    }

    /**
     * Prepare the applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataTransactionRating($data) {
        $response_data = array(
            '_id' => (string) $data['transaction_id'],
            '$set' => array(
                'txn_rating_by_customer' => $data['rating'],
                'total_votes' => $data['vote_count'],
                'average_anonymous_rating' => $data['avg_rating']
            )
        );
        return $response_data;
    }

    /**
     * Prepare the applane data for transaction post sharing
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataTransactionPostSharing($data) {
        $response_data = array(
            '_id' => (string) $data['transaction_id'],
            '$set' => array(
                'is_shared' => true
            )
        );
        return $response_data;
    }

    /**
     *  Add transaction rating 
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onTransactionSharing(Event $event) {
        $dm = $this->dm;
        $em = $this->em;
        $data = $event->getData();
        $shopId = $data['store_id'];
        $userId = $data['user_id'];
        $transactionId = $data['transaction_id'];
        $commet = '';
        $rating = $data['customer_voting'];
        $invoice_id = $data['invoice_id'];
        //add data in transaction comment table
        $comment_status = $this->addTransactionComment($shopId, $userId, $transactionId, $commet, $rating, $invoice_id);
        if (!$comment_status) {
            $res_data = array('code' => 305, 'message' => 'ERROR_IN_ADDING_TRANSACTION_COMMENT', 'data' => $data);
            $this->returnResponse($res_data);
        } else {
            $avg_rating = 0;
            $vote_count = 0;
            $shop_rating = $dm->getRepository('StoreManagerPostBundle:ItemRating')
                    ->findBy(array('item_id' => (string) $shopId, 'item_type' => 'shop_rating'));
            //check if shop transaction is rated first time
            if (count($shop_rating) == 0) {
                $avg_rating = $rating;
                $vote_count = 1;
                $shop_rating = new ItemRating();
                $shop_rating->setItemId($shopId);
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
                    ->find((int) $shopId);
            if (count($store_data) > 0) {
                $store_data->setAvgRating($avg_rating);
                $store_data->setVoteCount($vote_count);
                $em->persist($store_data);
                $em->flush();
            }
        }
        //update to applane
        $appalne_data = array();
        $appalne_data['user_id'] = $userId;
        $appalne_data['shop_id'] = $shopId;
        $appalne_data['transaction_id'] = $transactionId;
        $appalne_data['comment'] = '';
        $appalne_data['rating'] = $rating;
        $appalne_data['invoice_id'] = $invoice_id;
        $appalne_data['vote_count'] = $vote_count;
        $appalne_data['avg_rating'] = $avg_rating;
        //get dispatcher object
        $event = new FilterDataEvent($appalne_data);
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('transaction.addrating', $event);
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
    public function addTransactionComment($shopId, $userId, $transactionId, $commet, $rating, $invoice_id) {
        //get doctrine object
        $data = array();
        $dm = $this->dm; //getting doctrine mongo odm object.
        $transaction_comment = $dm->getRepository('PaymentPaymentProcessBundle:TransactionComment')
                ->findBy(array('user_id' => (int) $userId, 'transaction_id' => (string) $transactionId, 'shop_id' => (int) $shopId));
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
     * return the response.
     * @param type $data_array
     */
    private function returnResponse($data_array) {
        echo json_encode($data_array);
        exit;
    }

    /**
     *  Add transaction rating 
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onTransactionSharingCustomerWallPost(Event $event) {
        $dm = $this->dm;
        $em = $this->em;
        $data = $event->getData();
        $time = new \DateTime("now");
        $shopId = $data['store_id'];
        $avg_rating = 0;
        $vote_count = 0;
        //get store object
        $store_data = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->find((int) $shopId);
        //get store avarage voting and count
        if (count($store_data) > 0) {
            $avg_rating = $store_data->getAvgRating();
            $vote_count = $store_data->getVoteCount();
        }
        if (isset($data['tagged_friends'])) {
            if (trim($data['tagged_friends'])) {
                $data['tagged_friends'] = explode(',', $data['tagged_friends']);
            } else {
                $data['tagged_friends'] = array();
            }
        } else {
            $data['tagged_friends'] = array();
        }
        
        $info = array();
        $info['store_id'] = $shopId;
        //Code for ACL checking
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $data['user_id']));

        $dashboard_post = new DashboardPost();
        $dashboard_post->setUserId($data['user_id']);
        $dashboard_post->setToId($data['user_id']); //assign the to id(current user or friend id)
        $dashboard_post->setTitle($data['post_title']);
        $dashboard_post->setDescription($data['post_desc']);
        $dashboard_post->setLinkType($data['link_type']);
        $dashboard_post->setCreatedDate($time);
        $dashboard_post->setIsActive(1); // 0=>disabled, 1=>enabled //first time disabled..
        $dashboard_post->setTaggedFriends($data['tagged_friends']);
        $dashboard_post->setShareType('TXN');
        $dashboard_post->setCustomerVoting($data['customer_voting']);
        $dashboard_post->setStoreVotingAvg($avg_rating);
        $dashboard_post->setStoreVotingCount($vote_count);
        $dashboard_post->setTransactionId($data['transaction_id']);
        $dashboard_post->setInvoiceId($data['invoice_id']);
        $dashboard_post->setprivacySetting(3);
        $dashboard_post->setInfo($info);
        $dm->persist($dashboard_post); //storing the post data.
        $dm->flush();
        $post_id = $dashboard_post->getId(); //getting the last inserted id of posts.
        //update ACL for a user
        $this->updateAclAction($sender_user, $dashboard_post);

        
        $medias = isset($data['media_id']) ? $data['media_id'] : array();
        
        //loop for the post medias
        foreach ($medias as $media) {
            $dashboard_media_path = '';
            $post_media_res = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->find($media);
            $image_upload = $this->container->get('amazan_upload_object.service');
            $StorePostId = $post_media_res->getPostId();
            $media_name =  $post_media_res->getMediaName();
            $media_type =  $post_media_res->getMediaType();
            $image_type =  $post_media_res->getImageType();
            
            //creating the post media for the dashboard
            $dashboard_post_media = new DashboardPostMedia();
            $dashboard_post_media->setIsFeatured(1);
            $dashboard_post_media->setPostId($post_id);
            $dashboard_post_media->setMediaName($media_name);
            $dashboard_post_media->setType($media_type);
            $dashboard_post_media->setCreatedDate($time);
            $dashboard_post_media->setPath('');
            $dashboard_post_media->setImageType($image_type);
            $dashboard_post_media->setMediaStatus(1); //making it unpublish..
            $dm->persist($dashboard_post_media);
            $dm->flush();
            //update ACL for a user 
            $this->updateAclAction($sender_user, $dashboard_post_media);
            $media_original_path = __DIR__ . "/../../../../web" . $this->container->getParameter('store_post_media_path') . $StorePostId . '/'.$media_name;
            $thumb_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_post_media_path_thumb') . $StorePostId . '/'.$media_name;
            $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->container->getParameter('store_post_media_path_thumb_crop') . $StorePostId . "/".$media_name;
            $dashboard_media_path_org = $this->container->getParameter('s3_post_media_path'). $post_id;
            $dashboard_media_path_thumb = $this->container->getParameter('s3_post_media_thumb_path'). $post_id;
            $image_upload->ImageS3UploadService($dashboard_media_path_org,$media_original_path,$media_name);
            $image_upload->ImageS3UploadService($dashboard_media_path_thumb,$thumb_dir,$media_name);
        }
    }

    /**
     * creating the ACL for the entity for a user
     * @param object $sender_user
     * @param object $dashboard_post_entity
     * @return none
     */
    public function updateAclAction($sender_user, $dashboard_post_entity) {
        $aclProvider = $this->container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($dashboard_post_entity);
        $acl = $aclProvider->createAcl($objectIdentity);

        // retrieving the security identity of the currently logged-in user
        $securityIdentity = UserSecurityIdentity::fromAccount($sender_user);
        $builder = new MaskBuilder();
        $builder->add('view')
                ->add('edit')
                ->add('create')
                ->add('delete');
        $mask = $builder->get();
        // grant owner access
        $acl->insertObjectAce($securityIdentity, $mask);
        $aclProvider->updateAcl($acl);
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

}
