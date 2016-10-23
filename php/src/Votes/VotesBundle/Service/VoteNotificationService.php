<?php
namespace Votes\VotesBundle\Service;

class VoteNotificationService {
    private $container;

    private $allowedTypes = array(
      'social_project'=>'social_project'
    );

    public function __construct() {
        global $kernel;
        $this->container = $kernel->getContainer();
    }

    public function send($voter_id, $item_id, $item_type){
        switch(strtolower($item_type)){
            case $this->allowedTypes['social_project']:
                $this->_socialProject($voter_id, $item_id);
                break;
        }
    }

    public function getFormatedWebNotification($notification, $users, $type){
        $response = array();
        switch(strtolower($type)){
            case $this->allowedTypes['social_project']:
                $response= $this->_getSocialProjectWN($notification, $users);
                break;
        }
        return $response;
    }

    private function _socialProject($voter_id, $item_id){
        $dm = $this->_getDocumentManager();
        $postService = $this->_getPostService();
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $href = $this->container->getParameter('social_project_url');
        try{
           $project = $dm->getRepository('PostFeedsBundle:SocialProject')->find($item_id);
           if($project){
               $pOwnerId = $project->getOwnerId();
               if($pOwnerId == $voter_id ) {
                   return ;
               }
               $type = strtoupper($this->allowedTypes['social_project']);
               $sender = $postService->getUserData($voter_id);
               $sender['name'] = trim($sender['first_name'].' '.$sender['last_name']);
               $url = $angular_app_hostname. str_replace(':projectId', $project->getId(), $href);
               $item_name = $project->getTitle();
               $replaceTexts = array('item_name'=>  ucwords($item_name), 'total_vote'=>$project->getWeWant());
               $this->_sendNotifications($sender, $pOwnerId, $type, array('url'=>$url, 'item_id'=>$item_id, 'replaceTexts'=>$replaceTexts, 'info'=>array('total_vote'=>$project->getWeWant())), true, true, true);
           }
        } catch (\Exception $ex) {

        }
    }

    private function _sendNotifications(array $sender, $receiver_id, $type, $options, $isEmail, $isWeb, $isPush){
        $postService = $this->_getPostService();
        $emailService = $this->container->get('email_template.service');
        $_options = array('url'=>'', 'item_id'=>'', 'extraPushParams'=>array(), 'info'=>array(), 'replaceTexts'=>array());
        $options = is_array($options) ? $options : (array) $options;
        $options = array_merge($_options, $options);
        $sender_name = isset($sender['name']) ? ucfirst($sender['name']) : '';
        $senderId = isset($sender['id']) ? $sender['id'] : '';
        $thumb = isset($sender['profile_image_thumb']) ? $sender['profile_image_thumb'] : '';
        $hasToReplace = array('item_name', 'sender_name', 'total_vote');
        $replaceText = $withOtherVote = array();
        foreach($hasToReplace as $hTR){
            $replaceText[$hTR] = isset($options['replaceTexts'][$hTR]) ? $options['replaceTexts'][$hTR] : '';
            $withOtherVote[$hTR] = isset($options['replaceTexts'][$hTR]) ? $options['replaceTexts'][$hTR] : '';
        }

        $replaceText['sender_name'] = $sender_name;
        $withOtherVote['sender_name'] = $sender_name;
        $withOtherVote['total_vote'] = $replaceText['total_vote']>0 ? $replaceText['total_vote']-1: 0;
        if($isEmail){
            $receivers = $postService->getUserData($receiver_id, true);
            $recieverByLanguage = $postService->getUsersByLanguage($receivers);
            $emailResponse = '';
            foreach($recieverByLanguage as $lng=>$recievers){
                $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
                $lang_array = $this->container->getParameter($locale);

                $subject = vsprintf($lang_array['VOTED_ON_'.$type.'_SUBJECT'], $withOtherVote);
                $bodyTitle = vsprintf($lang_array['VOTED_ON_'.$type.'_BODY'], $withOtherVote);
                $mail_text = vsprintf($lang_array['VOTED_ON_'.$type.'_TEXT'],$replaceText);
                $link = "<a href='".$options['url']."'>".$lang_array['CLICK_HERE']."</a>";
                $bodyData = $mail_text.'<br><br>'. sprintf($lang_array['VOTED_ON_'.$type.'_LINK'], $link);

                // HOTFIX NO NOTIFY MAIL
                //$emailResponse = $emailService->sendMail($recievers, $bodyData, $bodyTitle, $subject, $thumb, 'VOTE_NOTIFICATION');
            }
        }
        $postService->sendUserNotifications($senderId, $receiver_id, $type, 'VOTE', $options['item_id'], true, true, $withOtherVote, 'CITIZEN', $options['extraPushParams'], 'U', $options['info']);
    }

    private function _getSocialProjectWN($notification, $users){
        $response = array();
        $feed_service = $this->getPostFeedsService();
        try{
            $dm = $this->_getDocumentManager();
            $sender_id      = $notification->getFrom();
            //call the serviec for user object.
            $user_object = isset($users[$sender_id]) ? $users[$sender_id] : array();

            $notification_id = $notification->getId();
            $notification_from = $user_object;
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            $info = $notification->getInfo();
            $item = $dm->getRepository('PostFeedsBundle:SocialProject')
                ->find($item_id);
            $project = $dm->getRepository('PostFeedsBundle:SocialProject')->find($item->getId());

            if($project){
               $vote = isset($info['total_vote']) ? $info['total_vote'] : $project->getWeWant();
            }

                if($item)
                {
                     $cover_img =   $item->getCoverImg();
                     $cover_data = $feed_service->getCoverImageinfo($cover_img);

                     $response = array('notification_id'=>$notification_id,
                        'notification_from'=>$notification_from,
                        'message_type' =>$message_type,
                        'message'=>$message,
                        'message_status'=>$message_status,
                        'post_info'=>array(
                            'projectName'=>$item->getTitle(),
                            'projectId'=>$item->getId(),
                          ),
                        'is_read'=>(int)$notification->getIsRead(),
                        'create_date'=>$notification->getDate(),
                        'vote_count'=>($vote >0 ? ($vote-1) : 0 ),
                        'cover_img' => $cover_data
                        );
                }
        } catch (\Exception $ex) {
            //echo $ex->getMessage();
        }
        return $response;
    }

    private function _getPostService() {
        return $this->container->get('post_detail.service');
    }

    private function _getDocumentManager(){
        return $this->container->get('doctrine.odm.mongodb.document_manager');
    }
    protected function getPostFeedsService() {
        return $this->container->get('post_feeds.postFeeds');
    }

}
