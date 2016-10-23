<?php
/**
 * Share posts on friends emails 
 * @author Akhtar Khan
 *
 */
namespace Utility\UniversalNotificationsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use Utility\UniversalNotificationsBundle\Utils\MessageFactory as Msg;
use Applane\WrapperApplaneBundle\Controller\ApplaneController;
use Utility\UniversalNotificationsBundle\Document\SharingNotifications;


class ShareController  extends Controller{
    private $utilityService;
    private $userObjectService;
    private $postService;
    private $emailService;
    const userPost = "USER_POST";
    const clubPost = "CLUB_POST";
    const shopPost = "SHOP_POST";
    const sProjectPost = "SOCIAL_PROJECT_POST";
    const offer = "OFFER";
    const sProject = 'SOCIAL_PROJECT';
    const SOCIAL_PROJECT_POST_URL = 'unouth/post/project/';
    const coupon = 'COUPON';
    const shop = 'SHOP';
    const cardDetailUrl = 'unouth/card/:cardId/:userId';
    const tamoilOffer = "BCE";
    const tamoilOfferPublicUrl = "unouth/specialoffer/:offerId";
    
    public function postSharePostAction(Request $request){
        $uService = $this->_getUtilityService();
        $requiredParameters = array('user_id', "item_id", "item_type", "receivers");
        $this->_log('Entered in [ShareController:postSharePost]');
        if($uService->checkRequest($request, $requiredParameters)!==true){
            $this->_log('Parameter missing [ShareController:postSharePost]');
            $this->_response(1001);
        }
        $dm = $this->_getDocumentManager();
        $postService = $this->_getPostService();
        $data = $uService->getDeSerializeDataFromRequest($request);
        $itemData = array();
        try{
            switch(strtoupper($data['item_type'])){
                case self::userPost:
                    $this->_shareUserPost($data);
                    break;
                case self::clubPost:
                    $this->_shareClubPost($data);
                    break;
                case self::shopPost:
                    $this->_shareShopPost($data);
                    break;
                case self::sProjectPost:
                    $this->_shareSocialProjectPost($data);
                    break;
                case self::offer:
                    $this->_shareOffer($data, self::offer);
                    break;
                case self::sProject:
                    $this->_shareSocialProject($data);
                    break;
                case self::coupon:
                    $this->_shareOffer($data, self::coupon);
                    break;
                case self::shop:
                    $this->_shareShop($data);
                    break;
                case self::tamoilOffer:
                    $this->_shareTamoilOffer($data);
                    break;
                default :
                    $this->_response(1114);
                    break;
            }
        }catch(\Exception $e){
            $this->_log($e->getMessage());
            $this->_response(1035);
        }
        $this->_response(101);
    }
    
    private function _shareSocialProjectPost($data){
        $this->_log('Entered in [ShareController:_shareSocialProjectPost]');
        $dm = $this->_getDocumentManager();
        $postService = $this->_getPostService();
        $post = $dm->getRepository("PostFeedsBundle:PostFeeds")->find($data['item_id']);
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        if(!$post){ 
            $this->_response(1101);
        }
        $mediaObject = $post->getMedia();
        $mediaFeedService = $this->container->get('post_feeds.MediaFeeds');
        $post_media= $mediaFeedService->getGalleryMedia($mediaObject);
        $postImage = '';
        try{
            if(!empty($post_media)){
                $postImage = $post_media[0]['thum_image'];
            }
        }catch(\Exception $e){
            
        }
        $postTypeInfo = $post->getTypeInfo();
        $project_id = $postTypeInfo['id'];
        $ptext = $post->getDescription();
        $itemData = array();
        $itemData['text'] = $this->truncateString($ptext, 100);
        $itemData['link'] = $angular_app_hostname.self::SOCIAL_PROJECT_POST_URL. $project_id.'/'.$data['item_id'];
        $itemData['post_image'] = !empty($postImage) ? '<img src="'.$postImage.'" style="max-width:340px;" />' : '';
        $postOwnerId = $post->getUserId();
        $users = $this->_getUserObjectService()->MultipleUserObjectService(array($data['user_id'], $postOwnerId));
        $sender = isset($users[$data['user_id']]) ? $users[$data['user_id']] : array();
        $postOwner = isset($users[$postOwnerId]) ? $users[$postOwnerId] : array();
        if(empty($sender)){
            $this->_log('Sender does not exists [ShareController:_shareSocialProjectPost]');
            $this->_response(1021);
        }
        $locale = !empty($sender['current_language']) ? $sender['current_language'] : $this->container->getParameter('locale');
        $lang = $this->container->getParameter($locale);
        $sender_name = trim(ucwords($sender['first_name'].' '.$sender['last_name']));
        $postOwnerName = trim(ucwords($postOwner['first_name'].' '.$postOwner['last_name']));
        $replaceTexts = array(
            'user'=>$sender_name,
            'post_text'=> trim($itemData['text'])==''?'<br>' : $itemData['text']
        );
        $extrainfo = array('[view_post_link]'=>$itemData['link'],
            '[post_image]'=>$itemData['post_image'],
            '[post_image_show]'=>!empty($itemData['post_image']) ? 'block' : 'none',
            '[user_thumb]'=>$postOwner['profile_image_thumb'],
             '[post_author]'=>$postOwnerName
                );
        $response = $this->_preparePostOrSpData($sender, $replaceTexts, self::sProjectPost, $sender_name, $extrainfo);
        
        $this->_sendEmail($response, $data);
        return true;
    }
    
