<?php
// Notification/NotificationBundle/Services/PostService.php
namespace Notification\NotificationBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Notification\NotificationBundle\Document\UserNotifications;
// service method  class
class PostService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
    //define the required params

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container)
    {
        $this->em        = $em;
        $this->dm        = $dm;
        $this->container = $container;
    }

    /**
     * Get post detail by id and type (store , club)
     * @param string $post_id
     * @param string $post_type
     * @return array
     */
    public function getPostDetail($post_id, $post_type)
    {
        $postDetail = array();
        if($post_type=='store'){
            $postDetail = $this->getStorePostDetail($post_id);
        }elseif($post_type=='club'){
            $postDetail = $this->getClubPostDetail($post_id);
        }elseif($post_type=='dashboard'){
            $postDetail = $this->getDashboardPostDetail($post_id);
        }

        return $postDetail;
    }

    /**
     * get store post detail by post id
     * @param string $post_id
     * @return array
     */
    protected function getStorePostDetail($post_id){
        $post = $this->dm->getRepository('StoreManagerPostBundle:StorePosts')
                ->find($post_id);

        if (!$post) {
            return array();
        }
        // get all comments of the post to retrieve author id
        $qb = $this->dm->createQueryBuilder('StoreManagerPostBundle:StoreComments');

        $postComments = $qb
                ->field('post_id')->equals($post_id)
                ->distinct('comment_author')
                ->getQuery()
                ->execute()
                ->toArray();


        $authorsCommented = array();
        if(!empty($postComments)){
            foreach($postComments as $postComment){
                $authorsCommented[] = $postComment;
            }
        }

        $postAuthor = $this->getUserData($post->getStorePostAuthor());

        $postDetail = array(
            'id' => $post->getId(),
            'store_id'=> $post->getStoreId(),
            'store_post_author'=> $post->getStorePostAuthor(),
            'store_post_created' => $post->getStorePostCreated(),
            'store_post_desc' => $post->getStorePostDesc(),
            'store_post_status' => $post->getStorePostStatus(),
            'store_post_title' => $post->getStorePostTitle(),
            'store_post_updated' => $post->getStorePostUpdated(),
            'comment_author_ids'=>$authorsCommented,
            'post_author_info'=>$postAuthor,
            'tagged_friends' => $post->getTaggedFriends()
        );

        return $postDetail;
    }

    /**
     * get club post detail by id
     * @param string $post_id
     * @return array
     */
    protected function getClubPostDetail($post_id){
        $post = $this->dm->getRepository('PostPostBundle:Post')
                ->find($post_id);

        if (!$post) {
            return array();
        }
        // get all comments of the post to retrieve author id
        $qb = $this->dm->createQueryBuilder('PostPostBundle:Comments');

        $postComments = $qb
                ->field('post_id')->equals($post_id)
                ->distinct('comment_author')
                ->getQuery()
                ->execute()
                ->toArray();

        $authorsCommented = array();
        if(!empty($postComments)){
            foreach($postComments as $postComment){
                $authorsCommented[] = $postComment;
            }
        }

        $postAuthor = $this->getUserData($post->getPostAuthor());

        $postDetail = array(
            'id' => $post->getId(),
            'link_type'=> $post->getLinkType(),
            'post_author'=> $post->getPostAuthor(),
            'post_created' => $post->getPostCreated(),
            'post_desc' => $post->getPostDesc(),
            'post_status' => $post->getPostStatus(),
            'post_title' => $post->getPostTitle(),
            'post_updated' => $post->getPostUpdated(),
            'post_gid' => $post->getPostGid(),
            'post_group_owner_id' => $post->getPostGroupOwnerId(),
            'comment_author_ids'=>$authorsCommented,
            'post_author_info'=>$postAuthor
        );

        return $postDetail;

    }

    /**
     * get dashboard post detail by id
     * @param string $post_id
     * @return array
     */
    protected function getDashboardPostDetail($post_id){
        $post = $this->dm->getRepository('DashboardManagerBundle:DashboardPost')
                ->find($post_id);

        if (!$post) {
            return array();
        }
        // get all comments of the post to retrieve author id
        $qb = $this->dm->createQueryBuilder('DashboardManagerBundle:DashboardComments');
        $postComments = $qb
                ->field('post_id')->equals($post_id)
                ->distinct('user_id')
                ->getQuery()
                ->execute()
                ->toArray();

        $authorsCommented = array();
        if(!empty($postComments)){
            foreach($postComments as $postComment){
                $authorsCommented[] = $postComment;
            }
        }

        $postAuthor = $this->getUserData($post->getUserId());

        $postDetail = array(
            'id' => $post->getId(),
            'user_id'=> $post->getUserId(),
            'to_id'=> $post->getToId(),
            'title' => $post->getTitle(),
            'description' => $post->getDescription(),
            'link_type' => $post->getLinkType(),
            'is_active' => $post->getIsActive(),
            'privacy_setting' => $post->getPrivacySetting(),
            'created_date' => $post->getCreatedDate(),
            'tagged_friends' => $post->getTaggedFriends(),
            'vote_count'=>$post->getVoteCount(),
            'vote_sum'=>$post->getVoteSum(),
            'avg_rating'=>$post->getAvgRating(),
            'comment_author_ids'=>$authorsCommented,
            'post_author_info'=>$postAuthor
        );
        return $postDetail;

    }

    /**
     * send notification email for post author and commented authors when any person comment on any post
     * this is common method to send notification mails for store/club post comments
     * @param string $post_id
     * @param int $comment_author_id
     * @param string $post_type
     * @return boolian
     */
    public function sendCommentNotificationEmail($post_id, $comment_author_id, $post_type, $comment_id=0, $webNotification=false, $tagging=array()){
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $saveNotificationForCommentId = $webNotification===true ? $comment_id : null;
        $postDetail = $this->getPostDetail($post_id,$post_type);
        if(empty($postDetail)){
            return false;
        }
        $authorsCommented = $postDetail['comment_author_ids'];
        $postAuthor = $postDetail['post_author_info'];
        // exclude postAuthor email in comments so, author can get one email each time
        $excludeCommentEmail = array($postAuthor['id'], $comment_author_id);
        $linkUrl = '';
        $extraPushParams = array('ref_id'=>$post_id);
        if($post_type=='store'){
            $lang_prefix = 'STORE_';
            $linkUrl   = $this->getStoreClubUrl(array('storeId'=>$postDetail['store_id'], 'postId'=>$post_id), 'store');
            $extraPushParams['store_id'] = $postDetail['store_id'];
        }elseif($post_type=='club'){
            $lang_prefix = 'CLUB_';
            $extraPushParams['club_id'] = $postDetail['post_gid'];
            $linkUrl   = $this->getStoreClubUrl(array('clubId'=>$postDetail['post_gid'], 'postId'=>$post_id), 'club');
        }elseif($post_type=='dashboard'){
            $lang_prefix = 'DASHBOARD_';
            $linkUrl   = $angular_app_hostname.'post/'. $post_id;
        }else{
            return false;
        }

        // get all comments of the post to retrieve author id
        $from_user_info = $this->getUserData($comment_author_id);
        $notificationTypePrefix = $lang_prefix;
        // do not send email tp post author if this comment is by self.
        if($postAuthor['id']!= $comment_author_id){
            $this->sendEmailToPostAuthors($postAuthor['id'], $from_user_info, '', $linkUrl, $saveNotificationForCommentId, $notificationTypePrefix, $extraPushParams);
        }

        if($post_type=='dashboard'){
            if(!in_array($postDetail['to_id'], array($postAuthor['id'] , $comment_author_id))){
                $this->sendEmailToWallUser($postDetail['to_id'], $from_user_info, $lang_prefix, $linkUrl, $saveNotificationForCommentId, $notificationTypePrefix, $extraPushParams);
            }

        }

        $taggedFriends = !empty($postDetail['tagged_friends']) ? $postDetail['tagged_friends'] : array();
        if(!empty($taggedFriends)){
            $this->sendEmailToTaggedFriends($taggedFriends, array('id'=>$postAuthor['id']), $from_user_info, $lang_prefix, $linkUrl, $saveNotificationForCommentId, $notificationTypePrefix, $extraPushParams);
        }
        $postIdArr = isset($postDetail['to_id']) ? array($postDetail['to_id']) : array();
        $excludeCommentEmail = array_merge($excludeCommentEmail, $taggedFriends, $postIdArr);

        $excludeCommentEmail = array_unique($excludeCommentEmail);

        if(!empty($authorsCommented)){
            $this->sendEmailToCommentAuthors(array(
                    'authors'=>$authorsCommented,
                    'post'=>array('id'=>$postAuthor['id'])
                ),
                $excludeCommentEmail,
                $from_user_info,
                $lang_prefix,
                $linkUrl,
                $saveNotificationForCommentId,
                $notificationTypePrefix,
                $extraPushParams
            );
        }

        if(!empty($tagging)){
            $this->commentTaggingNotifications($tagging, $comment_author_id, $comment_id, $linkUrl, $post_type, true, $extraPushParams);
        }
    }

    /**
     * send email to authors who has already commented on the post
     * @param array $authorsAndPost
     * @param array $excludeCommentEmail
     * @param array $sender
     * @param string $lang_prefix
     * @param string $link_url
     */
    protected function sendEmailToCommentAuthors($authorsAndPost, $excludeCommentEmail, $sender, $lang_prefix='', $link_url='', $saveNotificationForCommentId=null, $notificationTypePrefix='', $extraPushParams=array()){
        $authorsCommented = $authorsAndPost['authors'];
        $postAuthor = $authorsAndPost['post'];
        $authorsCommented = array_diff($authorsCommented, $excludeCommentEmail);
        if(empty($authorsCommented)){
            return false;
        }



        $authorsCommentedData = $this->getUserData($authorsCommented, true);
        try{
            $recieverByLanguage = $this->getUsersByLanguage($authorsCommentedData);
            $emailResponse = '';
            foreach($recieverByLanguage as $lng=>$recievers){
                $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                $lang_array = $this->container->getParameter($locale);

                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                $subject = sprintf($lang_array[$lang_prefix.'COMMENT_SUBJECT'],$sender_name);
                $mail_link = sprintf($lang_array[$lang_prefix.'COMMENT_LINK'],$sender_name);
                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
                $bodyData = empty($link_url)? $mail_link : $mail_link.'<br><br>'.sprintf($lang_array[$lang_prefix.'COMMENT_CLICK_HERE'],$href);
                $bodyTitle = sprintf($lang_array[$lang_prefix.'COMMENT_BODY'],$sender_name);
                $thumb = $sender['profile_image_thumb'];

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $this->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'COMMENT_NOTIFICATION');
            }
            if($saveNotificationForCommentId!=null){
                $this->saveUserNotification($sender['id'], $authorsCommented, strtoupper($notificationTypePrefix.'COMMENT_ON_COMMENTED'), 'comment', $saveNotificationForCommentId);
                $this->sendUserNotifications($sender['id'], $authorsCommented, strtoupper($notificationTypePrefix.'COMMENT_ON_COMMENTED'), 'comment', $saveNotificationForCommentId, false, true, ucwords($sender['first_name'].' '.$sender['last_name']), 'CITIZEN', $extraPushParams);
            }
            return $emailResponse;
        }catch(\Exception $e){

        }
    }

    /**
     * send email to author who has posted this post
     * @param int $postAuthorId
     * @param array $sender
     * @param string $lang_prefix
     * @param string $link_url
     */
    protected function sendEmailToPostAuthors($postAuthorId, $sender, $lang_prefix='', $link_url='', $saveNotificationForCommentId=null, $notificationTypePrefix='', $extraPushParams=array()){
        try{
            $authorsCommentedData = $this->getUserData($postAuthorId, true);
            $recieverByLanguage = $this->getUsersByLanguage($authorsCommentedData);
            $emailResponse = '';
            foreach($recieverByLanguage as $lng=>$recievers){
                //get locale
                $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                $lang_array = $this->container->getParameter($locale);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

                $subject = sprintf($lang_array[$lang_prefix.'POST_COMMENT_SUBJECT'],$sender_name);
                $mail_link = sprintf($lang_array[$lang_prefix.'POST_COMMENT_LINK'],$sender_name);
                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
                $bodyData = empty($link_url) ? $mail_link : $mail_link.'<br><br>'.sprintf($lang_array[$lang_prefix.'POST_COMMENT_CLICK_HERE'],$href);
                $bodyTitle = sprintf($lang_array[$lang_prefix.'POST_COMMENT_BODY'],$sender_name);
                $thumb = $sender['profile_image_thumb'];

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $this->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'COMMENT_NOTIFICATION');
            }
            if($saveNotificationForCommentId!=null){
                $this->saveUserNotification($sender['id'], $postAuthorId, strtoupper($notificationTypePrefix.'POST_COMMENT'), 'comment', $saveNotificationForCommentId);
                $this->sendUserNotifications($sender['id'], $postAuthorId, strtoupper($notificationTypePrefix.'POST_COMMENT'), 'comment', $saveNotificationForCommentId, false, true, ucwords($sender['first_name'].' '.$sender['last_name']), 'CITIZEN', $extraPushParams);
            }
            return $emailResponse;
        }catch(\Exception $e){

        }
        return false;
    }

    /**
     * send email to post wall user who has posted this post
     * @param int $postWallUserId
     * @param array $sender
     * @param string $lang_prefix
     * @param string $link_url
     */
    protected function sendEmailToWallUser($postWallUserId, $sender, $lang_prefix='', $link_url='', $saveNotificationForCommentId=null, $notificationTypePrefix='', $extraPushParams=array()){
        try{
            $authorsCommentedData = $this->getUserData($postWallUserId, true);
            $recieverByLanguage = $this->getUsersByLanguage($authorsCommentedData);
            $emailResponse = '';
            foreach($recieverByLanguage as $lng=>$recievers){
                //get locale
                $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                $lang_array = $this->container->getParameter($locale);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                // send comment notification to post user
                $subject = sprintf($lang_array[$lang_prefix.'WALL_POST_COMMENT_SUBJECT'],$sender_name);
                $mail_link = sprintf($lang_array[$lang_prefix.'WALL_POST_COMMENT_LINK'],$sender_name);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
                $bodyData = empty($link_url) ? $mail_link : $mail_link.'<br><br>'.sprintf($lang_array[$lang_prefix.'WALL_POST_COMMENT_CLICK_HERE'],$href);
                $bodyTitle = sprintf($lang_array[$lang_prefix.'WALL_POST_COMMENT_BODY'],$sender_name);
                $thumb = $sender['profile_image_thumb'];

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $this->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'COMMENT_NOTIFICATION');
            }
            if($saveNotificationForCommentId!=null){
                $this->saveUserNotification($sender['id'], $postWallUserId, strtoupper($notificationTypePrefix.'WALL_POST_COMMENT'), 'comment', $saveNotificationForCommentId);
                $this->sendUserNotifications($sender['id'], $postWallUserId, strtoupper($notificationTypePrefix.'WALL_POST_COMMENT'), 'comment', $saveNotificationForCommentId, false, true, ucwords($sender['first_name'].' '.$sender['last_name']), 'CITIZEN', $extraPushParams);
            }
            return $emailResponse;
        }catch(\Exception $e){

        }
        return false;
    }

    /**
     * send email to tagged friends in post
     * @param array $taggedFriends
     * @param array $postAuthor
     * @param array $sender
     * @param string $lang_prefix
     * @param string $link_url
     */
    protected function sendEmailToTaggedFriends($taggedFriends, $postAuthor, $sender, $lang_prefix='', $link_url='', $saveNotificationForCommentId=null, $notificationTypePrefix='', $extraPushParams=array()){
        try{
            $excludeEmail = array($sender['id'], $postAuthor['id']);
            $taggedFriends = array_diff($taggedFriends, $excludeEmail);
            if(empty($taggedFriends)){
                return false;
            }
            $taggedFriendsData = $this->getUserData($taggedFriends, true);
            $recieverByLanguage = $this->getUsersByLanguage($taggedFriendsData);
            $emailResponse = '';
            foreach($recieverByLanguage as $lng=>$recievers){
                //get locale
                $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                $lang_array = $this->container->getParameter($locale);

                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                $subject = sprintf($lang_array[$lang_prefix.'TAGGED_FRIENDS_COMMENT_SUBJECT'], $sender_name);
                $mail_link = sprintf($lang_array[$lang_prefix.'TAGGED_FRIENDS_COMMENT_LINK'],$sender_name);
                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
                $bodyData = empty($link_url) ? $mail_link : $mail_link.'<br><br>'.sprintf($lang_array[$lang_prefix.'TAGGED_FRIENDS_COMMENT_CLICK_HERE'],$href);
                $bodyTitle =   sprintf($lang_array[$lang_prefix.'TAGGED_FRIENDS_COMMENT_BODY'],$sender_name);


                $thumb = $sender['profile_image_thumb'];

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $this->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'COMMENT_NOTIFICATION');
            }
            if($saveNotificationForCommentId!=null){
                $this->saveUserNotification($sender['id'], $taggedFriends, strtoupper($notificationTypePrefix.'COMMENT_ON_TAGGED_POST'), 'comment', $saveNotificationForCommentId);
                $this->sendUserNotifications($sender['id'], $taggedFriends, strtoupper($notificationTypePrefix.'COMMENT_ON_TAGGED_POST'), 'comment', $saveNotificationForCommentId, false, true, ucwords($sender['first_name'].' '.$sender['last_name']), 'CITIZEN', $extraPushParams);
            }
            return $emailResponse;
        }catch(\Exception $e){

        }
        return false;
    }


    /**
     * get single/multiple user inforation
     * @param int/array $id user id for getting information
     * @param boolean $isMulti
     * @return array
     */
    public function getUserData($id, $isMulti = false) {
        $user_service = $this->container->get('user_object.service');
        $userData = array();
        if($isMulti){
            $userIds = is_array($id) ? $id : (array)$id;
            $userData = $user_service->MultipleUserObjectService($userIds);
        }else{
            $userId = is_array($id) ? $id[0] : $id;
            $userData = $user_service->UserObjectService($userId);
        }
        return $userData;
    }

    /**
     * Send mails using Sendgrid API
     * @param array $options
     * @param array $templateParams
     * @param string $templateId
     * @param string $mailTypempla
     * @return array
     */
    public function sendMail(array $receivers, $bodyData, $bodyTitle='', $subject='', $thumb='', $category = 'uncategorized'){
        $email_template_service = $this->container->get('email_template.service');
        return $email_template_service->sendMail($receivers, $bodyData, $bodyTitle, $subject, $thumb, $category);
    }

    /**
     * send notification mails when some one post on their wall, shop or club
     * @param array $post
     * @param string $post_type
     */
    public function sendPostNotificationEmail(array $post, $post_type, $webNotification=false, $fbshare=false) {
        if($post_type=='dashboard'){
            $this->sendDashboardPostNotification($post, $webNotification, $fbshare);
        }elseif($post_type=='club'){
            $this->sendClubPostNotification($post, $webNotification, $fbshare);
        }elseif($post_type=='store'){
            $this->sendShopPostNotification($post, $webNotification, $fbshare);
        }elseif($post_type=='TXN'){
            $this->sendTransactionNotification($post, $webNotification, $fbshare);
        }elseif($post_type=='SOCIAL_DASHBOARD'){
            $this->sendSocialDashboardPostNotification($post, $webNotification, $fbshare);
        }
    }

    /**
     * send mails to user when some one post on his/her wall
     * @param array $post
     * @return boolean/array
     */
    protected function sendDashboardPostNotification(array $post, $webNotification, $fbshare) {
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $sender = $post['user_info'];
        $recieverData = $post['reciver_user_info'];
        $linkUrl   = $angular_app_hostname.'post/'. $post['id'];
        if($fbshare){
            $this->facebookPostShare(array(
              'user_id'=>$post['user_id'],
              'link'=> $this->getPublicPostUrl(array('postId'=>$post['id']), 'user'),
              'description'=>$post['description'],
              'media_thumb_link'=> isset($post['media_info'][0]) ? $post['media_info'][0]['media_thumb_link'] : ''
            ));
        }

        if($sender['id']==$recieverData['id']){
            return false;
        }

        if($webNotification){
            $this->saveUserNotification($sender['id'], $recieverData['id'], 'POST_AT_USER_WALL', 'post', $post['id']);
            $this->sendUserNotifications($sender['id'], $recieverData['id'], 'POST_AT_USER_WALL', 'post', $post['id'], false, true, ucwords($sender['first_name'].' '.$sender['last_name']));
        }
        return $this->sendPostNotificationMail($sender, $recieverData, 'DASHBOARD_', $linkUrl);
    }

    /**
     * send mails to user when some one post on his/her club
     * @param array $post
     * @return boolean/array
     */
    protected function sendClubPostNotification(array $post, $webNotification, $fbshare){
        $post = isset($post[0]) ? $post[0] : $post;
        if(empty($post)){
            return false;
        }
        $sender = $post['user_profile'];
        $linkUrl = $this->getStoreClubUrl(array('clubId'=>$post['post_gid'], 'postId'=>$post['post_id']), 'club');
        $extraPushParams = array('club_id'=>$post['post_gid']);
        if($fbshare){
            $this->facebookPostShare(array(
              'user_id'=>$sender['id'],
              'link'=>$this->getPublicPostUrl(array('clubId'=>$post['post_gid'], 'postId'=>$post['post_id']), 'club'),
              'description'=>$post['post_description'],
              'media_thumb_link'=> isset($post['media_info'][0]) ? $post['media_info'][0]['media_thumb_path'] : ''
            ));
        }

        $postGroup = $this->dm->getRepository('UserManagerSonataUserBundle:Group')
                ->find($post['post_gid']);
        $group_members = $this->dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->findClubMembers($post['post_gid']);
        $receiver_users = array();
        foreach ($group_members as $group_member) {
            $receiver_users[] = $group_member->getUserId();
        }

        $receivers = array_unique($receiver_users);
        $receivers = array_diff($receivers, array($sender['id']));
        $recieverData = $this->getUserData($receivers, true);

        //if($sender['id']==$postGroup->getOwnerId()){
        //    return false;
       // }
        if($webNotification){
            $this->saveUserNotification($sender['id'], $receivers, 'POST_AT_CLUB_WALL', 'post', $post['post_id']);
            $this->sendUserNotifications($sender['id'], $receivers, 'POST_AT_CLUB_WALL', 'post', $post['post_id'], false, true, ucwords($sender['first_name'].' '.$sender['last_name']), 'CITIZEN', $extraPushParams);
        }
        return $this->sendPostNotificationMail($sender, $recieverData, 'CLUB_', $linkUrl);
    }

    /**
     * send mails to user when some one post on his/her wall
     * @param array $post
     * @return boolean/array
     */
    protected function sendShopPostNotification(array $post, $webNotification, $fbshare){
        $post = isset($post['data']) ? $post['data'] : $post;
        if(empty($post)){
            return false;
        }
        $sender = $post['user_profile'];
        $linkUrl = $this->getStoreClubUrl(array('storeId'=>$post['store_id'], 'postId'=>$post['post_id']), 'store');
        $extraPushParams = array('store_id'=>$post['store_id']);
        if($fbshare){
            $this->facebookPostShare(array(
              'user_id'=>$sender['id'],
              'link'=>$this->getPublicPostUrl(array('shopId'=>$post['store_id'], 'postId'=>$post['post_id']), 'shop'),
              'description'=>$post['store_post_desc'],
              'media_thumb_link'=> isset($post['media_info'][0]) ? $post['media_info'][0]['media_thumb_path'] : ''
            ));
        }
        $store = $this->em->getRepository('StoreManagerStoreBundle:UserToStore')
                        ->findOneBy(array('storeId'=>$post['store_id']));
        $recieverData = $this->getUserData($store->getUserId());

        if($sender['id']==$recieverData['id']){
            return false;
        }
        if($webNotification){
            $this->saveUserNotification($sender['id'], $recieverData['id'], 'POST_AT_SHOP_WALL', 'post', $post['post_id']);
            $this->sendUserNotifications($sender['id'], $recieverData['id'], 'POST_AT_SHOP_WALL', 'post', $post['post_id'], false, true, ucwords($sender['first_name'].' '.$sender['last_name']), 'CITIZEN', $extraPushParams);
        }
            return $this->sendPostNotificationMail($sender, $recieverData, 'STORE_', $linkUrl);
            }

    /**
     * common method to prepare params to send post notification emails for wall,shop or club
     * @param array $sender
     * @param array $reciever
     * @param string $lang_prefix
     * @return array
     */
    protected function sendPostNotificationMail(array $sender, array $reciever, $lang_prefix, $link_url='',$extra_params=array()){
        try{
            $reciever = isset($reciever['id']) ? array($reciever) : $reciever;
            $recieverByLanguage = $this->getUsersByLanguage($reciever);
            $emailResponse = '';
            foreach($recieverByLanguage as $lng=>$recievers){
                $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                $lang_array = $this->container->getParameter($locale);
                $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
                // send comment notification to post user
                if($lang_prefix == 'TXN_CUST_SHARE') {
                 $shop_name = $reciever['name'];
                $subject = $lang_array['TXN_SHARE_MAIL_SUB'];
                $mail_link = $lang_array['TXN_SHARE_LINK_TEXT'];
                $mail_body = sprintf($lang_array['TXN_SHARE_MAIL_BODY'],$shop_name,$sender_name);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']} </a>".$mail_link;
                $bodyData = $mail_body.'<br><br>'.$href;
                $bodyTitle = $lang_array['TXN_SHARE_MAIL_TEXT'];
                $thumb = $sender['profile_image_thumb'];

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $this->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'TRANSACTION_SHARE');

                } elseif($lang_prefix == 'TXN_CUST_RATING') {

                $subject = $lang_array['TXN_RATING_MAIL_SUB'];
                $mail_link = $lang_array['TXN_RATING_LINK_TEXT'];
                $mail_body = sprintf($lang_array['TXN_RATING_MAIL_BODY'],$sender_name);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']} </a>".$mail_link;

                $bodyData = $mail_body.'<br><br>'.$href;

                $bodyTitle = sprintf($lang_array['TXN_RATING_MAIL_TEXT'], ucwords($sender_name));
                $thumb = $sender['profile_image_thumb'];

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $this->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'TRANSACTION_RATING');

                } elseif($lang_prefix == 'TXN_CUST_CI_GAIN') {

                $subject = $lang_array['TXN_CI_GAIN_MAIL_SUB'];
                $mail_link = $lang_array['TXN_CI_GAIN_LINK_TEXT'];
                $mail_body = sprintf($lang_array['TXN_CI_GAIN_MAIL_BODY'],$extra_params[0]);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']} </a>".$mail_link;

                $bodyData = $mail_body.'<br><br>'.$href;

                $bodyTitle = sprintf($lang_array['TXN_CI_GAIN_MAIL_TEXT']);
                $thumb = $this->container->getParameter('sixthcontinent_logo_path');
                $emailResponse = $this->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'CITIZEN_INCOME_GAIN');
                } else {
                $subject = sprintf($lang_array[$lang_prefix.'POST_SUBJECT'],$sender_name);
                $mail_link = sprintf($lang_array[$lang_prefix.'POST_LINK'],$sender_name);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']}</a>";
                $bodyData = empty($link_url) ? $mail_link : $mail_link.'<br><br>'.sprintf($lang_array[$lang_prefix.'POST_CLICK_HERE'],$href);
                $bodyTitle = sprintf($lang_array[$lang_prefix.'POST_BODY'],$sender_name);
                $thumb = $sender['profile_image_thumb'];

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $this->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'COMMENT_NOTIFICATION');
                }
            }
            return $emailResponse;
        }catch(\Exception $e){

        }
        return false;
    }

    public function getStoreClubUrl(array $options, $urlType){
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $url = '';
        try{
            switch($urlType){
                case 'store':
                    $url = $this->container->getParameter('store_post_url');
                    break;
                case 'club':
                    $url = $this->container->getParameter('club_post_url');
                    $club = $this->dm->getRepository('UserManagerSonataUserBundle:Group')
                                ->find($options['clubId']);
                    $url .= '/'. $club->getGroupStatus();
                    break;
            }

            foreach($options as $k=>$v){
                $url = str_replace(':'.$k, $v, $url);
            }
        }  catch (\Exception $e){

        }

        return !empty($url) ? $angular_app_hostname.$url : $url;
    }

    /**
     * Save user notification
     * @param int $from_id
     * @param int/array $to_id
     * @param string $msgtype
     * @param string $msg
     * @param string $itemId
     * @return boolean
     */
    public function saveUserNotification($from_id, $to_id, $msgtype, $msg, $itemId , $message_status = 'U',$info = array(), $role=5, $extraInfo=array()) {
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $to_ids = is_array($to_id) ? $to_id : (array)$to_id;
        foreach($to_ids as $_to_id){
            if(!empty($extraInfo)){
                if(isset($extraInfo[$_to_id]['club_info'])){
                    $info['club'] = $extraInfo[$_to_id]['club_info'];
                }
                if(isset($extraInfo[$_to_id]['shop_info'])){
                    $info['shop'] = $extraInfo[$_to_id]['shop_info'];
                }
            }
            $notification = new UserNotifications();
            $notification->setFrom($from_id);
            $notification->setTo($_to_id);
            $notification->setMessageType($msgtype);
            $notification->setMessage($msg);
            $notification->setMessageStatus($message_status);
            $notification->setItemId($itemId);
            $time = new \DateTime("now");
            $notification->setDate($time);
            $notification->setIsRead('0');
            $notification->setInfo($info);
            $notification->setNotificationRole($role);
            $dm->persist($notification);
            $dm->flush();
        }
        return true;
    }

    /**
     * Comment tagging email and web notifications for
     * dashboard, club and shop post
     *
     * @param array $tagging
     * @param string $commentAuthor
     * @param string $commentId
     * @param string $postLink
     * @param bool $webNotification
     */
    public function commentTaggingNotifications($tagging, $commentAuthorId, $commentId, $postLink, $postType, $webNotification=false, $extraPushParams=array(), $isPushDisable=false, $info=array(), $replaceText=array()){
        $response = false;
        $userService = $this->container->get('user_object.service');
        $postType = strtoupper($postType);
        $sender = $this->getUserData($commentAuthorId);
        $_replaceText = array(ucwords($sender['first_name'].' '.$sender['last_name']));
        $replaceText = is_array($replaceText) ? $replaceText : (array)$replaceText;
        $replaceText = array_merge($_replaceText, $replaceText);
        if(!empty($tagging['user'])){
            $taggedUsers = array_diff(array_unique($tagging['user']), array($commentAuthorId));
            if(!empty($taggedUsers)){
                $receivers = $this->getUserData($taggedUsers, true);
                //$this->sendTaggedInMailNotifications($receivers, $sender, $postLink, 'user');
                $this->sendTaggedInMailNotifications($receivers, $sender, $postLink, 'user', $postType, $replaceText);
                if($webNotification){
                    $this->saveUserNotification($commentAuthorId, $taggedUsers, 'USER_TAGGED_IN_'.$postType.'_COMMENT', 'ctagging', $commentId, 'U', $info);
                    $isPushDisable==false ? $this->sendUserNotifications($commentAuthorId, $taggedUsers, 'USER_TAGGED_IN_'.$postType.'_COMMENT', 'ctagging', $commentId, false, true, $replaceText, 'CITIZEN', $extraPushParams) : null;
                }
            }
            $response = true;
        }
        if(!empty($tagging['shop'])){
            $shops = array_unique($tagging['shop']);
            $shopOwners = $userService->getShopsWithOwner($shops, array($commentAuthorId));
            if(!empty($shopOwners)){
                $this->sendTaggedInMailNotifications($shopOwners, $sender, $postLink, 'shop', $postType, $replaceText);
                if($webNotification){
                    $shopOwnerIds = array_keys($shopOwners);
                    $this->saveUserNotification($commentAuthorId, $shopOwnerIds, 'SHOP_TAGGED_IN_'.$postType.'_COMMENT', 'ctagging', $commentId, 'U', $info, 5, $shopOwners);
                    $isPushDisable==false ? $this->sendUserNotifications($commentAuthorId, $shopOwnerIds, 'SHOP_TAGGED_IN_'.$postType.'_COMMENT', 'ctagging', $commentId, false, true, $replaceText, 'CITIZEN', $extraPushParams) : null;
                }
            }
            $response = true;
        }
        if(!empty($tagging['club'])){
            $clubMembers = array();
            $clubs = array_unique($tagging['club']);
            $excludeMembers = array($commentAuthorId);
            foreach ($clubs as $_club){
                // get all club admins
                $excludeMembers = array_merge($excludeMembers, array_keys($clubMembers));
                $_clubMembers = $userService->groupMembersByGroupRole($_club, 2, $excludeMembers);
                $clubMembers += $_clubMembers;
            }
            if(!empty($clubMembers)){
                $clubMemberIds = array_keys($clubMembers);
                $this->sendTaggedInMailNotifications($clubMembers, $sender, $postLink, 'club', $postType, $replaceText);
                if($webNotification){
                    $this->saveUserNotification($commentAuthorId, $clubMemberIds, 'CLUB_TAGGED_IN_'.$postType.'_COMMENT', 'ctagging', $commentId, 'U', $info, 5, $clubMembers);
                    $isPushDisable==false ? $this->sendUserNotifications($commentAuthorId, $clubMemberIds, 'CLUB_TAGGED_IN_'.$postType.'_COMMENT', 'ctagging', $commentId, false, true, $replaceText, 'CITIZEN', $extraPushParams) : null;
                }
            }
            $response = true;
        }
        return $response;
    }

    /**
     *
     * @param array $receiverIds
     * @param int $senderId
     * @param string $postLink
     * @param string $mailType
     * @return array
     */
    protected function sendTaggedInMailNotifications(array $receivers, $sender, $postLink, $mailType, $type, $replaceText){

        $email_template_service = $this->container->get('email_template.service');
        $emailResponse = array();

        $sender_name = ucwords(trim($sender['first_name'].' '.$sender['last_name']));
        $replaceTxt = !empty($replaceText) ? (is_array($replaceText) ? $replaceText : (array)$replaceText) : (array)$sender_name;
        $lang_prefix='';
        switch($mailType){
            case 'user':
                $lang_prefix = 'USER_';
                break;
            case 'shop':
                $lang_prefix = 'SHOP_';
                break;
            case 'club':
                $lang_prefix = 'CLUB_';
                break;
        }

        $textType = '';
        switch(strtoupper($type)){
            case 'USER_ALBUM':
                $textType = 'USER_ALBUM_';
                break;
            case 'CLUB_ALBUM':
                $textType = 'CLUB_ALBUM_';
                break;
            case 'STORE_ALBUM':
                $textType = 'STORE_ALBUM_';
                break;
            case 'SP_MEDIA':
                $textType = 'SP_MEDIA_';
                break;
            case 'SP_POST':
                $textType = 'SP_POST_';
                break;
            case 'SP_POST_MEDIA':
                $textType = 'SP_POST_MEDIA_';
                break;
        }

        if(!empty($lang_prefix)){
            try{
                $recieverByLanguage = $this->getUsersByLanguage($receivers);
                $emailResponse = '';
                foreach($recieverByLanguage as $lng=>$recievers){
                    $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                    $lang_array = $this->container->getParameter($locale);
                    $subject = $this->_updateByGivenText($lang_array[$lang_prefix.'TAGGED_IN_'.$textType.'COMMENT_SUBJECT'], $replaceText);
                    $subject = vsprintf($subject,$replaceTxt);

                    if(empty($textType)){
                        $mail_text = $this->_updateByGivenText($lang_array[$lang_prefix.'TAGGED_IN_'.$textType.'COMMENT_TEXT'], $replaceText);
                        $mail_text = vsprintf($mail_text,$replaceTxt);
                    }else{
                        $mail_text = $this->_updateByGivenText($lang_array[$lang_prefix.'TAGGED_IN_'.$textType.'COMMENT_TEXT'], $replaceText);
                        $mail_text = vsprintf($mail_text,array($sender_name, '[groupName]'));
                    }

                    $link = $email_template_service->getLinkForMail($postLink,$locale);
                    $bodyData = $mail_text.'<br><br>'.$link;
                    $bodyTitle = $this->_updateByGivenText($lang_array[$lang_prefix.'TAGGED_IN_'.$textType.'COMMENT_BODY'],$replaceTxt);
                    $bodyTitle = vsprintf($bodyTitle,$replaceTxt);
                    $thumb = $sender['profile_image_thumb'];

                    // HOTFIX NO NOTIFY MAIL
                    //$emailResponse = $this->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'COMMENT_NOTIFICATION');
                }
            }catch(\Exception $e){

            }
        }
        return $emailResponse;
    }

    public function facebookPostShare($post){
        $fbPostAllow = $this->container->getParameter('facebook_post_allow');
        if($fbPostAllow){
            try{
                $this->_log('Initilize facebook post for user: '.$post['user_id'], 'facebook_log');
                $postOwnerFacebook = $this->getFacebookInfo($post['user_id']);
                if($postOwnerFacebook and $postOwnerFacebook->getPublishActions()){
                    $fbService = $this->container->get('facebook_auto_post.service');
                    $fbPostParam = $this->container->getParameter('facebook_post');
                    $fbResponse = $fbService->setAccessToken($postOwnerFacebook->getFacebookAccessToken())
                             ->setTitle(isset($fbPostParam['default_title']) ? $fbPostParam['default_title'] : '')
                             ->setMessage(isset($fbPostParam['default_message']) ? $fbPostParam['default_message'] : '')
                             ->setDescription($post['description'])
                             ->setImageUrl(isset($post['media_thumb_link']) ? $post['media_thumb_link'] : '')
                             ->setTargetLink($post['link'])
                             ->send();
                    if($fbResponse['code']==200){
                        $this->updateFacebookPublishAction($postOwnerFacebook);
                    }
                }else{
                    $this->_log('User: '.$post['user_id'].' either has not connected with facebook account or has not given publish_actions permission.', 'facebook_log');
                }

            }catch(\Exception $e){
            }
        }
    }

    public function getFacebookInfo($user_id){
        $response = $this->em
                ->getRepository('UserManagerSonataUserBundle:FacebookUser')
                ->getUserDetail($user_id);
        return $response;
    }

    public function getPublicPostUrl(array $options, $urlType){
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $url = '';
        try{
            switch($urlType){
                case 'shop':
                    $url = $this->container->getParameter('public_shop_post_url');
                    break;
                case 'club':
                    $url = $this->container->getParameter('public_club_post_url');
                    $club = $this->dm->getRepository('UserManagerSonataUserBundle:Group')
                                ->find($options['clubId']);
                    $options['clubStatus'] = $club->getGroupStatus();
                    break;
                case 'user':
                    $url = $this->container->getParameter('public_user_post_url');
                    break;
            }

            foreach($options as $k=>$v){
                $url = str_replace(':'.$k, $v, $url);
            }
        }  catch (\Exception $e){

        }

        return !empty($url) ? $angular_app_hostname.$url : $url;
    }

    /**
     * Send web, push or both (web and push) notifications for users
     * @param int $from_id  Sender id
     * @param array $to_id  Receiver id
     * @param string $msgtype   Notification Message Type
     * @param string $msg   Notification message
     * @param string $itemId    Item id
     * @param bool $isWeb   True if send web notification
     * @param bool $isPush  True if send Push notification
     * @param string $pushText  Push notification text
     * @param string $clientType    Client type
     */
    public function sendUserNotifications($from_id, $to_id, $msgtype, $msg, $itemId, $isWeb=true, $isPush=false, $replaceText=null, $clientType='CITIZEN', $extraParams=array(), $msgStatus='U', $info=array()){
        $to_id = is_array($to_id) ? $to_id : (array)$to_id;
        $receivers = $this->getUserData($to_id, true);
        $defaultLang = $this->container->getParameter('locale');
        $push_object_service = $this->container->get('push_notification.service');
        $_receiversIds = array_keys($receivers);
        $pushInfo = array(
                  'from_id'=>  $from_id, 'to_id' => $_receiversIds, 'msg_code'=>$msg, 'ref_type'=>$msgtype,
                    'ref_id'=> $itemId, 'role'=>1, 'client_type'=> $clientType,
                    'msg'=> ''
                );
        // save
        if($isWeb){
            $pushInfo['role']= ($isWeb and $isPush) ? 5 : 1;
            $this->saveUserNotification($pushInfo['from_id'], $pushInfo['to_id'], $pushInfo['ref_type'], $pushInfo['msg_code'], $pushInfo['ref_id'],$msgStatus, $info, $pushInfo['role']);
        }

        if($isPush){
            $deviceByLangs = array();
            $usersDevices = $push_object_service->getReceiverDeviceInfo($to_id, strtolower($clientType));
            foreach($usersDevices as $dType=>$dInfos){
                foreach ($dInfos as $dInfo){
                    $_lng = !empty($dInfo['lang']) ? $dInfo['lang'] : (!empty($receivers[$dInfo['user_id']]['current_language']) ? $receivers[$dInfo['user_id']]['current_language'] : $defaultLang);
                    $deviceByLangs[$_lng][$dType][] = $dInfo;
                }
            }

            try{
                foreach($deviceByLangs as $lang=>$_devices){
                    $pushInfo['msg'] = $this->getPushNotificationText($msgtype, $replaceText, $lang,$msg);
                    $push_object_service->sendPush($_devices, $pushInfo['from_id'], $pushInfo['msg_code'], $pushInfo['msg'], $pushInfo['ref_type'] , $pushInfo['ref_id'], $pushInfo['client_type'], $extraParams);
                }
            }catch(\Exception $e){
                //print_r($e);
            }
        }

        return true;
    }

    public function getPushNotificationText($notificationType, $replaceStr='', $lang=null,$msg=''){
        $locale = is_null($lang) ? $this->container->getParameter('locale') : $lang;
        $language_const_array = $this->container->getParameter($locale);
        $text = '';
        $notificationType = in_array($notificationType, array('TXN', 'SUBSCRIPTION', 'RECURRING_NOTIFICATION', 'SUBSCRIPTION_NOTIFICATION_PUSH')) ? $notificationType.$msg : $notificationType;
        switch(trim(strtoupper($notificationType))){
            case 'POST_AT_USER_WALL':
                $text = $language_const_array['PUSH_DASHBOARD_POST'];
                break;
            case 'DASHBOARD_POST_COMMENT':
                $text = $language_const_array['PUSH_DASHBOARD_POST_COMMENT'];
                break;
            case 'CLUB_POST_COMMENT':
                $text = $language_const_array['PUSH_CLUB_POST_COMMENT'];
                break;
            case 'STORE_POST_COMMENT':
                $text = $language_const_array['PUSH_STORE_POST_COMMENT'];
                break;
            case 'DASHBOARD_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_DASHBOARD_COMMENT_ON_COMMENTED'];
                break;
            case 'CLUB_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_CLUB_COMMENT_ON_COMMENTED'];
                break;
            case 'STORE_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_STORE_COMMENT_ON_COMMENTED'];
                break;
            case 'DASHBOARD_WALL_POST_COMMENT':
                $text = $language_const_array['PUSH_COMMENT_DASHBOARD_WALL'];
                break;
            case 'DASHBOARD_COMMENT_ON_TAGGED_POST':
                $text = $language_const_array['PUSH_DASHBOARD_COMMENT_ON_TAGGED_POST'];
                break;
            case 'STORE_COMMENT_ON_TAGGED_POST':
                $text = $language_const_array['PUSH_STORE_COMMENT_ON_TAGGED_POST'];
                break;
            case 'USER_TAGGED_IN_DASHBOARD_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_DASHBOARD_COMMENT'];
                break;
            case 'USER_TAGGED_IN_CLUB_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_CLUB_COMMENT'];
                break;
            case 'USER_TAGGED_IN_STORE_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_SHOP_COMMENT'];
                break;
            case 'USER_TAGGED_IN_STORE_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_STORE_ALBUM_MEDIA_COMMENT'];
                break;
            case 'USER_TAGGED_IN_USER_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_USER_ALBUM_MEDIA_COMMENT'];
                break;
            case 'USER_TAGGED_IN_CLUB_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_CLUB_ALBUM_MEDIA_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_DASHBOARD_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_DASHBOARD_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_CLUB_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_CLUB_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_STORE_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_SHOP_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_STORE_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_STORE_ALBUM_MEDIA_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_CLUB_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_CLUB_ALBUM_MEDIA_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_USER_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_USER_ALBUM_MEDIA_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_DASHBOARD_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_DASHBOARD_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_CLUB_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_CLUB_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_STORE_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_SHOP_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_STORE_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_STORE_ALBUM_MEDIA_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_USER_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_USER_ALBUM_MEDIA_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_CLUB_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_CLUB_ALBUM_MEDIA_COMMENT'];
                break;
            case 'POST_AT_CLUB_WALL':
                $text = $language_const_array['PUSH_CLUB_POST'];
                break;
            case 'POST_AT_SHOP_WALL':
                $text = $language_const_array['PUSH_STORE_POST'];
                break;
            case 'TAGGED_IN_POST':
                $text = $language_const_array['PUSH_TAGGED_IN_DASHBOARD_POST'];
                break;
            case 'DASHBOARD_POST_RATE':
                $text = $language_const_array['PUSH_DASHBOARD_POST_RATE'];
                break;
            case 'DASHBOARD_COMMENT_RATE':
                $text = $language_const_array['PUSH_DASHBOARD_COMMENT_RATE'];
                break;
            case 'USER_ALBUM_RATE':
                $text = $language_const_array['PUSH_USER_ALBUM_RATE'];
                break;
            case 'USER_PHOTO_RATE':
                $text = $language_const_array['PUSH_USER_PHOTO_RATE'];
                break;
            case 'STORE_POST_RATE':
                $text = $language_const_array['PUSH_STORE_POST_RATE'];
                break;
            case 'STORE_POST_COMMENT_RATE':
                $text = $language_const_array['PUSH_STORE_POST_COMMENT_RATE'];
                break;
            case 'STORE_ALBUM_RATE':
                $text = $language_const_array['PUSH_STORE_ALBUM_RATE'];
                break;
            case 'STORE_MEDIA_RATE':
                $text = $language_const_array['PUSH_STORE_MEDIA_RATE'];
                break;
            case 'CLUB_RATE':
                $text = $language_const_array['PUSH_CLUB_RATE'];
                break;
            case 'CLUB_POST_RATE':
                $text = $language_const_array['PUSH_CLUB_POST_RATE'];
                break;
            case 'CLUB_POST_COMMENT_RATE':
                $text = $language_const_array['PUSH_CLUB_POST_COMMENT_RATE'];
                break;
            case 'CLUB_ALBUM_RATE':
                $text = $language_const_array['PUSH_CLUB_ALBUM_RATE'];
                break;
            case 'CLUB_ALBUM_PHOTO_RATE':
                $text = $language_const_array['PUSH_CLUB_ALBUM_PHOTO_RATE'];
                break;
            case 'TAGGED_IN_PHOTO':
                $text = $language_const_array['PUSH_TAGGED_IN_PHOTO'];
                break;
            case 'FRIEND':
                $text = $language_const_array['PUSH_ACCEPTED_FRIEND_REQUEST'];
                break;
            case 'TXNTXN_CUST_SHARE':
                $text = $language_const_array['PUSH_TXN_CUST_SHARE-TXN'];
                break;
            case 'TXNTXN_CUST_RATING':
                $text = $language_const_array['TRANSACTION_CUSTOMER_FEEDBACK_PUSH_BODY'];
                break;
            case 'TXNTXN_CUST_CI_GAIN':
                $text = $language_const_array['TXN_CUST_CI_GAIN_PUSH_BODY'];
                break;
            case 'SHOP_RESPONSE':
                $text = '%s';
                break;
            case 'FRIEND_REQUEST':
                $text = $language_const_array['PUSH_FRIEND_REQUEST'];
                break;
            case 'SUBSCRIPTION39EURO_SHOPPING_CARD':
                $text = $language_const_array['PUSH_SUBSCRIPTION_39EURO_SHOPPING_CARD'];
                break;
            case 'BUYS_SHOPPING_CARD':
                $text = $language_const_array['PUSH_BUYS_SHOPPING_CARD'];
                break;
            case 'SELLS_SHOPPING_CARD':
                $text = $language_const_array['PUSH_SELLS_SHOPPING_CARD'];
                break;
            case 'SHOP_AFFILIATION':
                $text = $language_const_array['PUSH_REFERRAL_AMOUNT_SHOP_AFFILIATION'];
                break;
            case 'RECURRING_NOTIFICATIONREG_FEE_NOT_PAID':
                $text = $language_const_array['PUSH_RECURRING_NOTIFICATION_PUSHREG_FEE_NOT_PAID'];
                break;
            case 'RECURRING_NOTIFICATIONSHOP_BLOCKED':
                $text = $language_const_array['PUSH_RECURRING_NOTIFICATION_PUSHSHOP_BLOCKED'];
                break;
            case 'USER_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_USER_ALBUM_MEDIA_COMMENT'];
                break;
            case 'USER_ALBUM_MEDIA_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_USER_ALBUM_MEDIA_COMMENT_ON_COMMENTED'];
                break;
            case 'CLUB_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_CLUB_ALBUM_MEDIA_COMMENT'];
                break;
            case 'CLUB_ALBUM_MEDIA_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_CLUB_ALBUM_MEDIA_COMMENT_ON_COMMENTED'];
                break;
            case 'RECURRING_NOTIFICATIONPENDING_FEE_NOT_PAID':
                $text = $language_const_array['PUSH_RECURRING_NOTIFICATION_PUSHPENDING_FEE_NOT_PAID'];
                break;
            case 'STORE_ALBUM_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_STORE_ALBUM_MEDIA_COMMENT'];
                break;
            case 'STORE_ALBUM_MEDIA_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_STORE_ALBUM_MEDIA_COMMENT_ON_COMMENTED'];
                break;
            case 'STORE_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_STORE_ALBUM_COMMENT'];
                break;
            case 'STORE_ALBUM_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_STORE_ALBUM_COMMENT_ON_COMMENTED'];
                break;
            case 'CLUB_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_CLUB_ALBUM_COMMENT'];
                break;
            case 'CLUB_ALBUM_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_CLUB_ALBUM_COMMENT_ON_COMMENTED'];
                break;
            case 'USER_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_USER_ALBUM_COMMENT'];
                break;
            case 'USER_ALBUM_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_USER_ALBUM_COMMENT_ON_COMMENTED'];
                break;
            case 'STORE_MEDIA_COMMENT_RATE':
                $text = $language_const_array['PUSH_STORE_ALBUM_MEDIA_COMMENT_RATE'];
            case 'SUBSCRIPTION_NOTIFICATION_PUSHSUBSCRIPTION_FEE_NOT_PAID':
                $text = $language_const_array['SUBSCRIPTION_NOTIFICATION_PUSHSUBSCRIPTION_FEE_NOT_PAID'];
                break;
            case 'SUBSCRIPTION_NOTIFICATION_PUSHSHOP_SUBSCRIPTION_BLOCKED':
                $text = $language_const_array['SUBSCRIPTION_NOTIFICATION_PUSHSHOP_SUBSCRIPTION_BLOCKED'];
                break;
            case 'SOCIAL_PROJECT':
                $text = $language_const_array['PUSH_SOCIAL_PROJECT_VOTE'];
                break;
            case 'USER_TAGGED_IN_SP_MEDIA_COMMENT':
                $text = $this->_updateByGivenText($language_const_array['PUSH_USER_TAGGED_IN_SP_MEDIA_COMMENT'], $replaceStr);
                $replaceStr = array();
                break;
            case 'CLUB_TAGGED_IN_SP_MEDIA_COMMENT':
                $text = $this->_updateByGivenText($language_const_array['PUSH_CLUB_TAGGED_IN_SP_MEDIA_COMMENT'], $replaceStr);
                $replaceStr = array();
                break;
            case 'SHOP_TAGGED_IN_SP_MEDIA_COMMENT':
                $text = $this->_updateByGivenText($language_const_array['PUSH_SHOP_TAGGED_IN_SP_MEDIA_COMMENT'], $replaceStr);
                $replaceStr = array();
            case 'USER_TAGGED_IN_CLUB_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_CLUB_ALBUM_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_CLUB_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_CLUB_ALBUM_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_CLUB_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_CLUB_ALBUM_COMMENT'];
                break;
            case 'USER_TAGGED_IN_USER_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_USER_ALBUM_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_USER_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_USER_ALBUM_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_USER_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_USER_ALBUM_COMMENT'];
                break;
            case 'USER_TAGGED_IN_STORE_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_STORE_ALBUM_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_STORE_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_STORE_ALBUM_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_STORE_ALBUM_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_STORE_ALBUM_COMMENT'];
                break;
            case 'STORE_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_STORE_POST_MEDIA_COMMENT'];
                break;
            case 'STORE_POST_MEDIA_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_STORE_POST_MEDIA_COMMENT_ON_COMMENTED'];
                break;
            case 'USER_TAGGED_IN_STORE_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_STORE_POST_MEDIA_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_STORE_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_STORE_POST_MEDIA_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_STORE_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_STORE_POST_MEDIA_COMMENT'];
                break;
            case 'STORE_POST_MEDIA_COMMENT_RATE':
                $text = $language_const_array['PUSH_STORE_POST_MEDIA_COMMENT_RATE'];
                break;
            case 'DASHBOARD_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_DASHBOARD_POST_MEDIA_COMMENT'];
                break;
            case 'DASHBOARD_POST_MEDIA_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_DASHBOARD_POST_MEDIA_COMMENT_ON_COMMENTED'];
                break;
            case 'USER_TAGGED_IN_DASHBOARD_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_DASHBOARD_POST_MEDIA_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_DASHBOARD_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_DASHBOARD_POST_MEDIA_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_DASHBOARD_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_DASHBOARD_POST_MEDIA_COMMENT'];
                break;
            case 'DASHBOARD_POST_MEDIA_COMMENT_RATE':
                $text = $language_const_array['PUSH_DASHBOARD_POST_MEDIA_COMMENT_RATE'];
                break;
            case 'STORE_POST_MEDIA_RATE':
                $text = $language_const_array['PUSH_STORE_POST_MEDIA_RATE'];
                break;
            case 'CLUB_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_STORE_POST_MEDIA_COMMENT'];
                break;
            case 'CLUB_POST_MEDIA_COMMENT_ON_COMMENTED':
                $text = $language_const_array['PUSH_STORE_POST_MEDIA_COMMENT_ON_COMMENTED'];
                break;
            case 'USER_TAGGED_IN_CLUB_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_CLUB_POST_MEDIA_COMMENT'];
                break;
            case 'CLUB_TAGGED_IN_CLUB_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_CLUB_POST_MEDIA_COMMENT'];
                break;
            case 'SHOP_TAGGED_IN_CLUB_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_CLUB_POST_MEDIA_COMMENT'];
                break;
            case 'CLUB_POST_MEDIA_COMMENT_RATE':
                $text = $language_const_array['PUSH_CLUB_POST_MEDIA_COMMENT_RATE'];
                break;
            case 'USER_ALBUM_COMMENT_RATE':
                $text = $language_const_array['PUSH_USER_ALBUM_COMMENT_RATE'];
                break;
            case 'TAGGED_IN_CLUB_PHOTO':
                $text = $language_const_array['PUSH_TAGGED_IN_CLUB_PHOTO'];
            case 'TAGGED_IN_CLUB_POST':
                $text = $language_const_array['PUSH_TAGGED_IN_CLUB_POST'];
                break;
            case 'TAGGED_IN_STORE_PHOTO':
                $text = $language_const_array['PUSH_TAGGED_IN_STORE_PHOTO'];
                break;
            case 'CLUB_ALBUM_COMMENT_RATE':
                $text = $language_const_array['PUSH_CLUB_ALBUM_COMMENT_RATE'];
                break;
            case 'STORE_ALBUM_COMMENT_RATE':
                $text = $language_const_array['PUSH_STORE_ALBUM_COMMENT_RATE'];
                break;
            case 'USER_ALBUM_MEDIA_COMMENT_RATE':
                $text = $language_const_array['PUSH_USER_ALBUM_MEDIA_COMMENT_RATE'];
                break;
            case 'CLUB_ALBUM_MEDIA_COMMENT_RATE':
                $text = $language_const_array['PUSH_CLUB_ALBUM_MEDIA_COMMENT_RATE'];
                break;
            case 'USER_TAGGED_IN_SP_POST_COMMENT':
                $text = $this->_updateByGivenText($language_const_array['PUSH_USER_TAGGED_IN_SP_POST_COMMENT'], $replaceStr);
                break;
            case 'CLUB_TAGGED_IN_SP_POST_COMMENT':
                $text = $this->_updateByGivenText($language_const_array['PUSH_CLUB_TAGGED_IN_SP_POST_COMMENT'], $replaceStr);
                break;
            case 'SHOP_TAGGED_IN_SP_POST_COMMENT':
                $text = $this->_updateByGivenText($language_const_array['PUSH_SHOP_TAGGED_IN_SP_POST_COMMENT'], $replaceStr);
                break;
            case 'TXNTXN_CUST_CI_REDISTRIBUTION':
                $text = $language_const_array['TXN_CUST_CI_REDISTRIBUTION_PUSH_BODY'];
                break;
            case 'USER_TAGGED_IN_SP_POST_MEDIA_COMMENT':
                $text = $this->_updateByGivenText($language_const_array['PUSH_USER_TAGGED_IN_SP_POST_MEDIA_COMMENT'], $replaceStr);
                break;
            case 'CLUB_TAGGED_IN_SP_POST_MEDIA_COMMENT':
                $text = $this->_updateByGivenText($language_const_array['PUSH_CLUB_TAGGED_IN_SP_POST_MEDIA_COMMENT'], $replaceStr);
                break;
            case 'SHOP_TAGGED_IN_SP_POST_MEDIA_COMMENT':
                $text = $this->_updateByGivenText($language_const_array['PUSH_SHOP_TAGGED_IN_SP_POST_MEDIA_COMMENT'], $replaceStr);
                break;
            case 'BUY_ECOMMERCE_PRODUCT':
                $text = $language_const_array['PUSH_ECOMMERCE_PRODUCT_PURCHASE_TEXT'];
                break;
        }
        $returnText = '';
        if(!empty($text)){
            $rplcStrArr = is_array($replaceStr) ? $replaceStr : (array)$replaceStr;
            $returnText = vsprintf($text, $rplcStrArr);
        }
        return $returnText;
    }

    public function getUsersByLanguage(array $users){
        $response = array();
        foreach($users as $k=>$v){
            $key = (isset($v['current_language']) and !empty($v['current_language'])) ? $v['current_language'] : 0;
            $response[$key][$k] = $v;
        }
        return $response;
    }

    /**
     *  function for sending the transaction notification
     * @param type $post
     * @param type $webNotification
     * @param type $fbshare
     */
    public function sendTransactionNotification($post, $webNotification, $fbshare) {
         $user_service = $this->container->get('user_object.service');
        if(isset($post['message']) && $post['message'] == 'TXN_CUST_RATING') {
            $sender_id = $post['citizen_id'];
            $reciever_id = $post['store_owner_id'];
            $recieverData = $this->getUserData($reciever_id);
            $sender = $this->getUserData($sender_id);
            $message = 'TXN_CUST_RATING';
            $id = $post['txn_id'];
            $info = array();
            //for storing the extra information
            $info['_id'] = $post['_id'];
            $info['store_owner_id'] = $post['store_owner_id'];
            $info['store_id'] = $post['store_id'];
            $info['citizen_id'] = $post['citizen_id'];
            $info['message_status'] = $post['message_status'];
            $info['txn_id'] = $post['txn_id'];
            $info['store_info'] = $user_service->getStoreObjectService($info['store_id']);
            $extraPushParams = array('store_id'=>$post['store_id']);
            $url = $this->container->getParameter('shop_profile_url');
            $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
            $linkUrl = $angular_app_hostname.$url. "/".$info['store_id'];

        } else {
             $post = isset($post['data']) ? $post['data'] : $post;
             if(empty($post)){
             return false;
             }

            $sender = $post['user_profile'];
            $sender_id = $sender['id'];
            $linkUrl = $this->getStoreClubUrl(array('storeId'=>$post['store_id'], 'postId'=>$post['post_id']), 'store');
            $extraPushParams = array('store_id'=>$post['store_id']);
            if($fbshare){
                $this->facebookPostShare(array(
                  'user_id'=>$sender_id,
                  'link'=>$this->getPublicPostUrl(array('shopId'=>$post['store_id'], 'postId'=>$post['post_id']), 'shop'),
                  'description'=>$post['store_post_desc'],
                  'media_thumb_link'=> isset($post['media_info'][0]) ? $post['media_info'][0]['media_thumb_path'] : ''
                ));
            }
            $store = $this->em->getRepository('StoreManagerStoreBundle:UserToStore')
                            ->findOneBy(array('storeId'=>$post['store_id']));

            $store_info = $user_service->getStoreObjectService($post['store_id']);

            $recieverData = $this->getUserData($store->getUserId());
            $recieverData['name'] = $store_info['name'];
            $reciever_id = $recieverData['id'];
            if($sender_id==$reciever_id){
                return false;
            }

            $message = 'TXN_CUST_SHARE';
            $id = $post['post_id'];
            $info = array();
            //for storing the extra information
            $info['store_id'] = $post['store_id'];
            $info['post_id'] = $post['post_id'];
            $info['txn_id'] = isset($post['txn_id']) ? $post['txn_id'] : '';
            $info['store_info'] = $user_service->getStoreObjectService($info['store_id']);

        }
        if($webNotification){
                $this->saveUserNotification($sender_id, $reciever_id, 'TXN', $message, $id,'T',$info,5);
                $this->sendUserNotifications($sender_id, $reciever_id, 'TXN', $message,$id, false, true, ucwords($sender['first_name'].' '.$sender['last_name']), 'SHOP', $extraPushParams);
                return $this->sendPostNotificationMail($sender, $recieverData, $message, $linkUrl);
        }
    }

    /**
     *  function for sending the transaction notification
     * @param type $post
     * @param type $webNotification
     * @param type $fbshare
     */
    public function sendCitizenIncomeNotification($user_id,$citizen_income, $webNotification, $fbshare) {
         $user_service = $this->container->get('user_object.service');
         $em = $this->em;
         $admin_id = $em
               ->getRepository('TransactionTransactionBundle:RecurringPayment')
               ->findByRole('ROLE_ADMIN');
         $this->_log('Admin id is:'.$admin_id);
            $sender_id = $admin_id;
            $reciever_id = $user_id;
            $recieverData = $this->getUserData($reciever_id);
            if(count($recieverData) > 0 ) {
            $sender = $this->getUserData($sender_id);

            $message = 'TXN_CUST_CI_GAIN';
            $id = $user_id;
            $info = array();
            //for storing the extra information
            $info['user_id'] = $user_id;
            $info['citizen_income'] = $citizen_income;
            $url = $this->container->getParameter('citizen_wallet');
            $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
            $linkUrl = $angular_app_hostname.$url;
        $this->_log("calling the functions for sending notifications");
        if($webNotification){
                $this->saveUserNotification($sender_id, $reciever_id, 'TXN', $message, $id,'I',$info,5);
                $this->sendUserNotifications($sender_id, $reciever_id, 'TXN', $message,$id, false, true,$citizen_income, 'CITIZEN');
                return $this->sendPostNotificationMail($sender, $recieverData, $message, $linkUrl,array($citizen_income));
        }
    }
    }

    /**
     *  function for writing the logs
     * @param type $sMessage
     */
    public function _log($sMessage, $logger='cinotification_logs'){
        $monoLog = $this->container->get('monolog.logger.'.$logger);
        $monoLog->info($sMessage);
    }

    public function updateFacebookPublishAction($fbUserObject, $isUserId=false){

        try{
            if($isUserId===true){
                $fbUserObject = $this->getFacebookInfo($fbUserObject);
            }

            $fbUserObject->setPublishActions(0);
            $this->em->persist($fbUserObject);
            $this->em->flush();
        }  catch (Exception $e){

        }
    }

    public function sendReferralAmountNotifications($shop_id=null, $toId=null){
        if($shop_id>0 and $toId>0){
            try{
                $url = $this->container->getParameter('shop_profile_url');
                $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
                $linkUrl = $angular_app_hostname.$url. "/".$shop_id;

                $storeReferrals = $this->em->getRepository('AffiliationAffiliationManagerBundle:AffiliationShop')
                                ->findOneBy(array('shopId'=>$shop_id, 'toId'=>$toId));
                if($storeReferrals){
                    $sender_id = $toId;
                    $referralUserId = $storeReferrals->getFromId();
                    $referralUser = $this->getUserData($referralUserId);
                    $shop = $this->em->getRepository('StoreManagerStoreBundle:Store')
                            ->findOneBy(array('id' => $shop_id));

                    $shopName = $shop->getName();
                    if ($shopName == '') {
                        $shopName = $shop->getBusinessName();
                    }

                    $locale = empty($referralUser['current_language']) ? $this->container->getParameter('locale') : $referralUser['current_language'];
                    $lang_array = $this->container->getParameter($locale);
                    $subject = $lang_array['REFERRAL_AMOUNT_SHOP_AFFILIATION_SUBJECT'];
                    $mail_text = sprintf($lang_array['REFERRAL_AMOUNT_SHOP_AFFILIATION_TEXT'],$shopName);
                    $link = "<a href='$linkUrl'>".$lang_array['CLICK_HERE']."</a>";
                    $bodyData = $mail_text.'<br><br>'. sprintf($lang_array['REFERRAL_AMOUNT_SHOP_AFFILIATION_LINK'], $link);
                    $bodyTitle = $lang_array['REFERRAL_AMOUNT_SHOP_AFFILIATION_BODY'];
                    $thumb = '';

                    // HOTFIX NO NOTIFY MAIL
                    //$emailResponse = $this->sendMail(array($referralUser), $bodyData, $bodyTitle, $subject, $thumb, 'REFERRAL_AMMOUNT_SHOP_AFFILIATION');

                    $this->saveUserNotification($sender_id, $referralUserId, 'SHOP_AFFILIATION', 'REFERRAL_AMOUNT', $shop_id,'I',array('store_id'=>$shop_id),5);
                    $this->sendUserNotifications($sender_id, $referralUserId, 'SHOP_AFFILIATION', 'REFERRAL_AMOUNT',$shop_id, false, true,$shopName, 'CITIZEN');
                }
            }catch(Exception $e){

            }
        }
    }

    /**
     *  function for sending the CI notifications to the users
     * @param type $users_array
     * @param type $citizen_incomes
     * @param type $sender_data
     * @param type $admin_id
     * @param type $msgtype
     * @param type $msg
     * @param type $message_status
     * @param type $role
     * @param type $infos
     * @return boolean
     */
    public function saveCitizenIncomeNotification($users_array, $citizen_incomes, $sender_data, $admin_id, $msgtype, $msg, $message_status, $role, $infos) {
        set_error_handler(array($this, 'errorHandler'), E_ALL^E_NOTICE);
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //$to_ids = is_array($to_id) ? $to_id : (array)$to_id;
        $check_flag = 1;
        $batchSize = 100;
        $i = 0;
        $users_data = array();
        $message = 'TXN_CUST_CI_GAIN';
        $url = $this->container->getParameter('citizen_wallet');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $linkUrl = $angular_app_hostname . $url;
        $info = array();


        foreach ($users_array as $user) {
            try {
//                if($user['id'] == 5143) {
//                    $check_flag = 1;
//                }
//
//                if($user['id'] == 5154) {
//                    $check_flag = 1;
//                }
//                if($check_flag == 0) {
//                    continue;
//                }

                $i = $i + 1;
                $citizen_income = isset($citizen_incomes[$user['id']]) ? $citizen_incomes[$user['id']] : 0.00;
                $info[$user['id']] = isset($infos[$user['id']]) ? $infos[$user['id']] : array();
                $users_data[$user['id']] = $user;
                $users_data[$user['id']]['citizen_income'] = $citizen_income;

                if (($i % $batchSize) == 0) {
                    $this->_log("[Send notification for batch_number:".(int)($i/$batchSize).", and users_info:".  json_encode(array_keys($users_data)).")]");
                    try {
                        $this->saveUserNotificationBatch($users_data, $admin_id, $msgtype, $msg, $message_status, $info, $role);
                    } catch (\Exception $ex) {
                        $this->_log("[Expection Occure:".$ex->getMessage()."]");
                    }
                    try {
                        $this->sendNotificationBatch($sender_data, $users_data, $message, $linkUrl);
                    } catch (\Exception $ex) {
                        $this->_log("[Expection Occure:".$ex->getMessage()."]");
                    }
                    try {
                        $this->sendUserNotificationsBatch($admin_id, $users_data, 'TXN', $message, $admin_id, false, true, $citizen_incomes, 'CITIZEN');
                    } catch (\Exception $ex) {
                        $this->_log("[Expection Occure:".$ex->getMessage()."]");
                    }

                    $info = array();
                    $users_data = array();

                }
            } catch (\Exception $ex) {
                    $this->_log("[Expection Occure:".$ex->getMessage()."]");
            }
        }
        $this->_log("[Send notification for final batch and users_info:".  json_encode(array_keys($users_data)).")]");
        try {
            $this->saveUserNotificationBatch($users_data, $admin_id, $msgtype, $msg, $message_status, $info, $role);
        } catch (\Exception $ex) {
            $this->_log("[Expection Occure:".$ex->getMessage()."]");
        }
        try {
            $this->sendNotificationBatch($sender_data, $users_data, $message, $linkUrl);
        } catch (\Exception $ex) {
            $this->_log("[Expection Occure:".$ex->getMessage()."]");
        }
        try {
            $this->sendUserNotificationsBatch($admin_id, $users_data, 'TXN', $message, $admin_id, false, true, $citizen_incomes, 'CITIZEN');
        } catch (\Exception $ex) {
            $this->_log("[Expection Occure:".$ex->getMessage()."]");
        }

        return true;
    }

    /**
     *  function for saving the user notification in the batch system
     * @param type $users_array
     * @param type $admin_id
     * @param type $msgtype
     * @param type $msg
     * @param type $message_status
     * @param type $infos
     * @param type $role
     */
    public function saveUserNotificationBatch($users_array, $admin_id, $msgtype, $msg, $message_status, $infos, $role) {
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $time = new \DateTime("now");
        foreach ($users_array as $user) {
            $info = isset($infos[$user['id']]) ? $infos[$user['id']] : array();
            $notification = new UserNotifications();
            $notification->setFrom($admin_id);
            $notification->setTo($user['id']);
            $notification->setMessageType($msgtype);
            $notification->setMessage($msg);
            $notification->setMessageStatus($message_status);
            $notification->setItemId(1);
            $notification->setDate($time);
            $notification->setIsRead('0');
            $notification->setInfo($info);
            $notification->setNotificationRole($role);
            $dm->persist($notification);
        }
        $dm->flush();
        $dm->clear();
    }

    /**
     *  function for sending the mail notification in the batch system
     * @param array $sender
     * @param array $recievers
     * @param type $lang_prefix
     * @param type $link_url
     * @param type $extra_params
     * @return boolean
     */
    public function sendNotificationBatch(array $sender, array $recievers, $lang_prefix, $link_url = '', $extra_params = array()) {

        try {
            $emailResponse = '';
            $thumb = $this->container->getParameter('sixthcontinent_logo_path');
            $bodyData = array();
            $bodyTitle = array();
            $subject = array();
            foreach ($recievers as $reciever) {
                $locale = isset($reciever['current_language']) ? $reciever['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);
                $sender_name = trim(ucfirst($sender['first_name']) . ' ' . ucfirst($sender['last_name']));
                // send comment notification to post user
                $subject[$reciever['id']] = $lang_array['TXN_CI_GAIN_MAIL_SUB'];
                $mail_link = $lang_array['TXN_CI_GAIN_LINK_TEXT'];
                $mail_body = sprintf($lang_array['TXN_CI_GAIN_MAIL_BODY'], $reciever['citizen_income']);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']} </a>" . $mail_link;

                $bodyData[$reciever['id']] = $mail_body . '<br><br>' . $href;

                $bodyTitle[$reciever['id']] = sprintf($lang_array['TXN_CI_GAIN_MAIL_TEXT']);
            }
            $emailResponse = $this->sendMailNew($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'CITIZEN_INCOME_GAIN');
            return $emailResponse;
        } catch (\Exception $e) {
            //die("error");
        }
        return false;
    }

    /**
     * Send mails using Sendgrid API
     * @param array $options
     * @param array $templateParams
     * @param string $templateId
     * @param string $mailType
     * @return array
     */
    public function sendMailNew(array $receivers, $bodyData, $bodyTitle = '', $subject = '', $thumb = '', $category = 'uncategorized') {
        $email_template_service = $this->container->get('email_template.service');
        return $email_template_service->sendMailNew($receivers, $bodyData, $bodyTitle, $subject, $thumb, $category);
    }

    /**
     * Send web, push or both (web and push) notifications for users in batch system
     * @param int $from_id  Sender id
     * @param array $to_id  Receiver id
     * @param string $msgtype   Notification Message Type
     * @param string $msg   Notification message
     * @param string $itemId    Item id
     * @param bool $isWeb   True if send web notification
     * @param bool $isPush  True if send Push notification
     * @param string $pushText  Push notification text
     * @param string $clientType    Client type
     */
    public function sendUserNotificationsBatch($from_id, $users_data, $msgtype, $msg, $itemId, $isWeb = true, $isPush = false, $replaceText = null, $clientType = 'CITIZEN', $extraParams = array(), $msgStatus = 'U', $info = array()) {
        $push_object_service = $this->container->get('push_notification.service');
        // push notification
        $sendPush = true;
        $pushInfo['role'] = 4;
        $to_id = array_keys($users_data);
        $usersDevices = $push_object_service->getReceiverDeviceWithUserInfo($to_id, strtolower($clientType));
        $deviceByLangs=array();
        foreach ($usersDevices as $user_id => $user_device) {
            foreach ($user_device as $dType=>$dInfos){
                foreach($dInfos as $dInfo){
                    $_lng = !empty($dInfo['lang']) ? $dInfo['lang'] : (!empty($users_data[$user_id]['current_language']) ? $users_data[$user_id]['current_language'] : $this->container->getParameter('locale'));
                    $deviceByLangs[$user_id][$_lng][$dType][] = $dInfo;
                }
            }
        }

        foreach($deviceByLangs as $user_id=>$devices){
            foreach($devices as $lang=>$_devices){
                $pushText = $this->getPushNotificationText($msgtype, $replaceText[$user_id], $lang, $msg);
                try {
                    if ($isPush) {
                        // push notification
                        $sendPush = true;
                        $pushInfo['role'] = 4;
                        $push_object_service->sendPush($_devices, $from_id, $msg, $pushText, $msgtype, $itemId, $clientType, $extraParams);
                    }
                } catch (\Exception $e) {
                    //print_r($e);
                }
            }
        }

        return true;
    }

    function errorHandler($errno, $errstr, $errfile, $errline) {
        throw new \Exception($errstr, $errno);
    }

    public function sendOfferNotifications($users, $offers, $lang, $cats, $shops){
        set_error_handler(array($this, 'errorHandler'), E_ALL^E_NOTICE);
        $users_data = array();
        $i=0;
        $batchSize=100;
        $email_template_service = $this->container->get('email_template.service');
        $options = $this->_setOfferMail($offers, $lang, $cats, $shops);
        foreach ($users as  $user) {
            try {

                $i = $i + 1;
                $users_data[$user['id']] = $user;

                if (($i % $batchSize) == 0) {
                    $this->_log("[Send notification for batch_number:".(int)($i/$batchSize).", and users_info:".  json_encode(array_keys($users_data)).")]");

                    try {

                        $templateParams = $this->_setParams($options['data'], $options['templateParams'], $users_data);
                        $email_template_service->sendMailWithCustomParams($templateParams, $options['subject'], $options['body'], $options['templateId'], 'OFFER_NOTIFICATIONS');
                    } catch (\Exception $ex) {
                        $this->_log("[Expection Occure:".$ex->getMessage()."]");
                    }

                    $users_data = array();

                }
            } catch (\Exception $ex) {
                    $this->_log("[Expection Occure:".$ex->getMessage()."]");
            }
        }

        if(!empty($users_data)){
            try {
                $templateParams = $this->_setParams($options['data'], $options['templateParams'], $users_data);
                $email_template_service->sendMailWithCustomParams($templateParams, $options['subject'], $options['body'], $options['templateId'], 'OFFER_NOTIFICATIONS');
            } catch (\Exception $ex) {
                $this->_log("[Expection Occure:".$ex->getMessage()."]");
            }
        }
    }

    private function _setOfferMail($offers, $lang, $cats, $shops){
        $lang = $lang=='0' ? $this->container->getParameter('locale') : $lang;
        $lang_array = $this->container->getParameter($lang);
        $data = array();
        $options = array();
        $maxCoupon=3;
        $maxCards=3;
        $couponCount=0;
        foreach($offers['coupons'] as $coupon){
            $couponCount++;
            $data['[coupon_amount_'.$couponCount.']'] = isset($coupon['value']) ? $coupon['value'] : 0;
            $data['[coupon_title_'.$couponCount.']'] = isset($shops[$coupon['shop_id']['_id']]['name']) ? $shops[$coupon['shop_id']['_id']]['name'] : (isset($shops[$coupon['shop_id']['_id']]['businessName']) ? $shops[$coupon['shop_id']['_id']]['businessName'] : '');
            $catId = isset($coupon['shop_id']['category_id']) ? $coupon['shop_id']['category_id']['_id'] : 0;
            $data['[coupon_category_'.$couponCount.']'] = isset($cats[$catId]) ? $cats[$catId]['name'] : ($catId>0 ? $coupon['shop_id']['category_id']['name'] : '');
            $rate = isset($coupon['shop_id']['average_anonymous_rating']) ? $coupon['shop_id']['average_anonymous_rating'] :0;
            $rates = array(
                1=> $rate>=0.6 ? 'left bottom' : (($rate>0 and $rate<=0.5) ? '0px -20px' : '0px  0px'),
                2=> $rate>=1.6 ? 'left bottom' : (($rate>1 and $rate<=1.5) ? '0px -20px' : '0px  0px'),
                3=> $rate>=2.6 ? 'left bottom' : (($rate>2 and $rate<=2.5) ? '0px -20px' : '0px  0px'),
                4=> $rate>=3.6 ? 'left bottom' : (($rate>3 and $rate<=3.5) ? '0px -20px' : '0px  0px'),
                5=> $rate>=4.6 ? 'left bottom' : (($rate>4 and $rate<=4.5) ? '0px -20px' : '0px  0px')
            );

            $data['[coupon_rate_'.$couponCount.'_1]'] = $rates[1];
            $data['[coupon_rate_'.$couponCount.'_2]'] = $rates[2];
            $data['[coupon_rate_'.$couponCount.'_3]'] = $rates[3];
            $data['[coupon_rate_'.$couponCount.'_4]'] = $rates[4];
            $data['[coupon_rate_'.$couponCount.'_5]'] = $rates[5];
            $data['[coupon_location_'.$couponCount.']'] = isset($coupon['shop_id']['address_l2']) ? $coupon['shop_id']['address_l2'] : '';
            $couponImg = isset($coupon['shop_id']['shop_thumbnail_img']) ? $coupon['shop_id']['shop_thumbnail_img'] : '';
            $couponImgArray = explode(',', $couponImg);
            $data['[coupon_shop_img_'.$couponCount.']'] = trim($couponImgArray[0]);
            $data['[coupon_vote_'.$couponCount.']'] = isset($coupon['shop_id']['total_votes']) ? $coupon['shop_id']['total_votes'] : 0;

            if($data['[coupon_vote_'.$couponCount.']']==0){
                $data['[coupon_vote_'.$couponCount.']'] = isset($shops[$coupon['shop_id']['_id']]['vote_count']) ? $shops[$coupon['shop_id']['_id']]['vote_count'] : 0;
            }
            if(empty($data['[coupon_shop_img_'.$couponCount.']'])){
                $data['[coupon_shop_img_'.$couponCount.']'] = isset($shops[$coupon['shop_id']['_id']]['thumb_path']) ? $shops[$coupon['shop_id']['_id']]['thumb_path'] :
                    (isset($cats[$catId]['image_thumb']) ? $cats[$catId]['image_thumb'] : $this->container->getParameter('store_default_image_thumb'));
            }
            $data['[coupon_show_'.$couponCount.']'] = '';
            if($couponCount%$maxCoupon==0){
                break;
            }
        }

        //hide coupon if less then 3
        for($cpC=$couponCount+1;$cpC<=$maxCoupon; $cpC++){
            $data['[coupon_show_'.$cpC.']'] = 'display:none !important;max-height:0px !important;';
        }

        $cardsCount=0;
        foreach($offers['cards'] as $card){
            $cardsCount++;
            $data['[card_amount_'.$cardsCount.']'] = isset($card['value']) ? $card['value'] : 0;
            $data['[card_title_'.$cardsCount.']'] = isset($shops[$card['shop_id']['_id']]['name']) ? $shops[$card['shop_id']['_id']]['name'] : (isset($shops[$card['shop_id']['_id']]['businessName']) ? $shops[$card['shop_id']['_id']]['businessName'] : '');
            $catId = isset($card['shop_id']['category_id']) ? $card['shop_id']['category_id']['_id'] : 0;
            $data['[card_category_'.$cardsCount.']'] = isset($cats[$catId]) ? $cats[$catId]['name'] : ($catId>0 ? $card['shop_id']['category_id']['name'] : '');
            $rate = isset($card['shop_id']['average_anonymous_rating']) ? $card['shop_id']['average_anonymous_rating'] : 0;
            $rates = array(
                1=> $rate>=0.6 ? 'left bottom' : (($rate>0 and $rate<=0.5) ? '0px -20px' : '0px  0px'),
                2=> $rate>=1.6 ? 'left bottom' : (($rate>1 and $rate<=1.5) ? '0px -20px' : '0px  0px'),
                3=> $rate>=2.6 ? 'left bottom' : (($rate>2 and $rate<=2.5) ? '0px -20px' : '0px  0px'),
                4=> $rate>=3.6 ? 'left bottom' : (($rate>3 and $rate<=3.5) ? '0px -20px' : '0px  0px'),
                5=> $rate>=4.6 ? 'left bottom' : (($rate>4 and $rate<=4.5) ? '0px -20px' : '0px  0px')
            );

            $data['[card_rate_'.$cardsCount.'_1]'] = $rates[1];
            $data['[card_rate_'.$cardsCount.'_2]'] = $rates[2];
            $data['[card_rate_'.$cardsCount.'_3]'] = $rates[3];
            $data['[card_rate_'.$cardsCount.'_4]'] = $rates[4];
            $data['[card_rate_'.$cardsCount.'_5]'] = $rates[5];
            $data['[card_location_'.$cardsCount.']'] = isset($card['shop_id']['address_l2']) ? $card['shop_id']['address_l2'] : '';
            $cardImg = isset($card['imageurl']) ? $card['imageurl'] : '';
            $cardImgArray = explode(',', $cardImg);
            $data['[card_shop_img_'.$cardsCount.']'] = trim($cardImgArray[0]);
            $data['[card_id_'.$cardsCount.']'] = isset($card['_id']) ? $card['_id'] : '';
            $data['[card_vote_'.$cardsCount.']'] = isset($card['shop_id']['total_votes']) ? $card['shop_id']['total_votes'] : 0;

            if($data['[card_vote_'.$cardsCount.']']==0){
                $data['[card_vote_'.$cardsCount.']'] = isset($shops[$card['shop_id']['_id']]['vote_count']) ? $shops[$card['shop_id']['_id']]['vote_count'] : 0;
            }

            if(empty($data['[card_shop_img_'.$cardsCount.']'])){
                $data['[card_shop_img_'.$cardsCount.']'] = isset($shops[$card['shop_id']['_id']]['thumb_path']) ?
                        $shops[$card['shop_id']['_id']]['thumb_path'] : (
                            isset($cats[$catId]['image_thumb']) ?
                            $cats[$catId]['image_thumb'] :
                            $this->container->getParameter('store_default_image_thumb')
                        );
            }
            $data['[card_show_'.$cardsCount.']'] = '';
            if($cardsCount%$maxCards==0){
                break;
            }
        }

        //hide coupon if less then 3
        for($crC=$cardsCount+1;$crC<=$maxCards; $crC++){
            $data['[card_show_'.$crC.']'] = 'display:none !important;max-height:0px !important;';
        }

        // recheck data for image
        $data = $this->_checkAndGetShopImage($data);

        $data['[offer_title_1]'] = '[s_offer_title_1]';
        $data['[offer_title_2]'] = '[s_offer_title_2]';
        $data['[body_text_1]'] = '[s_body_text_1]';
        $data['[body_text_2]'] = '[s_body_text_2]';
        $data['[body_text_3]'] = '[s_body_text_3]';
        $data['[body_text_4]'] = '[s_body_text_4]';
        $data['[view_coupon]'] = '[s_view_coupon]';
        $data['[view_card]'] = '[s_view_card]';
        $data['[votes_text]'] = '[s_votes_text]';
        $data['[view_all_offers]'] = '[s_view_all_offers]';
        $data['[update_citizen_profile]'] = '[s_update_citizen_profile]';
        $data['[edit_profile]'] = '[s_edit_profile]';
        $data['[learn_more]'] = '[s_learn_more]';
        $data['[go_to_sixthcontinent]'] = '[s_go_to_sixthcontinent]';
        $data['[latest_offers]'] = $lang_array['OFFERS_LATEST'];
        $data['[card_block]'] = '[s_card_block]';
        $data['[coupon_block]'] = '[s_coupon_block]';

        $sections = array();
        $sections['[s_offer_title_1]'] = $lang_array['OFFERS_TITLE_1'];
        $sections['[s_offer_title_2]'] = $lang_array['OFFERS_TITLE_2'];
        $sections['[s_body_text_1]'] = $lang_array['OFFERS_BODY_TEXT_1'];
        $sections['[s_body_text_2]'] = $lang_array['OFFERS_BODY_TEXT_2'];
        $sections['[s_body_text_3]'] = $lang_array['OFFERS_BODY_TEXT_3'];
        $sections['[s_body_text_4]'] = $lang_array['OFFERS_BODY_TEXT_4'];
        $sections['[s_view_coupon]'] = $lang_array['OFFERS_VIEW_COUPON'];
        $sections['[s_view_card]'] = $lang_array['OFFERS_VIEW_CARD'];
        $sections['[s_votes_text]'] = $lang_array['OFFERS_VOTES'];
        $sections['[s_view_all_offers]'] = $lang_array['OFFERS_VIEW_ALL'];
        $sections['[s_update_citizen_profile]'] = $lang_array['OFFERS_UPDATE_CITIZEN'];
        $sections['[s_edit_profile]'] = $lang_array['OFFERS_EDIT_PROFILE'];
        $sections['[s_learn_more]'] = $lang_array['OFFERS_LEARN_MORE_ON_TUTORIAL'];
        $sections['[s_go_to_sixthcontinent]'] = $lang_array['OFFERS_GO_TO_SIXTHCONTINENT'];
        $sections['[s_card_block]'] = $cardsCount===0 ? 'display:none !important;max-height:0px !important;' : '';
        $sections['[s_coupon_block]'] = $couponCount===0 ? 'display:none !important;max-height:0px !important;' : '';


        $templateParams = array(
            'section'=> $sections
        );
        $this->_log('Emails params : '. json_encode($data), 'offer_notifications');
        $options['templateParams'] = $templateParams;
        $options['subject'] = $lang_array['OFFERS_SUBJECT'];
        $options['templateId'] = $this->container->getParameter('sendgrid_offers_template_id');
        $options['body'] = '<br>';
        $options['data'] = $data;
        return $options;
    }

    public function _setParams($data, $templateParams, $users){
        $params = array_keys($data);
        foreach($users as $toUser){
            foreach($params as $param){
                $templateParams['sub'][$param][] = $data[$param];
            }
            $templateParams['to'][] = $toUser['email'];
        }
        $this->_log('Emails will sent : '. json_encode($templateParams['to']), 'offer_notifications');
        return $templateParams;
    }

    public function _updateByGivenText($text, $replaces){
        if(!is_array($replaces)){
            return $text;
        }
        foreach($replaces as $find=>$replace){
            if(!preg_match('/^[0-9]+$/', $find)){
                $text = str_replace(':'.$find, $replace, $text);
            }
        }
        return $text;
    }

    public function sendCardSoldNotifications($users_data,$shop_owner_card_info,$shop_owner_purchase_card_info,$total_credit){
        //set_error_handler(array($this, 'errorHandler'), E_ALL^E_NOTICE);
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //$to_ids = is_array($to_id) ? $to_id : (array)$to_id;
        $check_flag = 1;
        $batchSize = 100;
        $i = 0;
        $user_datas = array();
        $url = $this->container->getParameter('citizen_wallet');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $linkUrl = $angular_app_hostname . $url;
        $info = array();


        foreach ($users_data as $user) {
            try {
                $i = $i + 1;
                $card_info = isset($shop_owner_card_info[$user['id']]) ? $shop_owner_card_info[$user['id']] : array();
                $purchase_cards = isset($shop_owner_purchase_card_info[$user['id']]) ? $shop_owner_purchase_card_info[$user['id']] : array();
                $users_data[$user['id']] = $user;
                $users_data[$user['id']]['cards_info'] = $card_info;
                $users_data[$user['id']]['purchase_cards_info'] = $purchase_cards;
                $users_data[$user['id']]['total_card_sold'] = $total_credit[$user['id']];

                if (($i % $batchSize) == 0) {
                    $this->_log("[Send notification for batch_number:".(int)($i/$batchSize).", and users_info:".  json_encode(array_keys($users_data)).")]",'cardsold_notifications');
                    try {
                        $this->_sendCardSoldEmailWithCustomParams($users_data);
                    } catch (\Exception $ex) {
                        $this->_log("[Expection Occure:".$ex->getMessage()."]",'cardsold_notifications');
                    }
                    $info = array();
                    $users_data = array();

                }
            } catch (\Exception $ex) {
                    $this->_log("[Expection Occure:".$ex->getMessage()."]",'cardsold_notifications');
            }
        }

        $this->_log("[Send notification for final batch and users_info:".  json_encode(array_keys($users_data)).")]",'cardsold_notifications');
        try {
            $this->_sendCardSoldEmailWithCustomParams($users_data);
        } catch (\Exception $ex) {
            $this->_log("[Expection Occure:".$ex->getMessage()."]",'cardsold_notifications','cardsold_notifications');
        }

        return true;
    }

    private function getShopWithUserFormat(array $shopUser, array $users){
        $response = array();
        foreach($shopUser as $shop=>$user){
            if(key_exists($user, $users)){
                $response['cur'][$shop] = $user;
            }else{
                $response['nxt'][$shop] = $user;
            }
        }
        return $response;
    }

    private function _sendCardSoldEmailWithCustomParams($users_data){
        $email_template_service = $this->container->get('email_template.service');
        $templateId = $this->container->getParameter('card_sold_in_a_day_template_id');

        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $approve_card_url = $this->container->getParameter('approve_card_url');
        $accept_link = $angular_app_hostname.$approve_card_url;
        $current_date = date('F d Y');
        //$shopUrl = $this->container->getParameter('shop_profile_url');
        //$href = $angular_app_hostname.$shopUrl.'/[shop_id]';
            foreach($users_data as $user){
                $locale = isset($user['current_language']) ? $user['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);
                $current_locale = $lang_array['TIME_ZONE_LOCALE'];
                    $oldLocale = setlocale(LC_TIME, $current_locale);
                    $current_date =  utf8_encode( strftime("%d %B %Y", time()) );
                    setlocale(LC_TIME, $oldLocale);
                $toName = trim(ucfirst($user['first_name']).' '.ucfirst($user['last_name']));
                $templateParams['to'][] = $user['email'];
                $templateParams['sub']['[current_date]'][] = $current_date;
                $templateParams['sub']['[title_1]'][] = $lang_array['CS_TITLE_1'].' '.$current_date;
                $templateParams['sub']['[user_name]'][] = sprintf($lang_array['CS_USER_NAME'],$toName);
                $templateParams['sub']['[today_shopping_card_text]'][] = $lang_array['CS_TODAY_SHOPPING_CARD_TEXT'];
                $templateParams['sub']['[shopping_cards_list]'][] = $this->prepareHTMLForShoppingCardPurchase($user['cards_info'],$lang_array);
                $templateParams['sub']['[today_you_cashed_text]'][] = $lang_array['CS_TODAY_YOU_CASHED_TEXT'];
                $templateParams['sub']['[today_you_cashed_value]'][] = $user['total_card_sold'];
                $templateParams['sub']['[today_you_cashed_congratulation]'][] = $lang_array['CS_TODAY_YOU_CASHED_CONGRATULATION'];
                $templateParams['sub']['[payment_accept_for_cards_text]'][] = $lang_array['CS_PAYMENT_ACCEPT_FOR_CARD_TEXT'];
                $templateParams['sub']['[purchase_card_list]'][] = $this->prepareHTMLForPurchaseCardPurchase($user['purchase_cards_info'],$lang_array);
                $templateParams['sub']['[card_approve_link_text]'][] = $lang_array['CS_CARD_APPROVE_LINK_TEXT'];
                $templateParams['sub']['[card_approve_link]'][] = $accept_link;
                $templateParams['sub']['[view_details_link]'][] = "www.sixthcontinent.com";
                $templateParams['sub']['[view_details_text]'][] = $lang_array['CS_VIEW_DETAILS_TEXT'];
                $templateParams['sub']['[view_details_link_text]'][] = $lang_array['CS_VIEW_DETAILS_LINK_TEXT'];
                $templateParams['sub']['[mail_bottom_text]'][] = $lang_array['CS_MAIL_BOTTOM_TEXT'];
                $templateParams['sub']['[subject]'][] = $lang_array['CS_TITLE_1'].' '.$current_date;
            }
            $templateParams['section']['[shop_card_table_start]'] = '<table cellpadding="0" cellspacing="0" width="100%"><tr>
<td style="border: 1px solid #e2e2e2; padding: 20px;">'.$lang_array['CS_SHOPPING_CARD_HEAD'].'</td>
<td style="padding: 20px; border-bottom: 1px solid #e2e2e2; border-top: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$lang_array['CS_SHOPPING_CARD_VALUE'].'</td>
<td style="padding: 20px; border-bottom: 1px solid #e2e2e2; border-top: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$lang_array['CS_SHOPPING_CARD_SHOP_NAME'].'</td>
			</tr>';
            $templateParams['section']['[purchase_card_table_start]'] = '<table cellpadding="0" cellspacing="0" width="100%"><tr><td style="border: 1px solid #e2e2e2; padding: 20px;">'.$lang_array['CS_PURCHASE_CARD_HEAD'].'</td>
<td style="padding: 20px; border-bottom: 1px solid #e2e2e2; border-top: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$lang_array['CS_PURCHASE_CARD_VALUE'].'</td>
<td style="padding: 20px; border-bottom: 1px solid #e2e2e2; border-top: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$lang_array['CS_PURCHASE_CARD_SHOP_NAME'].'</td>
			</tr>';
            //$this->_log('Email sent - '. json_encode($templateParams['to']), 'cardsold_notifications');
            $bodyData = "<br/>";
            $subject = '[subject]';
            $response = $email_template_service->sendMailWithCustomParams($templateParams, $subject, $bodyData, $templateId, "CARDSOLD_NOTIFICATIONS");
    }

    public function getUsersBunchForCardSolds($shopDetails, $shopOwnerDetails, $usersInBunch, $bunchNo=1, $cardSolds=array()){
        $emails = array();

        $nextBunchEmails = array();
        $iteration=1;
        $bunchName = 'bunch-'.$bunchNo;
        foreach($shopDetails as $shopId=>$ownerId){
            if(!isset($emails[$bunchName]) or !key_exists($ownerId, $emails[$bunchName])){
                if(isset($shopOwnerDetails[$ownerId])){
                    $emails[$bunchName][$ownerId] = $shopOwnerDetails[$ownerId];
                    $emails[$bunchName][$ownerId]['shop_info']['id'] = $shopId;
                    $emails[$bunchName][$ownerId]['shop_info']['card_solds'] = $cardSolds[$shopId];
                }
                unset($shopDetails[$shopId]);
                $iteration++;
            }
            if($iteration==$usersInBunch){
                break;
            }

        }
        if(count($shopDetails)>0){
            $bunchNo++;
            $nextBunchEmails = $this->getUsersBunchForCardSolds($shopDetails, $shopOwnerDetails, $usersInBunch, $bunchNo, $cardSolds);
        }

        return array_merge($nextBunchEmails, $emails);
    }

    public function _checkAndGetShopImage($data){
        foreach($data as $key=>$val){
            if(preg_match('/\_shop\_img\_/', $key)  and trim($val)==''){
                $data[$key] = $this->container->getParameter('store_default_image_thumb');
            }
        }
        return $data;
    }


    public function saveCardSoldNotification($user_data,$store_data, $shop_to_user, $cards_sold,$sender_data,$admin_id) {
        set_error_handler(array($this, 'errorHandler'), E_ALL^E_NOTICE);
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //$to_ids = is_array($to_id) ? $to_id : (array)$to_id;
        $check_flag = 1;
        $batchSize = 100;
        $i = 0;
        //$users_data = array();
        $message = 'CARD_SOLD_APPROVED';
        $url = $this->container->getParameter('citizen_wallet');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $linkUrl = $angular_app_hostname . $url;
        $info = array();

        foreach ($shop_to_user as $shop_id => $user_id) {
            try {
                $i = $i + 1;
                if(isset($store_data[$shop_id]) && isset($user_data[$user_id])) {
                $users_data[$shop_id]['shop_info'] = isset($store_data[$shop_id]) ? $store_data[$shop_id] : array();
                $users_data[$shop_id]['user_data'] = isset($user_data[$user_id]) ? $user_data[$user_id] : array();
                $users_data[$shop_id]['card_solds'] = isset($cards_sold[$shop_id]) ? $cards_sold[$shop_id] : 0;
                }

                if (($i % $batchSize) == 0) {
                    $this->_log("[Send notification for batch_number:".(int)($i/$batchSize).", and users_info:".  json_encode($shop_to_user).")]",'cardsold_notifications');
                    try {
                        $this->sendNotificationCardSoldBatch($sender_data, $users_data, $message, $linkUrl);
                    } catch (\Exception $ex) {
                        $this->_log("[Expection Occure:".$ex->getMessage()."]",'cardsold_notifications');
                    }
                    $info = array();
                    $users_data = array();

                }
            } catch (\Exception $ex) {
                    $this->_log("[Expection Occure:".$ex->getMessage()."]");
            }
        }
        $this->_log("[Send notification for final batch and users_info:".  json_encode($shop_to_user).")]",'cardsold_notifications');

        try {
            $this->sendNotificationCardSoldBatch($sender_data, $users_data, $message, $linkUrl);
        } catch (\Exception $ex) {
            $this->_log("[Expection Occure:".$ex->getMessage()."]",'cardsold_notifications');
        }

        return true;
    }

    public function sendNotificationCardSoldBatch(array $sender, array $recievers, $lang_prefix, $link_url = '', $extra_params = array()) {
        try {
            $emailResponse = '';
            $thumb = $this->container->getParameter('sixthcontinent_logo_path');
            $email_template_service = $this->container->get('email_template.service');
            $templateId = $this->container->getParameter('sendgrid_notification_tpl_id');
            $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
            $shopUrl = $this->container->getParameter('shop_wallet_url');


            $bodyData = array();
            $bodyTitle = array();
            $subject = array();
            foreach ($recievers as $shop_id => $reciever) {
                $locale = isset($reciever['current_language']) ? $reciever['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);
                $sender_name = trim(ucfirst($sender['first_name']) . ' ' . ucfirst($sender['last_name']));
                // send comment notification to post user
                $subject[$shop_id] = sprintf($lang_array['CARD_SOLD_TO_APPROVED_SUBJECT'], $reciever['card_solds'],$reciever['shop_info']['name']);
                $mail_body = sprintf($lang_array['CARD_SOLD_TO_APPROVED_TEXT'], $reciever['card_solds'],$reciever['shop_info']['name']);
                $href = $angular_app_hostname.$shopUrl.'/'.$shop_id.'/7';

                $link = '<a href="'.$href.'">'. sprintf($lang_array['CARD_SOLD_TO_APPROVED_LINK']).'</a>';
                $bodyData[$shop_id] = $mail_body . '<br><br>' . $link;

                $bodyTitle[$shop_id] = sprintf($lang_array['CARD_SOLD_TO_APPROVED_MAIL_TEXT'], $reciever['card_solds'],$reciever['shop_info']['name']);
            }
            $emailResponse = $this->sendMailShopBatch($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'CARD_SOLD_NOTIFICATION');
            return $emailResponse;
        } catch (\Exception $e) {
            //die("error");
        }
        return false;
    }

    /**
     * Send mails using Sendgrid API
     * @param array $options
     * @param array $templateParams
     * @param string $templateId
     * @param string $mailType
     * @return array
     */
    public function sendMailShopBatch(array $receivers, $bodyData, $bodyTitle = '', $subject = '', $thumb = '', $category = 'uncategorized') {
        $email_template_service = $this->container->get('email_template.service');
        return $email_template_service->sendMailInBatchShop($receivers, $bodyData, $bodyTitle, $subject, $thumb, $category);
    }

    public function cardSoldLogs() {

    }

    public function sendShopInvitation($to_emails, $user_info, $url, $locale){
        try {
            $email_template_service = $this->container->get('email_template.service');
            $lang_array = $this->container->getParameter($locale);
            $templateId = $this->container->getParameter('sendgrid_shop_invite_template_id');
            $batchSize = 100;
            $sender_name = ucfirst($user_info['first_name']).' '.ucfirst($user_info['last_name']);
            $subject = sprintf($lang_array['AFFILIATION_INVITATION_SUBJECT_SHOP'],$sender_name);
            $bodyData = '<br>';
            $affiliation_category = 'INVITE_SHOP';

            $data['[b_title]'] = $lang_array['AFFILIATION_INVITATION_BODY_SHOP'];
            $data['[b_text_1A]'] = $lang_array['AFFILIATION_INVITATION_TEXT_SHOP_1A'];
            $data['[b_text_1B]'] = $lang_array['AFFILIATION_INVITATION_TEXT_SHOP_1B'];
            $data['[b_text_2A]'] = $lang_array['AFFILIATION_INVITATION_TEXT_SHOP_2A'];
            $data['[b_text_2B]'] = $lang_array['AFFILIATION_INVITATION_TEXT_SHOP_2B'];
            $data['[b_text_3A]'] = $lang_array['AFFILIATION_INVITATION_TEXT_SHOP_3A'];
            $data['[b_text_3B]'] = $lang_array['AFFILIATION_INVITATION_TEXT_SHOP_3B'];
            $data['[b_link_more]'] = $lang_array['AFFILIATION_INVITATION_LINK_MORE_SHOP'];
            $data['[b_link_more_href]'] = $url;

            $templateParams=array();
            $templateParams['section'] = $data;

            $i=0;
            foreach ($to_emails as $email){
                $i = $i + 1;
                $templateParams['sub']['[body_title]'][] = '[b_title]';
                $templateParams['sub']['[body_text_1A]'][] = '[b_text_1A]';
                $templateParams['sub']['[body_text_1B]'][] = '[b_text_1B]';
                $templateParams['sub']['[body_text_2A]'][] = '[b_text_2A]';
                $templateParams['sub']['[body_text_2B]'][] = '[b_text_2B]';
                $templateParams['sub']['[body_text_3A]'][] = '[b_text_3A]';
                $templateParams['sub']['[body_text_3B]'][] = '[b_text_3B]';
                $templateParams['sub']['[body_link_more]'][] = '[b_link_more]';
                $templateParams['sub']['[body_link_more_href]'][] = '[b_link_more_href]';
                $templateParams['to'][] = trim($email);

                if($i%$batchSize==0 or $i==count($to_emails)){
                    $email_template_service->sendMailWithCustomParams($templateParams, $subject, $bodyData, $templateId, $affiliation_category);
                    unset($templateParams['sub']);
                    unset($templateParams['to']);
                }
            }
        }  catch (\Exception $e){

        }
    }


    /**
     *  function for sending the CI notifications to the users
     * @param type $users_array
     * @param type $citizen_incomes
     * @param type $sender_data
     * @param type $admin_id
     * @param type $msgtype
     * @param type $msg
     * @param type $message_status
     * @param type $role
     * @param type $infos
     * @return boolean
     */
    public function saveCitizenIncomeReDistributionNotification($users_array, $days_left, $sender_data, $admin_id, $msgtype, $msg, $message_status, $role, $infos) {
        set_error_handler(array($this, 'errorHandler'), E_ALL^E_NOTICE);
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //$to_ids = is_array($to_id) ? $to_id : (array)$to_id;
        $check_flag = 1;
        $batchSize = 100;
        $i = 0;
        $users_data = array();
        $message = 'TXN_CUST_CI_REDISTRIBUTION';
        $url = $this->container->getParameter('shop_list_url');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $linkUrl = $angular_app_hostname . $url;
        $info = array();


        foreach ($users_array as $user) {
            try {
                $i = $i + 1;
                $day_left = isset($days_left[$user['id']]) ? $days_left[$user['id']] : 0;
                $info[$user['id']] = isset($infos[$user['id']]) ? $infos[$user['id']] : array();
                $users_data[$user['id']] = $user;
                $users_data[$user['id']]['day_left'] = $day_left;

                if (($i % $batchSize) == 0) {
                    $this->_log("[Send notification for batch_number:".(int)($i/$batchSize).", and users_info:".  json_encode(array_keys($users_data)).")]",'ci_redistribution');
                    try {
                        $this->saveUserNotificationBatch($users_data, $admin_id, $msgtype, $msg, $message_status, $info, $role);
                    } catch (\Exception $ex) {
                        $this->_log("[Expection Occure:".$ex->getMessage()."]",'ci_redistribution');
                    }
                    try {
                        $this->sendNotificationBatchCIReDistribution($sender_data, $users_data, $message, $linkUrl);
                    } catch (\Exception $ex) {
                        $this->_log("[Expection Occure:".$ex->getMessage()."]",'ci_redistribution');
                    }
                    try {
                        $this->sendUserNotificationsBatch($admin_id, $users_data, 'TXN', $message, $admin_id, false, true, $days_left, 'CITIZEN');
                    } catch (\Exception $ex) {
                        $this->_log("[Expection Occure:".$ex->getMessage()."]",'ci_redistribution');
                    }

                    $info = array();
                    $users_data = array();

                }
            } catch (\Exception $ex) {
                    $this->_log("[Expection Occure:".$ex->getMessage()."]",'ci_redistribution');
            }
        }
        $this->_log("[Send notification for final batch and users_info:".  json_encode(array_keys($users_data)).")]",'ci_redistribution');
        try {
            $this->saveUserNotificationBatch($users_data, $admin_id, $msgtype, $msg, $message_status, $info, $role);
        } catch (\Exception $ex) {
            $this->_log("[Expection Occure:".$ex->getMessage()."]",'ci_redistribution');
        }
        try {
            $this->sendNotificationBatchCIReDistribution($sender_data, $users_data, $message, $linkUrl);
        } catch (\Exception $ex) {
            $this->_log("[Expection Occure:".$ex->getMessage()."]",'ci_redistribution');
        }
        try {
            $this->sendUserNotificationsBatch($admin_id, $users_data, 'TXN', $message, $admin_id, false, true, $days_left, 'CITIZEN');
        } catch (\Exception $ex) {
            $this->_log("[Expection Occure:".$ex->getMessage()."]",'ci_redistribution');
        }

        return true;
    }


    /**
     *  function for sending the mail notification in the batch system
     * @param array $sender
     * @param array $recievers
     * @param type $lang_prefix
     * @param type $link_url
     * @param type $extra_params
     * @return boolean
     */
    public function sendNotificationBatchCIReDistribution(array $sender, array $recievers, $lang_prefix, $link_url = '', $extra_params = array()) {

        try {
            $emailResponse = '';
            $thumb = $this->container->getParameter('sixthcontinent_logo_path');
            $bodyData = array();
            $bodyTitle = array();
            $subject = array();
            foreach ($recievers as $reciever) {
                $locale = isset($reciever['current_language']) ? $reciever['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);
                $sender_name = trim(ucfirst($sender['first_name']) . ' ' . ucfirst($sender['last_name']));
                // send comment notification to post user
                $subject[$reciever['id']] = sprintf($lang_array['TXN_CI_REDISTRIBUTION_MAIL_SUB'],$reciever['day_left']);
                $mail_link = $lang_array['TXN_CI_REDISTRIBUTION_LINK_TEXT'];
                $mail_body = sprintf($lang_array['TXN_CI_REDISTRIBUTION_MAIL_BODY'], $reciever['day_left']);

                $href = "<a href= '$link_url'>{$lang_array['CLICK_HERE']} </a>" . $mail_link;

                $bodyData[$reciever['id']] = $mail_body . '<br><br>' . $href;

                $bodyTitle[$reciever['id']] = sprintf($lang_array['TXN_CI_REDISTRIBUTION_MAIL_TEXT'],$reciever['day_left']);
            }
            $emailResponse = $this->sendMailNew($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'CITIZEN_INCOME_REDISTRIBUTION');
            return $emailResponse;
        } catch (\Exception $e) {
            //die("error");
        }
        return false;
    }

    /**
     *  function for preparing the HTML for shopping card lisy
     * @param type $cards_info
     * @param type $lang_array
     * @return string
     */
    private function prepareHTMLForShoppingCardPurchase($cards_info, $lang_array) {
        $html = '<table cellpadding="0" cellspacing="0" width="100%"><tr>
<td style="border: 1px solid #e2e2e2; padding: 20px;">'.$lang_array['CS_SHOPPING_CARD_HEAD'].'</td>
<td style="padding: 20px; border-bottom: 1px solid #e2e2e2; border-top: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$lang_array['CS_SHOPPING_CARD_VALUE'].'</td>
<td style="padding: 20px; border-bottom: 1px solid #e2e2e2; border-top: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$lang_array['CS_SHOPPING_CARD_SHOP_NAME'].'</td>
			</tr>';

        foreach($cards_info as $card) {
            $html = $html.'<tr><td style="font-size: 16px; font-weight: bold; color: #0b7eba; padding: 20px; border-bottom: 1px solid #e2e2e2; border-left: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$card['card_number'].'</td>';
            $html = $html.'<td style="font-size: 16px; font-weight: bold; color: #0b7eba; padding: 20px; border-bottom: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">&euro; '.$card['card_value'].'</td>';
            $html = $html.'<td style="font-size: 16px; font-weight: bold; color: #0b7eba; padding: 20px; border-bottom: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$card['shop_name'].'</td></tr>';
        }

        $html = $html.'</table>';
        return $html;
    }


    /**
     *  function for preparing the HTML for Purchase card list
     * @param type $cards_info
     * @param type $lang_array
     * @return string
     */
    private function prepareHTMLForPurchaseCardPurchase($cards_info, $lang_array) {
        $html = '<table cellpadding="0" cellspacing="0" width="100%"><tr><td style="border: 1px solid #e2e2e2; padding: 20px;">'.$lang_array['CS_PURCHASE_CARD_HEAD'].'</td>
<td style="padding: 20px; border-bottom: 1px solid #e2e2e2; border-top: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$lang_array['CS_PURCHASE_CARD_VALUE'].'</td>
<td style="padding: 20px; border-bottom: 1px solid #e2e2e2; border-top: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$lang_array['CS_PURCHASE_CARD_SHOP_NAME'].'</td>
			</tr>';

        foreach($cards_info as $card) {
            $html = $html.'<tr><td style="font-size: 16px; font-weight: bold; color: #0b7eba; padding: 20px; border-bottom: 1px solid #e2e2e2; border-left: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$card['card_number'].'</td>';
            $html = $html.'<td style="font-size: 16px; font-weight: bold; color: #0b7eba; padding: 20px; border-bottom: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">&euro; '.$card['card_value'].'</td>';
            $html = $html.'<td style="font-size: 16px; font-weight: bold; color: #0b7eba; padding: 20px; border-bottom: 1px solid #e2e2e2; border-right: 1px solid #e2e2e2;">'.$card['shop_name'].'</td></tr>';
        }

        $html = $html.'</table>';
        return $html;
    }

    /**
     * function for sending the mail notification for sending the mail notification to the purchaser
     * @param type $user_data
     * @param type $shop_data
     * @param type $shopping_card_details
     */
    public function sendShoppingCardUPTO100MailNotification($user_data, $shop_data, $shopping_card_details) {
        try{
        $locale = isset($user_data['current_language']) ? $user_data['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        $templateParams = array();
        $base_url = $this->container->getParameter('angular_app_hostname');
        $wallet_link = $this->container->getParameter('citizen_wallet');
        $link_url = $base_url.$wallet_link;
        //get current date by the the user language
        $current_locale = $lang_array['TIME_ZONE_LOCALE'];
        $oldLocale = setlocale(LC_TIME, $current_locale);
        $current_date = utf8_encode(strftime("%d %B %Y", time()));
        setlocale(LC_TIME, $oldLocale);
        $toName = trim(ucfirst($user_data['first_name']) . ' ' . ucfirst($user_data['last_name']));
        $templateParams['to'][] = $user_data['email'];
        $templateParams['sub']['[purchase_date]'][] = $current_date;
        $templateParams['sub']['[title_1]'][] = $lang_array['SC_TITLE_1'];
        $templateParams['sub']['[card_number]'][] = isset($shopping_card_details['card_number']) ? $shopping_card_details['card_number'] : '';
        $templateParams['sub']['[user_name_text]'][] = sprintf($lang_array['SC_USER_NAME_TEXT'],$toName);
        $templateParams['sub']['[shopping_card_text]'][] = $lang_array['SC_SHOPPING_CARD_TEXT'];
        $templateParams['sub']['[amount_text]'][] = $lang_array['SC_AMOUNT_TEXT'];
        $templateParams['sub']['[card_amount]'][] = isset($shopping_card_details['total_value']) ? $shopping_card_details['total_value'] : 0;
        $templateParams['sub']['[amount_shop_name_text]'][] = $lang_array['SC_AMOUNT_SHOPNAME_TEXT'];
        $templateParams['sub']['[shop_name]'][] = $shop_data['name'];
        $templateParams['sub']['[sixthcontinent_thanks]'][] = $lang_array['SC_SIXTHCONTINENT_THANKS'];
        $templateParams['sub']['[card_purchase_details]'][] = $lang_array['SC_CARD_PURCHASE_DETAILS'];
        $templateParams['sub']['[value_shopping_card]'][] = $lang_array['SC_SHOPPING_CARD_VALUE_TEXT'];
        $templateParams['sub']['[total_card_amount]'][] = isset($shopping_card_details['total_value']) ? $shopping_card_details['total_value'] : 0;
        $templateParams['sub']['[sixthcontinent_contribution]'][] = $lang_array['SC_SC_CONTRIBUTION_TEXT'];
        $templateParams['sub']['[sixthcontinent_contribution_amount]'][] = isset($shopping_card_details['sixth_contribution']) ? $shopping_card_details['sixth_contribution'] : 0;
        $templateParams['sub']['[shop_contribution]'][] = $lang_array['SC_SHOP_CONTRIBUTION_TEXT'];
        $templateParams['sub']['[shop_contribution_amount]'][] = isset($shopping_card_details['shop_contribution']) ? $shopping_card_details['shop_contribution'] : 0;
        $templateParams['sub']['[paybal_text]'][] = $lang_array['SC_PAYBAL_TEXT'];
        $templateParams['sub']['[paybal_amount]'][] = isset($shopping_card_details['paybal_amount']) ? $shopping_card_details['paybal_amount'] : 0;
        $templateParams['sub']['[shop_details]'][] = $lang_array['SC_SHOP_DETAILS_TEXT'];
        $templateParams['sub']['[shop_business_name]'][] = $shop_data['businessName'];
        $templateParams['sub']['[vat_number]'][] = $shop_data['vatNumber'];
        $templateParams['sub']['[shop_email_id]'][] = $shop_data['email'];
        $templateParams['sub']['[shop_contact]'][] = $shop_data['phone'];
        $templateParams['sub']['[detail_text]'][] = $lang_array['SC_FOOTER_TEXT'];
        $templateParams['sub']['[details_link]'][] = $link_url;
        $templateParams['sub']['[details_link_text]'][] = $lang_array['SC_FOOTER_LINK_TEXT'];
        $subject = $lang_array['SC_SUBJECT'];
        $body = "<br/>";
        $template_id = $this->container->getParameter('shopping_card_upto_100_template_id');
        $email_template_service = $this->container->get('email_template.service');
        $email_template_service->sendMailWithCustomParams($templateParams, $subject, $body, $template_id, 'SHOPPING_CARD_UPTO_100_CITIZEN');
        } catch (\Exception $ex) {

        }

        }

    /**
     * function for sending the mail notification for sending the mail notification to the purchaser
     * @param type $user_data
     * @param type $shop_data
     * @param type $shopping_card_details
     */
    public function sendEcommerceProductMailNotification($user_data, $shop_data, $product_name)
    {
        $email_template_service = $this->container->get('email_template.service'); //email template service.
        $postService = $this->container->get('post_detail.service');
        $receiver = $postService->getUserData($user_data['id'], true);
        $shop_id = $shop_data['id'];
        //get locale
        $locale = !empty($receiver[$user_data['id']]['current_language']) ? $receiver[$user_data['id']]['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $shop_profile_url = $this->container->getParameter('shop_profile_url');

       $mail_sub = sprintf($lang_array['ECOMMERCE_PRODUCT_PURCHASE_SUBJECT'], $product_name);
                $mail_body = sprintf($lang_array['ECOMMERCE_PRODUCT_PURCHASE_BODY'], $product_name);
                $mail_text = sprintf($lang_array['ECOMMERCE_PRODUCT_PURCHASE_TEXT'], $product_name, $shop_data['name']);
                $link = "<a href='$angular_app_hostname$shop_profile_url/$shop_id'>" . $lang_array['ECOMMERCE_PRODUCT_PURCHASE_LINK'] . "</a>"; //shop profile url
                $bodyData = $mail_text . '<br><br>' . $link;
        $emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, '', 'Prchase Ecommerce');

        return true;
    }
}
