<?php
namespace Notification\NotificationBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;


use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use StoreManager\StoreBundle\Entity\Store;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use StoreManager\StoreBundle\Entity\StoreMedia;
use StoreManager\StoreBundle\Entity\Storealbum;
use Utility\CurlBundle\Services\CurlRequestService;
use Notification\NotificationBundle\Services\HtmlToTextService;

// service method  class
class EmailTemplateService
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
        //$this->request   = $request;
    }
    
    /**
     * get email template
     * @param string $body
     * @param string $thumb_path
     * @return sting
     */
   public function EmailTemplateService($email_body,$thumb_path,$link,$reciever_id, $type = 1)
   {
        /*String replace and get content*/
        $user_service = $this->container->get('user_object.service');
        $user_info = $user_service->UserObjectService($reciever_id);
        if($user_info) {
            $first_name = ucfirst($user_info['first_name']);
            $last_name = ucfirst($user_info['last_name']);
            $user_from = $first_name.' '.$last_name.',';
        }else {
             $user_from = '';
        }

        $user_from_link = "<b>$user_from</b> <br><br> $link";
        
        // for forget password        
        if($type == 2){
            $user_from_link = "$link";
        }
        $body = file_get_contents(__DIR__.'/../Resources/mail/sixthcontinent_mail.html');
        $body =str_replace('%body%',$email_body,$body);
        if($user_from_link !='') {
            $body =str_replace('%user_from%',$user_from_link,$body);
        }

        $body =($thumb_path != '' && $thumb_path!= null)?str_replace('%user_thumb%',$thumb_path,$body):str_replace('%user_thumb%',$this->container->getParameter('template_email_thumb'),$body);
        return $body;
   }
   
   /**
     * get email template
     * @param string $body
     * @param string $thumb_path
     * @return sting
     */
   public function EmailTemplateServiceTypeB($email_body,$thumb_path,$link,$reciever_id, $user_msg)
   {
        /*String replace and get content*/
        $user_service = $this->container->get('user_object.service');
        $user_info = $user_service->UserObjectService($reciever_id);
        if($user_info) {
            $first_name = ucfirst($user_info['first_name']);
            $last_name = ucfirst($user_info['last_name']);
            $user_from = $first_name.' '.$last_name.',';
        }else {
             $user_from = '';
        }
       
        $user_from_link = "$user_msg <br><br> $link";
        
        $body = file_get_contents(__DIR__.'/../Resources/mail/sixthcontinent_mail.html');
        $body =str_replace('%body%',$email_body,$body);
        if($user_from_link !='') {
            $body =str_replace('%user_from%',$user_from_link,$body);
        }
        
        $body =($thumb_path != '' && $thumb_path!= null)?str_replace('%user_thumb%',$thumb_path,$body):str_replace('%user_thumb%',$this->container->getParameter('template_email_thumb'),$body);
        return $body;
   }
   
   
   /**
     * send email for notification on shop activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @param int $type , 0 for user, 1 for admin 
     * @return boolean
     */
    public function sendEmailNotification($mail_sub, $from_id, $to_id, $mail_body, $type=1, $attachmemt = 0, $attachmemt_path=array(),$is_admin=null) {
        
        $em = $this->em;
        $to_user = array();
        $from_user = array();
        // code for from user
        $user_object_from = $em->getRepository('UserManagerSonataUserBundle:User')->findBy(array('id'=>(int)$from_id));
        if ($user_object_from) {
            $from_user = $user_object_from[0];
        }
        
        // code for to user
        $user_object_to = $em->getRepository('UserManagerSonataUserBundle:User')->findBy(array('id'=>(int)$to_id));
        if ($user_object_to) {
            $to_user = $user_object_to[0];
        }
        
        //$from_user = $userManager->findUserBy(array('id' => (int) $from_id));
        //$to_user = $userManager->findUserBy(array('id' => (int) $to_id));
        $sixthcontinent_admin_email = $this->container->getParameter('sixthcontinent_admin_email');
        $sixthcontinent_admin_email_from = $this->container->getParameter('sixthcontinent_admin_email_from');
        $sixthcontinent_shop_admin_email = $this->container->getParameter('sixthcontinent_shop_admin_email');
        
        //check for sender user
        if(isset($to_user) || $is_admin == 1) {
            if($from_user && $type == 0) { 
                //handling when from email is user
                $user_from_email = $from_user->getEmail();
                $user_from_name = $from_user->getFirstname()." ".$from_user->getLastname();
                $from_email = array($user_from_email => $user_from_name);
            } elseif($from_user && $type == 1) {
                //handling when from email is admin
               $from_email = array($sixthcontinent_admin_email => $sixthcontinent_admin_email_from);
            } else {
                 //handling when from email is admin
                $from_email = array($sixthcontinent_admin_email => $sixthcontinent_admin_email_from);
            }
            try{
            if($is_admin == 1) {
                $send_to_email = $sixthcontinent_admin_email;
            }elseif($is_admin == 2) {
                $send_to_email = $sixthcontinent_shop_admin_email;
            } else {
                $send_to_email = $to_user->getEmail();
            }
            $notification_msg = \Swift_Message::newInstance()
                ->setSubject($mail_sub)
                ->setFrom($from_email)
                ->setTo(array($send_to_email))
                ->setBody($mail_body, 'text/html');
            if($attachmemt){
                #$attachment_path = $attachmemt_path;
                if(isset($attachmemt_path['contract_a'])) {
                    $notification_msg->attach(\Swift_Attachment::fromPath($attachmemt_path['contract_a']));
                }
                
                if(isset($attachmemt_path['contract_b'])) {
                    $notification_msg->attach(\Swift_Attachment::fromPath($attachmemt_path['contract_b']));
                }
                
                
            }

            if ($this->container->get('mailer')->send($notification_msg)) {
                return true;
            } else {
                return true;
            }
            }catch(\Exception $e){
                return true;
            }
        }else{
            return true;
        }
        
    }

     /**
      * 
      * @param type $mail_sub
      * @param type $mail_body
      * @param type $emails
      */
    public function sendEmailNotificationToEmailsAddress($mail_sub, $mail_body,$to_emails = array()) {
        $sixthcontinent_admin_email = $this->container->getParameter('sixthcontinent_admin_email');
        $sixthcontinent_admin_email_from = $this->container->getParameter('sixthcontinent_admin_email_from');
        $from_email = array($sixthcontinent_admin_email => $sixthcontinent_admin_email_from);
        if(!empty($to_emails)){
            
            $message = \Swift_Message::newInstance()
                    ->setSubject($mail_sub)
                    ->setFrom($from_email)
                    ->setTo($to_emails)
                    ->setBody($mail_body, 'text/html');
            $this->container->get('mailer')->send($message);
        }
        return true;
    }
    
    /**
     * finding the link string
     * @param string $href
     * @return string $link
     */
    public function getLinkForMail($href, $locale=0)
    {
        $locale      = $locale===0 ? $this->container->getParameter('locale') : $locale;
        $language_const_array = $this->container->getParameter($locale);
        $click_here  = sprintf($language_const_array['CLICK_HERE']);
        $detail_text = sprintf($language_const_array['MESSAGE_DETAIL']);
        $link = "<a href='$href"."'>$click_here</a> $detail_text";
        return $link;
    }
    
    /**
     * finding the link string
     * @param string $href
     * @return string $link
     */
    public function getLinkForOrderMail($href, $locale=0)
    {
        $locale      = $locale===0 ? $this->container->getParameter('locale') : $locale;
        $language_const_array = $this->container->getParameter($locale);
        $click_here  = sprintf($language_const_array['CLICK_HERE']);
        $detail_text = sprintf($language_const_array['ORDER_MESSAGE_DETAIL']);
        $link = "<a href='$href"."'>$click_here</a> $detail_text";
        return $link;
    }
    
    /**
     * Send emails using Sendgrid API
     * @param array $options                Containing information such as to (single email), html, subject
     * @param array $templateParams         Containing information such as to (multiple emails array), sub (substitution text array)
     * @param string $templateId            Sendgrid template id to use template for mails
     * @param string $mailCategory          Email category to easy track emails on sendgrid
     * @param integer $sendAfterSeconds     Number of seconds to delay email delivery time
     * @return array                        Sendgrid response array
     */
    private function sendGridMail(array $options, array $templateParams, $templateId, $mailCategory='uncategorized', $sendAfterSeconds=2) {
        $requestUrl = $this->container->getParameter('sendgrid_api_url');
        $fromName = $this->container->getParameter('sixthcontinent_admin_email_from');
        $from = array(
            'email'=>  $this->container->getParameter('sixthcontinent_admin_email'),
            'name'=> isset($fromName) ? $fromName : 'SixthContinent'
        );
        $params = array(
            'api_user'  => $this->container->getParameter('sendgrid_username'),
            'api_key'   => $this->container->getParameter('sendgrid_password'),
            'headers'   => json_encode(array('X-Mailer'=>'SixthContinent')),
            'fromname' => $from['name'],
            'from' => $from['email'],
            'replyto'=>$from['email']
          );
        $text = $this->htmlToText($options['html']);       
        $options['text'] = $text;
        $params = array_merge($params, $options);
        $template = array("filters" => array(
                "templates" => array(
                  "settings" => array(
                    "enabled"=> 1,
                    "template_id"=>$templateId
                  )
                )
              )
            );
                
        $smtpApiVars = array(
            'send_at'=>time()+$sendAfterSeconds,
            'category'=>$mailCategory
        );
        $smtpApiVars = array_merge($smtpApiVars, $templateParams);
        if(!empty($templateId)){
            $smtpApiVars = array_merge($smtpApiVars, $template);
        }

        $params['x-smtpapi'] = json_encode($smtpApiVars);
        // Generate curl request
        $curlRequest = new CurlRequestService();
        $response = '';
        $log = json_encode(array('receivers'=>$smtpApiVars['to'], 'subject'=>$params['subject'], 'category'=> $smtpApiVars['category']));
        $this->_log('Email sending for : '.$log);
        try{
        $response = $curlRequest->setUrl($requestUrl)
                    ->setParams($params)
                    ->send('POST')
                    ->getResponse();
            $this->_log($response);
        }  catch (\Exception $e){
            $this->_log('Email sent error : '.  json_encode(array('code'=>$e->getCode(), 'message'=>$e->getMessage())));
        }
        return json_decode($response, true);
    }
    
    /**
     * 
     * @param array $receivers  Receivers email in array
     * @param string $bodyData  Text/Html to place in mail body center
     * @param string $bodyTitle Text/Html to place as heading/title in email
     * @param string $subject   Subject of mail
     * @param string $thumb     Thumbnail url
     * @param string $category  Category name to filter emails log in sendgrid panel
     * @param int $mailDelay    Number of seconds to delay mail delivery
     * @return boolean
     */
    public function sendMailBySendgrid(array $receivers, $bodyData, $bodyTitle='', $subject='', $thumb='', $category = 'uncategorized', $attachmentPath=null,  $mailDelay=2, $is_shop){
        if(!empty($receivers)){
            $templateParams = $this->setSendGridParams($receivers);
            $templateParams['section']['[body]'] = $bodyTitle;
            $templateParams['section']['[sender_thumb]'] = empty($thumb) ? $this->container->getParameter('template_email_thumb') : $thumb;
            $options = array(
                'to' => $templateParams['to'][0],
                'subject'=>$subject,
                'html'=>$bodyData
                );
            
            if(!is_null($attachmentPath)){
                $attachments = is_array($attachmentPath) ? $attachmentPath : (array)$attachmentPath;
                $curlRequest = new CurlRequestService();
                foreach ($attachments as $attachment){
                    $fileName = basename($attachment);
                    $_curlFile = $curlRequest->getCurlFile($attachment, $fileName);
                    $options['files['.$fileName.']'] = $_curlFile;
                }
            }
            $templateId = $this->container->getParameter('sendgrid_notification_tpl_id');
            if($is_shop == 1){
                $templateId = $this->container->getParameter('sendgrid_notification_tpl_id_shop');
            }
            $emailResponse = $this->sendGridMail($options, $templateParams, $templateId, $category, $mailDelay);
            return $emailResponse;
        }
        return false;
    }
    
    /**
     * Set params for sendgrid api
     * @param array $usersData user information
     * @return array
     */
    public function setSendGridParams(array $usersData) {
        $templateParams = array();
        if(!empty($usersData)){
            foreach($usersData as $toUser){
                $toName = trim(ucfirst($toUser['first_name']).' '.ucfirst($toUser['last_name']));
                $templateParams['sub']['[reciever_name]'][] = $toName.',';
                $templateParams['sub']['[body_title]'][] = '[body]';
                $templateParams['sub']['[user_thumb]'][] = '[sender_thumb]';
                $templateParams['to'][] = $toUser['email'];
                if(isset($toUser['shop_info'])){
                    $templateParams['sub']['[groupName]'][] = $toUser['shop_info']['name'];
                }
                if(isset($toUser['club_info'])){
                    $templateParams['sub']['[groupName]'][] = $toUser['club_info']['name'];
                }
            }
        }
        return $templateParams;
    }
    
    /**
     * Set params for sendgrid api
     * @param array $userEmails user emails
     * @return array
     */
    public function setSendGridParamsToEmails(array $userEmails) {
        $templateParams = array();
        if(!empty($userEmails)){
            foreach($userEmails as $toUser){
                $templateParams['sub']['[reciever_name]'][] = '';
                $templateParams['sub']['[body_title]'][] = '[body]';
                $templateParams['sub']['[user_thumb]'][] = '[sender_thumb]';
                $templateParams['to'][] = $toUser;
            }
        }
        return $templateParams;
    }
    
    public function sendGridNotificationToEmails(array $receivers, $bodyData, $bodyTitle='', $subject='', $thumb='', $category = 'uncategorized', $attachmentPath=null, $mailDelay=2){
        if(!empty($receivers)){
            $templateParams = $this->setSendGridParamsToEmails($receivers);
            $templateParams['section']['[body]'] = $bodyTitle;
            $templateParams['section']['[sender_thumb]'] = empty($thumb) ? $this->container->getParameter('template_email_thumb') : $thumb;
            $options = array(
                'to' => $templateParams['to'][0],
                'subject'=>$subject,
                'html'=>$bodyData
                );
            if(!is_null($attachmentPath)){
                $attachments = is_array($attachmentPath) ? $attachmentPath : (array)$attachmentPath;
                $curlRequest = new CurlRequestService();
                foreach ($attachments as $attachment){
                    $fileName = basename($attachment);
                    $_curlFile = $curlRequest->getCurlFile($attachment, $fileName);
                    $options['files['.$fileName.']'] = $_curlFile;
                }
            }
            $templateId = $this->container->getParameter('sendgrid_notification_tpl_id');
            $emailResponse = $this->sendGridMail($options, $templateParams, $templateId, $category, $mailDelay);
            return $emailResponse;
        }
        return false;
    }
    
    public function sendMail(array $receivers, $bodyData, $bodyTitle='', $subject='', $thumb='', $category = 'uncategorized', $attachment=null, $mailDelay=2 , $is_email = 0, $is_shop=0){
        $response = false;
        try{
            $emailSender = $this->container->getParameter('email_sending_serivce');
            switch ($emailSender){
                case 'sendgrid':
                    if($is_email == 1) {
                        $response = $this->sendGridNotificationToEmails($receivers, $bodyData, $bodyTitle, $subject, $thumb, $category, $attachment, $mailDelay);
                    } else {
                        $response = $this->sendMailBySendgrid($receivers, $bodyData, $bodyTitle, $subject, $thumb, $category, $attachment, $mailDelay, $is_shop);
                    }                    
                    break;
            }
        }  catch (\Exception $e){
            $this->_log('Email sent error : '.  json_encode(array('code'=>$e->getCode(), 'message'=>$e->getMessage())));
        } 
        return $response;
    }
    
    public function getDashboardAlbumUrl(array $options, $isFriendProfile=false){
        $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
        $url = '';
        if($isFriendProfile){
            $url     = $this->container->getParameter('friend_album_url');
        }else{
            $url     = $this->container->getParameter('user_album_url');
            $url .= '/:albumId/:albumName';
        }
        foreach($options as $key=>$val){
            $url = str_replace(':'.$key, $val, $url);
        }
        
        return $angular_app_hostname.$url;
    }
    
    public function getClubInvitationUrl(array $options, $urlType, $isLinkRequired=false, $locale = 'it'){
        $angular_app_hostname   = $this->container->getParameter('angular_app_hostname'); //angular app host
        $url     = $this->container->getParameter('club_invite_url');
        
        $language_const_array = $this->container->getParameter($locale);
        $linkText = '';
        switch(strtolower($urlType)){
            case 'accept':
                $linkText = $language_const_array['CLICK_ACCEPT_INVITATION_LINK'];
                break;
            case 'reject':
                $linkText = $language_const_array['CLICK_REJECT_INVITATION_LINK'];
                break;
                
        }
        
        foreach($options as $key=>$val){
            $url = str_replace(':'.$key, $val, $url);
        }
        
        $fullUrl = $angular_app_hostname.$url;
        
        return $isLinkRequired ? "<a href='{$fullUrl}'>{$linkText}</a>" : $fullUrl;
    }
    
    public function getPageUrl(array $options, $urlType){
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $url = '';
        try{
            switch($urlType){
                case 'single_image_page':
                    $url = $this->container->getParameter('single_image_page');
                    break;
            }
            
            foreach($options as $k=>$v){
                $url = str_replace(':'.$k, $v, $url);
            }
        }  catch (\Exception $e){
            
        }
        
        return !empty($url) ? $angular_app_hostname.$url : $url;
    }
    
    public function sendNewsLetters(array $receivers, $subject='', $category = 'uncategorized', $attachmentPath=null){
        $emailSender = $this->container->getParameter('email_sending_serivce');
        switch ($emailSender){
            case 'sendgrid':
                $response = $this->sendNewsLetterBySendgrid($receivers, $subject, $category, $attachmentPath);
                break;
        }
    }

    public function sendNewsLetterBySendgrid(array $receivers, $subject='', $category = 'uncategorized', $attachmentPath=null){
        if(!empty($receivers)){
            $templateParams = $this->setNewsletterSendGridParams($receivers);
            $options = array(
                'to' => $templateParams['to'][0],
                'subject'=>$subject,
                'html'=>'[shop_profile_link]'
                );
            //maintaining log for users
            $monoLog = $this->container->get('monolog.logger.storenotification_log');
            $monoLog->info('Store newsletter receivers : '. json_encode($templateParams['to']));
            $monoLog->info('Store newsletter subject : '. $subject);
            
            if(!is_null($attachmentPath)){
                $attachments = is_array($attachmentPath) ? $attachmentPath : (array)$attachmentPath;
                $curlRequest = new CurlRequestService();
                foreach ($attachments as $attachment){
                    $fileName = basename($attachment);
                    $_curlFile = $curlRequest->getCurlFile($attachment, $fileName);
                    $options['files['.$fileName.']'] = $_curlFile;
                }
            }
            $templateId = $this->container->getParameter('sendgrid_newsletter_tpl_id');
            $emailResponse = $this->sendGridMail($options, $templateParams, $templateId, $category);
            return $emailResponse;
        }
        return false;
    }
    /**
     * Set params for sendgrid api
     * @param array $usersData user information
     * @return array
     */
    public function setNewsletterSendGridParams(array $usersData) {
        $templateParams = array();
        if(!empty($usersData)){
            foreach($usersData as $toUser){
                $templateParams['sub']['[shop_profile_link]'][] = $toUser['shop_profile_link'];
                $templateParams['to'][] = $toUser['email'];
            }
        }
        return $templateParams;
    }
    
    public function htmlToText($html){
        return HtmlToTextService::convert($html);
    }
    
    public function _log($message, $logger='email_delivery'){
        try{
            $monoLog = $this->container->get('monolog.logger.'.$logger);
            $monoLog->info($message);
        }  catch (Exception $e){
            
        }
    }
    
    /**
     *  function for sending the mail notification to the user in batch system
     * @param array $receivers
     * @param type $bodyData
     * @param type $bodyTitle
     * @param type $subject
     * @param type $thumb
     * @param type $category
     * @param type $attachment
     * @param type $mailDelay
     * @param type $is_email
     * @param type $is_shop
     * @return type
     */
    public function sendMailNew(array $receivers, $bodyData, $bodyTitle='', $subject='', $thumb='', $category = 'uncategorized', $attachment=null, $mailDelay=2 , $is_email = 0, $is_shop=0){
        $response = false;
        try{
            $emailSender = $this->container->getParameter('email_sending_serivce');
            switch ($emailSender){
                case 'sendgrid':
                    if($is_email == 1) {
                        $response = $this->sendGridNotificationToEmails($receivers, $bodyData, $bodyTitle, $subject, $thumb, $category, $attachment, $mailDelay);
                    } else {
                        $response = $this->sendMailBySendgridNew($receivers, $bodyData, $bodyTitle, $subject, $thumb, $category, $attachment, $mailDelay, $is_shop);
                    }                    
                    break;
            }
        }  catch (\Exception $e){
            
        } 
        return $response;
    }
    
    
    /**
     * 
     * @param array $receivers  Receivers email in array
     * @param string $bodyData  Text/Html to place in mail body center
     * @param string $bodyTitle Text/Html to place as heading/title in email
     * @param string $subject   Subject of mail
     * @param string $thumb     Thumbnail url
     * @param string $category  Category name to filter emails log in sendgrid panel
     * @param int $mailDelay    Number of seconds to delay mail delivery
     * @return boolean
     */
    public function sendMailBySendgridNew(array $receivers, $bodyData, $bodyTitle=array(), $subject=array(), $thumb='', $category = 'uncategorized', $attachmentPath=null,  $mailDelay=2, $is_shop){
        if(!empty($receivers)){
            $templateParams = $this->setSendGridParamsNew($receivers,$bodyData,$bodyTitle,$subject);
            //$templateParams['section']['[body]'] = $bodyTitle;
            $templateParams['section']['[sender_thumb]'] = empty($thumb) ? $this->container->getParameter('template_email_thumb') : $thumb;
            $options = array(
                'to' => $templateParams['to'][0],
                'subject'=>'[mail_subject]',
                'html'=>'<br/>'
                );
            
            if(!is_null($attachmentPath)){
                $attachments = is_array($attachmentPath) ? $attachmentPath : (array)$attachmentPath;
                $curlRequest = new CurlRequestService();
                foreach ($attachments as $attachment){
                    $fileName = basename($attachment);
                    $_curlFile = $curlRequest->getCurlFile($attachment, $fileName);
                    $options['files['.$fileName.']'] = $_curlFile;
                }
            }
            $templateId = $this->container->getParameter('sendgrid_notification_tpl_batch_id');
            if($is_shop == 1){
                $templateId = $this->container->getParameter('sendgrid_notification_tpl_id_shop');
            }
            $emailResponse = $this->sendGridMail($options, $templateParams, $templateId, $category, $mailDelay);
            return $emailResponse;
        }
        return false;
    }
    
    
    
    
    /**
     * Set params for sendgrid api for batch
     * @param array $usersData user information
     * @return array
     */
    public function setSendGridParamsNew(array $usersData,$bodyData,$bodyTitle,$subject) {
        $templateParams = array();
        if(!empty($usersData)){
            foreach($usersData as $toUser){
                $toName = trim(ucfirst($toUser['first_name']).' '.ucfirst($toUser['last_name']));
                $templateParams['sub']['[reciever_name]'][] = $toName.',';
                $templateParams['sub']['[body_title]'][] = $bodyTitle[$toUser['id']];
                $templateParams['sub']['[user_thumb]'][] = '[sender_thumb]';
                $templateParams['sub']['[mail_body]'][] = $bodyData[$toUser['id']];
                $templateParams['sub']['[mail_subject]'][] = $subject[$toUser['id']];
                $templateParams['to'][] = $toUser['email'];
                if(isset($toUser['shop_info'])){
                    $templateParams['sub']['[groupName]'][] = $toUser['shop_info']['name'];
                }
                if(isset($toUser['club_info'])){
                    $templateParams['sub']['[groupName]'][] = $toUser['club_info']['name'];
                }
            }
        }
        return $templateParams;
    }
    
    public function sendMailWithCustomParams($templateParams, $subject, $bodyData, $templateId, $category, $attachmentPath=null){
            $options = array(
                'to' => $templateParams['to'][0],
                'subject'=>$subject,
                'html'=>$bodyData
                );
            
            if(!is_null($attachmentPath)){
                $attachments = is_array($attachmentPath) ? $attachmentPath : (array)$attachmentPath;
                $curlRequest = new CurlRequestService();
                foreach ($attachments as $attachment){
                    $fileName = basename($attachment);
                    $_curlFile = $curlRequest->getCurlFile($attachment, $fileName);
                    $options['files['.$fileName.']'] = $_curlFile;
                }
            }
            
            $emailResponse = $this->sendGridMail($options, $templateParams, $templateId, $category);
            return $emailResponse;
    }
    
    
    /**
     *  function for sending the mail notification to the user in batch system
     * @param array $receivers
     * @param type $bodyData
     * @param type $bodyTitle
     * @param type $subject
     * @param type $thumb
     * @param type $category
     * @param type $attachment
     * @param type $mailDelay
     * @param type $is_email
     * @param type $is_shop
     * @return type
     */
    public function sendMailInBatchShop(array $receivers, $bodyData, $bodyTitle='', $subject='', $thumb='', $category = 'uncategorized', $attachment=null, $mailDelay=2 , $is_email = 0, $is_shop=0){
        $response = false;
        try{
            $emailSender = $this->container->getParameter('email_sending_serivce');
            switch ($emailSender){
                case 'sendgrid':
                    if($is_email == 1) {
                        $response = $this->sendGridNotificationToEmails($receivers, $bodyData, $bodyTitle, $subject, $thumb, $category, $attachment, $mailDelay);
                    } else {
                        $response = $this->sendMailBySendgridShop($receivers, $bodyData, $bodyTitle, $subject, $thumb, $category, $attachment, $mailDelay, $is_shop);
                    }                    
                    break;
            }
        }  catch (\Exception $e){
            
        } 
        return $response;
    }
    
    
    /**
     * 
     * @param array $receivers  Receivers email in array
     * @param string $bodyData  Text/Html to place in mail body center
     * @param string $bodyTitle Text/Html to place as heading/title in email
     * @param string $subject   Subject of mail
     * @param string $thumb     Thumbnail url
     * @param string $category  Category name to filter emails log in sendgrid panel
     * @param int $mailDelay    Number of seconds to delay mail delivery
     * @return boolean
     */
    public function sendMailBySendgridShop(array $receivers, $bodyData, $bodyTitle=array(), $subject=array(), $thumb='', $category = 'uncategorized', $attachmentPath=null,  $mailDelay=2, $is_shop){
        if(!empty($receivers)){
            $templateParams = $this->setSendGridParamsShop($receivers,$bodyData,$bodyTitle,$subject);
            //$templateParams['section']['[body]'] = $bodyTitle;
            //$templateParams['section']['[sender_thumb]'] = empty($thumb) ? $this->container->getParameter('template_email_thumb') : $thumb;
            $options = array(
                'to' => $templateParams['to'][0],
                'subject'=>'[mail_subject]',
                'html'=>'<br/>'
                );
            
            if(!is_null($attachmentPath)){
                $attachments = is_array($attachmentPath) ? $attachmentPath : (array)$attachmentPath;
                $curlRequest = new CurlRequestService();
                foreach ($attachments as $attachment){
                    $fileName = basename($attachment);
                    $_curlFile = $curlRequest->getCurlFile($attachment, $fileName);
                    $options['files['.$fileName.']'] = $_curlFile;
                }
            }
            $templateId = $this->container->getParameter('sendgrid_notification_tpl_batch_id');
            if($is_shop == 1){
                $templateId = $this->container->getParameter('sendgrid_notification_tpl_id_shop');
            }
            $emailResponse = $this->sendGridMail($options, $templateParams, $templateId, $category, $mailDelay);
            return $emailResponse;
        }
        return false;
    }
    
    
    /**
     * Set params for sendgrid api for batch
     * @param array $usersData user information
     * @return array
     */
    public function setSendGridParamsShop(array $usersData,$bodyData,$bodyTitle,$subject) {
        $templateParams = array();
        if(!empty($usersData)){
            foreach($usersData as $toUser){
                $toName = trim(ucfirst($toUser['user_data']['first_name']).' '.ucfirst($toUser['user_data']['last_name']));
                $templateParams['sub']['[reciever_name]'][] = $toName.',';
                $templateParams['sub']['[body_title]'][] = $bodyTitle[$toUser['shop_info']['id']];
                $templateParams['sub']['[user_thumb]'][] = empty($toUser['shop_info']['thumb_path']) ? $this->container->getParameter('template_email_thumb') : $toUser['shop_info']['thumb_path'];
                $templateParams['sub']['[mail_body]'][] = $bodyData[$toUser['shop_info']['id']];
                $templateParams['sub']['[mail_subject]'][] = $subject[$toUser['shop_info']['id']];
                $templateParams['to'][] = $toUser['user_data']['email'];
                if(isset($toUser['shop_info'])){
                    $templateParams['sub']['[groupName]'][] = $toUser['shop_info']['name'];
                }
                if(isset($toUser['club_info'])){
                    $templateParams['sub']['[groupName]'][] = $toUser['club_info']['name'];
                }
            }
        }
        return $templateParams;
    }
 
}