    private function _shareShopPost($data){
        $this->_log('Entered in [ShareController:_shareShopPost]');
        $dm = $this->_getDocumentManager();
        $postService = $this->_getPostService();
        $post = $dm->getRepository("StoreManagerPostBundle:StorePosts")->find($data['item_id']);
        if(!$post){ 
            $this->_response(1101);
        }
        $post_media = $dm->getRepository('StoreManagerPostBundle:StorePostsMedia')
                    ->findOneBy(array('post_id'=>$data['item_id'], 'media_status'=>1));
        $postImage = '';
        try{
            if($post_media){
                $post_media_name = $post_media->getMediaName();
                $postImage = $this->getS3BaseUri() . '/uploads/stores/posts/thumb/' . $data['item_id'] . '/' . $post_media_name;
            }
        }catch(\Exception $e){
            
        }
        $ptext = $post->getStorePostDesc();
        $itemData = array();
        $itemData['text'] = $this->truncateString($ptext, 100);
        $itemData['link'] = $postService->getPublicPostUrl(array('postId'=>$post->getId(), 'shopId'=>$post->getStoreId()), 'shop');
        $itemData['post_image'] = !empty($postImage) ? '<img src="'.$postImage.'" style="max-width:340px;" />' : '';
        $postOwnerId = $post->getStorePostAuthor();
        $users = $this->_getUserObjectService()->MultipleUserObjectService(array($data['user_id'], $postOwnerId));
        $sender = isset($users[$data['user_id']]) ? $users[$data['user_id']] : array();
        $postOwner = isset($users[$postOwnerId]) ? $users[$postOwnerId] : array();
        if(empty($sender)){
            $this->_log('Sender does not exists [ShareController:_shareShopPost]');
            $this->_response(1021);
        }
        $locale = !empty($sender['current_language']) ? $sender['current_language'] : $this->container->getParameter('locale');
        $lang = $this->container->getParameter($locale);
        $sender_name = trim(ucwords($sender['first_name'].' '.$sender['last_name']));
        $postOwnerName = trim(ucwords($postOwner['first_name'].' '.$postOwner['last_name']));
        $replaceTexts = array(
            'user'=>$sender_name,
            'post_text'=> trim($itemData['text'])==''?'<br>' : $itemData['text']
        );
        $extrainfo = array('[view_post_link]'=>$itemData['link'],
            '[post_image]'=>$itemData['post_image'],
            '[post_image_show]'=>!empty($itemData['post_image']) ? 'block' : 'none', 
            '[user_thumb]'=>$postOwner['profile_image_thumb'],
             '[post_author]'=>$postOwnerName
                );
        $response = $this->_preparePostOrSpData($sender, $replaceTexts, self::shopPost, $sender_name, $extrainfo);
        
        $this->_sendEmail($response, $data);
    }
    
