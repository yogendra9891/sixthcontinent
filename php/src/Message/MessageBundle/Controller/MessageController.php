<?php

namespace Message\MessageBundle\Controller;

use FOS\MessageBundle\Controller\MessageController as BaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\MessageBundle\Provider\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use JMS\Serializer\SerializerBuilder as JMSR;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Symfony\Component\Form\FormTypeInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use FOS\MessageBundle\EntityManager\ThreadManager;
use Doctrine\ORM\EntityManager;
use FOS\MessageBundle\ModelManager\ThreadManager as ThreadMsg;
use FOS\MessageBundle\DocumentManager\ThreadManager as ThreadMsgDoc;
use Message\MessageBundle\Document\Message;
use FOS\MessageBundle\Document\Message as other;
use Message\MessageBundle\Document\MessageMetadata;
use Message\MessageBundle\Document\Thread;
use Message\MessageBundle\Document\MessageMedia;
use Message\MessageBundle\Document\ThreadMetadata;
use Message\MessageBundle\MessageMessageBundle;
use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use UserManager\Sonata\UserBundle\Entity\UserActiveProfile;
use StoreManager\StoreBundle\Entity\Store;
use Sonata\UserBundle\Admin\Model as ald;
use FOS\UserBundle\Model\UserManagerInterface;
use Message\MessageBundle\Document\MessageThread;

class MessageController extends Controller {

    protected $message_media_path = '/uploads/documents/message/original/';
    protected $message_media_path_thumb = '/uploads/documents/message/thumb/';
    protected $message_media_path_thumb_crop = '/uploads/documents/message/thumb_crop/';
    protected $message_thumb_image_width = 654;
    protected $message_thumb_image_height = 360;
    protected $user_profile_type_code = 22;
    protected $profile_type_code = 'user';
    protected $miss_param = '';
    protected $original_resize_image_width = 910;
    protected $original_resize_image_height = 910;

