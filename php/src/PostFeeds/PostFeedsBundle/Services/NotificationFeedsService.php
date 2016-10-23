<?php
namespace PostFeeds\PostFeedsBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use FOS\UserBundle\Model\UserInterface;
use PostFeeds\PostFeedsBundle\Document\MediaFeeds;
use Utility\UtilityBundle\Utils\Utility;

class NotificationFeedsService {

    protected $em;
    protected $dm;
    protected $container;
    protected $request;
    CONST USER_POST = 'USER';
    CONST SHOP_POST = 'SHOP';
    CONST CLUB_POST = 'CLUB';
    CONST TXN = 'TXN';
    CONST SOCIAL_PROJECT_POST = 'SOCIAL_PROJECT';
    CONST POST_AT_SOCIAL_PROJECT_WALL = 'POST_AT_SOCIAL_PROJECT_WALL';
    CONST POST = 'post';
    CONST SP_MEDIA_COMMENT= 'SP_MEDIA_COMMENT';
    CONST SP_MEDIA_COMMENT_ON_COMMENTED= 'SP_MEDIA_COMMENTED';
    CONST SP_POST_COMMENT= 'SP_POST_COMMENT';
    CONST SP_POST_COMMENT_ON_COMMENTED= 'SP_POST_COMMENTED';
    CONST SP_MEDIA_CTAGGING = 'SP_MEDIA';
    CONST SP_MEDIA_RATE = 'SP_MEDIA_RATE';
    CONST SP_MEDIA_COMMENT_RATE = 'SP_MEDIA_COMMENT_RATE';
    CONST MEDIA_COMMENT = 'MEDIA_COMMENT';
    CONST POST_COMMENT = 'POST_COMMENT';
    CONST SP_POST_CTAGGING = 'SP_POST';
    CONST SOCIAL_PROJECT_POST_URL = 'unouth/post/project/';
    CONST POST_NOTIFICATION = 'POST_NOTIFICATION';
    CONST SP_MEDIA_TAGGED_COMMENT= 'SP_MEDIA_TAGGED';
    CONST SP_POST_TAGGED_COMMENT= 'SP_POST_TAGGED';
    CONST SP_MEDIA_PAGE_URL = 'project/media/:mediaId/:parentId/:ownerId';
    CONST SP_POST_MEDIA_CTAGGING= 'SP_POST_MEDIA';
    CONST SP_POST_MEDIA_COMMENT= 'SP_POST_MEDIA_COMMENT';
    CONST SP_POST_MEDIA_COMMENT_ON_COMMENTED= 'SP_POST_MEDIA_COMMENTED';
    CONST SP_POST_MEDIA_TAGGED_COMMENT= 'SP_POST_MEDIA_TAGGED';
    const SP_POST_MEDIA = "SP_POST_MEDIA";
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
     * Send Post notification
     * @param array $data
     * @param string $type
     * @param string $post_type
     */
    public function sendPostNotification($data, $type, $post_type)
    {
        $post_type   = Utility::getUpperCaseString($post_type);
        $postService = $this->container->get('post_detail.service');
        $post_type_message = $post_url = '';
        $to_id = '';
        switch ($post_type) {
            CASE self::SOCIAL_PROJECT_POST:
                $to_id = $data['type_info']['project_owner']['id'];
                $post_type_message = self::POST_AT_SOCIAL_PROJECT_WALL;
                $post_url = self::SOCIAL_PROJECT_POST_URL.$data['to_info']['id'].'/'.$data['id'];
                break;
        }
        if ($to_id == '') {
            return true;
        }
        $post_data = array('post_data'=>$data, 'user_info' =>$data['user_id'], 'reciver_user_info' => $to_id, 'id' =>$data['id'],
            'post_type'=>$data['post_type'], 'post_type_message'=>$post_type_message, 'post_url'=>$post_url);
        $this->sendPostNotifications($post_data, $post_type, true, false);
    }