    private function _shareUserPost($data){
        $this->_log('Entered in [ShareController:_shareUserPost]');
        $dm = $this->_getDocumentManager();
        $postService = $this->_getPostService();
        $post = $dm->getRepository("DashboardManagerBundle:DashboardPost")->find($data['item_id']);
        if(!$post){ 
            $this->_response(1101);
        }
        $post_media = $dm->getRepository('DashboardManagerBundle:DashboardPostMedia')
                    ->findOneBy(array('post_id'=>$data['item_id'], 'media_status'=>1));
        $postImage = '';
        try{
            if($post_media){
                $post_media_name = $post_media->getMediaName();
                $postImage = $this->getS3BaseUri() . '/uploads/documents/dashboard/post/thumb/' . $data['item_id'] . '/' . $post_media_name;
            }
        }catch(\Exception $e){
            
        }
        $ptext = $post->getDescription();
        $itemData = array();
        $itemData['text'] = $this->truncateString($ptext, 100);
        $itemData['link'] = $postService->getPublicPostUrl(array('postId'=>$post->getId()), 'user');
        $itemData['post_image'] = !empty($postImage) ? '<img src="'.$postImage.'" style="max-width:340px;" />' : '';
        $postOwnerId = $post->getUserId();
        $users = $this->_getUserObjectService()->MultipleUserObjectService(array($data['user_id'], $postOwnerId));
        $sender = isset($users[$data['user_id']]) ? $users[$data['user_id']] : array();
        $postOwner = isset($users[$postOwnerId]) ? $users[$postOwnerId] : array();
        if(empty($sender)){
            $this->_log('Sender does not exists [ShareController:_shareUserPost]');
            $this->_response(1021);
        }
        $locale = !empty($sender['current_language']) ? $sender['current_language'] : $this->container->getParameter('locale');
        $lang = $this->container->getParameter($locale);
        $sender_name = trim(ucwords($sender['first_name'].' '.$sender['last_name']));
        $postOwnerName = trim(ucwords($postOwner['first_name'].' '.$postOwner['last_name']));
        $replaceTexts = array(
            'user'=>$sender_name,
            'post_text'=> trim($itemData['text'])==''?'<br>' : $itemData['text']
        );
        $extrainfo = array('[view_post_link]'=>$itemData['link'],
            '[post_image]'=>$itemData['post_image'],
            '[post_image_show]'=>!empty($itemData['post_image']) ? 'block' : 'none', 
            '[user_thumb]'=>$postOwner['profile_image_thumb'],
             '[post_author]'=>$postOwnerName
                );
        $response = $this->_preparePostOrSpData($sender, $replaceTexts, self::userPost, $sender_name, $extrainfo);
        
        $this->_sendEmail($response, $data);
    }
    
    private function _shareClubPost($data){
        $this->_log('Entered in [ShareController:_shareClubPost]');
        $dm = $this->_getDocumentManager();
        $postService = $this->_getPostService();
        $post = $dm->getRepository("PostPostBundle:Post")->find($data['item_id']);
        if(!$post){ 
            $this->_response(1101);
        }
        $post_media = $dm->getRepository('PostPostBundle:PostMedia')
                    ->findOneBy(array('post_id'=>$data['item_id'], 'media_status'=>1));
        $postImage = '';
        try{
            if($post_media){
                $post_media_name = $post_media->getMediaName();
                $postImage = $this->getS3BaseUri() . '/uploads/documents/groups/posts/thumb/' . $data['item_id'] . '/' . $post_media_name;
            }
        }catch(\Exception $e){
            $this->_log($e->getMessage().' [Line: '.$e->getLine().']');
        }
        $ptext = $post->getPostDesc();
        $itemData = array();
        $itemData['text'] = $this->truncateString($ptext, 100);
        $itemData['link'] = $postService->getPublicPostUrl(array('postId'=>$post->getId(), 'clubId'=>$post->getPostGid()), 'club');
        $itemData['post_image'] = !empty($postImage) ? '<img src="'.$postImage.'" style="max-width:340px;" />' : '';
        $postOwnerId = $post->getPostAuthor();
        $users = $this->_getUserObjectService()->MultipleUserObjectService(array($data['user_id'], $postOwnerId));
        $sender = isset($users[$data['user_id']]) ? $users[$data['user_id']] : array();
        $postOwner = isset($users[$postOwnerId]) ? $users[$postOwnerId] : array();
        if(empty($sender)){
            $this->_log('Sender does not exists [ShareController:_shareClubPost]');
            $this->_response(1021);
        }
        $locale = !empty($sender['current_language']) ? $sender['current_language'] : $this->container->getParameter('locale');
        $lang = $this->container->getParameter($locale);
        $sender_name = trim(ucwords($sender['first_name'].' '.$sender['last_name']));
        $postOwnerName = trim(ucwords($postOwner['first_name'].' '.$postOwner['last_name']));
        $replaceTexts = array(
            'user'=>$sender_name,
            'post_text'=> trim($itemData['text'])==''?'<br>' : $itemData['text']
        );
         $extrainfo = array('[view_post_link]'=>$itemData['link'],
             '[post_image]'=>$itemData['post_image'], 
             '[post_image_show]'=>!empty($itemData['post_image']) ? 'block' : 'none',
             '[user_thumb]'=>$postOwner['profile_image_thumb'],
             '[post_author]'=>$postOwnerName
             );
        $response = $this->_preparePostOrSpData($sender, $replaceTexts, self::clubPost, $sender_name, $extrainfo);
        
        $this->_sendEmail($response, $data);
    }
    