    /**
     *
     * @param type $request
     * @return type
     */
    public function getAppData(Request$request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeData($content);

        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }

    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }

    /**
     * Displays the authenticated participant inbox
     *
     * @return Response
     */
    public function inboxAction() {
        $threads = $this->getProvider()->getInboxThreads();

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:inbox.html.twig', array(
                    'threads' => $threads
        ));
    }

    /**
     * Displays the authenticated participant messages sent
     *
     * @return Response
     */
    public function sentAction() {
        $threads = $this->getProvider()->getSentThreads();

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:sent.html.twig', array(
                    'threads' => $threads
        ));
    }

    /**
     * Displays the authenticated participant deleted threads
     *
     * @return Response
     */
    public function deletedAction() {
        $threads = $this->getProvider()->getDeletedThreads();

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:deleted.html.twig', array(
                    'threads' => $threads
        ));
    }

    /**
     * Displays a thread, also allows to reply to it
     *
     * @param string $threadId the thread id
     *
     * @return Response
     */
    public function threadAction($threadId) {
        $thread = $this->getProvider()->getThread($threadId);
        $form = $this->container->get('fos_message.reply_form.factory')->create($thread);
        $formHandler = $this->container->get('fos_message.reply_form.handler');

        if ($message = $formHandler->process($form)) {
            return new RedirectResponse($this->container->get('router')->generate('fos_message_thread_view', array(
                        'threadId' => $message->getThread()->getId()
            )));
        }

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:thread.html.twig', array(
                    'form' => $form->createView(),
                    'thread' => $thread
        ));
    }

    /**
     * Create a new message thread
     *
     * @return Response
     */
    public function newThreadAction() {

        $form = $this->container->get('fos_message.new_thread_form.factory')->create();
        $formHandler = $this->container->get('fos_message.new_thread_form.handler');

        if ($message = $formHandler->process($form)) {
            return new RedirectResponse($this->container->get('router')->generate('fos_message_thread_view', array(
                        'threadId' => $message->getThread()->getId()
            )));
        }

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:newThread.html.twig', array(
                    'form' => $form->createView(),
                    'data' => $form->getData()
        ));
    }

    /**
     * Deletes a thread
     *
     * @param string $threadId the thread id
     *
     * @return RedirectResponse
     */
    public function deleteAction($threadId) {
        $thread = $this->getProvider()->getThread($threadId);
        $this->container->get('fos_message.deleter')->markAsDeleted($thread);
        $this->container->get('fos_message.thread_manager')->saveThread($thread);

        return new RedirectResponse($this->container->get('router')->generate('fos_message_inbox'));
    }

    /**
     * Undeletes a thread
     *
     * @param string $threadId
     *
     * @return RedirectResponse
     */
    public function undeleteAction($threadId) {
        $thread = $this->getProvider()->getThread($threadId);
        $this->container->get('fos_message.deleter')->markAsUndeleted($thread);
        $this->container->get('fos_message.thread_manager')->saveThread($thread);

        return new RedirectResponse($this->container->get('router')->generate('fos_message_inbox'));
    }

    /**
     * Searches for messages in the inbox and sentbox
     *
     * @return Response
     */
    public function searchAction() {
        $query = $this->container->get('fos_message.search_query_factory')->createFromRequest();
        $threads = $this->container->get('fos_message.search_finder')->find($query);

        return $this->container->get('templating')->renderResponse('FOSMessageBundle:Message:search.html.twig', array(
                    'query' => $query,
                    'threads' => $threads
        ));
    }

    /**
     * Gets the provider service
     *
     * @return ProviderInterface
     */
    protected function meProvider() {
        return $this->container->get('fos_message.provider');
    }

    /**
     * Uplaod on s3 server
     */
    public function s3imageUpload($s3imagepath, $image_local_path, $filename) {
        $amazan_service = $this->get('amazan_upload_object.service');
        $image_url = $amazan_service->ImageS3UploadService($s3imagepath, $image_local_path, $filename);
        return $image_url;
    }

    /**
     * Call api/send action
     * @param Request $request
     * @return array
     */
    public function postSendsAction(Request $request) {
        //code for aws s3 server path
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path . '/' . $aws_bucket;
        //get object of email template service
        $email_template_service = $this->container->get('email_template.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $message_profile_url = $this->container->getParameter('message_profile_url'); //message profile url
        $user_service = $this->get('user_object.service');
        //get locale
        $locale = $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        //initilise the data array
        $data = array();

        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('title', 'body', 'recipient', 'session_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $msg_sub = $de_serialize['title'];
        $msg_body = $de_serialize['body'];
        $msg_recipient = $de_serialize['recipient'];
        $msg_sender = $de_serialize['session_id'];


        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $msg_sender));
        $recipient_user = $userManager->findUserBy(array('id' => $msg_recipient));

        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }
        if ($recipient_user == '') {
            $data[] = "RECIPIENT_ID_IS_INVALID";
            print_r();
            exit;
            ;
        }

        if (!empty($data)) {
            return array('code' => 100, 'message' => 'failure', 'data' => $data);
        }
        if (isset($_FILES['commentfile'])) {
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
            }
        }

        $container = MessageMessageBundle::getContainer();

        $check_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $check_res = $check_dm
                ->getRepository('MessageMessageBundle:Message')
                ->checkThreadExist($msg_sender, $msg_recipient);

        if ($check_res) {
            $thread_id = $check_res[0]->getId();
            $dm = $container->get('doctrine.odm.mongodb.document_manager');
            $message = new Message();
            $message->setThreadId($thread_id);
            $message->setSenderId($msg_sender);
            $message->setReceiverId($msg_recipient);
            $message->setBody($msg_body);
            $message->setSubject($msg_sub);
            $message->setIsRead(0);
            $message->setIsSpam(0);
            $message->setMessageType('R');
            $message->setCreatedAt(time());
            $message->setUpdatedAt(time());
            $dm->persist($message);
            $dm->flush();
        } else {
            $dm = $container->get('doctrine.odm.mongodb.document_manager');
            $message = new Message();
            $message->setThreadId(0);
            $message->setSenderId($msg_sender);
            $message->setReceiverId($msg_recipient);
            $message->setBody($msg_body);
            $message->setSubject($msg_sub);
            $message->setIsRead(0);
            $message->setIsSpam(0);
            $message->setMessageType('N');
            $message->setCreatedAt(time());
            $message->setUpdatedAt(time());
            $dm->persist($message);
            $dm->flush();
        }
        $message_id = '';
        if ($message->getId()) {
            $message_id = $message->getId();
        }
        //for file uploading...
        if (isset($_FILES['commentfile'])) {
            $message_thumb_image_width = $this->message_thumb_image_width;
            $message_thumb_image_height = $this->message_thumb_image_height;
            foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                $original_file_name = $_FILES['commentfile']['name'][$key];
//              $file_name = time() . $_FILES['commentfile']['name'][$key];
                $file_name = time() . strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));

                $image_info = getimagesize($_FILES['commentfile']['tmp_name'][$key]);
                $orignal_mediaWidth = $image_info[0];
                $original_mediaHeight = $image_info[1];
                //call service to get image type. Basis of this we save data 3,2,1 in db
                $image_type_service = $this->get('user_object.service');
                $image_type = $image_type_service->CheckImageType($orignal_mediaWidth, $original_mediaHeight, $message_thumb_image_width, $message_thumb_image_height);
                //clean image name
                $clean_name = $this->get('clean_name_object.service');
                $file_name = $clean_name->cleanString($file_name);
                //end image name

                if (!empty($original_file_name)) { //if file name is not exists means file is not present.
                    $file_tmp = $_FILES['commentfile']['tmp_name'][$key];
                    $file_type = $_FILES['commentfile']['type'][$key];
                    $media_type = explode('/', $file_type);
                    $actual_media_type = $media_type[0];

                    $dm = $this->get('doctrine.odm.mongodb.document_manager');
                    $dashboard_comment_media = new MessageMedia();
                    if (!$key) //consider first image the featured image.
                        $dashboard_comment_media->setIsFeatured(1);
                    else
                        $dashboard_comment_media->setIsFeatured(0);
                    $dashboard_comment_media->setMessageId($message_id);
                    $dashboard_comment_media->setMediaName($file_name);
                    $dashboard_comment_media->setType($actual_media_type);
                    $dashboard_comment_media->setCreatedAt(time());
                    $dashboard_comment_media->setUpdatedAt(time());
                    $dashboard_comment_media->setPath('');
                    $dashboard_comment_media->setImageType($image_type);
                    $dashboard_comment_media->setIsActive(1);
                    $dashboard_comment_media->upload($message_id, $key, $file_name); //uploading the files.
                    $dm->persist($dashboard_comment_media);
                    $dm->flush();

                    if ($actual_media_type == 'image') {

                        $media_original_path = __DIR__ . "/../../../../web" . $this->message_media_path . $message_id . '/';
                        $media_original_path_to_be_croped = __DIR__ . "/../../../../web" . $this->message_media_path_thumb_crop . $message_id . "/";
                        $thumb_dir = __DIR__ . "/../../../../web" . $this->message_media_path_thumb . $message_id . "/";
                        $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->message_media_path_thumb_crop . $message_id . "/";

                        $resize_original_dir = __DIR__ . "/../../../../web/uploads/documents/dashboard/message/original/" . $message_id . '/';

                        if (preg_match('/[.](jpg)$/', $file_name) || preg_match('/[.](jpeg)$/', $file_name)) {
                            $image_rotate_service = $this->get('image_rotate_object.service');
                            $image_rotate = $image_rotate_service->ImageRotateService($media_original_path . $file_name);
                        }

                        //resize the original image..
                        $this->resizeOriginal($file_name, $media_original_path, $resize_original_dir, $message_id);

                        //first resize the post image into crop folder
                        $this->createThumbnail($file_name, $media_original_path, $thumb_crop_dir, $message_id);
                        //crop the image from center
                        $this->createCenterThumbnail($file_name, $media_original_path_to_be_croped, $thumb_dir, $message_id);
                    }
                }
            }
        }


        if ($message->getId() == '') {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        } else {
            //send mail

            $from_id = $msg_sender;
            $to_id = $msg_recipient;

            $this->sendNotificationMail($from_id, $to_id, 'single');
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        exit;
    }

    /**
     * send email for notification on activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @return boolean
     */
    public function sendEmailNotification($mail_sub, $from_id, $to_id, $mail_body) {
        $userManager = $this->getUserManager();
        $from_user = $userManager->findUserBy(array('id' => (int) $from_id));
        $to_user = $userManager->findUserBy(array('id' => (int) $to_id));

        /* get info realted to the sender */
        $user_service = $this->get('user_object.service');
        $user_info = $user_service->UserObjectService($from_user->getId());
        $user_thumb = $user_info['profile_image_thumb'];
        $user_from = $user_info['first_name'] . ' ' . $user_info['last_name'];

        /* String replace and get content */
        $body = file_get_contents($this->container->getParameter('template_email'));
        $body = str_replace('%body%', $mail_body, $body);
        $body = str_replace('%user_from%', $user_from, $body);
        $body = ($user_thumb != '' && $user_thumb != null) ? str_replace('%user_thumb%', $user_thumb, $body) : str_replace('%user_thumb%', $this->container->getParameter('template_email_thumb'), $body);
        $sixthcontinent_admin_email = array(
                    $this->container->getParameter('sixthcontinent_admin_email') => $this->container->getParameter('sixthcontinent_admin_email_from')
        );
        $notification_msg = \Swift_Message::newInstance()
                ->setSubject($mail_sub)
                ->setFrom($sixthcontinent_admin_email)
                ->setTo(array($to_user->getEmail()))
                ->setBody($body, 'text/html');

        if ($this->container->get('mailer')->send($notification_msg)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Call api/deletethread action
     * @param Request $request
     * @return array
     */
    public function deletethreadAction(Request $request) {

        //initilise the data array
        $data = array();

        //get request object
        //$request = $this->getRequest();

        $req_obj = $request->get('reqObj');

        //call the deseralizer
        $de_serialize = $this->decodeData($req_obj);

        $thread_id = $de_serialize['thread_id'];
        $msg_sender = $de_serialize['session_id'];

        $container = MessageMessageBundle::getContainer();

        $thread_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $thread_res = $thread_dm
                ->getRepository('MessageMessageBundle:Thread')
                ->findOneBy(array("id" => $thread_id));
        $thread_dm->remove($thread_res);
        $thread_dm->flush();

        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->findOneBy(array("thread_id" => $thread_id));
        $message_id = $message_res->getId();
        $message_dm->remove($message_res);
        $message_dm->flush();

        $message_meta_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_meta_res = $message_meta_dm
                ->getRepository('MessageMessageBundle:MessageMetadata')
                ->findOneBy(array("message_id" => $message_id));
        $message_meta_dm->remove($message_meta_res);
        $message_meta_dm->flush();

        $thread_meta_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $thread_meta_res = $thread_meta_dm
                ->getRepository('MessageMessageBundle:ThreadMetadata')
                ->findOneBy(array("thread_id" => $thread_id));
        $thread_meta_dm->remove($thread_meta_res);
        $thread_meta_dm->flush();
        if ($message_id) {
            $res_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        } else {
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        exit;
    }

    /**
     * Call api/deletemessage action
     * @param Request $request
     * @return array
     */
    public function postDeletemessagesAction(Request $request) {

        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id', 'message_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $message_id = $de_serialize['message_id'];
        $msg_sender = $de_serialize['session_id'];

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $msg_sender));
        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }
        $container = MessageMessageBundle::getContainer();

        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->findOneBy(array("id" => $message_id));
        if ($message_res) {
            $message_res_id = $message_res->getId();
            $del_str = $message_res->getDeleteby();
            $del_str[] = $msg_sender;

            $message_res->setDeleteby($del_str);
            $message_dm->flush();

            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        } else {
            $data[] = "Message Id does not exist";
            $res_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        exit;
    }

    /**
     * Call api/updatemessage action
     * @param Request $request
     * @return array
     */
    public function postUpdatemessagesAction(Request $request) {

        //initilise the data array
        $data = array();


        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id', 'message_id', 'body');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $message_id = $de_serialize['message_id'];
        $message_body = $de_serialize['body'];
        $msg_sender = $de_serialize['session_id'];
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $msg_sender));
        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }
        if (isset($_FILES['commentfile'])) {
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
            }
        }


        $container = MessageMessageBundle::getContainer();

        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->findOneBy(array("id" => $message_id));
        if ($message_res) {
            $message_res->setBody($message_body);
            $message_res->setUpdatedAt(time());
            $message_dm->flush();


            //for file uploading...
            if (isset($_FILES['commentfile'])) {
                $message_thumb_image_width = $this->message_thumb_image_width;
                $message_thumb_image_height = $this->message_thumb_image_height;
                foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                    $original_file_name = $_FILES['commentfile']['name'][$key];
                    $file_name = time() . strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));
                    if (!empty($original_file_name)) { //if file name is not exists means file is not present.
                        $file_tmp = $_FILES['commentfile']['tmp_name'][$key];
                        $file_type = $_FILES['commentfile']['type'][$key];
                        $media_type = explode('/', $file_type);
                        $actual_media_type = $media_type[0];

                        $image_info = getimagesize($_FILES['commentfile']['tmp_name'][$key]);
                        $orignal_mediaWidth = $image_info[0];
                        $original_mediaHeight = $image_info[1];
                        //call service to get image type. Basis of this we save data 3,2,1 in db
                        $image_type_service = $this->get('user_object.service');
                        $image_type = $image_type_service->CheckImageType($orignal_mediaWidth, $original_mediaHeight, $message_thumb_image_width, $message_thumb_image_height);

                        $dm = $this->get('doctrine.odm.mongodb.document_manager');
                        $dashboard_comment_media = new MessageMedia();
                        if (!$key) //consider first image the featured image.
                            $dashboard_comment_media->setIsFeatured(1);
                        else
                            $dashboard_comment_media->setIsFeatured(0);
                        $dashboard_comment_media->setMessageId($message_id);
                        $dashboard_comment_media->setMediaName($file_name);
                        $dashboard_comment_media->setType($actual_media_type);
                        $dashboard_comment_media->setCreatedAt(time());
                        $dashboard_comment_media->setUpdatedAt(time());
                        $dashboard_comment_media->setPath('');
                        $dashboard_comment_media->setImageType($image_type);
                        $dashboard_comment_media->setIsActive(1);
                        $dashboard_comment_media->upload($message_id, $key, $file_name); //uploading the files.
                        $dm->persist($dashboard_comment_media);
                        $dm->flush();
                        if ($actual_media_type == 'image') {
                            $media_original_path = __DIR__ . "/../../../../web" . $this->message_media_path . $message_id . '/';
                            $media_original_path_to_be_croped = __DIR__ . "/../../../../web" . $this->message_media_path_thumb_crop . $message_id . "/";
                            $thumb_dir = __DIR__ . "/../../../../web" . $this->message_media_path_thumb . $message_id . "/";
                            $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->message_media_path_thumb_crop . $message_id . "/";

                            $resize_original_dir = __DIR__ . "/../../../../web/uploads/documents/dashboard/message/original/" . $message_id . '/';
                            //resize the original image..
                            $this->resizeOriginal($file_name, $media_original_path, $resize_original_dir, $message_id);

                            //first resize the post image into crop folder
                            $this->createThumbnail($file_name, $media_original_path, $thumb_crop_dir, $message_id);
                            //crop the image from center
                            $this->createCenterThumbnail($file_name, $media_original_path_to_be_croped, $thumb_dir, $message_id);
                        }
                    }
                }
            }

            //send mail
            //NOT NECESSARY
            /*
             * //not necessary
              $msg_recipient = $message_res->getSenderId();
              $mail_sub = "Message";
              $from_id = $msg_sender;
              $to_id = $msg_recipient;
              $userManager = $this->getUserManager();
              $from_user = $userManager->findUserBy(array('id' => (int) $from_id));

              $fname = $from_user->getFirstname();
              $lastname = $from_user->getLastname();
              $username = $from_user->getUsername();
              $sender_name = $fname . " " . $lastname;
              if ($fname == "") {
              $sender_name = $username;
              }


              $mail_body = ucfirst($sender_name) . " has sent you a message. ";

              //$mail_notification = $this->sendEmailNotification($mail_sub, $from_id, $to_id, $mail_body);
             */
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        } else {
            $data[] = "Message Id does not exist";
            $res_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        exit;
    }

    /**
     * Call api/readmessage action
     * @param Request $request
     * @return array
     */
    public function postReadmessagesAction(Request $request) {

        //initilise the data array
        $data = array();

        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id', 'thread_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $message_id = $de_serialize['thread_id'];
        $msg_sender = $de_serialize['session_id'];

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $msg_sender));
        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }
        $container = MessageMessageBundle::getContainer();

        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_thread_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->findBy(array("thread_id" => $message_id));

        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->findBy(array("thread_id" => $message_id, "receiver_id" => (int) $msg_sender));

        if ($message_thread_res) {
            if ($message_res) {
                foreach ($message_res as $record) {

                    $IsViewByUsers = $record->getIsViewBy();
                    array_push($IsViewByUsers, $msg_sender);
                    $record->setIsViewBy($IsViewByUsers);
                    $message_dm->persist($record);
                    $message_dm->flush();
                    $message_dm->clear();
                }
            }
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        } else {
            $data[] = "Message Id does not exist";
            $res_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * Call api/listmessage action
     * @param Request $request
     * @return array
     */
    public function postListmessagesAction(Request $request) {
        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }


        $user_id = (int) $de_serialize['session_id'];

        $search_body_text = (isset($de_serialize['search_body_text']) ? $de_serialize['search_body_text'] : '');
        $search_sub_text = (isset($de_serialize['search_sub_text']) ? $de_serialize['search_sub_text'] : '');

        if (!isset($de_serialize['limit_size']) || $de_serialize['limit_size'] == 0) {
            $limit = 20;
        } else {
            $limit = (int) $de_serialize['limit_size'];
        }

        if (!$de_serialize['limit_start']) {
            $offset = 0;
        } else {
            $offset = (int) $de_serialize['limit_start'];
        }

        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_data = $message_dm->getRepository('MessageMessageBundle:Message')
                ->searchBySubjectOrOtherMessage($search_body_text, $search_sub_text, $user_id, $limit, $offset);

        $data_count = count($message_data);
        $res_data = array('message' => $message_data, 'count' => $data_count);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Functionality decoding data
     * @param json $object
     * @return array
     */
    public function decodeData($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * Functionality return inbox message list
     * @param json $object
     * @return array
     */
    public function postInboxlistsAction(Request $request) {
        //initilise the data array
        $data = array();

        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $session_id = (int) $de_serialize['session_id'];


        if (!isset($de_serialize['limit_size']) || $de_serialize['limit_size'] == 0) {
            $limit = 20;
        } else {
            $limit = (int) $de_serialize['limit_size'];
        }

        if (!isset($de_serialize['limit_start'])) {
            $offset = 0;
        } else {
            $offset = (int) $de_serialize['limit_start'];
        }

        $provider = $this->container->get('fos_message.provider');

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $session_id));
        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'failure', 'data' => $data);
        }

        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllMessage($session_id, $limit, $offset);
        $i = 1;
        if ($message_res) {
            foreach ($message_res as $message_rec) {
                //$thread_to_manipulate = $provider->getThread($thread->getId());
                $data[$i]['message_info']['message_id'] = $message_rec->getId();
                $data[$i]['message_info']['subject'] = $message_rec->getSubject();
                $data[$i]['message_info']['body'] = $message_rec->getBody();
                $data[$i]['message_info']['created_by'] = $message_rec->getSenderId();
                $data[$i]['message_info']['created_time'] = $message_rec->getCreatedAt();
                $data[$i]['message_info']['last_updated_time'] = $message_rec->getUpdatedAt();
                $data[$i]['message_info']['is_read'] = $message_rec->getIsRead();
                $data[$i]['message_info']['is_spam'] = $message_rec->getIsSpam();
                $data[$i]['message_info']['receiver_id'] = $message_rec->getReceiverId();
                $data[$i]['message_info']['sender_id'] = $message_rec->getSenderUserid();
                $data[$i]['message_info']['message_type'] = $message_rec->getMessageType();
                $data[$i]['message_info']['thread_id'] = $message_rec->getThreadId();

                $i++;
            }
        }

        $data['total_msg'] = $i - 1;
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();

        exit;
    }

    /**
     * Listing all threads
     * @param Request $request
     * @return josn string
     */
    /*
      public function postListusermessagesAction(Request $request)
      {
      //code for aws s3 server path
      $aws_base_path  = $this->container->getParameter('aws_base_path');
      $aws_bucket    = $this->container->getParameter('aws_bucket');
      $aws_path = $aws_base_path.'/'.$aws_bucket;
      //initilise the data array
      $data = array();
      //Code repeat start
      $freq_obj = $request->get('reqObj');
      $fde_serialize = $this->decodeData($freq_obj);

      if(isset($fde_serialize)){
      $de_serialize = $fde_serialize;
      }else{
      $de_serialize = $this->getAppData($request);
      }
      $object_info = (object) $de_serialize; //convert an array into object.

      $required_parameter = array('user_id','friend_id','thread_id');
      //checking for parameter missing.
      $chk_error = $this->checkParamsAction($required_parameter, $object_info);
      if ($chk_error) {
      return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
      }

      $user_id = $object_info->user_id;
      $friend_id = $object_info->friend_id;

      $limit = (int)(isset($de_serialize['limit_size'])? $de_serialize['limit_size']:20);
      $offset = (int)(isset($de_serialize['limit_start'])? $de_serialize['limit_start']:0);

      $userManager = $this->getUserManager();
      $sender_user = $userManager->findUserBy(array('id' => $user_id));
      $friend_user = $userManager->findUserBy(array('id' => $friend_id));

      if($sender_user=='')
      {
      $data[] = "SENDER_ID_IS_INVALID";
      }
      if($friend_user=='')
      {
      $data[] = "FRIEND_ID_IS_INVALID";
      }

      if(!empty($data))
      {
      return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
      }

      $thread_id = $object_info->thread_id;


      $total = 0;
      $container = MessageMessageBundle::getContainer();

      $message_thread_dm = $container->get('doctrine.odm.mongodb.document_manager');
      $message_thread_res_main = $message_thread_dm
      ->getRepository('MessageMessageBundle:Message')
      ->listAllMessages($thread_id,$user_id,$limit,$offset);

      $message_total_thread_dm = $container->get('doctrine.odm.mongodb.document_manager');
      $message_total_thread_res_main = $message_total_thread_dm
      ->getRepository('MessageMessageBundle:Message')
      ->listAllTotalMessages($thread_id,$user_id);
      if($message_total_thread_res_main){
      $total = count($message_total_thread_res_main);
      }else{
      $total = 0;
      }

      $all_thread_details = array();
      $user_service = $this->get('user_object.service');

      if($message_thread_res_main){

      foreach($message_thread_res_main as $message_main_res){
      $thread_details_main = array();
      $sender_main_user_detail = array();
      $reciever_main_user_detail = array();
      $main_sender = $message_main_res->getSenderId();
      $main_receiver = $message_main_res->getReceiverId();
      $em_user_main = $this->getDoctrine()->getManager();

      $sender_main_user_detail = $user_service->UserObjectService($main_sender);
      $reciever_main_user_detail = $user_service->UserObjectService($main_receiver);

      $thread_id_write = '';
      if($message_main_res->getMessageType() == 'N'){
      $thread_id_write = $message_main_res->getId();
      }else{
      $thread_id_write = $message_main_res->getThreadId();
      }
      $message_media_dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
      $message_media_res = $message_media_dm->getRepository('MessageMessageBundle:MessageMedia')
      ->findBy(array('message_id'=>$message_main_res->getId()));
      $message_media_link = '';
      $message_media_thumb = '';
      if($message_media_res){
      foreach($message_media_res as $value){
      $message_media_name = $value->getMediaName();
      }

      if($message_media_name){
      $message_media_link = $aws_path . $this->message_media_path . $message_main_res->getId() . '/' . $message_media_name;
      $message_media_thumb = $aws_path . $this->message_media_path_thumb . $message_main_res->getId() . '/' . $message_media_name;
      }

      }
      $thread_details_main = array(
      'id'=>$message_main_res->getId() ,
      'thread_id'=>$thread_id_write ,
      'sender_user'=>$sender_main_user_detail ,
      'receiver_user'=>$reciever_main_user_detail ,
      'body'=> $message_main_res->getBody(),
      'message_type'=> $message_main_res->getMessageType(),
      'media_original_path'=>$message_media_link,
      'media_thumb_path'=>$message_media_thumb,
      'created_at'=> $message_main_res->getCreatedAt(),
      'is_read'=> $message_main_res->getIsRead(),
      'is_spam'=> $message_main_res->getIsSpam(),
      'updated_at'=> $message_main_res->getUpdatedAt(),
      'deleteby'=> $message_main_res->getDeleteby(),
      );
      $all_thread_details[] = $thread_details_main;
      }
      }



      //$all_thread_details['total'] = $total;
      $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $all_thread_details,'total'=>$total);
      echo json_encode($res_data);
      exit();
      } */

    /**
     * Listing all threads
     * @param Request $request
     * @return josn string
     */
    public function postListusermessagesAction(Request $request) {
        //code for aws s3 server path
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path . '/' . $aws_bucket;
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //initilise the data array
        $data = array();
        $thread_main_ids = array();
        $sender_ids = array();
        $receiver_ids = array();
        $user_ids_array = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'friend_id', 'thread_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $user_id = $object_info->user_id;
        $friend_id = $object_info->friend_id;

        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        $friend_user = $userManager->findUserBy(array('id' => $friend_id));

        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }
        if ($friend_user == '') {
            $data[] = "FRIEND_ID_IS_INVALID";
        }

        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }

        $thread_id = $object_info->thread_id;


        $total = 0;
        $container = MessageMessageBundle::getContainer();

        $message_thread_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_thread_res_main = $message_thread_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllMessages($thread_id, $user_id, $limit, $offset);
        //getting the posts ids.
        $thread_main_ids = array_map(function($message_thread_res_main_record) {
            return "{$message_thread_res_main_record->getId()}";
        }, $message_thread_res_main);

        $message_media_all = $dm->getRepository('MessageMessageBundle:MessageMedia')
                ->getAllThreadMedias($thread_main_ids);

        $message_media_all_final = array();
        if ($message_media_all) {
            foreach ($message_media_all as $message_media_record) {
                $message_media_all_final[$message_media_record->getMessageId()][] = $message_media_record;
            }
        }

        $message_total_thread_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_total_thread_res_main = $message_total_thread_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllTotalMessages($thread_id, $user_id);
        if ($message_total_thread_res_main) {
            $total = count($message_total_thread_res_main);
        } else {
            $total = 0;
        }

        $all_thread_details = array();
        $user_service = $this->get('user_object.service');
        //getting the sender ids.
        $sender_ids = array_map(function($message_res_record) {
            return "{$message_res_record->getSenderId()}";
        }, $message_thread_res_main);

        //getting the reciever ids.
        $receiver_ids = array_map(function($message_res_record) {
            return "{$message_res_record->getReceiverId()}";
        }, $message_thread_res_main);

        $user_ids_array = array_merge($sender_ids, $receiver_ids);
        $user_ids_array[] = $user_id;
        $user_ids_array = array_unique($user_ids_array);

        //find user object service..
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($user_ids_array);

        if ($message_thread_res_main) {

            foreach ($message_thread_res_main as $message_main_res) {
                $thread_details_main = array();
                $sender_main_user_detail = array();
                $reciever_main_user_detail = array();
                $main_sender = $message_main_res->getSenderId();
                $main_receiver = $message_main_res->getReceiverId();
                $em_user_main = $this->getDoctrine()->getManager();

                $sender_main_user_detail = isset($users_object_array[$main_sender]) ? $users_object_array[$main_sender] : array();
                $reciever_main_user_detail = isset($users_object_array[$main_receiver]) ? $users_object_array[$main_receiver] : array();

                $thread_id_write = '';
                if ($message_main_res->getMessageType() == 'N') {
                    $thread_id_write = $message_main_res->getId();
                } else {
                    $thread_id_write = $message_main_res->getThreadId();
                }
                $message_media_dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.

                $temp_media = array();
                $message_media_res = isset($message_media_all_final[$message_main_res->getId()]) ? $message_media_all_final[$message_main_res->getId()] : '';

                $message_media_link = '';
                $message_media_thumb = '';
                $message_image_type = '';
                if ($message_media_res) {
                    foreach ($message_media_res as $value) {
                        $message_media_name = $value->getMediaName();
                        $message_image_type = $value->getImageType();
                    }

                    if ($message_media_name) {
                        $message_media_link = $aws_path . $this->message_media_path . $message_main_res->getId() . '/' . $message_media_name;
                        $message_media_thumb = $aws_path . $this->message_media_path_thumb . $message_main_res->getId() . '/' . $message_media_name;
                    }
                }
                $thread_details_main = array(
                    'id' => $message_main_res->getId(),
                    'thread_id' => $thread_id_write,
                    'sender_user' => $sender_main_user_detail,
                    'receiver_user' => $reciever_main_user_detail,
                    'body' => $message_main_res->getBody(),
                    'message_type' => $message_main_res->getMessageType(),
                    'media_original_path' => $message_media_link,
                    'media_thumb_path' => $message_media_thumb,
                    'image_type' => $message_image_type,
                    'created_at' => $message_main_res->getCreatedAt(),
                    'is_read' => $message_main_res->getIsRead(),
                    'is_spam' => $message_main_res->getIsSpam(),
                    'updated_at' => $message_main_res->getUpdatedAt(),
                    'deleteby' => $message_main_res->getDeleteby(),
                );
                $all_thread_details[] = $thread_details_main;
            }
        }



        //$all_thread_details['total'] = $total;
        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $all_thread_details, 'total' => $total);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Listing all users
     * @param Request $request
     * @return josn string
     */
    /*
      public function postListuserthreadmessagesAction(Request $request)
      {
      //initilise the data array
      $data = array();
      //Code repeat start
      $freq_obj = $request->get('reqObj');
      $fde_serialize = $this->decodeData($freq_obj);

      if(isset($fde_serialize)){
      $de_serialize = $fde_serialize;
      }else{
      $de_serialize = $this->getAppData($request);
      }
      $object_info = (object) $de_serialize; //convert an array into object.

      $required_parameter = array('user_id');
      //checking for parameter missing.
      $chk_error = $this->checkParamsAction($required_parameter, $object_info);
      if ($chk_error) {
      return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
      }

      $limit = (int)(isset($de_serialize['limit_size'])? $de_serialize['limit_size']:20);
      $offset = (int)(isset($de_serialize['limit_start'])? $de_serialize['limit_start']:0);

      $user_id = $object_info->user_id;

      $userManager = $this->getUserManager();
      $sender_user = $userManager->findUserBy(array('id' => $user_id));

      if($sender_user=='')
      {
      $data[] = "SENDER_ID_IS_INVALID";
      }

      if(!empty($data))
      {
      return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
      }

      $container = MessageMessageBundle::getContainer();
      $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
      $message_res = $message_dm
      ->getRepository('MessageMessageBundle:Message')
      ->listAllThread($user_id,$limit,$offset);
      $message_total_dm = $container->get('doctrine.odm.mongodb.document_manager');
      $message_total_res = $message_total_dm
      ->getRepository('MessageMessageBundle:Message')
      ->listAllTotalThread($user_id);
      $all_thread_details = array();
      if($message_total_res){
      $total = count($message_total_res);
      }else{
      $total = 0;
      }
      $user_service = $this->get('user_object.service');
      if($message_res){

      foreach($message_res as $message_res_render){
      $thread_id = $message_res_render->getId();
      $message_main_dm = $container->get('doctrine.odm.mongodb.document_manager');
      $message_main_res = $message_main_dm
      ->getRepository('MessageMessageBundle:Message')
      ->find(array('id'=>$thread_id));
      if($message_main_res){
      $thread_details_main = array();
      $show_user_id = '';
      $last_message = array();
      $main_sender = $message_main_res->getSenderId();
      $main_receiver = $message_main_res->getReceiverId();
      if($main_receiver == $user_id){
      $show_user_id = $main_sender;
      }else{
      $show_user_id = $main_receiver;
      }

      $em_user_main = $this->getDoctrine()->getManager();
      $user_show_sender_info = $em_user_main
      ->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
      ->getUserDetials($show_user_id);
      $sender_show_user_detail = array();


      $message_last_dm = $container->get('doctrine.odm.mongodb.document_manager');
      $message_last_res = $message_last_dm
      ->getRepository('MessageMessageBundle:Message')
      ->getLastMessage($thread_id,$user_id);
      $sender_user_last_detail = array();
      $reciever_user_last_detail = array();
      if($message_last_res){
      $em_user_last = $this->getDoctrine()->getManager();

      $reciever_user_last_detail = $user_service->UserObjectService($message_last_res[0]->getReceiverId());
      $sender_user_last_detail = $user_service->UserObjectService($message_last_res[0]->getSenderId());

      $last_message = array(
      'id'=>$message_last_res[0]->getId() ,
      'thread_id'=>$message_last_res[0]->getThreadId() ,
      'sender_user'=>$sender_user_last_detail ,
      'receiver_user'=>$reciever_user_last_detail ,
      'body'=> $message_last_res[0]->getBody(),
      'message_type'=> $message_last_res[0]->getMessageType(),
      'created_at'=> $message_last_res[0]->getCreatedAt(),
      'is_read'=> $message_last_res[0]->getIsRead(),
      'is_spam'=> $message_last_res[0]->getIsSpam(),
      'updated_at'=> $message_last_res[0]->getUpdatedAt(),
      );
      }else{
      $message_last_dm_user = $container->get('doctrine.odm.mongodb.document_manager');
      $message_last_res_user = $message_last_dm_user
      ->getRepository('MessageMessageBundle:Message')
      ->getLastWithUserDetailMessage($thread_id);
      if($message_last_res_user){
      $reciever_user_last_detail = $user_service->UserObjectService($message_last_res_user[0]->getReceiverId());
      $sender_user_last_detail = $user_service->UserObjectService($message_last_res_user[0]->getSenderId());
      }
      }

      if(isset($sender_user_last_detail['id'])){
      if($sender_user_last_detail['id'] == $user_id){
      $sender_show_user_detail = $reciever_user_last_detail;
      }else{
      $sender_show_user_detail = $sender_user_last_detail;
      }
      }
      $thread_details_main = array(
      'thread_id'=>$message_main_res->getId() ,
      'user_detail'=>$sender_show_user_detail ,
      // 'sender_user'=>$sender_main_user_detail ,
      //'receiver_user'=>$reciever_main_user_detail ,
      'body'=> $message_main_res->getBody(),
      'message_type'=> $message_main_res->getMessageType(),
      'created_at'=> $message_main_res->getCreatedAt(),
      // 'is_read'=> $message_main_res->getIsRead(),
      // 'is_spam'=> $message_main_res->getIsSpam(),
      //'updated_at'=> $message_main_res->getUpdatedAt(),
      'last_message'=>$last_message,

      );

      $all_thread_details[] = $thread_details_main;
      }


      }

      }
      $data = $all_thread_details;
      $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data,'total'=>$total);
      echo json_encode($res_data);
      exit();
      } */

    /**
     * Listing all users
     * @param Request $request
     * @return josn string
     */
    public function postListuserthreadmessagesAction(Request $request) {
        //initilise the data array
        $data = array();
        $container = MessageMessageBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager');
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);

        $user_id = $object_info->user_id;

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));

        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }

        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }


        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllThread($user_id, $limit, $offset);
        $message_total_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_total_res = $message_total_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllTotalThread($user_id);

        $user_ids_array = array();
        $sender_ids = array();
        $receiver_ids = array();

        //getting the sender ids.
        $sender_ids = array_map(function($message_res_record) {
            return "{$message_res_record->getSenderId()}";
        }, $message_res);

        //getting the reciever ids.
        $receiver_ids = array_map(function($message_res_record) {
            return "{$message_res_record->getReceiverId()}";
        }, $message_res);

        $user_ids_array = array_merge($sender_ids, $receiver_ids);
        $user_ids_array[] = $user_id;
        $user_ids_array = array_unique($user_ids_array);

        $thread_ids = array();

        $thread_ids = array_map(function($message_res_record) {
            return "{$message_res_record->getId()}";
        }, $message_res);

        //get all threads
        $message_all_threads_res = $dm
                ->getRepository('MessageMessageBundle:Message')
                ->getAllThreadMessage($thread_ids);

        if ($message_all_threads_res) {
            foreach ($message_all_threads_res as $message_thread_record) {
                $thread_res_all[$message_thread_record->getId()] = $message_thread_record;
            }
        }
        //find user object service..
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($user_ids_array);

        $all_thread_details = array();
        if ($message_total_res) {
            $total = $message_total_res;
        } else {
            $total = 0;
        }

        if ($message_res) {

            foreach ($message_res as $message_res_render) {
                $thread_id = $message_res_render->getId();
                $message_main_res = $thread_res_all[$thread_id];
                if ($message_main_res) {
                    $thread_details_main = array();
                    $show_user_id = '';
                    $last_message = array();
                    $main_sender = isset($users_object_array[$message_main_res->getSenderId()]) ? $users_object_array[$message_main_res->getSenderId()] : array();
                    $main_receiver = isset($users_object_array[$message_main_res->getReceiverId()]) ? $users_object_array[$message_main_res->getReceiverId()] : array();
                    if ($main_receiver == $user_id) {
                        $show_user_id = $main_sender;
                    } else {
                        $show_user_id = $main_receiver;
                    }

                    $sender_show_user_detail = array();
                    // to do remove this query from the foreach loop
                    $message_last_dm = $container->get('doctrine.odm.mongodb.document_manager');
                    $message_last_res = $message_last_dm
                            ->getRepository('MessageMessageBundle:Message')
                            ->getLastMessage($thread_id, $user_id);
                    $sender_user_last_detail = array();
                    $reciever_user_last_detail = array();
                    if ($message_last_res) {
                        $em_user_last = $this->getDoctrine()->getManager();

                        $reciever_user_last_detail = isset($users_object_array[$message_last_res[0]->getReceiverId()]) ? $users_object_array[$message_last_res[0]->getReceiverId()] : array();
                        $sender_user_last_detail = isset($users_object_array[$message_last_res[0]->getSenderId()]) ? $users_object_array[$message_last_res[0]->getSenderId()] : array();

                        $last_message = array(
                            'id' => $message_last_res[0]->getId(),
                            'thread_id' => $message_last_res[0]->getThreadId(),
                            'sender_user' => $sender_user_last_detail,
                            'receiver_user' => $reciever_user_last_detail,
                            'body' => $message_last_res[0]->getBody(),
                            'message_type' => $message_last_res[0]->getMessageType(),
                            'created_at' => $message_last_res[0]->getCreatedAt(),
                            'is_read' => $message_last_res[0]->getIsRead(),
                            'is_spam' => $message_last_res[0]->getIsSpam(),
                            'updated_at' => $message_last_res[0]->getUpdatedAt(),
                        );
                    } else {
                        $message_last_dm_user = $container->get('doctrine.odm.mongodb.document_manager');
                        $message_last_res_user = $message_last_dm_user
                                ->getRepository('MessageMessageBundle:Message')
                                ->getLastWithUserDetailMessage($thread_id);
                        if ($message_last_res_user) {
                            $reciever_user_last_detail = isset($users_object_array[$message_last_res_user[0]->getReceiverId()]) ? $users_object_array[$message_last_res_user[0]->getReceiverId()] : array();
                            $sender_user_last_detail = isset($users_object_array[$message_last_res_user[0]->getSenderId()]) ? $users_object_array[$message_last_res_user[0]->getSenderId()] : array();
                        }
                    }

                    if (isset($sender_user_last_detail['id'])) {
                        if ($sender_user_last_detail['id'] == $user_id) {
                            $sender_show_user_detail = $reciever_user_last_detail;
                        } else {
                            $sender_show_user_detail = $sender_user_last_detail;
                        }
                    }
                    $thread_details_main = array(
                        'thread_id' => $message_main_res->getId(),
                        'user_detail' => $sender_show_user_detail,
                        'body' => $message_main_res->getBody(),
                        'message_type' => $message_main_res->getMessageType(),
                        'created_at' => $message_main_res->getCreatedAt(),
                        'last_message' => $last_message,
                    );

                    $all_thread_details[] = $thread_details_main;
                }
            }
        }
        $data = $all_thread_details;
        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data, 'total' => $total);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Listing Search on all users
     * @param Request $request
     * @return josn string
     */
    public function postSearchuserthreadsAction___old(Request $request) {
        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'firstname');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);

        $user_id = $object_info->user_id;
        $first_name = $object_info->firstname;
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));

        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }

        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }
        if ($first_name == '') {
            $data[] = "firstname can not be empty.";
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }

        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllThreadForSearch($user_id);
        $all_thread_details = array();
        $total = count($message_res);
        $user_service = $this->get('user_object.service');
        if ($message_res) {
            foreach ($message_res as $message_res_render) {
                $thread_id = $message_res_render->getId();
                $message_main_dm = $container->get('doctrine.odm.mongodb.document_manager');
                $message_main_res = $message_main_dm
                        ->getRepository('MessageMessageBundle:Message')
                        ->find(array('id' => $thread_id));
                if ($message_main_res) {
                    $thread_details_main = array();
                    $show_user_id = '';
                    $last_message = array();
                    $main_sender = $message_main_res->getSenderId();
                    $main_receiver = $message_main_res->getReceiverId();


                    $sender_main_user_detail = array();
                    $reciever_main_user_detail = array();
                    $sender_show_user_detail = array();
                    $sender_user_last_detail = array();

                    $message_last_dm = $container->get('doctrine.odm.mongodb.document_manager');
                    $message_last_res = $message_last_dm
                            ->getRepository('MessageMessageBundle:Message')
                            ->getLastMessage($thread_id, $user_id);


                    $reciever_user_last_detail = $user_service->UserObjectService($main_receiver);
                    $sender_user_last_detail = $user_service->UserObjectService($main_sender);

                    if ($message_last_res) {

                        //multiprofile code
                        $last_message = array(
                            'id' => $message_last_res[0]->getId(),
                            'thread_id' => $message_last_res[0]->getThreadId(),
                            'sender_user' => $sender_user_last_detail,
                            'receiver_user' => $reciever_user_last_detail,
                            'body' => $message_last_res[0]->getBody(),
                            'message_type' => $message_last_res[0]->getMessageType(),
                            'created_at' => $message_last_res[0]->getCreatedAt(),
                            'is_read' => $message_last_res[0]->getIsRead(),
                            'is_spam' => $message_last_res[0]->getIsSpam(),
                            'updated_at' => $message_last_res[0]->getUpdatedAt(),
                        );
                    }
                    /*
                      if(isset($sender_user_last_detail['id'])){
                      if($sender_user_last_detail['id'] == $user_id){
                      $sender_show_user_detail = $reciever_user_last_detail;
                      }else{
                      $sender_show_user_detail = $sender_user_last_detail;
                      }
                      }else{
                      $sender_show_user_detail = $sender_user_last_detail;
                      }
                     */

                    if ($main_sender == $user_id) {
                        $sender_show_user_detail = $reciever_user_last_detail;
                    } else {
                        $sender_show_user_detail = $sender_user_last_detail;
                    }

                    $thread_details_main = array(
                        'thread_id' => $message_main_res->getId(),
                        'user_detail' => $sender_show_user_detail,
                        // 'sender_user'=>$sender_main_user_detail ,
                        //'receiver_user'=>$reciever_main_user_detail ,
                        //'body'=> $message_main_res->getBody(),
                        'message_type' => $message_main_res->getMessageType(),
                        'created_at' => $message_main_res->getCreatedAt(),
                        // 'is_read'=> $message_main_res->getIsRead(),
                        // 'is_spam'=> $message_main_res->getIsSpam(),
                        //'updated_at'=> $message_main_res->getUpdatedAt(),
                        'last_message' => $last_message,
                    );

                    $all_thread_details[] = $thread_details_main;
                }
            }
        }

        if ($all_thread_details) {
            foreach ($all_thread_details as $key => $value) {
                if (isset($value['user_detail']['first_name'])) {
                    $findme = $first_name;
                    $mystring = $value['user_detail']['first_name'];
                    if ($value['user_detail']['first_name'] != '') {
                        $pos = strpos(strtolower($mystring), strtolower($findme));
                        if ($pos !== false) {

                        } else {
                            unset($all_thread_details[$key]);
                        }
                    }
                } else {
                    unset($all_thread_details[$key]);
                }
            }
        }

        $output = array_slice($all_thread_details, $offset, $limit);
        $total = count($output);
        $data = $output;
        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data, 'total' => $total);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Call api/reply action
     * @param Request $request
     * @return array
     */
    public function postReplymessagesAction(Request $request) {

        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        //get object of email template service
        $email_template_service = $this->container->get('email_template.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $message_profile_url = $this->container->getParameter('message_profile_url'); //message profile url
        $user_service = $this->get('user_object.service');
        //get locale
        $locale = $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('message_id', 'body', 'session_id', 'recipient');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $msg_id = $de_serialize['message_id'];
        $msg_body = $de_serialize['body'];
        $msg_sender = $de_serialize['session_id'];
        $msg_recipient = $de_serialize['recipient'];

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $msg_sender));
        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }

        $recieverManager = $this->getUserManager();
        $receiver_user = $recieverManager->findUserBy(array('id' => $msg_recipient));

        if ($receiver_user == '') {
            $data[] = "RECIPIENT_ID_IS_INVALID";
        }

        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }

        if (isset($_FILES['commentfile'])) {
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
            }
        }


        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->findOneBy(array("id" => $msg_id));

        if ($message_res) {
            $dm = $container->get('doctrine.odm.mongodb.document_manager');
            $message = new Message();
            $message->setThreadId($msg_id);
            $message->setSenderId($msg_sender);
            $message->setReceiverId($msg_recipient);
            $message->setBody($msg_body);
            $message->setSubject('');
            $message->setIsRead(0);
            $message->setIsSpam(0);
            $message->setMessageType('R');
            $message->setCreatedAt(time());
            $message->setUpdatedAt(time());
            $dm->persist($message);
            $dm->flush();
            $message_id = '';
            if ($message->getId()) {
                $message_id = $message->getId();
            }
            //for file uploading...
            if (isset($_FILES['commentfile'])) {
                $message_thumb_image_width = $this->message_thumb_image_width;
                $message_thumb_image_height = $this->message_thumb_image_height;
                foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                    $original_file_name = $_FILES['commentfile']['name'][$key];
                    $file_name = time() . strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));
                    //clean image name
                    $clean_name = $this->get('clean_name_object.service');
                    $file_name = $clean_name->cleanString($file_name);

                    $image_info = getimagesize($_FILES['commentfile']['tmp_name'][$key]);
                    $orignal_mediaWidth = $image_info[0];
                    $original_mediaHeight = $image_info[1];
                    //call service to get image type. Basis of this we save data 3,2,1 in db
                    $image_type_service = $this->get('user_object.service');
                    $image_type = $image_type_service->CheckImageType($orignal_mediaWidth, $original_mediaHeight, $message_thumb_image_width, $message_thumb_image_height);


                    //end image name
                    if (!empty($original_file_name)) { //if file name is not exists means file is not present.
                        $file_tmp = $_FILES['commentfile']['tmp_name'][$key];
                        $file_type = $_FILES['commentfile']['type'][$key];
                        $media_type = explode('/', $file_type);
                        $actual_media_type = $media_type[0];

                        $dm = $this->get('doctrine.odm.mongodb.document_manager');
                        $dashboard_comment_media = new MessageMedia();
                        if (!$key) //consider first image the featured image.
                            $dashboard_comment_media->setIsFeatured(1);
                        else
                            $dashboard_comment_media->setIsFeatured(0);
                        $dashboard_comment_media->setMessageId($message_id);
                        $dashboard_comment_media->setMediaName($file_name);
                        $dashboard_comment_media->setType($actual_media_type);
                        $dashboard_comment_media->setCreatedAt(time());
                        $dashboard_comment_media->setUpdatedAt(time());
                        $dashboard_comment_media->setPath('');
                        $dashboard_comment_media->setImageType($image_type);
                        $dashboard_comment_media->setIsActive(1);
                        $dashboard_comment_media->upload($message_id, $key, $file_name); //uploading the files.
                        $dm->persist($dashboard_comment_media);
                        $dm->flush();

                        if ($actual_media_type == 'image') {
                            $media_original_path = __DIR__ . "/../../../../web" . $this->message_media_path . $message_id . '/';
                            $media_original_path_to_be_croped = __DIR__ . "/../../../../web" . $this->message_media_path_thumb_crop . $message_id . "/";
                            $thumb_dir = __DIR__ . "/../../../../web" . $this->message_media_path_thumb . $message_id . "/";
                            $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->message_media_path_thumb_crop . $message_id . "/";

                            $resize_original_dir = __DIR__ . "/../../../../web/uploads/documents/dashboard/message/original/" . $message_id . '/';

                            if (preg_match('/[.](jpg)$/', $file_name) || preg_match('/[.](jpeg)$/', $file_name)) {
                                $image_rotate_service = $this->get('image_rotate_object.service');
                                $image_rotate = $image_rotate_service->ImageRotateService($media_original_path . $file_name);
                            }

                            //resize the original image..
                            $this->resizeOriginal($file_name, $media_original_path, $resize_original_dir, $message_id);

                            //first resize the post image into crop folder
                            $this->createThumbnail($file_name, $media_original_path, $thumb_crop_dir, $message_id);
                            //crop the image from center
                            $this->createCenterThumbnail($file_name, $media_original_path_to_be_croped, $thumb_dir, $message_id);
                        }
                    }
                }
            }

            //$msg_recipient = $message_res->getSenderId();

            $from_id = $msg_sender;
            $to_id = $msg_recipient;

            $this->sendNotificationMail($from_id, $to_id, 'single');
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        } else {
            $res_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        exit;
    }

    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
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
     * Checking for file extension
     * @param $_FILE
     * @return int $file_error
     */
    private function checkFileTypeAction() {
        $file_error = 0;
        foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['commentfile']['name'][$key]);
            //$filecheck = basename($_FILES['imagefile']['name']);
            if (!empty($file_name)) {
                $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
                //for video and images.

                if (!(((($ext == 'jpeg' || $ext == 'jpg' || $ext == 'gif' || $ext == 'png') &&
                        ($_FILES['commentfile']['type'][$key] == 'image/jpg' || $_FILES['commentfile']['type'][$key] == 'image/jpeg' ||
                        $_FILES['commentfile']['type'][$key] == 'image/gif' || $_FILES['commentfile']['type'][$key] == 'image/png'))) || (preg_match('/^.*\.(mp3|mp4|mov|mpg|mpeg|wmv|mkv)$/i', $file_name)))) {
                    $file_error = 1;
                    break;
                }
            }
        }
        return $file_error;
    }

    /**
     * Function to retrieve current applications base URI(hostname/project/web)
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        // return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl() . '/';
    }

    /**
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $comment_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $comment_id) {
        // $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/thumb_crop/";
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $thumb_width = $this->message_thumb_image_width;
        $thumb_height = $this->message_thumb_image_height;
        if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        }
        $ox = imagesx($im);
        $oy = imagesy($im);

        //getting aspect ratio
        $original_aspect = $ox / $oy;
        $thumb_aspect = $thumb_width / $thumb_height;

        if ($original_aspect >= $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $thumb_height;
            $new_width = $ox / ($oy / $thumb_height);
            if ($new_width < $thumb_width) {
                $new_width = $thumb_width;
                $new_height = $oy / ($ox / $thumb_width);
            }
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
            if ($new_height < $thumb_height) {
                $new_height = $thumb_height;
                $new_width = $ox / ($oy / $thumb_height);
            }
        }

        $nx = $new_width;
        $ny = $new_height;
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        //  imagejpeg($nm, $path_to_thumbs_directory . $filename);
        if (preg_match('/[.](jpg)$/', $filename)) {
            imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }
    }

    /**
     * getting the user info object
     * @param int $post_user_id
     * @param int $profile_type
     * @return array
     */
    private function getUserInfo($post_user_id, $profile_type) {
        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in UserMultiProfile Repository
        $return_user_profile_data = $em->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                ->getUserProfileDetails($post_user_id, $profile_type);
        $return_data = array();
        if (count($return_user_profile_data)) {
            $user_profile_data = $return_user_profile_data[0];
            $return_data = array('user_id' => $user_profile_data->getUserId(),
                'first_name' => $user_profile_data->getFirstName(),
                'last_name' => $user_profile_data->getLastName(),
                'email' => $user_profile_data->getEmail(),
                'gender' => $user_profile_data->getGender(),
                'birth_date' => $user_profile_data->getBirthDate(),
                'phone' => $user_profile_data->getPhone(),
                'country' => $user_profile_data->getCountry(),
                'street' => $user_profile_data->getStreet(),
                'profile_type' => $user_profile_data->getProfileType(),
                'created_at' => $user_profile_data->getCreatedAt(),
                'is_active' => $user_profile_data->getIsActive(),
                'updated_at' => $user_profile_data->getUpdatedAt(),
                'profile_setting' => $user_profile_data->getProfileSetting(),
                'type' => 'user'
            );
        }
        return $return_data;
    }

    /**
     * getting the store info object
     * @param int $store_id
     * @return array
     */
    private function getStoreInfo($store_id, $user_id) {

        //get entity manager object
        $em = $this->getDoctrine()->getManager();

        //fire the query in Store Repository
        $return_store_profile_data = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findBy(array('id' => $store_id));
        $return_data = array();
        if (count($return_store_profile_data)) {
            $store_profile_data = $return_store_profile_data[0];
            $return_data = array(
                'user_id' => $user_id,
                'store_id' => $store_profile_data->getId(),
                'parent_store_id' => $store_profile_data->getParentStoreId(),
                'title' => $store_profile_data->getTitle(),
                'email' => $store_profile_data->getEmail(),
                'url' => $store_profile_data->getUrl(),
                'description' => $store_profile_data->getDescription(),
                'address' => $store_profile_data->getAddress(),
                'contact_number' => $store_profile_data->getContactNumber(),
                'created_at' => $store_profile_data->getCreatedAt(),
                'is_active' => $store_profile_data->getIsActive(),
                'is_allowed' => $store_profile_data->getIsAllowed(),
                'type' => 'store'
            );
        }
        return $return_data;
    }

    /**
     * Listing unread messages list
     * @param Request $request
     * @return josn string
     */
    /*
      public function postListunreadmessagesAction(Request $request)
      {
      //code for aws s3 server path
      $aws_base_path  = $this->container->getParameter('aws_base_path');
      $aws_bucket    = $this->container->getParameter('aws_bucket');
      $aws_path = $aws_base_path.'/'.$aws_bucket;
      //initilise the data array
      $data = array();
      //Code repeat start
      $freq_obj = $request->get('reqObj');
      $fde_serialize = $this->decodeData($freq_obj);

      if(isset($fde_serialize)){
      $de_serialize = $fde_serialize;
      }else{
      $de_serialize = $this->getAppData($request);
      }
      $object_info = (object) $de_serialize; //convert an array into object.

      $required_parameter = array('user_id');
      //checking for parameter missing.
      $chk_error = $this->checkParamsAction($required_parameter, $object_info);
      if ($chk_error) {
      return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
      }
      $limit = (int)(isset($de_serialize['limit_size'])? $de_serialize['limit_size']:20);
      $offset = (int)(isset($de_serialize['limit_start'])? $de_serialize['limit_start']:0);

      $user_id = $object_info->user_id;

      $userManager = $this->getUserManager();
      $sender_user = $userManager->findUserBy(array('id' => $user_id));

      if($sender_user=='')
      {
      $data[] = "SENDER_ID_IS_INVALID";
      }

      if(!empty($data))
      {
      return array('code'=>100, 'message'=>'FAILURE','data'=>$data);
      }

      $container = MessageMessageBundle::getContainer();
      $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
      $message_res = $message_dm
      ->getRepository('MessageMessageBundle:Message')
      ->listAllUnreadThread($user_id,$limit,$offset);

      $message_total_dm = $container->get('doctrine.odm.mongodb.document_manager');
      $message_total_res = $message_total_dm
      ->getRepository('MessageMessageBundle:Message')
      ->listAllUnreadTotalThread($user_id);
      $all_thread_details = array();
      if($message_total_res){
      $total = count($message_total_res);
      }else{
      $total = 0;
      }
      $user_service = $this->get('user_object.service');
      $sender_ids_arr = array();
      $counter = 0;
      if($message_res){

      foreach($message_res as $message_res_render){
      if(in_array($message_res_render->getSenderId(), $sender_ids_arr)){
      $counter = $counter + 1;
      continue;
      }else{
      $sender_ids_arr[] = $message_res_render->getSenderId();
      }
      $message_media_dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
      $message_media_res = $message_media_dm->getRepository('MessageMessageBundle:MessageMedia')
      ->findBy(array('message_id'=>$message_res_render->getId()));
      $message_media_link = '';
      $message_media_thumb = '';
      if($message_media_res){
      foreach($message_media_res as $value){
      $message_media_name = $value->getMediaName();
      }

      if($message_media_name){
      $message_media_link = $aws_path . $this->message_media_path . $message_res_render->getId() . '/' . $message_media_name;
      $message_media_thumb = $aws_path . $this->message_media_path_thumb . $message_res_render->getId() . '/' . $message_media_name;
      }
      }

      $main_sender = $message_res_render->getSenderId();
      $main_receiver = $message_res_render->getReceiverId();
      $sender_main_user_detail = $user_service->UserObjectService($main_sender);
      $reciever_main_user_detail = $user_service->UserObjectService($main_receiver);

      $thread_details_main = array(
      'id'=>$message_res_render->getId() ,
      'thread_id'=>$message_res_render->getThreadId() ,
      'sender_user'=>$sender_main_user_detail ,
      'receiver_user'=>$reciever_main_user_detail ,
      'body'=> $message_res_render->getBody(),
      'message_type'=> $message_res_render->getMessageType(),
      'media_original_path'=>$message_media_link,
      'media_thumb_path'=>$message_media_thumb,
      'created_at'=> $message_res_render->getCreatedAt(),
      'is_read'=> $message_res_render->getIsRead(),
      'is_spam'=> $message_res_render->getIsSpam(),
      'updated_at'=> $message_res_render->getUpdatedAt(),
      );

      $all_thread_details[] = $thread_details_main;


      }

      }
      $total = $total - $counter;
      $data = $all_thread_details;
      $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data,'total'=>$total);
      echo json_encode($res_data);
      exit();
      }
     */

    /**
     * Listing unread messages list
     * @param Request $request
     * @return josn string
     */
    public function postListunreadmessagesAction(Request $request) {

        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);

        $user_id = $object_info->user_id;

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));

        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }

        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }

        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllUnreadThread($user_id, $limit, $offset);

        $message_total_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_total_res = $message_total_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllUnreadTotalThread($user_id);

        $user_ids_array = array();
        $user_ids_array = $message_total_res;
        $user_ids_array[] = $user_id;

        //find user object service..
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($user_ids_array);


        $all_thread_details = array();
        if ($message_total_res) {
            $total = count($message_total_res);
        } else {
            $total = 0;
        }

        $sender_ids_arr = array();

        if ($message_res) {

            foreach ($message_res as $message_res_render) {
                if (in_array($message_res_render->getSenderId(), $sender_ids_arr)) {
                    continue;
                } else {
                    $sender_ids_arr[] = $message_res_render->getSenderId();
                }

                $main_sender = $message_res_render->getSenderId();
                $main_receiver = $message_res_render->getReceiverId();
                $thread_details_main = array(
                    'id' => $message_res_render->getId(),
                    'thread_id' => $message_res_render->getThreadId(),
                    'sender_user' => isset($users_object_array[$main_sender]) ? $users_object_array[$main_sender] : array(),
                    //'receiver_user'=>isset($users_object_array[$main_receiver[0]]) ? $users_object_array[$main_receiver] : array() ,
                    'body' => $message_res_render->getBody(),
                    'message_type' => $message_res_render->getMessageType(),
                    'created_at' => $message_res_render->getCreatedAt(),
                    'is_read' => $message_res_render->getIsRead(),
                    'is_spam' => $message_res_render->getIsSpam(),
                    'updated_at' => $message_res_render->getUpdatedAt(),
                );

                $all_thread_details[] = $thread_details_main;
            }
        }

        $data = $all_thread_details;
        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data, 'total' => $total);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Listing mark all message as read
     * @param Request $request
     * @return josn string
     */
    public function postMarkreadallmessagesAction(Request $request) {
        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $user_id = $object_info->user_id;

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));

        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }

        if (!empty($data)) {
            return array('code' => 100, 'message' => 'failure', 'data' => $data);
        }

        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllUnreadTotalThread($user_id);

        if ($message_res) {
            foreach ($message_res as $record) {
                $record->setIsRead(1);
                $message_dm->flush();
            }
        }
        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * create thumbnail from center  for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function createCenterThumbnail($filename, $media_original_path, $thumb_dir, $comment_id) {
        $original_filename = $filename;
        //thumbnail image directory
        $path_to_thumbs_center_directory = __DIR__ . "/../../../../web" . $this->message_media_path_thumb . $comment_id . "/";
        //thumbnail image name with path
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory . $filename;
        $original_file_path = $filename;
        $filename = $media_original_path . $filename; //original image name with path

        if (preg_match('/[.](jpg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
            $image = imagecreatefromgif($filename);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
            $image = imagecreatefrompng($filename);
        }
        // Get dimensions of the original image
        list($current_width, $current_height) = getimagesize($filename);

        // The x and y coordinates on the original image where we
        // will begin cropping the image
        $width = imagesx($image);
        $height = imagesy($image);

        //crop image height and width.
        $crop_image_width = $this->message_thumb_image_width;
        $crop_image_height = $this->message_thumb_image_height;

        //login for crop the image from center
        $left = $width / 2;
        $left1 = $left - ($crop_image_width / 2);
        $top = $height / 2;
        $top1 = $top - ($crop_image_height / 2);

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left1, $top1, $crop_image_width, $crop_image_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        imagejpeg($canvas, $path_to_thumbs_center_image_path, 75); //100 is quality
        //upload on amazon
        $s3imagepath = "uploads/documents/message/thumb/" . $comment_id;
        $image_local_path = $media_original_path . $original_file_path;
        $this->s3imageUpload($s3imagepath, $image_local_path, $original_file_path);
    }

    /**
     * resize original for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function resizeOriginal($filename, $media_original_path, $thumb_dir, $comment_id) {
        //get image thumb width
        $thumb_width = $this->original_resize_image_width;
        $thumb_height = $this->original_resize_image_height;
        // $path_to_thumbs_directory = __DIR__ . "/../../../../web/uploads/documents/stores/gallery/" . $store_id . "/original/" . $album_id . '/';
        $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        //$final_width_of_image = 200;
        if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        }
        $ox = imagesx($im);
        $oy = imagesy($im);
        //check a image is less than defined size..
        if ($ox > $thumb_width || $oy > $thumb_height) {
            //getting aspect ratio
            $original_aspect = $ox / $oy;
            $thumb_aspect = $thumb_width / $thumb_height;

            if ($original_aspect >= $thumb_aspect) {
                // If image is wider than thumbnail (in aspect ratio sense)
                $new_height = $thumb_height;
                $new_width = $ox / ($oy / $thumb_height);
                //check if new width is less than minimum width
                if ($new_width > $thumb_width) {
                    $new_width = $thumb_width;
                    $new_height = $oy / ($ox / $thumb_width);
                }
            } else {
                // If the thumbnail is wider than the image
                $new_width = $thumb_width;
                $new_height = $oy / ($ox / $thumb_width);
                //check if new height is less than minimum height
                if ($new_height > $thumb_height) {
                    $new_height = $thumb_height;
                    $new_width = $ox / ($oy / $thumb_height);
                }
            }
            $nx = $new_width;
            $ny = $new_height;
        } else {
            $nx = $ox;
            $ny = $oy;
        }

        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresized($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
        if (preg_match('/[.](jpg)$/', $filename)) {
            imagejpeg($nm, $path_to_thumbs_directory . $filename, 75);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            imagejpeg($nm, $path_to_thumbs_directory . $filename, 75);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename, 75);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename, 9);
        }


        //upload on amazon
        $s3imagepath = "uploads/documents/message/original/" . $comment_id;
        $image_local_path = $path_to_image_directory . $filename;
        $this->s3imageUpload($s3imagepath, $image_local_path, $filename);
    }

    /**
     * Call Group Message Conversation
     * @param Request $request
     * @return array
     */
    public function postGroupmessagesendsAction(Request $request) {
        //code for aws s3 server path
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path . '/' . $aws_bucket;
        //get object of email template service
        $email_template_service = $this->container->get('email_template.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $message_profile_url = $this->container->getParameter('message_profile_url'); //message profile url
        $user_service = $this->get('user_object.service');
        //get locale
        $locale = $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        //initilise the data array
        $data = array();
        //Code repeat start
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeData($freq_obj);
//        if(isset($fde_serialize)){
//            $de_serialize = $fde_serialize;
//        }else{
//            $de_serialize = $this->getAppData($request);
//        }
        $de_serialize = $this->getRequestobj($request);
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        if (isset($_FILES['commentfile'])) {
            $required_parameter = array('thread_id', 'recipient', 'session_id');
        } else {
            $required_parameter = array('thread_id', 'body', 'recipient', 'session_id');
        }

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        // Set variable to save in db
        $thread_id = $de_serialize['thread_id'];
        $msg_body = $de_serialize['body'];
        $msg_sender = $de_serialize['session_id'];
        $msg_recipient = $de_serialize['recipient'];

        //$expArrReceipnt = explode(',',$msg_recipient);
        $expArrReceipnt = $msg_recipient;


        //$expArrReceipnt[] = $msg_sender;
        $readBy = array();
        $readBy[] = (integer) $msg_sender;

        // Check if receipnt user is more that one that it is group else one to one cnversation
        if (count($expArrReceipnt) > 1) {
            $thread_type = 'group';
            $group_default_name = 'Group';
        } else {
            $thread_type = 'single';
            $group_default_name = '';
        }
        // check user
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $msg_sender));
        $recipient_user = $userManager->findUserBy(array('id' => $msg_recipient));
        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }
        if ($recipient_user == '') {
            $data[] = "RECIPIENT_ID_IS_INVALID";
            print_r();
            exit;
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'failure', 'data' => $data);
        }
        if (isset($_FILES['commentfile'])) {
            $file_error = $this->checkFileTypeAction(); //checking the file type extension.
            if ($file_error) {
                return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
            }
        }
        $container = MessageMessageBundle::getContainer();
        $check_dm = $container->get('doctrine.odm.mongodb.document_manager');
        // Check If thread exit
        if ($thread_type == 'single' && $thread_id == 0) {
            $check_single_thread = $check_dm
                    ->getRepository('MessageMessageBundle:MessageThread')
                    ->checkSignleThreadExist($msg_sender, $msg_recipient);
            if (!empty($check_single_thread)) {
                $thread_id = $check_single_thread[0]->getId();
            }
        }
        // Update group members
        if ($thread_id != 0 && $thread_id != '' && $thread_type == 'group') {



            $receiver_id = array();
            // Get Thread/Group Info
            $thread_result = $check_dm
                    ->getRepository('MessageMessageBundle:MessageThread')
                    ->findBy(array("id" => $thread_id));
            if ($thread_result) {
                $receiver_id = $thread_result[0]->getGroupMembers();
                $receiver_id[] = $thread_result[0]->getCreatedBy();
                $addSender[] = $msg_sender;
                $expArrReceipntNew = array_merge($addSender, $expArrReceipnt);
                $new_added_members = array_diff($expArrReceipntNew, $receiver_id);
                $userIdMerge = array();
                if (!empty($new_added_members)) {
                    $user_ids_array = array_values($new_added_members);
                    $userIdMerge = array_merge($user_ids_array, $thread_result[0]->getGroupMembers());
                    //find user object service.
                    $user_service = $this->get('user_object.service');
                    //get user profile and cover images..
                    $users_object_array = $user_service->MultipleUserObjectService($user_ids_array);
                    // Update group member
                    // Get Group Info
                    $group_result = $check_dm
                            ->getRepository('MessageMessageBundle:MessageThread')
                            ->editThreadMember($thread_id, $thread_type, $userIdMerge);
                    if ($group_result) {
                        foreach ($user_ids_array as $value) {
                            $message = new Message();
                            $userInfo = $users_object_array[$value];
                            $msg_body_new = $userInfo['first_name'] . ' ' . $userInfo['last_name'] . ' is added in the group';
                            $message->setThreadId($thread_id);
                            $message->setSenderId($msg_sender);
                            $message->setReceiverId($expArrReceipnt);
                            $message->setBody($msg_body_new);
                            $message->setReadBy($readBy);
                            $message->setIsRead(0);
                            $message->setIsSpam(0);
                            $message->setDeleteby(array());
                            $message->setMessageType('N');
                            $message->setCreatedAt(time());
                            $message->setUpdatedAt(time());
                            $check_dm->persist($message);
                            $check_dm->flush();
                            $check_dm->clear();
                        }
                    }
                }
            } else {
                $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => array());
                return $res_data;
            }
        }
        // Create Thread/Group
        if ($thread_id == 0) {
            /*if ($thread_type == 'single') {
                $expArrReceipnt[] = $msg_sender;
            }*/
            $message_thread = new MessageThread();
            $message_thread->setName($group_default_name);
            $message_thread->setCreatedBy($msg_sender);
            $message_thread->setThreadType($thread_type);
            if ($thread_type == 'group') {
                $expArrReceipnt[] = $msg_sender;
            }
            $message_thread->setGroupMembers($expArrReceipnt);
            $message_thread->setCreatedAt(time());
            $message_thread->setUpdatedAt(time());
            $check_dm->persist($message_thread);
            $check_dm->flush();
            // Get current created thread id
            $thread_id = $message_thread->getId();
        }
        // Get Thread/Group Info
        $thread_result = $check_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->findBy(array("id" => $thread_id));
        // Create thread message
        if (!empty($thread_result)) {
            $receiver_id = array();
            $messageObj = new Message();
            if ($thread_type == 'group') {
                array_pop($expArrReceipnt);
            }
            $messageObj->setThreadId($thread_id);
            $messageObj->setSenderId($msg_sender);
            $messageObj->setReceiverId($expArrReceipnt);
            $messageObj->setBody($msg_body);
            $messageObj->setReadBy($readBy);
            $messageObj->setIsRead(0);
            $messageObj->setIsSpam(0);
            $messageObj->setDeleteby(array());
            $messageObj->setMessageType('M');
            $messageObj->setCreatedAt(time());
            $messageObj->setUpdatedAt(time());
            $check_dm->persist($messageObj);
            $check_dm->flush();
            // Update thread updated time according to last message send
            $thread_time = $check_dm
                    ->getRepository('MessageMessageBundle:MessageThread')
                    ->updateThreadTime($thread_id);
        } else {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => array());
            return $res_data;
        }
        $message_id = '';
        /* Message Updates Is View By */
        $this->SetUserIsviewBy($msg_sender, $messageObj->getId());
        /* End Here Message Update Is View By */
        if ($messageObj->getId()) {
            $message_id = $messageObj->getId();
            $created_at = $messageObj->getCreatedAt();
            $thread_name = $thread_result[0]->getName();
            // Set data to display
            $arrMergeVal = array();
            $senderVal2 = array();
            if ($thread_type == 'group') {
                $senderVal[] = $thread_result[0]->getCreatedBy();
                $senderVal2 = $thread_result[0]->getGroupMembers();
                $arrMergeVal = array_merge($senderVal, $senderVal2);
            } else {
                $arrMergeVal = $expArrReceipnt;
            }
            $data['thread_id'] = $thread_id;
            $data['message_id'] = $message_id;
            $data['body'] = $msg_body;
            $data['is_read'] = "0";
            $data['message_type'] = 'M';
            $data['thread_name'] = $thread_name;
            $data['session_id'] = $msg_sender;
            $data['recipient'] = array_unique($arrMergeVal);
            $data['created_at']['date'] = date('Y-m-d h:i:s', $created_at);
        }
        //for file uploading...
        if (isset($_FILES['commentfile'])) {
            foreach ($_FILES['commentfile']['tmp_name'] as $key => $tmp_name) {
                $original_file_name = $_FILES['commentfile']['name'][$key];
//              $file_name = time() . $_FILES['commentfile']['name'][$key];
                $file_name = time() . strtolower(str_replace(' ', '', $_FILES['commentfile']['name'][$key]));
                //clean image name
                $clean_name = $this->get('clean_name_object.service');
                $file_name = $clean_name->cleanString($file_name);
                //end image name
                if (!empty($original_file_name)) { //if file name is not exists means file is not present.
                    $file_tmp = $_FILES['commentfile']['tmp_name'][$key];
                    $file_type = $_FILES['commentfile']['type'][$key];
                    $media_type = explode('/', $file_type);
                    $actual_media_type = $media_type[0];

                    $dm = $this->get('doctrine.odm.mongodb.document_manager');
                    $dashboard_comment_media = new MessageMedia();
                    if (!$key) //consider first image the featured image.
                        $dashboard_comment_media->setIsFeatured(1);
                    else
                        $dashboard_comment_media->setIsFeatured(0);
                    $dashboard_comment_media->setMessageId($message_id);
                    $dashboard_comment_media->setMediaName($file_name);
                    $dashboard_comment_media->setType($actual_media_type);
                    $dashboard_comment_media->setCreatedAt(time());
                    $dashboard_comment_media->setUpdatedAt(time());
                    $dashboard_comment_media->setPath('');
                    $dashboard_comment_media->setIsActive(1);
                    $dashboard_comment_media->upload($message_id, $key, $file_name); //uploading the files.
                    $dm->persist($dashboard_comment_media);
                    $dm->flush();
                    if ($actual_media_type == 'image') {
                        $media_original_path = __DIR__ . "/../../../../web" . $this->message_media_path . $message_id . '/';
                        $media_original_path_to_be_croped = __DIR__ . "/../../../../web" . $this->message_media_path_thumb_crop . $message_id . "/";
                        $thumb_dir = __DIR__ . "/../../../../web" . $this->message_media_path_thumb . $message_id . "/";
                        $thumb_crop_dir = __DIR__ . "/../../../../web" . $this->message_media_path_thumb_crop . $message_id . "/";
                        $resize_original_dir = __DIR__ . "/../../../../web/uploads/documents/dashboard/message/original/" . $message_id . '/';
                        if (preg_match('/[.](jpg)$/', $file_name) || preg_match('/[.](jpeg)$/', $file_name)) {
                            $image_rotate_service = $this->get('image_rotate_object.service');
                            $image_rotate = $image_rotate_service->ImageRotateService($media_original_path . $file_name);
                        }
                        //resize the original image..
                        $this->resizeOriginal($file_name, $media_original_path, $resize_original_dir, $message_id);
                        //first resize the post image into crop folder
                        $this->createThumbnail($file_name, $media_original_path, $thumb_crop_dir, $message_id);
                        //crop the image from center
                        $this->createCenterThumbnail($file_name, $media_original_path_to_be_croped, $thumb_dir, $message_id);
                    }
                }
            }
        }
        if ($messageObj->getId() == '') {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        } else {
            if ($thread_type == 'single') {
                //send mail
                $from_id = $msg_sender;
                $to_id = $msg_recipient[0];
                $userManager = $this->getUserManager();
                $from_user = $userManager->findUserBy(array('id' => (int) $from_id));
                $to_user = $userManager->findUserBy(array('id' => (int) $to_id));
                $this->sendNotificationMail($from_id, $to_id, 'single');
            } elseif ($thread_type == 'group') {
                if ($de_serialize['thread_id'] == 0 || $de_serialize['thread_id'] == '') {
                    $_newAddedMembers = $expArrReceipnt;
                } else {
                    $_newAddedMembers = !empty($new_added_members) ? $new_added_members : array();
                }

                if (!empty($_newAddedMembers)) {
                    //send mail
                    $_toIds = array_values($_newAddedMembers);


                    $from_id = $msg_sender;
                    $from_user = $userManager->findUserBy(array('id' => (int) $from_id));
                    if ($from_user) {
                        $fname = $from_user->getFirstname();
                        $lastname = $from_user->getLastname();
                        $username = $from_user->getUsername();
                        $sender_name = ucfirst($fname) . " " . ucfirst($lastname);
                    } else {
                        $sender_name = '';
                    }


                    $this->sendNotificationMail($from_id, $_toIds, 'group');
                }
            }

            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
        exit;
    }

    /**
     * Listing all group message
     * @param Request $request
     * @return josn string
     */
    public function postListgroupmessagesAction(Request $request) {
        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //code for aws s3 server path
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path . '/' . $aws_bucket;
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('thread_id', 'user_id');

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);

        $group_id = $object_info->thread_id;
        $user_id = $object_info->user_id;
        // check user
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }

        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        // Get Group Info
        $group_result = $message_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->findBy(array("id" => $group_id));
        $messages_res_all = array();
        $total = 0;
        if (!empty($group_result)) {
            $group_name = $group_result[0]->getName();
            $group_type = $group_result[0]->getThreadType();
            $group_members_all = $group_result[0]->getGroupMembers();
            if (empty($group_members_all)) {
                $group_members_all = $group_result[0]->getCreatedBy();
            }
            $messages_res_all = array();
            $messages_res_all['group_id'] = $group_id;
            $messages_res_all['group_name'] = $group_name;
            $messages_res_all['group_type'] = $group_type;

            $arrMergeVal = array();
            if ($group_type == 'group') {
                $senderVal[] = $group_result[0]->getCreatedBy();
                $senderVal2 = $group_result[0]->getGroupMembers();
                $arrMergeVal = array_merge($senderVal, $senderVal2);
            }

            if ($group_type == 'single') {
                $_reciever = $user_id == $group_members_all[0] ? $group_result[0]->getCreatedBy() : $group_members_all[0];
                if (is_array($_reciever)) {
                    $messages_res_all['recipient'] = $_reciever;
                } else {
                    $messages_res_all['recipient'] = array($_reciever);
                }
            } else {
                $messages_res_all['recipient'] = $arrMergeVal;
            }

            // Get Group Messages
            $message_all_data = $message_dm
                    ->getRepository('MessageMessageBundle:Message')
                    ->getGroupMessages($group_id, $user_id, $limit, $offset);

            // Get Group Messages Total Count
            $message_total_res = $message_dm
                    ->getRepository('MessageMessageBundle:Message')
                    ->getGroupMessagesCount($group_id, $user_id);

            if ($message_total_res) {
                $total = $message_total_res;
            }
            $user_ids_array = array();
            $sender_ids = array();
            //getting the sender ids.
            $sender_ids = array_map(function($message_res_record) {
                return "{$message_res_record->getSenderId()}";
            }, $message_all_data);
            $user_ids_array = array_unique($sender_ids);

            //getting the posts ids.
            $thread_message_ids = array_map(function($message_thread_res_main_record) {
                return "{$message_thread_res_main_record->getId()}";
            }, $message_all_data);

            $message_media_all = $message_dm->getRepository('MessageMessageBundle:MessageMedia')
                    ->getAllThreadMedias($thread_message_ids);
            $message_media_all_final = array();
            if ($message_media_all) {
                foreach ($message_media_all as $message_media_record) {
                    $message_media_all_final[$message_media_record->getMessageId()][] = $message_media_record;
                }
            }
            // set media variables
            $message_media_link = '';
            $message_media_thumb = '';
            //find user object service.
            $user_service = $this->get('user_object.service');
            //get user profile and cover images..
            $users_object_array = $user_service->MultipleUserObjectService($user_ids_array);
            // List message
            if ($message_all_data) {
                foreach ($message_all_data as $key => $msgVal) {
                    if (isset($message_media_all_final[$msgVal->getId()])) {
                        $message_media_res = $message_media_all_final[$msgVal->getId()];
                        if ($message_media_res) {
                            $message_media_name = $message_media_res[0]->getMediaName();
                            if ($message_media_name != '') {
                                $message_media_link = $aws_path . $this->message_media_path . $msgVal->getId() . '/' . $message_media_name;
                                $message_media_thumb = $aws_path . $this->message_media_path_thumb . $msgVal->getId() . '/' . $message_media_name;
                            }
                        }
                    }


                    $messages_res_all['messages'][$key]['message_id'] = $msgVal->getId();
                    $messages_res_all['messages'][$key]['session_id'] = $msgVal->getSenderId();
                    if ($group_type == 'group') {
                        $messages_res_all['messages'][$key]['recipient'] = $arrMergeVal;
                    } else {
                        if (is_array($msgVal->getReceiverId())) {
                            $messages_res_all['messages'][$key]['recipient'] = $msgVal->getReceiverId();
                        } else {
                            $messages_res_all['messages'][$key]['recipient'] = array($msgVal->getReceiverId());
                        }
                    }
                    $messages_res_all['messages'][$key]['body'] = $msgVal->getBody();
                    $messages_res_all['messages'][$key]['media_original_path'] = $message_media_link;
                    $messages_res_all['messages'][$key]['media_thumb_path'] = $message_media_thumb;
                    $messages_res_all['messages'][$key]['message_type'] = $msgVal->getMessageType();
                    $messages_res_all['messages'][$key]['is_read'] = $msgVal->getIsRead();
                    $messages_res_all['messages'][$key]['created_at'] = $msgVal->getCreatedAt();
                    $messages_res_all['messages'][$key]['sender_user'] = isset($users_object_array[$msgVal->getSenderId()]) ? $users_object_array[$msgVal->getSenderId()] : array();

//                    $group_members = $msgVal->getReceiverId();
//                    $arrMerge = array_merge(array(),$group_members);
                    $usrDetails = $user_service->MultipleUserListObjectService(array_unique($group_members_all));
                    $messages_res_all['messages'][$key]['receiver_user'] = $usrDetails;

                    $message_media_link = '';
                    $message_media_thumb = '';
                }
            } else {
                $messages_res_all['messages'] = array();
            }
        }
        $data = $messages_res_all;
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data, 'total' => $total);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Listing Group
     * @param Request $request
     * @return josn string
     */
    public function postListgroupsAction(Request $request) {

        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);
        $user_id = $object_info->user_id;
        // Check User
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }
        // doctrine
        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        // Get Group Info
        $group_result = $message_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->listGroup($user_id, $limit, $offset);
        // Get Group Info count
        $group_result_cnt = $message_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->listGroupCnt($user_id);

        //find user object service..
        $user_service = $this->get('user_object.service');
        $messages_res_all = array();
        $total = 0;
        if ($group_result_cnt) {
            $total = $group_result_cnt;
        }

        $group_members = array();
        if (!empty($group_result)) {
            foreach ($group_result as $key => $value) {
                $messages_res_all[$key]['thread_id'] = $value->getId();
                $messages_res_all[$key]['thread_name'] = $value->getName();
                $messages_res_all[$key]['thread_type'] = $value->getThreadType();
                $messages_res_all[$key]['createdBy'] = $value->getCreatedBy();
                $messages_res_all[$key]['createdAt'] = $value->getCreatedAt();
                // to do remove this query from the foreach loop
                $message_last_res = $message_dm
                        ->getRepository('MessageMessageBundle:Message')
                        ->getThreadLastMessage($value->getId(), $user_id);

                if ($message_last_res) {
                    // Check read by
                    $readBy = $message_last_res[0]->getReadBy();
                    $readBy = $readBy ? $readBy : array();
                    if (in_array($user_id, $readBy)) {
                        $is_read = 1;
                    } else {
                        $is_read = 0;
                    }

                    $messages_res_all[$key]['last_message']['body'] = $message_last_res[0]->getbody();
                    $messages_res_all[$key]['last_message']['created_at'] = $message_last_res[0]->getCreatedAt();
                    $messages_res_all[$key]['last_message']['is_read'] = $is_read;
                    $messages_res_all[$key]['last_message']['is_spam'] = $message_last_res[0]->getIsSpam();
                    $messages_res_all[$key]['last_message']['updated_at'] = $message_last_res[0]->getUpdatedAt();
                } else {
                    $messages_res_all[$key]['last_message']['body'] = '';
                }
                // User details
                if ($messages_res_all[$key]['thread_type'] == 'single') {
                    if ($messages_res_all[$key]['createdBy'] == $user_id) {
                        $thread_sender_id = $value->getGroupMembers();
                    } else {
                        $thread_sender_id = $messages_res_all[$key]['createdBy'];
                    }

                    if (count($value->getGroupMembers()) <= 0) {
                        $deleted_by = $value->getDeleteBy();
                        $thread_sender_id = $deleted_by[0];
                    }
                    $usrDetails = $user_service->MultipleUserListObjectService($thread_sender_id);

                    if (is_array($usrDetails) && count($usrDetails)) {
                        $messages_res_all[$key]['thread_members'] = array($usrDetails[0]);
                    } else {
                        $messages_res_all[$key]['thread_members'] = array();
                    }
                } else {
                    $group_members = $value->getGroupMembers();
                    //$group_members[] = $messages_res_all[$key]['createdBy'];
                    //print_r($group_members);
                    //exit;
//                    if( count($value->getGroupMembers()) <= 0 ){
//                       $deleted_by = $value->getDeleteBy();
//                       foreach($deleted_by as $deleted_user)
//                        {
//                           $group_members[] = $deleted_user;
//                        }
//
//                    }
                    $messages_res_all[$key]['thread_members'] = $user_service->MultipleUserListObjectService(array_unique($group_members));
                }
            }
        }

        $data = $messages_res_all;
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data, 'total' => $total);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Edit Group Name
     * @param Request $request
     * @return josn string
     */
    public function editGroupAction(Request $request) {
        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('group_id', 'group_name');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $group_id = $object_info->group_id;
        $group_name = $object_info->group_name;
        // Check empty value
        /*         * *
         * ERRRRRROOOR
         */
        if ($group_id == '' && trim($group_name) == '') {
            $res_data = array('code' => 100, 'message' => 'EMPTY_DATA', 'data' => array());
            echo json_encode($res_data);
            exit();
        }
        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        // Get Group Info
        $group_result = $message_dm
                ->getRepository('MessageMessageBundle:MessageGroup')
                ->editGroup($group_id, $group_name);
        if ($group_result) {
            $messages_res_all['group_id'] = $group_id;
            $messages_res_all['group_name'] = $group_name;
            $data = $messages_res_all;
            $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        } else {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => array());
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * Remove Group from self environment
     * @param Request $request
     * @return josn string
     */
    public function postRemovegroupusersAction(Request $request) {
        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('session_id', 'deleted_user_id', 'thread_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $group_id = $object_info->thread_id;
        $user_id = $object_info->session_id;
        $deleted_user_id = $object_info->deleted_user_id;

        // Check empty value
        if ($user_id == '' && trim($group_id) == '') {
            $res_data = array('code' => 100, 'message' => 'EMPTY_DATA', 'data' => array());
            echo json_encode($res_data);
            exit();
        }

        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        // Get Group Info
        $group = $message_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->find($group_id);

        if (!$group) {
            $res_data = array('code' => 100, 'message' => 'NO_GROUP_EXISTS', 'data' => ARRAY());
            echo json_encode($res_data);
            exit();
        }
        $new_array = array();
        $group_members = $group->getGroupMembers();
        //$group_members[] = $user_id;
        $creater_id = $group->getCreatedBy();
        /*if ($user_id == $deleted_user_id) {
                $res_data = array('code' => 302, 'message' => 'ACTION_NOT_PERMITED', 'data' => array());
                echo json_encode($res_data);
                exit;
        }*/
        if ($user_id != $creater_id) {
            if ($user_id != $deleted_user_id) {
                $res_data = array('code' => 302, 'message' => 'ACTION_NOT_PERMITED', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
        }
        /* echo "deleted User Id and created Id";
          echo $deleted_user_id.'---'.$creater_id; */
            $index = array_search($deleted_user_id, $group_members);

        /* echo "index";
          echo $index;
          echo "Group Member";
          print_r($group_members); */
        if ($deleted_user_id != $creater_id) {

                unset($group_members[$index]);

        }
        if ($deleted_user_id == $creater_id) {

            unset($group_members[$index]);
        }
        $group_members_ids = array_values($group_members);
        //echo "Group Member Id";
        //print_r($group_members_ids);
        $group->setGroupMembers($group_members_ids);
        $deletedGroupUsers = $group->getDeleteBy($group_members_ids);
        $deletedGroupUsers[] = $deleted_user_id;
        $group->setDeleteBy($deletedGroupUsers);
        $message_dm->persist($group);
        $message_dm->flush();
        //find user object service.
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($deleted_user_id);

        $message = new Message();
        $userInfo = $users_object_array[$deleted_user_id];
        $msg_body_new = $userInfo['first_name'] . ' ' . $userInfo['last_name'] . ' has Left Conversation';
        $message->setThreadId($group_id);
        $message->setSenderId($user_id);
        $message->setReceiverId($group_members);
        $message->setBody($msg_body_new);
        $message->setReadBy(array($user_id));
        $message->setIsRead(0);
        $message->setIsSpam(0);
        $message->setDeleteby(array());
        $message->setMessageType('N');
        $message->setCreatedAt(time());
        $message->setUpdatedAt(time());
        $message_dm->persist($message);
        $message_dm->flush();
        $message_dm->clear();
//        $message = new Message();
//        $message->setDeleteby($deleted_user_id);
//        $check_dm->persist($message);
//        $check_dm->flush();
//        $check_dm->clear();
//
        // Update message
        $groupMszUpdate = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->updateUnreadMessage($group_id, $deleted_user_id);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);

        exit();
    }

    /**
     * Call api/deletemessage action
     * @param Request $request
     * @return array
     */
    public function postDeletegroupmessagesAction(Request $request) {
        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('session_id', 'message_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $message_id = $de_serialize['message_id'];
        $msg_sender = $de_serialize['session_id'];

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $msg_sender));
        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }
        $container = MessageMessageBundle::getContainer();

        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->findOneBy(array("id" => $message_id));
        if ($message_res) {
            $sender_id = $message_res->getSenderId();
            if ($sender_id != $msg_sender) {
                $message_res_id = $message_res->getId();
                $del_str = $message_res->getDeleteby();
                $del_str[] = $msg_sender;
                $message_res->setDeleteby($del_str);
                $message_dm->flush();
                $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($res_data);
                exit();
            } else {
                $data[] = "User can not delete own message.";
                $res_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
                echo json_encode($res_data);
                exit();
            }
        } else {
            $data[] = "Message Id does not exist";
            $res_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }

        exit;
    }

    /**
     * Get Message Search
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function postSearchmessagesAction(Request $request) {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('user_id', 'thread_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);
        $group_id = $object_info->thread_id;
        $user_id = $object_info->user_id;
        $searchText = trim($object_info->keyword);
        // check user
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }
        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        // Get Group Info
        $group_result = $message_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->findBy(array("id" => $group_id));
        $messages_res_all = array();
        $total = 0;
        if (!empty($group_result)) {
            $group_name = $group_result[0]->getName();
            $messages_res_all = array();
            $messages_res_all['group_id'] = $group_id;
            $messages_res_all['group_name'] = $group_name;
            // Get Group Messages
            $message_all_data = $message_dm
                    ->getRepository('MessageMessageBundle:Message')
                    ->getMessageSearch($group_id, $searchText, $user_id, $limit, $offset);
            // Get Group Messages Total Count
            $message_total_res = $message_dm
                    ->getRepository('MessageMessageBundle:Message')
                    ->getMessageSearchCnt($group_id, $searchText, $user_id);
            if ($message_total_res) {
                $total = $message_total_res;
            }
            $user_ids_array = array();
            $sender_ids = array();
            //getting the sender ids.
            $sender_ids = array_map(function($message_res_record) {
                return "{$message_res_record->getSenderId()}";
            }, $message_all_data);
            $user_ids_array = array_unique($sender_ids);
            //find user object service.
            $user_service = $this->get('user_object.service');
            //get user profile and cover images..
            $users_object_array = $user_service->MultipleUserObjectService($user_ids_array);
            // List message
            if ($message_all_data) {
                foreach ($message_all_data as $key => $msgVal) {
                    $messages_res_all['messages'][$key]['message_id'] = $msgVal->getId();
                    $messages_res_all['messages'][$key]['session_id'] = $msgVal->getSenderId();
                    $messages_res_all['messages'][$key]['recipient'] = $msgVal->getReceiverId();
                    $messages_res_all['messages'][$key]['body'] = $msgVal->getBody();
                    $messages_res_all['messages'][$key]['message_type'] = $msgVal->getMessageType();
                    $messages_res_all['messages'][$key]['is_read'] = $msgVal->getIsRead();
                    $messages_res_all['messages'][$key]['created_at'] = $msgVal->getCreatedAt();
                    $messages_res_all['messages'][$key]['sender_user'] = isset($users_object_array[$msgVal->getSenderId()]) ? $users_object_array[$msgVal->getSenderId()] : array();
                }
            } else {
                $messages_res_all['messages'] = array();
            }
        }
        $data = $messages_res_all;
        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data, 'total' => $total);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Call api/readmessage group action
     * @param Request $request
     * @return array
     */
    public function postReadgroupmessagesAction(Request $request) {
        //initilise the data array
        $data = array();

        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('session_id', 'thread_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        $message_id = $de_serialize['thread_id'];
        $msg_sender = $de_serialize['session_id'];

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $msg_sender));
        if ($sender_user == '') {
            $data[] = "USER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }
        $container = MessageMessageBundle::getContainer();

        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_thread_res = $message_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->findBy(array("id" => $message_id));

        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->findBy(array("thread_id" => $message_id, "receiver_id" => $msg_sender));
        // Set Read By
        $readBy = array();
        $readBy[] = (integer) $msg_sender;
        if ($message_thread_res) {
            if ($message_res) {
                foreach ($message_res as $record) {
                    $readByAlready = $record->getReadBy();
                    $user_id_merge = array_merge($readByAlready, $readBy);
                    $record->setReadBy($user_id_merge);
                    $message_dm->flush();
                }
            }
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        } else {
            $data[] = "Message Id does not exist";
            $res_data = array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * Listing group unread messages list
     * @param Request $request
     * @return josn string
     */
    public function postListgroupunreadmessagesAction(Request $request) {
        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'is_view');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);

        $user_id = $object_info->user_id;
        $is_view = $object_info->is_view;

        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));

        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }

        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }

        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');

        //get deleted member thread id
        $deleted_thread = $message_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->listGroupDeletedMemebersThreadId($user_id);

        //getting deleted member thread id array
        if (is_array($deleted_thread) && count($deleted_thread)) {
            $thread_ids = array_map(function($threads) {
                return $threads->getId();
            }, $deleted_thread);
        } else {
            $thread_ids = array();
        };

        /* Update Is View Status on message */
        $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->getisviewUpdateGroupUnreadThread($user_id, $is_view);

        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllGroupUnreadThread($user_id, $limit, $offset, $thread_ids);

        $message_total_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $message_total_res = $message_total_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllGroupUnreadTotalThread($user_id, $thread_ids);
        $user_ids_array = array();