    /**
     * Send email, push and web notifications, when someone comment
     * @param object $sproject
     * @param array $user_info
     * @param string $type
     * @param string $item_id
     * @param array $tagging
     * @param object $postObject
     */
    public function sendCommentNotification($user_info, $type, $item_id, $tagging, $postObject,$tagged_data)
    {
        $postService = $this->_getPostService();
        $email_template_service = $this->_getEmailService(); //email template service.
        $angular_app_hostname   = $this->container->getParameter('angular_app_hostname');
        $mediaService = $this->_getMediaFeedService();
        $defaultLang = $this->container->getParameter('locale');
        $postType= $link_url = $postCommentedType= $postCTagging= '';
        $user_id = $user_info['id'];
        $sender_name = ucwords($user_info['first_name'] .' '. $user_info['last_name']);
        $sproject = null;
        $response = array();
        switch(strtoupper($type)){
            case self::MEDIA_COMMENT:
                $response = $this->_prepareMediaCommentData($item_id, $user_info);
                break;
            case self::POST_COMMENT:
                $projectInfo = $postObject->getTypeInfo();
                $sproject = $this->dm->getRepository('PostFeedsBundle:SocialProject')->find($projectInfo['id']);
                if(!$sproject) {
                    return ;
                }
                $project_id = $sproject->getId();

                $response['postType']= self::SP_POST_COMMENT;
                $response['postCommentedType'] = self::SP_POST_COMMENT_ON_COMMENTED;
                $response['postCTagging'] = self::SP_POST_CTAGGING;
                $response['mediaType']= self::SP_POST_TAGGED_COMMENT;
                $link_url =   $angular_app_hostname. self::SOCIAL_PROJECT_POST_URL.$project_id.'/'.$item_id;
                $response['replaceTexts'] = array(
                    'user'=>$sender_name,
                    'project'=>$sproject->getTitle()
                );
                $response['extraParams'] =array(
                    'project_id'=>$project_id
                  );
                $response['link'] = $link_url;
                $projectOwner = $postService->getUserData($sproject->getOwnerId());
                $response['info']= array('project_id'=>$project_id, 'project_owner'=>$projectOwner, 'project_name'=>$sproject->getTitle());
                break;
        }

        if(empty($response)){
            return;
        }

        $postOwnerId = $postObject->getUserId();
        // notifications to media owner
        if($postOwnerId!=$user_id){
            try{
                $postType = $response['postType'];
                $postOwner = $postService->getUserData($postOwnerId);
                $locale = !empty($postOwner['current_language']) ? $postOwner['current_language'] : $defaultLang;
                $lang_array = $this->container->getParameter($locale);
                $response['replaceTexts']['link'] = '<a href="'.$response['link'].'">'.$lang_array['CLICK_HERE'].'</a>';
                $mail_sub  = $postService->_updateByGivenText($lang_array[$postType.'_SUBJECT'], $response['replaceTexts']);
                $mail_body = $postService->_updateByGivenText($lang_array[$postType.'_BODY'], $response['replaceTexts']);
                $mail_text = $postService->_updateByGivenText($lang_array[$postType.'_TEXT'], $response['replaceTexts']);
                $link_text = $postService->_updateByGivenText($lang_array[$postType.'_LINK'], $response['replaceTexts']);
                $bodyData      = $mail_text."<br><br>".$link_text;

                // HOTFIX NO NOTIFY MAIL
                //$email_template_service->sendMail(array($postOwner), $bodyData, $mail_body, $mail_sub, $user_info['profile_image_thumb'], 'COMMENT_NOTIFICATION');
                $this->sendUserNotifications($user_id, array($postOwnerId=>$postOwner), $postType, 'comment', $item_id, true, true, $response['replaceTexts'], 'CITIZEN', $response['extraParams'], 'U', $response['info']);
            }catch(\Exception $e){
                echo $e->getMessage();
            }
        }

        // notifications to users, who has already commented
        $commentedAuthorIds = array();
        $comments = $postObject->getComments();
        foreach($comments as $comment){
            array_push($commentedAuthorIds, $comment->getUserId());
        }
        $commentedAuthIds = array_diff(array_unique($commentedAuthorIds), array($user_id, $postOwnerId));
        if(!empty($commentedAuthIds)){
            try{
                $commentedAuthors = $postService->getUserData($commentedAuthIds, true);
                $recieverByLanguage = $postService->getUsersByLanguage($commentedAuthors);
                $postCommentedType = $response['postCommentedType'];
                foreach($recieverByLanguage as $lng=>$recievers){
                    $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                    $lang_array = $this->container->getParameter($locale);
                    $response['replaceTexts']['link'] = '<a href="'.$response['link'].'">'.$lang_array['CLICK_HERE'].'</a>';
                    $mail_sub  = $postService->_updateByGivenText($lang_array[$postCommentedType.'_SUBJECT'], $response['replaceTexts']);
                    $mail_body = $postService->_updateByGivenText($lang_array[$postCommentedType.'_BODY'], $response['replaceTexts']);
                    $mail_text = $postService->_updateByGivenText($lang_array[$postCommentedType.'_TEXT'], $response['replaceTexts']);
                    $link_text = $postService->_updateByGivenText($lang_array[$postCommentedType.'_LINK'], $response['replaceTexts']);
                    $bodyData      = $mail_text."<br><br>".$link_text;

                    // HOTFIX NO NOTIFY MAIL
                    //$email_template_service->sendMail($recievers, $bodyData, $mail_body, $mail_sub, $user_info['profile_image_thumb'], 'COMMENT_NOTIFICATION');
                }
                $this->sendUserNotifications($user_id, $recievers, $postCommentedType, 'comment', $item_id, true, true, $response['replaceTexts'], 'CITIZEN', $response['extraParams'], 'U', $response['info']);
            }catch(\Exception $e){

            }
        }

        // @user tagging in comment notifications
        if(isset($tagging)){
            $rplc = array_merge($response['replaceTexts'], array('club' =>'[groupName]', 'shop' =>'[groupName]'));
            $postService->commentTaggingNotifications($tagging, $user_id , $item_id, $response['link'] , $response['postCTagging'], true, $response['extraParams'], false ,
                          $response['info'],
                          $rplc);
        }

        //get noraml tagged users
       $tagged_data = is_array($tagged_data) ? $tagged_data : array();
       $userService = $this->container->get('user_object.service');
       if($tagged_data){
            $mediaType = $response['mediaType'];
             $taggedCommentType = $type.'_'.$mediaType;
               try{
                   $taggedUserInfo = $taggedshopInfo = $taggedclubInfo = array();
                   switch($type){
                        case 'MEDIA_COMMENT' :
                          $_usrs = is_array($tagged_data['user']) ? $tagged_data['user'] : (array)$tagged_data['user'];
                          $_users = array_diff($_usrs, array($user_id));
                          $taggedUserInfo = $postService->getUserData($_users, true);
                          $taggedshopInfo = $tagged_data['shop'];
                          $taggedclubInfo = $tagged_data['club'];
                          break;
                        case 'POST_COMMENT' :
                            if(!empty($tagged_data['user'])){
                                foreach($tagged_data['user'] as $user){
                                    if($user_id!=$user['id']){
                                        $taggedUserInfo[$user['id']] = $user;
                                    }
                                }
                            }
                            $taggedshopInfo = array_map(function($_shop){
                                return $_shop['id'];
                            },$tagged_data['shop']);
                            $taggedclubInfo = array_map(function($_club){
                                return $_club['id'];
                            },$tagged_data['club']);
                            break;
                   }

                   if(!empty($taggedUserInfo)){
                        $this->sendCommentOnTaggedMailNotifications($taggedUserInfo, $user_info, $response['link'], 'user', $taggedCommentType, $response['replaceTexts']);
                        $this->sendUserNotifications($user_id, $taggedUserInfo, $taggedCommentType.'_USER', 'tagging', $item_id, true, true, $response['replaceTexts'], 'CITIZEN',$response['extraParams'], 'U', $response['info']);
                   }

                   if(!empty($taggedshopInfo)){
                        $shops = $taggedshopInfo;
                        $shopOwners = $userService->getShopsWithOwner($shops, array($user_id));
                        if(!empty($shopOwners)){
                            $response['replaceTexts']['shop'] = '[groupName]';

                            $this->sendCommentOnTaggedMailNotifications($shopOwners, $user_info, $response['link'], 'shop', $taggedCommentType, $response['replaceTexts']);
                            $this->sendUserNotifications($user_id, $shopOwners, $taggedCommentType.'_SHOP', 'tagging', $item_id, true, true, $response['replaceTexts'], 'CITIZEN', $response['extraParams'], 'U', $response['info'], $shopOwners);
                        }

                }

                if(!empty($taggedclubInfo)){
                    $clubMembers = array();
                    $clubs = $taggedclubInfo;
                    $excludeMembers = array($user_id);
                    foreach ($clubs as $club){
                        // get all club admins
                        $excludeMembers = array_merge($excludeMembers, array_keys($clubMembers));
                        $_clubMembers = $userService->groupMembersByGroupRole($club, 2, $excludeMembers);
                        $clubMembers += $_clubMembers;
                    }
                    if(!empty($clubMembers)){
                        $response['replaceTexts']['club'] = '[groupName]';
                        $this->sendCommentOnTaggedMailNotifications($clubMembers, $user_info, $response['link'], 'club', $taggedCommentType, $response['replaceTexts']);
                        $this->sendUserNotifications($user_id, $clubMembers, $taggedCommentType.'_CLUB', 'tagging', $item_id, true, true, $response['replaceTexts'], 'CITIZEN', $response['extraParams'], 'U', $response['info'], $clubMembers);
                    }
                }

               } catch (\Exception $ex) {
                   //var_dump($ex->getTrace());
               }

       }

    }