    private function _shareOffer($data, $type){
        $this->_log('Entered in [ShareController:_shareOffer]');
        $applane = new ApplaneController();
        $offerJson = $applane->process('query', array('query'=>'{"$collection":"sixc_offers","$filter":{"_id":"'.$data['item_id'].'"}}'));
        $offers = json_decode($offerJson, true);
        try{
            $offerResult = $offers['response']['result'];
            if(!empty($offerResult)){
                $offer = $offerResult[0];
                $this->_log('Getting sender ['.$data['user_id'].'] information');
                $sender = $this->_getUserObjectService()->UserObjectService($data['user_id']);
                if(empty($sender)){
                    $this->_log('Sender does not exists [ShareController:_shareOffer]');
                    $this->_response(1021);
                }
                $options = array();
                switch(strtoupper($type)){
                    case self::offer:
                        $options = $this->_prepareOfferData($offer, $sender);
                        break;
                    case self::coupon:
                        $options = $this->_prepareCouponData($offer, $sender);
                        break;
                }
                if(!empty($options)){
                    $this->_sendEmail($options, $data);
                }
            }
        } catch (\Exception $ex) {
            $this->_log($ex->getMessage().' [Line : '. $ex->getLine().']');
        }
    }
    
    private function _prepareOfferData($card, $sender){
        $this->_log('Getting shop ['.$card['shop_id']['_id'].'] data');
        $shop = $this->_getUserObjectService()->getStoreObjectService($card['shop_id']['_id']);
        $locale = !empty($sender['current_language']) ? $sender['current_language'] : $this->container->getParameter('locale');
        $lang = $this->container->getParameter($locale);
        $catId = isset($card['shop_id']['category_id']) ? $card['shop_id']['category_id']['_id'] : 0;
        $this->_log('Getting business category ['.$catId.'] info');
        $businessCatService = $this->container->get('business_category.service');
        $cats = $businessCatService->getBusinessCategoriesByLangAndIds($locale, array($catId));
        $shopName = (isset($card['shop_id']['name']) and !empty($card['shop_id']['name'])) ? $card['shop_id']['name'] : (isset($shop['name']) ? $shop['name'] : (isset($shop['businessName']) ? $shop['businessName'] : ''));
        $cardDiscount = isset($card['discount']) ? $card['discount'] : 0;
        $cardDiscount .= '%';
        $data['[card_amount]'] = isset($card['value']) ? $card['value'] : 0;
        $data['[card_title]'] = $shopName;
        $catId = isset($card['shop_id']['category_id']) ? $card['shop_id']['category_id']['_id'] : 0;
        
        $data['[card_location]'] = isset($card['shop_id']['address_l2']) ? $card['shop_id']['address_l2'] : '';
        $cardImg = isset($card['imageurl']) ? $card['imageurl'] : '';
        $cardImgArray = explode(',', $cardImg);
        $data['[card_shop_img]'] = trim($cardImgArray[0]);
        if(empty($data['[card_shop_img]'])){
            $data['[card_shop_img]'] = isset($shop['thumb_path']) ?
                    $shop['thumb_path'] : (
                        isset($cats[$catId]['image_thumb']) ? 
                        $cats[$catId]['image_thumb'] : 
                        $this->container->getParameter('store_default_image_thumb')
                    );
        }
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $link = $angular_app_hostname.$this->_replaceTexts(self::cardDetailUrl, array('cardId'=>$card['_id'], 'userId'=> base64_encode($sender['id'])));
        $replaceTexts = array(
            'user'=> trim(ucwords($sender['first_name'].' '.$sender['last_name'])),
            'shop'=>$shopName,
            'discount'=>$cardDiscount
        );
        $data['[card_desc]'] = isset($card['description']) ? $card['description'] : '';
        $data['[phone_no]'] = isset($card['shop_id']['mobile_no']) ? $card['shop_id']['mobile_no'] : '';
        $data['[card_email]'] = isset($card['shop_id']['email_address']) ? $card['shop_id']['email_address'] : '';
        $data['[phone_text]'] = $lang['PHONE_TEXT'];
        $data['[view_offer_link]'] = $link;
        $data['[dear_user]'] = $lang['DEAR_USER'];
        $data['[body_title]'] = $this->_replaceTexts($lang['SHARE_OFFER_BODY'], $replaceTexts);
        $data['[click_here_link]'] = $this->_replaceTexts($lang['SHARE_OFFER_LINK_TEXT'], $replaceTexts);
        $data['[view_shop_link]'] = $angular_app_hostname.'shop/view/'.$card['shop_id']['_id'];
        
        $subject = $this->_replaceTexts($lang['SHARE_OFFER_SUBJECT'], $replaceTexts);
        $data['[body_text]'] = $this->_replaceTexts($lang['SHARE_OFFER_TEXT'], $replaceTexts);
        $response = array(
            'templateParams'=>array('section'=>$data),
            'subParams'=> array_keys($data),
            'subject'=>$subject,
            'bodyData'=>'<br>',
            'templateId'=>  $this->container->getParameter('sendgrid_share_offer_template')
        );
        
        $this->_log('Data prepaired for email');
        
        return $response;
    }
    