//        $user_ids_array = $message_total_res;
        $user_ids_array[] = $user_id;

        //find user object service..
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($user_ids_array);
        $all_thread_details = array();
        if ($message_total_res) {
            $total = count($message_total_res);
        } else {
            $total = 0;
        }
        $sender_ids_arr = array();
        $main_sender = array();
        $thread_id = array();

        if ($message_res) {
            foreach ($message_res as $message_res_render) {

                if (in_array($message_res_render->getSenderId(), $sender_ids_arr)) {
                    // continue;
                } else {
                    $sender_ids_arr[] = $message_res_render->getSenderId();
                }
                if (in_array($message_res_render->getThreadId(), $thread_id)) {
                    continue;
                } else {
                    $thread_id[] = $message_res_render->getThreadId();
                }
                /* Message Updates Is View By */
                //echo $message_res_render->getId().$user_id;exit;
                $this->SetUserIsviewBy($user_id, $message_res_render->getId());
                /* End Here Message Update Is View By */
                $main_sender = $message_res_render->getSenderId();
                $usrDetailsSender = $user_service->MultipleUserListObjectService($main_sender);
                $group_members = $message_res_render->getReceiverId();
                $arrMerge = array_merge(array(), $group_members);
                $usrDetails = $user_service->MultipleUserListObjectService(array_unique($arrMerge));
                $thread_details_main = array(
                    'id' => $message_res_render->getId(),
                    'thread_id' => $message_res_render->getThreadId(),
                    'sender_user' => $usrDetailsSender[0],
                    'receiver_user' => $usrDetails,
                    'body' => $message_res_render->getBody(),
                    'message_type' => $message_res_render->getMessageType(),
                    'created_at' => $message_res_render->getCreatedAt(),
                    'is_read' => $message_res_render->getIsRead(),
                    'is_spam' => $message_res_render->getIsSpam(),
                    'updated_at' => $message_res_render->getUpdatedAt(),
                );
                $all_thread_details[] = $thread_details_main;
            }
        }
        $data = $all_thread_details;
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data, 'total' => $total);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Listing Group
     * @param Request $request
     * @return josn string
     */