    public function sendRateNotifications(array $options, $type, $isEmail=true, $isWeb=false, $isPush=false){
        $to_id = isset($options['to_id']) ? $options['to_id'] : '';
        $from_id = isset($options['from_id']) ? $options['from_id'] : '';
        $project = isset($options['project']) ? $options['project'] : '';
        $projectId = isset($options['project_id']) ? $options['project_id'] : '';
        $rate = isset($options['rate']) ? $options['rate'] : '';
        $itemId = isset($options['item_id']) ? $options['item_id'] : '';
        $pOwner = isset($options['project_owner_id']) ? $options['project_owner_id'] : '';
        if(empty($to_id) or empty($from_id) or ($to_id == $from_id )){
            return;
        }
        try{
            $postService = $this->_getPostService();
            $email_template_service = $this->_getEmailService(); //email template service.
            $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
            $receiver = $postService->getUserData($to_id, true);
            //get locale
            $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);

            $sender = $postService->getUserData($from_id);
            $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));
            $href = $link = '';
            switch($type){
                case 'SP_MEDIA':
                    $urlParams = array('mediaId' => $itemId, 'parentId'=> $projectId, 'ownerId'=>  $pOwner);
                    $href =   $angular_app_hostname. $postService->_updateByGivenText(self::SP_MEDIA_PAGE_URL, $urlParams);
                    $link = "<a href='".$href."'>". $lang_array['CLICK_HERE']. "</a>";
                    break;
                case 'SP_MEDIA_COMMENT':
                    $urlParams = array('mediaId' => $itemId, 'parentId'=> $projectId, 'ownerId'=>  $pOwner);
                    $href =   $angular_app_hostname. $postService->_updateByGivenText(self::SP_MEDIA_PAGE_URL, $urlParams);
                    $link = "<a href='".$href."'>". $lang_array['CLICK_HERE']. "</a>";
                    break;
                case 'SP_POST':
                    $href =   $angular_app_hostname. self::SOCIAL_PROJECT_POST_URL.$projectId.'/'.$itemId;
                    $link = "<a href='".$href."'>". $lang_array['CLICK_HERE']. "</a>";
                    break;
                case 'SP_POST_COMMENT':
                    $href =   $angular_app_hostname. self::SOCIAL_PROJECT_POST_URL.$projectId.'/'.$itemId;
                    $link = "<a href='".$href."'>". $lang_array['CLICK_HERE']. "</a>";
                    break;
                case self::SP_POST_MEDIA_COMMENT:
                    $urlParams = array('mediaId' => $itemId, 'parentId'=> $projectId, 'ownerId'=>  $pOwner);
                    $href =   $angular_app_hostname. $postService->_updateByGivenText(self::SP_MEDIA_PAGE_URL, $urlParams);
                    $link = "<a href='".$href."'>". $lang_array['CLICK_HERE']. "</a>";
                    break;
                case self::SP_POST_MEDIA:
                    $urlParams = array('mediaId' => $itemId, 'parentId'=> $projectId, 'ownerId'=>  $pOwner);
                    $href =   $angular_app_hostname. $postService->_updateByGivenText(self::SP_MEDIA_PAGE_URL, $urlParams);
                    $link = "<a href='".$href."'>". $lang_array['CLICK_HERE']. "</a>";
                    break;

            }

            $replaceTexts = array(
                'user'=>$sender_name,
                'project'=>$project,
                'rate'=>$rate,
                'link'=>$link
            );

            $mail_sub  = $postService->_updateByGivenText($lang_array[$type.'_RATE_SUBJECT'], $replaceTexts);
            $mail_body = $postService->_updateByGivenText($lang_array[$type.'_RATE_BODY'], $replaceTexts);
            $mail_text = $postService->_updateByGivenText($lang_array[$type.'_RATE_TEXT'], $replaceTexts);
            $link_text = $postService->_updateByGivenText($lang_array[$type.'_RATE_LINK'], $replaceTexts);

            $bodyData      = $mail_text."<br><br>".$link_text;

            $extraParams = array(
                'project_id'=>$projectId,
                'msg_code'=>'rate'
            );
            $this->sendUserNotifications($from_id, $receiver, $type.'_RATE', $rate, $itemId, true, true, $replaceTexts, 'CITIZEN', $extraParams, 'U', array('project_id'=>$projectId, 'project_owner'=>$receiver[$to_id], 'project_name'=>$project));

            // HOTFIX NO NOTIFY MAIL
            //$email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'RATING_NOTIFICATION');
        } catch(\Exception $e){

        }
    }

    public function getWebNotification($notification, $users){
        $response = array();
        try{
            $type = $notification->getMessageType();
            switch(strtoupper($type)){
                case self::SP_MEDIA_RATE:
                    $response = $this->_formatSpMediaRateNotification($notification, $users);
                    break;
                case self::SP_MEDIA_COMMENT_RATE:
                    $response = $this->_formatSpMediaRateNotification($notification, $users);
                    break;
                case 'USER_TAGGED_IN_'.self::SP_MEDIA_CTAGGING.'_COMMENT':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'CLUB_TAGGED_IN_'.self::SP_MEDIA_CTAGGING.'_COMMENT':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'SHOP_TAGGED_IN_'.self::SP_MEDIA_CTAGGING.'_COMMENT':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case self::SP_MEDIA_COMMENT:
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case self::SP_MEDIA_COMMENT_ON_COMMENTED:
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'SP_POST_COMMENT_RATE':
                    $response = $this->_formatSpPostCommentRateNotification($notification, $users);
                    break;
                case 'SP_POST_RATE':
                   $response = $this->_formatSpPostCommentRateNotification($notification, $users);
                    break;
                case self::POST_AT_SOCIAL_PROJECT_WALL:
                    $response = $this->_formatSpSocialProjectPostNotification($notification, $users);
                    break;
                case 'USER_TAGGED_IN_'.self::SP_POST_CTAGGING.'_COMMENT':
                    $response = $this->_formatSpPostCommentNotification($notification, $users);
                    break;
                case 'CLUB_TAGGED_IN_'.self::SP_POST_CTAGGING.'_COMMENT':
                    $response = $this->_formatSpPostCommentNotification($notification, $users);
                    break;
                case 'SHOP_TAGGED_IN_'.self::SP_POST_CTAGGING.'_COMMENT':
                    $response = $this->_formatSpPostCommentNotification($notification, $users);
                    break;
                case self::SP_POST_COMMENT:
                    $response = $this->_formatSpPostCommentNotification($notification, $users);
                    break;
                case self::SP_POST_COMMENT_ON_COMMENTED:
                    $response = $this->_formatSpPostCommentNotification($notification, $users);
                    break;
                case 'USER_TAGGED_IN_'.self::SP_POST_CTAGGING:
                    $response = $this->_formatSpPostCommentNotification($notification, $users);
                    break;
                case 'CLUB_TAGGED_IN_'.self::SP_POST_CTAGGING:
                    $response = $this->_formatSpPostCommentNotification($notification, $users);
                    break;
                case 'SHOP_TAGGED_IN_'.self::SP_POST_CTAGGING:
                    $response = $this->_formatSpPostCommentNotification($notification, $users);
                    break;
                case 'USER_TAGGED_IN_'.self::SP_MEDIA_CTAGGING:
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'CLUB_TAGGED_IN_'.self::SP_MEDIA_CTAGGING:
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'SHOP_TAGGED_IN_'.self::SP_MEDIA_CTAGGING:
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'MEDIA_COMMENT_SP_MEDIA_TAGGED_USER':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'MEDIA_COMMENT_SP_MEDIA_TAGGED_CLUB':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'MEDIA_COMMENT_SP_MEDIA_TAGGED_SHOP':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'POST_COMMENT_SP_POST_TAGGED_USER':
                    $response = $this->_formatSpPostCommentNotification($notification, $users);
                    break;
                case 'POST_COMMENT_SP_POST_TAGGED_CLUB':
                    $response = $this->_formatSpPostCommentNotification($notification, $users);
                    break;
                case 'POST_COMMENT_SP_POST_TAGGED_SHOP':
                    $response = $this->_formatSpPostCommentNotification($notification, $users);
                    break;
                case 'USER_TAGGED_IN_'.self::SP_POST_MEDIA_CTAGGING:
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'CLUB_TAGGED_IN_'.self::SP_POST_MEDIA_CTAGGING:
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'SHOP_TAGGED_IN_'.self::SP_POST_MEDIA_CTAGGING:
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'MEDIA_COMMENT_SP_POST_MEDIA_TAGGED_USER':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'MEDIA_COMMENT_SP_POST_MEDIA_TAGGED_CLUB':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'MEDIA_COMMENT_SP_POST_MEDIA_TAGGED_SHOP':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'USER_TAGGED_IN_'.self::SP_POST_MEDIA_CTAGGING.'_COMMENT':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'CLUB_TAGGED_IN_'.self::SP_POST_MEDIA_CTAGGING.'_COMMENT':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'SHOP_TAGGED_IN_'.self::SP_POST_MEDIA_CTAGGING.'_COMMENT':
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case self::SP_POST_MEDIA_COMMENT:
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case self::SP_POST_MEDIA_COMMENT_ON_COMMENTED:
                    $response = $this->_formatSpMediaCommentTaggingNotification($notification, $users);
                    break;
                case 'SP_POST_MEDIA_COMMENT_RATE':
                    $response = $this->_formatSpMediaRateNotification($notification, $users);
                    break;
                case 'SP_POST_MEDIA_RATE':
                    $response = $this->_formatSpMediaRateNotification($notification, $users);
                    break;
            }
        }catch(\Exception $e){

        }
        return $response;
    }

    public function _formatSpMediaRateNotification($notification, $users){
        $mediaService = $this->_getMediaFeedService();
        $response = array();
        try{
            $notification_id = $notification->getId();
            $from_id= $notification->getFrom();
            $user_info = isset($users[$from_id]) ? $users[$from_id] : array();

            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();

            $media_id = $notification->getItemId();
            $media = $mediaService->getMediaObject($media_id);
            $info = $notification->getInfo();
            $project_id = isset($info['project_id']) ? $info['project_id'] : '';
            $pOwner = isset($info['project_owner']) ? $info['project_owner'] : array();
            if($media)
            {
                $mediaDetails = $mediaService->getGalleryMedia(array($media));
                $mediaDetail = array_shift($mediaDetails);
                $photo_info['projectId'] = $project_id;
                $photo_info["photoId"] = $media_id;
                $photo_info["projectOwner"] = $pOwner;
                $photo_info["project_title"] = isset($info['project_name']) ? $info['project_name'] : '';
                $photo_info["media_path"] = $mediaDetail['ori_image'];
                $photo_info["media_path_thumb"] = $mediaDetail['thum_image'];
                $photo_info["rate"]= $message;
                $response = array(
                    'notification_id'=>$notification_id,
                    'notification_from'=>$user_info,
                    'message_type' =>$message_type,
                    'message_status' =>'U',
                    'message'=>'rate',
                    'photo_info'=>$photo_info,
                    'extra_info'=>$info,
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate()
                        );
            }
        }catch(\Exception $e){
//            var_dump($e->getTrace());
        }
        return $response;
    }

    public function _formatSpMediaCommentTaggingNotification($notification, $users){
        $mediaService = $this->_getMediaFeedService();
        $response = array();
        try{
            $notification_id = $notification->getId();
            $from_id= $notification->getFrom();
            $user_info = isset($users[$from_id]) ? $users[$from_id] : array();

            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();

            $media_id = $notification->getItemId();
            $media = $mediaService->getMediaObject($media_id);
            $info = $notification->getInfo();
            $project_id = isset($info['project_id']) ? $info['project_id'] : '';
            $pOwner = isset($info['project_owner']) ? $info['project_owner'] : array();
            if($media)
            {
                $mediaDetails = $mediaService->getGalleryMedia(array($media));
                $mediaDetail = array_shift($mediaDetails);
                $photo_info['projectId'] = $project_id;
                $photo_info["photoId"] = $media_id;
                $photo_info["projectOwner"] = $pOwner;
                $photo_info["media_path"] = $mediaDetail['ori_image'];
                $photo_info["media_path_thumb"] = $mediaDetail['thum_image'];
                $photo_info["project_title"] = isset($info['project_name']) ? $info['project_name'] : '';

                $response = array(
                    'notification_id'=>$notification_id,
                    'notification_from'=>$user_info,
                    'message_type' =>$message_type,
                    'message_status' =>'U',
                    'message'=>$message,
                    'photo_info'=>$photo_info,
                    'extra_info'=>$info,
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate()
                        );
            }
        }catch(\Exception $e){
            //var_dump($e->getTrace());

            //echo $e->getMessage();
        }
        return $response;
    }

    private function _getMediaFeedService() {
        return $this->container->get('post_feeds.MediaFeeds'); //call media feed service
    }

    /**
     * send notification when some one post on their wall, shop or club or social project
     * @param array $post
     * @param string $post_type
     */
    public function sendPostNotifications(array $post, $post_type, $webNotification=false, $fbshare=false) {
        $post_service = $this->container->get('post_detail.service');
        Switch($post_type) {
            CASE self::USER_POST:
              $post_service->sendDashboardPostNotification($post, $webNotification, $fbshare);
              break;
            CASE self::SHOP_POST:
              $post_service->sendShopPostNotification($post, $webNotification, $fbshare);
              break;
            CASE self::CLUB_POST:
              $post_service->sendClubPostNotification($post, $webNotification, $fbshare);
              break;
            CASE self::TXN:
              $post_service->sendTransactionNotification($post, $webNotification, $fbshare);
              break;
            CASE self::SOCIAL_PROJECT_POST:
              $this->sendSocialDashboardPostNotification($post, $webNotification, $fbshare);
              break;
        }
    }

    /**
     * send notifications to user when some one post on his/her wall
     * @param array $post
     * @return boolean/array
     */
    protected function sendSocialDashboardPostNotification(array $post, $webNotification, $fbshare) {
        $email_template_service = $this->_getEmailService(); //email template service.
        $post_service = $this->container->get('post_detail.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $user_service = $this->container->get('user_object.service');
        $user_objects = $user_service->MultipleUserObjectService(array($post['user_info'], $post['reciver_user_info']));
        $sender       = $user_objects[$post['user_info']];
        $reciever_data = $user_objects[$post['reciver_user_info']];
        $link_url       = $angular_app_hostname.$post['post_url'];

        if ($fbshare) {
            $this->facebookPostShare(array(
              'user_id'=>$post['user_id'],
              'link'=> $this->getPublicPostUrl(array('postId'=>$post['id']), 'user'),
              'description'=>$post['description'],
              'media_thumb_link'=> isset($post['media_info'][0]) ? $post['media_info'][0]['media_thumb_link'] : ''
            ));
        }

        // tagging notifications
        try{
            $clubs = array_map(function($c){
                return isset($c['id']) ? $c['id'] : false;
            }, is_array($post['post_data']['club_tag']) ? $post['post_data']['club_tag'] : (array)$post['post_data']['club_tag']);

            $shops = array_map(function($s){
                return isset($s['id']) ? $s['id'] : false;
            }, is_array($post['post_data']['shop_tag']) ? $post['post_data']['shop_tag'] : (array)$post['post_data']['shop_tag']);
            $tagging = array(
                'user'=> (isset($post['post_data']['user_tag']) and !empty($post['post_data']['user_tag'])) ? $post['post_data']['user_tag'] : array(),
                'club'=> $clubs,
                'shop'=> $shops
            );

            $info = array('project_id'=>$post['post_data']['type_info']['id'], 'project_name'=>$post['post_data']['type_info']['project_title'], 'project_owner'=>$post['post_data']['type_info']['project_owner']);
            $extraPushInfo = array('project_id'=>$post['post_data']['type_info']['id']);
            $replace = array('user'=> ucwords($sender['first_name']. ' '.$sender['last_name']), 'project'=>$post['post_data']['type_info']['project_title']);
            $this->sendTaggingNotifications($tagging, $sender, $post['post_data']['id'], $link_url, self::SP_POST_CTAGGING, $info, $extraPushInfo, $replace);
        } catch(\Exception $e){

        }

        if ($sender['id'] == $reciever_data['id']) {
            return false;
        }
        $replace_txt = $this->getSocialPostReplaceText($post['post_data'], $sender);
        if ($webNotification) {
            $post_service->saveUserNotification($sender['id'], $reciever_data['id'], $post['post_type_message'], self::POST, $post['id']);
            $this->sendUserNotifications($sender['id'], array($reciever_data['id']=>$reciever_data), $post['post_type_message'], self::POST, $post['id'], false, true, $replace_txt);
        }
        try {
            $recieverByLanguage = $post_service->getUsersByLanguage(array($reciever_data['id']=>$reciever_data));
            $emailResponse = '';
            foreach ($recieverByLanguage as $lng=>$reciever_user_data) {
                $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                $lang_array = $this->container->getParameter($locale);
                $mail_data  = $this->getSocialPostMailData($post['post_data'], $sender, $lang_array);

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $email_template_service->sendMail($reciever_user_data, $mail_data['body_data'], $mail_data['mail_body'], $mail_data['mail_sub'], $sender['profile_image_thumb'], self::POST_NOTIFICATION);
            }
        } catch (\Exception $ex) {

        }
        return $emailResponse;
    }

    /**
     * getting the replace text for social project post
     * @param array $post_data
     * @param array $sender
     * @return array $return_array
     */
    public function getSocialPostReplaceText($post_data, $sender) {
       $post_type    = Utility::getUpperCaseString($post_data['post_type']);
       $return_array = array();
       switch ($post_type) {
            CASE self::SOCIAL_PROJECT_POST:
                $social_project_name = ucwords($post_data['to_info']['project_title']);
                $sender_name   = ucwords($sender['first_name'].' '. $sender['last_name']);
                $return_array = array($sender_name, $social_project_name);
                break;
        }
        return $return_array;
    }

    /**
     * getting the mail data for social project.
     * @param array $post_data
     * @param array $sender
     * @param array $lang_array
     * @return array $return_array
     */
    public function getSocialPostMailData($post_data, $sender, $lang_array) {
       $post_type    = Utility::getUpperCaseString($post_data['post_type']);
       $post_service = $this->container->get('post_detail.service');
       $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
       $return_array = array();
       $project_name = $type = '';
       switch ($post_type) {
            CASE self::SOCIAL_PROJECT_POST:
                $type = 'SP_SOCIAL_PROJECT';
                $project_name = $post_data['to_info']['project_title'];
                $href = $angular_app_hostname. self::SOCIAL_PROJECT_POST_URL.$post_data['to_info']['id'].'/'.$post_data['id'];
                $link = "<a href='".$href."'>". $lang_array['CLICK_HERE']. "</a>";
                break;
        }
        $replaceTexts = array(
           'user'=>  ucwords($sender['first_name']. ' '.$sender['last_name']),
           'project'=>$project_name,
           'link'=>$link
        );
        $mail_sub  = $post_service->_updateByGivenText($lang_array[$type.'_POST_SUBJECT'], $replaceTexts);
        $mail_body = $post_service->_updateByGivenText($lang_array[$type.'_POST_BODY'], $replaceTexts);
        $mail_text = $post_service->_updateByGivenText($lang_array[$type.'_POST_TEXT'], $replaceTexts);
        $link_text = $post_service->_updateByGivenText($lang_array[$type.'_POST_LINK'], $replaceTexts);
        $body_data = $mail_text."<br><br>".$link_text;
        return array('mail_sub'=>$mail_sub, 'body_data'=>$body_data, 'mail_body'=>$mail_body);
    }

    private function _getPostService(){
        return $this->container->get('post_detail.service');
    }

    private function _getEmailService(){
        return $this->container->get('email_template.service');
    }

    /**
     *
     * @param int $from_id
     * @param array $receivers  users information with name, email, current language etc.
     * @param string $msgtype
     * @param string $msg
     * @param string $itemId
     * @param boolean $isWeb
     * @param boolean $isPush
     * @param array $replaceText
     * @param string $clientType
     * @param array $extraParams
     * @param string $msgStatus
     * @param array $info
     * @return boolean
     */
    public function sendUserNotifications($from_id, $receivers, $msgtype, $msg, $itemId, $isWeb=true, $isPush=false, $replaceText=null, $clientType='CITIZEN', $extraParams=array(), $msgStatus='U', $info=array(), $extraInfo=array()){
        $defaultLang = $this->container->getParameter('locale');
        $push_object_service = $this->container->get('push_notification.service');
        $postService = $this->_getPostService();
        $to_id = array_keys($receivers);
        $pushInfo = array(
                  'from_id'=>  $from_id, 'to_id' => $to_id, 'msg_code'=>$msg, 'ref_type'=>$msgtype,
                    'ref_id'=> $itemId, 'role'=>1, 'client_type'=> $clientType,
                    'msg'=> ''
                );
        // save
        if($isWeb){
            $pushInfo['role']= ($isWeb and $isPush) ? 5 : 1;
            $postService->saveUserNotification($pushInfo['from_id'], $pushInfo['to_id'], $pushInfo['ref_type'], $pushInfo['msg_code'], $pushInfo['ref_id'],$msgStatus, $info, $pushInfo['role'], $extraInfo);
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
                print_r($e->getMessage());
            }
        }

        return true;
    }

    public function getPushNotificationText($notificationType, $replaceStr='', $lang=null,$msg=''){
        $locale = is_null($lang) ? $this->container->getParameter('locale') : $lang;
        $language_const_array = $this->container->getParameter($locale);
        $postService = $this->_getPostService();
        $text = '';
        //handle type if same message type has assigned to multiples
        $notificationType = in_array($notificationType, array()) ? $notificationType.'_'.$msg : $notificationType;
        switch(trim(strtoupper($notificationType))){
            case 'SP_MEDIA_RATE':
                $text = $language_const_array['PUSH_SP_MEDIA_RATE'];
                break;
            case 'SP_MEDIA_COMMENT_RATE':
                $text = $language_const_array['PUSH_SP_MEDIA_COMMENT_RATE'];
                break;
            case 'SP_POST_RATE':
                $text = $language_const_array['PUSH_SP_POST_RATE'];
                break;
            case 'SP_POST_COMMENT_RATE':
                $text = $language_const_array['PUSH_SP_POST_COMMENT_RATE'];
                break;
            case 'SP_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_SP_MEDIA_COMMENT'];
                break;
            case 'SP_MEDIA_COMMENTED':
                $text = $language_const_array['PUSH_SP_MEDIA_COMMENTED'];
                break;
            case 'SP_POST_COMMENT':
                $text = $language_const_array['PUSH_SP_POST_COMMENT'];
                break;
            case 'SP_POST_COMMENTED':
                $text = $language_const_array['PUSH_SP_POST_COMMENTED'];
                break;
            case 'POST_AT_SOCIAL_PROJECT_WALL':
                $text = $language_const_array['PUSH_SP_SOCIAL_PROJECT_POST'];
                break;
            case 'USER_TAGGED_IN_SP_POST':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_SP_POST'];
                break;
            case 'CLUB_TAGGED_IN_SP_POST':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_SP_POST'];
                break;
            case 'SHOP_TAGGED_IN_SP_POST':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_SP_POST'];
                break;
            case 'USER_TAGGED_IN_SP_MEDIA':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_SP_MEDIA'];
                break;
            case 'CLUB_TAGGED_IN_SP_MEDIA':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_SP_MEDIA'];
                break;
            case 'SHOP_TAGGED_IN_SP_MEDIA':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_SP_MEDIA'];
                break;
            case 'MEDIA_COMMENT_SP_MEDIA_TAGGED_USER':
                $text = $language_const_array['PUSH_SP_MEDIA_COMMENT_ON_USER_TAGGED'];
                break;
            case 'POST_COMMENT_SP_POST_TAGGED_USER':
                $text = $language_const_array['PUSH_SP_POST_COMMENT_ON_USER_TAGGED'];
                break;
            case 'MEDIA_COMMENT_SP_MEDIA_TAGGED_CLUB':
                $text = $language_const_array['PUSH_SP_MEDIA_COMMENT_ON_CLUB_TAGGED'];
                break;
            case 'MEDIA_COMMENT_SP_MEDIA_TAGGED_SHOP':
                $text = $language_const_array['PUSH_SP_MEDIA_COMMENT_ON_SHOP_TAGGED'];
                break;
            case 'POST_COMMENT_SP_MEDIA_TAGGED_CLUB':
                $text = $language_const_array['PUSH_SP_POST_COMMENT_ON_CLUB_TAGGED'];
                break;
            case 'POST_COMMENT_SP_MEDIA_TAGGED_SHOP':
                $text = $language_const_array['PUSH_SP_POST_COMMENT_ON_SHOP_TAGGED'];
                break;
            case 'USER_TAGGED_IN_SP_POST_MEDIA':
                $text = $language_const_array['PUSH_USER_TAGGED_IN_SP_POST_MEDIA'];
                break;
            case 'CLUB_TAGGED_IN_SP_POST_MEDIA':
                $text = $language_const_array['PUSH_CLUB_TAGGED_IN_SP_POST_MEDIA'];
                break;
            case 'SHOP_TAGGED_IN_SP_POST_MEDIA':
                $text = $language_const_array['PUSH_SHOP_TAGGED_IN_SP_POST_MEDIA'];
                break;
            case 'SP_POST_MEDIA_COMMENT':
                $text = $language_const_array['PUSH_SP_POST_MEDIA_COMMENT'];
                break;
            case 'SP_POST_MEDIA_COMMENTED':
                $text = $language_const_array['PUSH_SP_POST_MEDIA_COMMENTED'];
                break;
            case 'MEDIA_COMMENT_SP_POST_MEDIA_TAGGED_USER':
                $text = $language_const_array['PUSH_SP_POST_MEDIA_COMMENT_ON_USER_TAGGED'];
                break;
            case 'MEDIA_COMMENT_SP_POST_MEDIA_TAGGED_CLUB':
                $text = $language_const_array['PUSH_SP_POST_MEDIA_COMMENT_ON_CLUB_TAGGED'];
                break;
            case 'MEDIA_COMMENT_SP_POST_MEDIA_TAGGED_SHOP':
                $text = $language_const_array['PUSH_SP_POST_MEDIA_COMMENT_ON_SHOP_TAGGED'];
                break;
            case 'SP_POST_MEDIA_COMMENT_RATE':
                $text = $language_const_array['PUSH_SP_POST_MEDIA_COMMENT_RATE'];
                break;
            case 'SP_POST_MEDIA_RATE':
                $text = $language_const_array['PUSH_SP_POST_MEDIA_RATE'];
                break;
        }
        $returnText = '';
        if(!empty($text)){
            $rplcStrArr = is_array($replaceStr) ? $replaceStr : (array)$replaceStr;
            $text = $postService->_updateByGivenText($text, $rplcStrArr);
            $returnText = vsprintf($text, $rplcStrArr);
        }
        return $returnText;
    }

    /**
     * function for getting the notification for the post and comment rating
     * @param type $notification
     * @param type $users
     * @return type
     */
    public function _formatSpPostCommentRateNotification($notification, $users){
        $postService = $this->_getPostFeedService();
        $response = array();
        try{
            $notification_id = $notification->getId();
            $from_id= $notification->getFrom();
            $user_info = isset($users[$from_id]) ? $users[$from_id] : array();

            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();

            $post_id = $notification->getItemId();
            $post = $postService->getPostObject($post_id);

            if($post)
            {
                $type_info = $post->getTypeInfo();
                $info = $notification->getInfo();
                $project_id = isset($info['project_id']) ? $info['project_id'] : '';
                $pOwner = isset($info['project_owner']) ? $info['project_owner'] : array();
                $projectTitle = isset($type_info['project_title']) ? $type_info['project_title'] : '';
                $project_info['projectId'] = $project_id;
                $project_info["postId"] = $post_id;
                $project_info["projectOwner"] = $pOwner;
                $project_info["project_title"] = isset($info['project_name']) ? $info['project_name'] : '';
                $project_info["rate"]= $message;
                $response = array(
                    'notification_id'=>$notification_id,
                    'notification_from'=>$user_info,
                    'message_type' =>$message_type,
                    'message_status' =>'U',
                    'message'=>'rate',
                    'project_info'=>$project_info,
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate(),
                        );
            }
        }catch(\Exception $e){
//            var_dump($e->getTrace());
        }
        return $response;
    }

    public function _formatSpPostCommentNotification($notification, $users){
        $postService = $this->_getPostFeedService();
        $response = array();
        try{
            $notification_id = $notification->getId();
            $from_id= $notification->getFrom();
            $user_info = isset($users[$from_id]) ? $users[$from_id] : array();

            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();

            $post_id = $notification->getItemId();
            $post = $postService->getPostObject($post_id);
            $info = $notification->getInfo();
            $project_id = isset($info['project_id']) ? $info['project_id'] : '';
            $pOwner = isset($info['project_owner']) ? $info['project_owner'] : array();
            if($post)
            {
                $project_info['projectId'] = $project_id;
                $project_info["postId"] = $post_id;
                $project_info["projectOwner"] = $pOwner;
                $project_info["project_title"] = isset($info['project_name']) ? $info['project_name'] : '';
                $response = array(
                    'notification_id'=>$notification_id,
                    'notification_from'=>$user_info,
                    'message_type' =>$message_type,
                    'message_status' =>'U',
                    'message'=>$message,
                    'project_info'=>$project_info,
                    'extra_info'=>$info,
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate(),
                        );
            }
        }catch(\Exception $e){
//            var_dump($e->getTrace());
        }
        return $response;
    }

    public function _getPostFeedService() {
        $post_feed_service = $this->container->get('post_feeds.postFeeds');
        return $post_feed_service;
    }

    /**
     * getting the notification of social project type
     * @param object array $notification
     * @param array $users
     * @return type
     */
    public function _formatSpSocialProjectPostNotification($notification, $users){
        $postService = $this->_getPostFeedService();
        $response = array();
        try{
            $notification_id = $notification->getId();
            $from_id= $notification->getFrom();
            $user_info = isset($users[$from_id]) ? $users[$from_id] : array();

            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();

            $post_id = $notification->getItemId();
            $post = $postService->getPostObject($post_id);
            if($post)
            {
                $type_info  = $post->getTypeInfo();
                $project_id = isset($type_info['id']) ? $type_info['id'] : '';
                $powner     = isset($type_info['project_owner']) ? $type_info['project_owner'] : null;
                $porject_title = isset($type_info['project_title']) ? $type_info['project_title'] : '';
                $info       = $notification->getInfo();
                $project_info['projectId'] = $project_id;
                $project_info["postId"] = $post_id;
                $project_info['project_title'] = $porject_title;
                $project_info["projectOwner"] = $powner;
                $project_info["project_title"] = isset($type_info['project_title']) ? $type_info['project_title'] : '';
                $project_info["post"]= $message;
                $response = array(
                    'notification_id'=>$notification_id,
                    'notification_from'=>$user_info,
                    'message_type' =>$message_type,
                    'message_status' =>'U',
                    'message'=>$message,
                    'project_info'=>$project_info,
                    'is_read'=>(int)$notification->getIsRead(),
                    'create_date'=>$notification->getDate(),
                        );
            }
        }catch(\Exception $e){
//            var_dump($e->getTrace());
        }
        return $response;
    }

    public function sendTaggingNotifications($tagging, $sender, $postId, $postLink, $postType, $info=array(), $extraPushParams=array(), $replaceText=array()){
        $response = false;
        $userService = $this->container->get('user_object.service');
        $postType = strtoupper($postType);
        $sender_id = $sender['id'];
        if(!empty($tagging['user'])){
            $taggedUsers = array();
            foreach($tagging['user'] as $u){
                if($u['id'] != $sender_id and !in_array($u['id'], $taggedUsers)){
                    $taggedUsers[$u['id']] = $u;
                }
            }
            if(!empty($taggedUsers)){
                $this->sendTaggedInMailNotifications($taggedUsers, $sender, $postLink, 'user', $postType, $replaceText);
                $this->sendUserNotifications($sender_id, $taggedUsers, 'USER_TAGGED_IN_'.$postType, 'tagging', $postId, true, true, $replaceText, 'CITIZEN', $extraPushParams, 'U', $info);
            }
            $response = true;
        }
        if(!empty($tagging['shop'])){
            $shops = $tagging['shop'];
            $shopOwners = $userService->getShopsWithOwner($shops, array($sender_id));
            if(!empty($shopOwners)){
                $replaceText['shop'] = '[groupName]';
                $this->sendTaggedInMailNotifications($shopOwners, $sender, $postLink, 'shop', $postType, $replaceText);
                $this->sendUserNotifications($sender_id, $shopOwners, 'SHOP_TAGGED_IN_'.$postType, 'tagging', $postId, true, true, $replaceText, 'CITIZEN', $extraPushParams, 'U', $info, $shopOwners);
            }
            $response = true;
        }
        if(!empty($tagging['club'])){
            $clubMembers = array();
            $clubs = $tagging['club'];
            $excludeMembers = array($sender_id);
            foreach ($clubs as $_club){
                // get all club admins
                $excludeMembers = array_merge($excludeMembers, array_keys($clubMembers));
                $_clubMembers = $userService->groupMembersByGroupRole($_club, 2, $excludeMembers);
                $clubMembers += $_clubMembers;
            }
            if(!empty($clubMembers)){
                $replaceText['club'] = '[groupName]';
                $this->sendTaggedInMailNotifications($clubMembers, $sender, $postLink, 'club', $postType, $replaceText);
                $this->sendUserNotifications($sender_id, $clubMembers, 'CLUB_TAGGED_IN_'.$postType, 'tagging', $postId, true, true, $replaceText, 'CITIZEN', $extraPushParams, 'U', $info, $clubMembers);
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
        $postService = $this->container->get('post_detail.service');
        $emailResponse = array();

        $sender_name = ucwords(trim($sender['first_name'].' '.$sender['last_name']));
        $replaceTxt = is_array($replaceText) ? $replaceText : (array)$replaceText;
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

        if(!trim($type)){
            return;
        }
        $textType = strtoupper($type).'_';
        if(!empty($lang_prefix)){
            try{
                $recieverByLanguage = $postService->getUsersByLanguage($receivers);
                $emailResponse = '';
                foreach($recieverByLanguage as $lng=>$recievers){
                    $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                    $lang_array = $this->container->getParameter($locale);
                    $subject = $postService->_updateByGivenText($lang_array[$lang_prefix.'TAGGED_IN_'.$textType.'SUBJECT'], $replaceText);
                    $mail_text = $postService->_updateByGivenText($lang_array[$lang_prefix.'TAGGED_IN_'.$textType.'TEXT'], $replaceText);

                    $alink = '<a href="'.$postLink.'">'.$lang_array['CLICK_HERE'].'</a>';
                    $replaceTxt['link'] = $alink;
                    $link = $postService->_updateByGivenText($lang_array[$lang_prefix.'TAGGED_IN_'.$textType.'CLICK_HERE'],$replaceTxt);
                    $bodyData = $mail_text.'<br><br>'.$link;
                    $bodyTitle = $postService->_updateByGivenText($lang_array[$lang_prefix.'TAGGED_IN_'.$textType.'BODY'],$replaceTxt);
                    $thumb = $sender['profile_image_thumb'];

                    // HOTFIX NO NOTIFY MAIL
                    //$emailResponse = $email_template_service->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'POST_NOTIFICATION');
                }
            }catch(\Exception $e){

            }
        }
        return $emailResponse;
    }

    public function mediaFeedTagNotification($tagging, $sender_id, $element_id, $element_type){
        $postService = $this->_getPostService();
        $angular_app_hostname   = $this->container->getParameter('angular_app_hostname');
        $usersTagged = (isset($tagging['user']) and !empty($tagging['user'])) ? $tagging['user'] : array();
        $usersTagged = array_diff($usersTagged, array($sender_id));
        $receivers = $postService->getUserData($usersTagged, true);
        $sender = $postService->getUserData($sender_id, false);
        $taggings = array(
                'user'=> $receivers,
                'club'=> (isset($tagging['club']) and !empty($tagging['club'])) ? $tagging['club'] : array(),
                'shop'=> (isset($tagging['shop']) and !empty($tagging['shop'])) ? $tagging['shop'] : array()
            );
        if(empty($taggings['user']) and empty($taggings['club']) and empty($taggings['shop'])){
            return;
        }
        try{
            $projectData = $this->_getMediaFeedService()->getProjectByMediaId($element_id);
            if($projectData){
                $owenerId = $projectData->getOwnerId();
                $projectOwner = $postService->getUserData($owenerId);
                $info = array('project_id'=>$projectData->getId(), 'project_name'=>$projectData->getTitle(), 'project_owner'=>$projectOwner);
                $extraPushInfo = array('project_id'=>$projectData->getId());
                $replace = array('user'=> ucwords($sender['first_name']. ' '.$sender['last_name']), 'project'=>$projectData->getTitle());
                $urlParams = array('mediaId' => $element_id, 'parentId'=> $projectData->getId(), 'ownerId'=>  $projectData->getOwnerId());
                $link_url =   $angular_app_hostname. $postService->_updateByGivenText(self::SP_MEDIA_PAGE_URL, $urlParams);
                $this->sendTaggingNotifications($taggings, $sender, $element_id, $link_url, self::SP_MEDIA_CTAGGING, $info, $extraPushInfo, $replace);
            }else{
                $post = $this->_getPostFeedService()->getPostIdFromMediaId($element_id);
                if($post){
                    $postType = $post->getPostType();
                    $postTypeInfo = $post->getTypeInfo();
                    switch(strtoupper($postType)){
                        case self::SOCIAL_PROJECT_POST:
                            $projectData = $this->dm->getRepository('PostFeedsBundle:SocialProject')->find($postTypeInfo['id']);
                            $owenerId = $projectData->getOwnerId();
                            $projectOwner = $postService->getUserData($owenerId);
                            $info = array('project_id'=>$projectData->getId(), 'project_name'=>$projectData->getTitle(), 'project_owner'=>$projectOwner);
                            $extraPushInfo = array('project_id'=>$projectData->getId());
                            $replace = array('user'=> ucwords($sender['first_name']. ' '.$sender['last_name']), 'project'=>$projectData->getTitle());
                            $urlParams = array('mediaId' => $element_id, 'parentId'=> $projectData->getId(), 'ownerId'=>  $projectData->getOwnerId());
                            $link_url =   $angular_app_hostname. $postService->_updateByGivenText(self::SP_MEDIA_PAGE_URL, $urlParams);
                            $this->sendTaggingNotifications($taggings, $sender, $element_id, $link_url, self::SP_POST_MEDIA_CTAGGING, $info, $extraPushInfo, $replace);
                            break;
                    }
                }
            }
        }catch(\Exception $e){
            //echo $e->getMessage();
        }
    }

    /**
     *
     * @param array $receivers
     * @param type $sender
     * @param type $postLink
     * @param type $mailType
     * @param type $type
     * @param type $replaceText
     * @return type
     */
    protected function sendCommentOnTaggedMailNotifications(array $receivers, $sender, $postLink, $mailType, $type, $replaceText){

        $email_template_service = $this->container->get('email_template.service');
        $postService = $this->container->get('post_detail.service');
        $emailResponse = array();
        $sender_name = ucwords(trim($sender['first_name'].' '.$sender['last_name']));
        $replaceTxt = is_array($replaceText) ? $replaceText : (array)$replaceText;
        $mailType = strtoupper($mailType);
        $textType = strtoupper($type).'_';
        try{
            $recieverByLanguage = $postService->getUsersByLanguage($receivers);
            $emailResponse = '';
            foreach($recieverByLanguage as $lng=>$recievers){
                $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                $lang_array = $this->container->getParameter($locale);
                $replaceTxt['link'] = '<a href="'.$postLink.'">'.$lang_array['CLICK_HERE'].'</a>';
                $subject  = $postService->_updateByGivenText($lang_array[$textType.$mailType.'_SUBJECT'], $replaceTxt);
                $bodyTitle = $postService->_updateByGivenText($lang_array[$textType.$mailType.'_BODY'], $replaceTxt);
                $mail_text = $postService->_updateByGivenText($lang_array[$textType.$mailType.'_TEXT'], $replaceTxt);
                $link_text = $postService->_updateByGivenText($lang_array[$textType.$mailType.'_LINK'], $replaceTxt);
                $bodyData      = $mail_text."<br><br>".$link_text;
                $thumb = $sender['profile_image_thumb'];

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $email_template_service->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'POST_NOTIFICATION');
            }
        }catch(\Exception $e){
            echo $e->getMessage();
        }
        return $emailResponse;
    }

    public function postFeedTagNotification($tagging, $sender_id, $post){
        $postService = $this->_getPostService();
        $angular_app_hostname   = $this->container->getParameter('angular_app_hostname');
        $link_url = $angular_app_hostname. self::SOCIAL_PROJECT_POST_URL.$post['type_info']['id'].'/'.$post['id'];
        $usersTagged = (isset($tagging['user']) and !empty($tagging['user'])) ? $tagging['user'] : array();
        $usersTagged = array_diff($usersTagged, array($sender_id));
        $receivers = $postService->getUserData($usersTagged, true);
        $sender = $postService->getUserData($sender_id, false);
        $taggings = array(
                'user'=> $receivers,
                'club'=> (isset($tagging['club']) and !empty($tagging['club'])) ? $tagging['club'] : array(),
                'shop'=> (isset($tagging['shop']) and !empty($tagging['shop'])) ? $tagging['shop'] : array()
            );
        try{
            $info = array('project_id'=>$post['type_info']['id'], 'project_name'=>$post['type_info']['project_title'], 'project_owner'=>$post['type_info']['project_owner']);
            $extraPushInfo = array('project_id'=>$post['type_info']['id']);
            $replace = array('user'=> ucwords($sender['first_name']. ' '.$sender['last_name']), 'project'=>$post['type_info']['project_title']);
            $this->sendTaggingNotifications($taggings, $sender, $post['id'], $link_url, self::SP_POST_CTAGGING, $info, $extraPushInfo, $replace);
        }catch(\Exception $e){

        }
    }

    public function _prepareMediaCommentData($item_id, $userInfo){
        $response = array();
        try{
            $postService = $this->_getPostService();
            $mediaService = $this->_getMediaFeedService();
            $angular_app_hostname   = $this->container->getParameter('angular_app_hostname');
            $post = $this->_getPostFeedService()->getPostIdFromMediaId($item_id);
            $sender_name = ucwords($userInfo['first_name'] .' '. $userInfo['last_name']);
            if($post){
                $postType = $post->getPostType();
                $postTypeInfo = $post->getTypeInfo();
                switch(strtoupper($postType)){
                    case self::SOCIAL_PROJECT_POST:
                        $projectData = $this->dm->getRepository('PostFeedsBundle:SocialProject')->find($postTypeInfo['id']);
                        $response['postType']= self::SP_POST_MEDIA_COMMENT;
                        $response['postCommentedType'] = self::SP_POST_MEDIA_COMMENT_ON_COMMENTED;
                        $response['postCTagging'] = self::SP_POST_MEDIA_CTAGGING;
                        $response['mediaType']= self::SP_POST_MEDIA_TAGGED_COMMENT;
                        $urlParams = array('mediaId' => $item_id, 'parentId'=> $postTypeInfo['id'], 'ownerId'=>  $projectData->getOwnerId());
                        $link_url = $angular_app_hostname. $postService->_updateByGivenText(self::SP_MEDIA_PAGE_URL, $urlParams);
                        $response['replaceTexts'] = array(
                            'user'=>$sender_name,
                            'project'=>$projectData->getTitle()
                        );
                        $response['extraParams'] =array(
                            'project_id'=>$postTypeInfo['id']
                          );
                        $response['link'] = $link_url;
                        $response['info']= array('project_id'=>$postTypeInfo['id'], 'project_owner'=>$postTypeInfo['project_owner'], 'project_name'=>$projectData->getTitle());
                        break;
                }
            }else{
                $sproject =  $mediaService->getProjectByMediaId($item_id);
                if(!$sproject) {
                    return $response;
                }
                $response['postType']= self::SP_MEDIA_COMMENT;
                $response['postCommentedType'] = self::SP_MEDIA_COMMENT_ON_COMMENTED;
                $response['postCTagging'] = self::SP_MEDIA_CTAGGING;
                $response['mediaType']= self::SP_MEDIA_TAGGED_COMMENT;

                $project_id = $sproject->getId();
                $urlParams = array('mediaId' => $item_id, 'parentId'=> $project_id, 'ownerId'=>  $sproject->getOwnerId());
                $link_url =   $angular_app_hostname. $postService->_updateByGivenText(self::SP_MEDIA_PAGE_URL, $urlParams);
                $response['replaceTexts'] = array(
                            'user'=>$sender_name,
                            'project'=>$sproject->getTitle()
                        );
                $response['extraParams'] =array(
                    'project_id'=>$project_id
                  );
                $response['link'] = $link_url;
                $projectOwner = $postService->getUserData($sproject->getOwnerId());
                $response['info']= array('project_id'=>$project_id, 'project_owner'=>$projectOwner, 'project_name'=>$sproject->getTitle());
            }
        }catch(\Exception $e){

        }
        return $response;
    }
}