    private function _prepareCouponData($coupon, $sender){
        $this->_log('Getting shop ['.$coupon['shop_id']['_id'].'] data');
        $shop = $this->_getUserObjectService()->getStoreObjectService($coupon['shop_id']['_id']);
        $locale = !empty($sender['current_language']) ? $sender['current_language'] : $this->container->getParameter('locale');
        $lang = $this->container->getParameter($locale);
        $catId = isset($coupon['shop_id']['category_id']) ? $coupon['shop_id']['category_id']['_id'] : 0;
        $this->_log('Getting business category ['.$catId.'] info');
        $businessCatService = $this->container->get('business_category.service');
        $cats = $businessCatService->getBusinessCategoriesByLangAndIds($locale, array($catId));
        $shopName = (isset($coupon['shop_id']['name']) and !empty($coupon['shop_id']['name'])) ? $coupon['shop_id']['name'] : (isset($shop['name']) ? $shop['name'] : (isset($shop['businessName']) ? $shop['businessName'] : ''));
        $couponDiscount = isset($coupon['discount']) ? $coupon['discount'] : 0;
        $couponDiscount .= '%';
        $data['[coupon_amount]'] = isset($coupon['value']) ? $coupon['value'] : 0;
        $data['[coupon_title]'] = $shopName;
        $catId = isset($coupon['shop_id']['category_id']) ? $coupon['shop_id']['category_id']['_id'] : 0;
        $couponImg = isset($coupon['shop_id']['shop_thumbnail_img']) ? $coupon['shop_id']['shop_thumbnail_img'] : '';
        $couponImgArray = explode(',', $couponImg);
        $data['[coupon_shop_img]'] = trim($couponImgArray[0]);

        if(empty($data['[coupon_shop_img]'])){
            $data['[coupon_shop_img]'] = isset($shop['thumb_path']) ?
                    $shop['thumb_path'] : (
                        isset($cats[$catId]['image_thumb']) ? 
                        $cats[$catId]['image_thumb'] : 
                        $this->container->getParameter('store_default_image_thumb')
                    );
        }
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $link = $angular_app_hostname.'offer/2';
        $replaceTexts = array(
            'user'=> trim(ucwords($sender['first_name'].' '.$sender['last_name'])),
            'shop'=>$shopName,
            'discount'=>$couponDiscount
        );
        $data['[shop_desc]'] = $this->truncateString(isset($shop['description']) ? $shop['description'] : '', 200);
        $data['[view_offer_link]'] = $link;
        $data['[dear_user]'] = $lang['DEAR_USER'];
        $data['[body_title]'] = $this->_replaceTexts($lang['SHARE_COUPON_BODY'], $replaceTexts);
        $data['[click_here_link]'] = $this->_replaceTexts($lang['SHARE_COUPON_LINK_TEXT'], $replaceTexts);
        
        if(trim($data['[coupon_shop_img]'])==''){
            $data['[coupon_shop_img]'] = $this->container->getParameter('store_default_image_thumb');
        }
        
        $subject = $this->_replaceTexts($lang['SHARE_COUPON_SUBJECT'], $replaceTexts);
        $data['[body_text_1]'] = $this->_replaceTexts($lang['SHARE_COUPON_TEXT_1'], $replaceTexts);
        $data['[body_text_2]'] = $this->_replaceTexts($lang['SHARE_COUPON_TEXT_2'], $replaceTexts);
        $response = array(
            'templateParams'=>array('section'=>$data),
            'subParams'=> array_keys($data),
            'subject'=>$subject,
            'bodyData'=>'<br>',
            'templateId'=>  $this->container->getParameter('sendgrid_share_coupon_template')
        );
        
        $this->_log('Data prepaired for email');
        
        return $response;
    }
    