//    public function postSearchuserthreadsAction(Request $request) {
//        //initilise the data array
//        $data = array();
//        //Code repeat start
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeData($freq_obj);
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }
//        //Code end for getting the request
//        $object_info = (object) $de_serialize; //convert an array into object.
//        $required_parameter = array('user_id', 'firstname');
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
//        }
//        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
//        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);
//        $user_id = $object_info->user_id;
//        $first_name = $object_info->firstname;
//        // Check User
//        $userManager = $this->getUserManager();
//        $sender_user = $userManager->findUserBy(array('id' => $user_id));
//        if ($sender_user == '') {
//            $data[] = "SENDER_ID_IS_INVALID";
//        }
//        if (!empty($data)) {
//            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
//        }
//        if ($first_name == '') {
//            $data[] = "firstname can not be empty.";
//            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
//        }
//        // doctrine
//        $container = MessageMessageBundle::getContainer();
//        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
//        // Get Group Info
//        $group_result = $message_dm
//                ->getRepository('MessageMessageBundle:MessageThread')
//                ->listGroup($user_id, $limit, $offset);
//        // Get Group Info count
//        $group_result_cnt = $message_dm
//                ->getRepository('MessageMessageBundle:MessageThread')
//                ->listGroupCnt($user_id);
//
//        //find user object service..
//        $user_service = $this->get('user_object.service');
//        $messages_res_all = array();
//        $total = 0;
//        if ($group_result_cnt) {
//            $total = $group_result_cnt;
//        }
//        $group_members = array();
//        if (!empty($group_result)) {
//
//            foreach ($group_result as $key => $value) {
//
//                $messages_res_all[$key]['thread_id'] = $value->getId();
//                $messages_res_all[$key]['thread_name'] = $value->getName();
//                $messages_res_all[$key]['thread_type'] = $value->getThreadType();
//                $messages_res_all[$key]['createdBy'] = $value->getCreatedBy();
//                $messages_res_all[$key]['createdAt'] = $value->getCreatedAt();
//                // to do remove this query from the foreach loop
//                $message_last_res = $message_dm
//                        ->getRepository('MessageMessageBundle:Message')
//                        ->getThreadLastMessage($value->getId(), $user_id);
//
//                if ($message_last_res) {
//                    // Check read by
//                    $readBy = $message_last_res[0]->getReadBy();
//                    $readBy = $readBy ? $readBy : array();
//                    if (in_array($user_id, $readBy)) {
//                        $is_read = 1;
//                    } else {
//                        $is_read = 0;
//                    }
//                    $messages_res_all[$key]['last_message']['body'] = $message_last_res[0]->getbody();
//                    $messages_res_all[$key]['last_message']['created_at'] = $message_last_res[0]->getCreatedAt();
//                    $messages_res_all[$key]['last_message']['is_read'] = $is_read;
//                    $messages_res_all[$key]['last_message']['is_spam'] = $message_last_res[0]->getIsSpam();
//                    $messages_res_all[$key]['last_message']['updated_at'] = $message_last_res[0]->getUpdatedAt();
//                } else {
//                    $messages_res_all[$key]['last_message']['body'] = '';
//                }
//                // User details
//                if ($messages_res_all[$key]['thread_type'] == 'single') {
//
//                    if ($messages_res_all[$key]['createdBy'] == $user_id) {
//                        $thread_sender_id = $value->getGroupMembers();
//                    } else {
//                        $thread_sender_id = $messages_res_all[$key]['createdBy'];
//                    }
//                    $usrDetails = $user_service->MultipleUserListObjectService($thread_sender_id);
//                    $messages_res_all[$key]['user_detail'] = $usrDetails[0];
//                } else {
//
//                    $group_members = $value->getGroupMembers();
//                    $group_members[] = $messages_res_all[$key]['createdBy'];
//                    $messages_res_all[$key]['thread_members'] = $user_service->MultipleUserListObjectService(array_unique($group_members));
//                }
//            }
//        }
//        // Sort user according to search
//        if ($messages_res_all) {
//
//            $findme = $first_name;
//            $myVal = true;
//            $Firstmystring ='';
//            $Lastmystring ='';
//            foreach ($messages_res_all as $key => $value) {
//                if ($value['thread_type'] == 'single') {
//                    if (isset($value['user_detail']['first_name'])) {
//                        $Firstmystring = $value['user_detail']['first_name'];
//                            $Lastmystring = $value['user_detail']['last_name'];
//                            $mystring = $Firstmystring.' '. $Lastmystring;
//                            if ($Firstmystring != '' || $Lastmystring !='') {
//                                    $pos = strpos(strtolower($mystring), strtolower($findme));
//                                    //$pos = isset($Firstmystring)?strpos(strtolower($mystring), strtolower($findme)):strpos(strtolower($mystring), strtolower($findme));
//
//                        //if ($value['user_detail']['first_name'] != '') {
//                          //  $pos = strpos(strtolower($mystring), strtolower($findme));
//                            if ($pos !== false) {
//
//                            } else {
//                                unset($messages_res_all[$key]);
//                            }
//                        }
//                    } else {
//                        unset($messages_res_all[$key]);
//                    }
//                } else {
//                    if (!empty($value['thread_members'])) {
//                        //echo count($value['thread_members']);exit;
//                        foreach ($value['thread_members'] as $usrKey => $usrValue) {
//                            $Firstmystring = $usrValue['first_name'];
//                            $Lastmystring = $usrValue['last_name'];
//                            $mystring = $Firstmystring.' '.$Lastmystring;
//                            if ($myVal) {
//                                if ($Firstmystring != '' || $Lastmystring !='') {
//                                    $pos = strpos(strtolower($mystring), strtolower($findme));
//                                    //$pos = isset($Firstmystring)?strpos(strtolower($mystring), strtolower($findme)):strpos(strtolower($mystring), strtolower($findme));
//                                    if ($pos !== false) {
//                                        echo ' MyVal'.$myVal;
//                                        $myVal = false;
//                                    } else {
//                                        unset($messages_res_all[$key]);
//                                    }
//                                }
//                                else {
//                                    unset($messages_res_all[$key]);
//                                }
//                            }
//                        }
//                    }
//                }
//            }
//        }
//        //exit;
//        $output = array_slice($messages_res_all, $offset, $limit);
//        $total = count($output);
//        $data = $output;
//        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data, 'total' => $total);
//        echo json_encode($res_data);
//        exit();
//    }


    public function postSearchuserthreadsAction(Request $request) {
        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('user_id', 'firstname');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);
        $user_id = $object_info->user_id;
        $first_name = $object_info->firstname;
        // Check User
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }
        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }
        if ($first_name == '') {
            $data[] = "firstname can not be empty.";
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }
        // doctrine
        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        // Get Group Info
        $group_result = $message_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->listGroup($user_id, $limit, $offset);
        // Get Group Info count
        $group_result_cnt = $message_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->listGroupCnt($user_id);

        //find user object service..
        $user_service = $this->get('user_object.service');
        $messages_res_all = array();

        $total = 0;
        if ($group_result_cnt) {
            $total = $group_result_cnt;
        }
        $group_members = array();
        if (!empty($group_result)) {

            foreach ($group_result as $key => $value) {
                //unset($messages_res_all[$key]);
                $messages_res_all[$key]['thread_id'] = $value->getId();
                $messages_res_all[$key]['thread_name'] = $value->getName();
                $messages_res_all[$key]['thread_type'] = $value->getThreadType();
                $messages_res_all[$key]['createdBy'] = $value->getCreatedBy();
                $messages_res_all[$key]['createdAt'] = $value->getCreatedAt();
                // to do remove this query from the foreach loop
                $message_last_res = $message_dm
                        ->getRepository('MessageMessageBundle:Message')
                        ->getThreadLastMessage($value->getId(), $user_id);

                if ($message_last_res) {
                    // Check read by
                    $readBy = $message_last_res[0]->getReadBy();
                    $readBy = $readBy ? $readBy : array();
                    if (in_array($user_id, $readBy)) {
                        $is_read = 1;
                    } else {
                        $is_read = 0;
                    }
                    $messages_res_all[$key]['last_message']['body'] = $message_last_res[0]->getbody();
                    $messages_res_all[$key]['last_message']['created_at'] = $message_last_res[0]->getCreatedAt();
                    $messages_res_all[$key]['last_message']['is_read'] = $is_read;
                    $messages_res_all[$key]['last_message']['is_spam'] = $message_last_res[0]->getIsSpam();
                    $messages_res_all[$key]['last_message']['updated_at'] = $message_last_res[0]->getUpdatedAt();
                } else {
                    $messages_res_all[$key]['last_message']['body'] = '';
                }
                // User details
                if ($messages_res_all[$key]['thread_type'] == 'single') {

                    if ($messages_res_all[$key]['createdBy'] == $user_id) {
                        $thread_sender_id = $value->getGroupMembers();
                    } else {
                        $thread_sender_id = $messages_res_all[$key]['createdBy'];
                    }
                    $usrDetails = $user_service->MultipleUserListObjectService($thread_sender_id);
                    $messages_res_all[$key]['user_detail'] = $usrDetails[0];
                } else {

                    $group_members = $value->getGroupMembers();
                    $group_members[] = $messages_res_all[$key]['createdBy'];
                    $messages_res_all[$key]['thread_members'] = $user_service->MultipleUserListObjectService(array_unique($group_members));
                }
            }
        }
        // Sort user according to search
        $FinalMessageResultAll = array();
        if ($messages_res_all)
        {

            foreach ($messages_res_all as $key => $value)
            {
                if($value['thread_type']=='single')
                {
                    if(!empty($value['user_detail']))
                    {
                            $myString = $value['user_detail']['first_name'].' '.$value['user_detail']['last_name'];
                            $pos = strpos(strtolower($myString), strtolower($first_name));
                            if($pos !== false)
                            {
                                array_push($FinalMessageResultAll,$messages_res_all[$key]);
                            }
                    }
                }
                else
                {
                    if(!empty($value['thread_members']))
                    {
                        foreach($value['thread_members'] as $userValue)
                        {
                            $myString = $userValue['first_name'].' '.$userValue['last_name'];
                            $pos = strpos(strtolower($myString), strtolower($first_name));
                            if($pos !== false)
                            {
                                array_push($FinalMessageResultAll,$messages_res_all[$key]);
                            }
                       }
                    }
                }
            }
        }
        $output = array_slice($FinalMessageResultAll, $offset, $limit);
        $total = count($output);
        $data = $output;
        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data, 'total' => $total);
        echo json_encode($res_data);
        exit();
    }
    /*     * *********************************************************** */

    /**
     * Listing group unread messages list
     * @param Request $request
     * @return josn string
     */
    public function postAlllistgroupunreadmessagesAction(Request $request) {
        //initilise the data array
        $data = array();
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'is_view');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $limit = (int) (isset($de_serialize['limit_size']) ? $de_serialize['limit_size'] : 20);
        $offset = (int) (isset($de_serialize['limit_start']) ? $de_serialize['limit_start'] : 0);

        $user_id = $object_info->user_id;
        $is_view = $object_info->is_view;



        $deleted_thread = $message_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->listGroupDeletedMemebersThreadId($user_id);


        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));

        if ($sender_user == '') {
            $data[] = "SENDER_ID_IS_INVALID";
        }

        if (!empty($data)) {
            return array('code' => 100, 'message' => 'FAILURE', 'data' => $data);
        }

        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');

        //get deleted member thread id
        $deleted_thread = $message_dm
                ->getRepository('MessageMessageBundle:MessageThread')
                ->listGroupDeletedMemebersThreadId($user_id);

        //getting deleted member thread id array
        if (is_array($deleted_thread) && count($deleted_thread)) {
            $thread_ids = array_map(function($threads) {
                return $threads->getId();
            }, $deleted_thread);
        } else {
            $thread_ids = array();
        }


        /* $message_res = $message_dm
          ->getRepository('MessageMessageBundle:Message')
          ->listAllGroupUnreadThread($user_id,$limit,$offset,$thread_ids);
         */
        /* Update  is view in UserNotification and GroupNotifications */



        $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->getisviewUpdateGroupUnreadThread($user_id, $is_view);
        $message_res = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllWeekGroupUnreadThread($user_id, $limit, $offset, $thread_ids);

        $message_total_dm = $container->get('doctrine.odm.mongodb.document_manager');
        /* $message_total_res = $message_total_dm
          ->getRepository('MessageMessageBundle:Message')
          ->listAllGroupUnreadTotalThread($user_id,$thread_ids); */
        $message_total_res = $message_total_dm
                ->getRepository('MessageMessageBundle:Message')
                ->listAllWeekGroupUnreadTotalThread($user_id, $thread_ids);

        $user_ids_array = array();