    private function _setParams($params, $templateParams, $users, $options){
        foreach($users as $toUser){
            foreach($params as $param){
                $templateParams['sub'][$param][] = "$param";
            }
            $templateParams['to'][] = $toUser['email'];
            if(isset($options['reciever_name'])){
                $templateParams['sub']['[reciever_name]'][] = $options['reciever_name'].',';
            }else{
                $templateParams['sub']['[reciever_name]'][] = ucwords(trim($toUser['name']) ? trim($toUser['name']) : "[dear_user]").',';
            }
            
        }
        
        return $templateParams;
    }
    
    private function _sendEmail($options, $data){
        $dm = $this->_getDocumentManager();
        $batch=100;
        $_receivers = array();
        $ec=0;
        $emailReceivers=array();
        $this->_log('Mail send process start on [ShareController:_sendEmail]');
        foreach($data['receivers'] as $receiver){
            $ec++;
            array_push($_receivers, $receiver);
            if($ec%$batch==0 or $ec==count($data['receivers'])){
                $templateParams = $this->_setParams($options['subParams'], $options['templateParams'], $_receivers, $options);
                $this->_getEmailService()->sendMailWithCustomParams($templateParams, $options['subject'], $options['bodyData'], $options['templateId'], 'OFFER_SHARE');
                $_receivers = array();
                $emailReceivers = array_merge($emailReceivers, $templateParams['to']);
            }
        }
        $this->_log('Email sent to: '. json_encode($emailReceivers));
        // update mongo collection
        foreach($emailReceivers as $email){
            $shareOffer = new SharingNotifications();
            $time = new \DateTime('now');
            $shareOffer->setItemId($data['item_id'])
                    ->setEmail($email)
                    ->setItemType($data['item_type'])
                    ->setUserId($data['user_id'])
                    ->setCreatedDate($time)
                    ->setUpdatedDate($time);
            $dm->persist($shareOffer);
        }
        $dm->flush();
    }
    
    private function _shareSocialProject($data){
        $this->_log('Entered in [ShareController:_shareSocialProject]');
        $dm = $this->_getDocumentManager();
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        try{
            $project = $dm->getRepository('PostFeedsBundle:SocialProject')
                            ->find($data['item_id']);
            if(!$project){
                $this->_response(1104);
            }
            $postFeedsService = $this->container->get('post_feeds.postFeeds');
            $cover_data = $project->getCoverImg() ? $project->getCoverImg() : array();
            $cover_info = $postFeedsService->getCoverImageinfo($cover_data);
            $userIds = array($project->getOwnerId(), $data['user_id']);
            $users = $this->_getUserObjectService()->MultipleUserObjectService($userIds);
            $sender = $users[$data['user_id']];
            $projectOwner = $users[$project->getOwnerId()];
            $projectOwnerName = trim(ucwords($projectOwner['first_name'].' '.$projectOwner['last_name']));
            if(empty($sender)){
                $this->_log('Sender does not exists [ShareController:_shareOffer]');
                $this->_response(1021);
            }
            
            $projectUrl = $angular_app_hostname. str_replace(':projectId', $project->getId(), $this->container->getParameter('social_project_url'));
            $locale = !empty($sender['current_language']) ? $sender['current_language'] : $this->container->getParameter('locale');
            $lang = $this->container->getParameter($locale);
            $senderName = trim(ucwords($sender['first_name'].' '.$sender['last_name']));
            $desc = $this->truncateString($project->getDescription(), 150);
            $replaceTexts = array(
                'user'=>$senderName,
                'project'=>$project->getTitle(),
                'desc_project'=> trim($desc)=='' ? '<br>' : $desc
            );
            
            $extrainfo = array(
                '[view_project_link]'=>$projectUrl,
                '[project_image_url]'=>isset($cover_info['thum_image']) ? $cover_info['thum_image'] : '',
                '[promoter_email]'=>$project->getEmail(),
                '[project]'=>$project->getTitle(),
                '[creation_date]'=>$project->getCreatedAt()->format('d-m-Y'),
                '[project_promoter_text]'=>$lang['PROMOTER_TEXT'],
                '[creation_date_text]'=>$lang['CREATION_DATE'],
                '[project_promoter]'=>$projectOwnerName,
                '[descrption_text]'=>$lang['DESCRIPTION_TEXT']
                );
            $response = $this->_preparePostOrSpData($sender, $replaceTexts, self::sProject, $senderName, $extrainfo);
            $this->_sendEmail($response, $data);
                        
        }catch(\Exception $e){
            $this->_log($e->getMessage());
        }
    }
    
    private function _preparePostOrSpData($sender, $replaceTexts, $type, $reciever_name=null, $extraparams=array()){
        $locale = !empty($sender['current_language']) ? $sender['current_language'] : $this->container->getParameter('locale');
        $lang = $this->container->getParameter($locale);
        $subject = $this->_replaceTexts($lang['SHARE_'.$type.'_SUBJECT'], $replaceTexts);
        $bodyTitle = $this->_replaceTexts($lang['SHARE_'.$type.'_BODY'], $replaceTexts);
        $bodyText = $this->_replaceTexts($lang['SHARE_'.$type.'_TEXT'], $replaceTexts);
        $linkText = $this->_replaceTexts($lang['SHARE_'.$type.'_LINK_TEXT'], $replaceTexts);
        $bodyData = $bodyText;
        $section = $extraparams;
        $section['[body_title]'] = $bodyTitle;
        $section['[user_thumb]']= isset($section['[user_thumb]']) ? $section['[user_thumb]'] :$sender['profile_image_thumb'];
        $section['[share_post_heading]'] = self::sProject==$type ? $lang['SHARE_PROJECT_HEADING'] : $lang['SHARE_POST_HEADING'];
        $section['[sender_name]'] = $reciever_name;
        $section['[click_to_view_text]'] = $linkText;
        if(empty($section['[user_thumb]'])){
            $section['[user_thumb]'] = $this->container->getParameter('template_email_thumb');
        }
        switch($type){
            case self::sProject:
                $template = 'sendgrid_share_project_template';
                break;
            case self::shop:
                $template = 'sendgrid_share_shop_template';
                break;
            default:
                $template = 'sendgrid_share_post_template';
                break;
        }
        
        $response = array(
            'templateParams'=>array('section'=>$section),
            'subParams'=> array_keys($section),
            'subject'=>$subject,
            'bodyData'=>$bodyData,
            'templateId'=>  $this->container->getParameter($template),
            'reciever_name'=>$reciever_name
        );
        $this->_log('Data prepaired at [ShareController:_preparePostOrSpData]');
        return $response;
    }
    
    private function _shareShop($data){
        $this->_log('Entered in [ShareController:_shareShop]');
        try{
            $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
            $shopProfileUrl = $this->container->getParameter('shop_profile_url');
            $em = $this->getDoctrine()->getManager();
            $postService = $this->_getPostService();
             $store =  $this->_getUserObjectService()->getStoreObjectService($data['item_id']);
            if(empty($store)){ 
                $this->_response(1055);
            }
            $storeName = empty($store['name']) ? $store['businessName'] : $store['name'];
            $storeId = $store['id'];
            $link = $angular_app_hostname.$shopProfileUrl.'/'.$storeId;

            $sender = $this->_getUserObjectService()->UserObjectService($data['user_id']);
            if(empty($sender)){
                $this->_log('Sender does not exists [ShareController:_shareShop]');
                $this->_response(1021);
            }
            $locale = !empty($sender['current_language']) ? $sender['current_language'] : $this->container->getParameter('locale');
            $lang = $this->container->getParameter($locale);
            $sender_name = trim(ucwords($sender['first_name'].' '.$sender['last_name']));
            $desc = $this->truncateString($store['description'], 150);
            $replaceTexts = array(
                'user'=>$sender_name,
                'shop'=> $storeName,
                'desc_shop'=> trim($desc)=='' ? '<br>' : $desc
            );
            
            $extrainfo = array(
                '[shop_profile_url]'=>$link,
                '[shop_image_url]'=>isset($store['thumb_path']) ? $store['thumb_path'] : $this->container->getParameter('store_default_image_thumb'),
                '[email]'=>$store['email'],
                '[shop]'=>$storeName,
                '[body_text_1]'=>$lang['SHARE_SHOP_TEXT_1'],
                '[body_text_2]'=>$lang['SHARE_SHOP_TEXT_2'],
                '[address_text]'=>$lang['ADDRESS_TEXT'],
                '[city_text]'=>$lang['CITY_TEXT'],
                '[address_val]'=>$store['businessAddress'],
                '[city_val]'=>$store['businessCity'],
                '[descrption_text]'=>$lang['DESCRIPTION_TEXT'],
                '[phone_text]'=> $lang['PHONE_TEXT'],
                '[phone_number]'=>$store['phone']
                );
            $response = $this->_preparePostOrSpData($sender, $replaceTexts, self::shop, $sender_name, $extrainfo);

            $this->_sendEmail($response, $data);
        }catch(\Exception $e){
            $this->_log('Error in [ShareController:_shareShop] : '. $e->getMessage());
        }
    }
    