//        $user_ids_array = $message_total_res;
        $user_ids_array[] = $user_id;

        //find user object service..
        $user_service = $this->get('user_object.service');
        //get user profile and cover images..
        $users_object_array = $user_service->MultipleUserObjectService($user_ids_array);
        $all_thread_details = array();
        if ($message_total_res) {
            $total = count($message_total_res);
        } else {
            $total = 0;
        }
        $sender_ids_arr = array();
        $main_sender = array();
        $thread_id = array();

        if ($message_res) {
            foreach ($message_res as $message_res_render) {

                if (in_array($message_res_render->getSenderId(), $sender_ids_arr)) {
                    // continue;
                } else {
                    $sender_ids_arr[] = $message_res_render->getSenderId();
                }

                if (in_array($message_res_render->getThreadId(), $thread_id)) {
                    continue;
                } else {
                    $thread_id[] = $message_res_render->getThreadId();
                }

                $main_sender = $message_res_render->getSenderId();
                $usrDetailsSender = $user_service->MultipleUserListObjectService($main_sender);
                $group_members = $message_res_render->getReceiverId();
                $arrMerge = array_merge(array(), $group_members);
                $usrDetails = $user_service->MultipleUserListObjectService(array_unique($arrMerge));
                $thread_details_main = array(
                    'id' => $message_res_render->getId(),
                    'thread_id' => $message_res_render->getThreadId(),
                    'sender_user' => $usrDetailsSender[0],
                    'receiver_user' => $usrDetails,
                    'body' => $message_res_render->getBody(),
                    'message_type' => $message_res_render->getMessageType(),
                    'created_at' => $message_res_render->getCreatedAt(),
                    'is_read' => $message_res_render->getIsRead(),
                    'is_spam' => $message_res_render->getIsSpam(),
                    'updated_at' => $message_res_render->getUpdatedAt(),
                );
                $all_thread_details[] = $thread_details_main;
            }
        }
        $data = $all_thread_details;
        $res_data = array('code' => 100, 'message' => 'SUCCESS', 'data' => $data, 'total' => $total);
        echo json_encode($res_data);
        exit();
    }

    /*     * *********************************************************** */

    private function sendNotificationMail($senderId, $receiver, $type = 'single') {
        $receiver = is_array($receiver) ? $receiver : (array) $receiver;

        $postService = $this->container->get('post_detail.service');
        $email_template_service = $this->container->get('email_template.service');
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
        $message_profile_url = $this->container->getParameter('message_profile_url'); //message profile url
        $locale = $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);
        $receiver = array_diff($receiver, array($senderId));
        if (empty($receiver) or $senderId <= 0) {
            return false;
        }
        $sender = $postService->getUserData($senderId);
        $sender_name = trim(ucfirst($sender['first_name']) . ' ' . ucfirst($sender['last_name']));
        $receivers = $postService->getUserData($receiver, true);

        $recieverByLanguage = $postService->getUsersByLanguage($receivers);
        $emailResponse = '';
        foreach($recieverByLanguage as $lng=>$reciever){
            $locale = $lng===0 ? $this->container->getParameter('locale') : $lng;
            $lang_array = $this->container->getParameter($locale);
            switch(strtolower($type)){
                case 'single':
                    $mail_sub = $lang_array['MESSAGE_SUBJECT'];
                    $mail_body = sprintf($lang_array['MESSAGE_BODY'],$sender_name);
                    $mail_link = sprintf($lang_array['MESSAGE_LINK'],$sender_name);
                    $href = "<a href= '$angular_app_hostname$message_profile_url'>{$lang_array['CLICK_HERE']}</a>";
                    $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['MESSAGE_CLICK_HERE'],$href);
                    break;
                case 'group':
                    $mail_sub = $lang_array['GROUP_MESSAGE_SUBJECT'];
                    $mail_body = sprintf($lang_array['GROUP_MESSAGE_BODY'], $sender_name);
                    $mail_link = sprintf($lang_array['GROUP_MESSAGE_LINK'], $sender_name);
                    $href = "<a href= '$angular_app_hostname$message_profile_url'>{$lang_array['CLICK_HERE']}</a>";
                    $bodyData = $mail_link.'<br><br>'.sprintf($lang_array['GROUP_MESSAGE_CLICK_HERE'],$href);
                    break;
            }

            // HOTFIX NO NOTIFY MAIL
            //$emailResponse = $email_template_service->sendMail($reciever, $bodyData, $mail_body, $mail_sub, $sender['profile_image_thumb'], 'MESSAGE_NOTIFICATION');        

        }

    }

    /**
     *
     * @param type $obj
     * @return type
     */
    public function getRequestobj($obj) {
        //Code start for getting se request
        $freq_obj = $obj->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);
        $device_request_type = $freq_obj['device_request_type'];

        if ($device_request_type == 'mobile') {  //for mobile if images are uploading.
            $de_serialize = $freq_obj;
        } else { //this handling for with out image.
            if (isset($fde_serialize)) {
                $de_serialize = $fde_serialize;
            } else {
                $de_serialize = $this->getAppData($obj);
            }
        }
        return $de_serialize;
        //Code end for getting the request
    }

    /**
     * Decoding the json string to object
     * @param json string $encode_object
     * @return object $decode_object
     */
    public function decodeObjectAction($encode_object) {
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $decode_object = $serializer->decode($encode_object, 'json');
        return $decode_object;
    }

    public function SetUserIsviewBy($senderId, $messageId) {
        $container = MessageMessageBundle::getContainer();
        $message_dm = $container->get('doctrine.odm.mongodb.document_manager');
        $messageIsViewBy = $message_dm
                ->getRepository('MessageMessageBundle:Message')
                ->find($messageId);
        $isViewByCount = count($messageIsViewBy->getIsViewBy());
        if ($isViewByCount == 0) {
            $messageIsViewBy->setIsViewBy(array($senderId));
            $message_dm->persist($messageIsViewBy);
            $message_dm->flush();
            $message_dm->clear();
        } else {
            $IsViewByUsers = $messageIsViewBy->getIsViewBy();
            if (!in_array($senderId, $IsViewByUsers)) {
                array_push($IsViewByUsers, $senderId);
                $messageIsViewBy->setIsViewBy($IsViewByUsers);
                $message_dm->persist($messageIsViewBy);
                $message_dm->flush();
                $message_dm->clear();
            }
        }
    }

}