    private function _shareTamoilOffer($data){
        $this->_log('Entered in [ShareController:_shareTamoilOffer]');
        $em = $this->getDoctrine()->getManager();
        try{
            $offer = $em
                ->getRepository('SixthContinentConnectBundle:Offer')
                ->find($data['item_id']);
            if($offer){
                $this->_log('Getting sender ['.$data['user_id'].'] information');
                $sender = $this->_getUserObjectService()->UserObjectService($data['user_id']);
                if(empty($sender)){
                    $this->_log('Sender does not exists [ShareController:_shareOffer]');
                    $this->_response(1021);
                }
                $options = $this->_prepareTamoilOfferData($offer, $sender);
                if(!empty($options)){
                    $this->_sendEmail($options, $data);
                }
            }
        } catch (\Exception $ex) {
            $this->_log($ex->getMessage().' [Line : '. $ex->getLine().']');
        }
    }
    
    private function _prepareTamoilOfferData($offer, $sender){
        $locale = !empty($sender['current_language']) ? $sender['current_language'] : $this->container->getParameter('locale');
        $lang = $this->container->getParameter($locale);
        $offerImg = $offer->getImageThumb();
        $offerDesc = $offer->getDescription();
        $data['[tamoil_offer_img]'] = "https://www.sixthcontinent.com/app/assets/images/specialoffer.jpg";
        
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $link = $angular_app_hostname.$this->_replaceTexts(self::tamoilOfferPublicUrl, array('offerId'=>$offer->getId()));
        $senderName = trim(ucwords($sender['first_name'].' '.$sender['last_name']));
        $replaceTexts = array(
            'user'=> $senderName,
        );
        $data['[offer_desc]'] = $this->_replaceTexts($lang['SHARE_TAMOIL_OFFER_TEXT_2'], $replaceTexts).'<br><br>';
        
        $data['[view_offer_link]'] = $link;
        $data['[dear_user]'] = $lang['DEAR_USER'];
        $data['[body_title]'] = $this->_replaceTexts($lang['SHARE_TAMOIL_OFFER_BODY'], $replaceTexts);
        $data['[click_here_link]'] = $this->_replaceTexts($lang['SHARE_TAMOIL_OFFER_LINK_TEXT'], $replaceTexts);
        
        $subject = $this->_replaceTexts($lang['SHARE_TAMOIL_OFFER_SUBJECT'], $replaceTexts);
        $data['[body_text]'] = $this->_replaceTexts($lang['SHARE_TAMOIL_OFFER_TEXT'], $replaceTexts);
        $response = array(
            'templateParams'=>array('section'=>$data),
            'subParams'=> array_keys($data),
            'subject'=>$subject,
            'bodyData'=>'<br>',
            "reciever_name"=>$senderName,
            'templateId'=>  $this->container->getParameter('sendgrid_share_tamloil_offer_tpl')
        );
        
        $this->_log('Data prepaired for email');
        
        return $response;
    }
    
    public function truncateString($string, $limit, $break=" ", $pad="...")
    {
      // return with no change if string is shorter than $limit
      if(strlen($string) <= $limit) return $string;

      $string = substr($string, 0, $limit);
      if(false !== ($breakpoint = strrpos($string, $break))) {
        $string = substr($string, 0, $breakpoint);
      }

      return $string . $pad;
    }
    
    private function _getUtilityService(){
        if(!$this->utilityService){
            $this->utilityService = $this->container->get('store_manager_store.storeUtility');
        }
        return $this->utilityService;
    }
    
    private function _response($code, $data=array()){
        $response = new Resp(Msg::getMessage($code)->getCode(), Msg::getMessage($code)->getMessage(), $data);
        Utility::createResponse($response);
    }
    
    private function _getUserObjectService(){
        if(!$this->userObjectService){
            $this->userObjectService = $this->container->get('user_object.service');
        }
        return $this->userObjectService;
    }
    
    private function _getPostService(){
        if(!$this->postService){
            $this->postService = $this->container->get('post_detail.service');
        }
        return $this->postService;
    }
    
    private function _getEmailService(){
        if(!$this->emailService){
            $this->emailService = $this->container->get('email_template.service');
        }
        return $this->emailService;
    }
    
    private function _replaceTexts($str, array $replaceTexts){
        foreach ($replaceTexts as $search=>$replace){
            $str = str_replace(':'.$search, $replace, $str);
        }
        return $str;
    }
    
    private function _log($sMessage){
        $monoLog = $this->container->get('monolog.logger.share_notification');
        $monoLog->info($sMessage);
    }
    
    private function _getDocumentManager(){
        return $this->container->get('doctrine.odm.mongodb.document_manager');
    }
    
    private function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path . '/' . $aws_bucket;
        return $full_path;
    }
}
