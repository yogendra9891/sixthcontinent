<?php

namespace Notification\NotificationBundle\Controller;

use Notification\NotificationBundle\Document\EmailNotification;
use Notification\NotificationBundle\Model\INotification;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Notification\NotificationBundle\Model;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Notification\NotificationBundle\Document;
use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use Symfony\Component\DependencyInjection\ContainerAware;
use Doctrine\ORM\EntityManager;
use Dashboard\DashboardManagerBundle\Controller\PostController as postcontroller;
use Notification\NotificationBundle\Services\PostService;
/**
 * Define the email class for sending the notification
 * @author admin
 *
 */
class EmailController extends Controller implements INotification {

    /**
     * sending the mail.
     * @param object
     * @see Notification\NotificationBundle\Model.INotification::sendAction()
     * @return int
     */
    public function sendAction($message_info_object) {
        try {
            $sixthcontinent_admin_email = 
            array(
                $this->container->getParameter('sixthcontinent_admin_email') => $this->container->getParameter('sixthcontinent_admin_email_from') 
            );
            $message = \Swift_Message::newInstance()
                    ->setSubject($message_info_object->mail_subject)
                    ->setFrom($sixthcontinent_admin_email)
                    ->setTo(array($message_info_object->to))
                    ->setBody($message_info_object->message, 'text/html');
            //getting the container defined in current bundle main file
            $container = NManagerNotificationBundle::getContainer();
            if ($container->get('mailer')->send($message)) {
                $this->setEmailNotificationAction($message_info_object);
                return 1;
            } else {
                return 0;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Saving the email notification into mongo db.
     * @param object $message_info_object
     * @return boolean
     */
    public function setEmailNotificationAction($message_info_object) {
        $time = new \DateTime("now");
        //get email notification document object
        $email_notification = new EmailNotification();
        $email_notification->setEmailFrom($message_info_object->from);
        $email_notification->setEmailTo($message_info_object->to);
        $email_notification->setMessageType($message_info_object->message_type);
        $email_notification->setMessage($message_info_object->message);
        $email_notification->setSubject($message_info_object->mail_subject);
        $email_notification->setSenderUserid($message_info_object->sender_userid);
        $email_notification->setReceiverUserid($message_info_object->receiver_userid);
        $email_notification->setReadvalue(0); //default is unread, (0=>unread, 1=>read)
        $email_notification->setDeletevalue(0); //default is undelete
        $email_notification->setDate($time);

        //getting the container object.
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager');
        $dm->persist($email_notification);
        $dm->flush();
        return true;
    }

    /**
     * getting all the messages for a user
     * @param object $current_notification_object(notification_type, receiver_userid)
     * @return object
     */
    public function getNotificationMessagesAction($current_notification_object) {
        // set default limit and range 20, 0.
        $limit = (isset($current_notification_object->limit_size)) ? $current_notification_object->limit_size : 20;
        $offset = (isset($current_notification_object->limit_start)) ? $current_notification_object->limit_start : 0;
        //getting the container object.
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager')->getRepository('NManagerNotificationBundle:EmailNotification');
        //getting the data of receiver user id and deletevalue field orderby date desc and limit and start.
        $messages = $dm->findBy(array('receiver_userid' => (int) $current_notification_object->receiver_userid, 'deletevalue' => 0), array('date' => 'DESC'), $limit, $offset);
        
        $message_data = array();
        foreach ($messages as $message) {
            //get entity manager object
            $em          = $container->get('doctrine.orm.entity_manager');
            $sender_id   = $message->getSenderUserid();
            $receiver_id = $message->getReceiverUserid();
            
            //sender user profile.
            $sender_user_service           = $container->get('user_object.service');
            $sender_user_object            = $sender_user_service->UserObjectService($sender_id);
            
            //receiver user profile.
            $receiver_user_service           = $container->get('user_object.service');
            $receiver_user_object            = $receiver_user_service->UserObjectService($receiver_id);

            $message_data[] = array('id'=>$message->getId(),
                                    'email_from'=>$message->getEmailFrom(),
                                    'email_to'=>$message->getEmailTo(),
                                    'readvalue'=>$message->getReadvalue(),
                                    'deletevalue'=>$message->getDeletevalue(),
                                    'message_type'=>$message->getMessageType(),
                                    'message'=>$message->getMessage(),
                                    'subject'=>$message->getSubject(),
                                    'create_at'=>$message->getDate(),
                                    'sender_info'=>$sender_user_object,
                                    'receiver_info'=>$receiver_user_object
                
                );
        }

        $messages = $message_data;
        $messages_count = count($dm->findBy(array('receiver_userid' => (int) $current_notification_object->receiver_userid, 'deletevalue' => 0)));
        $res_data    = array('messages' => $messages, 'count' => $messages_count);
        echo json_encode($res_data);
        exit();
    }

    /**
     * deleting a email message marking it 1(digital delete).
     * @param object $current_notification_object
     * @return int
     */
    public function deleteNotificationMessageAction($current_notification_object) {
        //getting the container object.
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager');
        $data = $dm->getRepository('NManagerNotificationBundle:EmailNotification')->find($current_notification_object->message_id);
        if (!$data) {  // in case record is not exists.
            return 0;
        }
        $data->setDeletevalue(1); //set the delete value..
        $dm->flush();
        return 1;
    }

    /**
     * mark read/unread a email message marking it 1(digital delete).
     * @param object $current_notification_object
     * @return int
     */
    public function readUnreadNotificationMessageAction($current_notification_object) {
        //getting the container object.
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager');
        $data = $dm->getRepository('NManagerNotificationBundle:EmailNotification')->find($current_notification_object->message_id);
        if (!$data) {  // in case record is not exists.
            return 0;
        }
        $data->setReadvalue($current_notification_object->read_value); //set the read value..
        $dm->flush();
        return 1;
    }

    /**
     * searching email notification messages.
     * @param object $current_notification_object(text for search)
     * @return int
     */
    public function searchNotificationMessageAction($current_notification_object) {
        // set default limit and range 20, 0.
        $limit   = (isset($current_notification_object->limit_size)) ? $current_notification_object->limit_size : 20;
        $offset  = (isset($current_notification_object->limit_start)) ? $current_notification_object->limit_start : 0;
        $text    = $current_notification_object->search_text;
        $user_id = (int) $current_notification_object->receiver_userid;
        $data    = array();
        //getting the container object.
        $container = NManagerNotificationBundle::getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager');
        //finding the total matched result result on limit basis
        $messages = $dm->getRepository('NManagerNotificationBundle:EmailNotification')
                ->searchBySubjectOrOther($text, $user_id, $offset, $limit);
       
        $message_data = array();
        foreach ($messages as $message) {
            //get entity manager object
            $em          = $container->get('doctrine.orm.entity_manager');
            $sender_id   = $message->getSenderUserid();
            $receiver_id = $message->getReceiverUserid();
         
            //sender user profile.
            $sender_user_service           = $container->get('user_object.service');
            $sender_user_object            = $sender_user_service->UserObjectService($sender_id);
            
            //receiver user profile.
            $receiver_user_service         = $container->get('user_object.service');
            $receiver_user_object          = $receiver_user_service->UserObjectService($receiver_id);
            
            $message_data[] = array('id'=>$message->getId(),
                                    'email_from'=>$message->getEmailFrom(),
                                    'email_to'=>$message->getEmailTo(),
                                    'readvalue'=>$message->getReadvalue(),
                                    'deletevalue'=>$message->getDeletevalue(),
                                    'message_type'=>$message->getMessageType(),
                                    'message'=>$message->getMessage(),
                                    'subject'=>$message->getSubject(),
                                    'create_at'=>$message->getDate(),
                                    'sender_info'=>$sender_user_object,
                                    'receiver_info'=>$receiver_user_object
                
                );
        }
        $data = $message_data;
        //finding the total count
        $data_count = $dm->getRepository('NManagerNotificationBundle:EmailNotification')
                ->searchBySubjectOrOtherCount($text, $user_id);
        $res_data = array('messages' => $data, 'count' => $data_count);
        echo json_encode($res_data);
        exit();
    }

    /**
     * get detail of a notification messages.
     * @param object $current_notification_object
     * @return int
     */
    public function getDetailNotificationAction($current_notification_object) {

        $notification_id = $current_notification_object->notification_id;
        //getting the container object.
        $container = NManagerNotificationBundle::getContainer();
        $data = array();
        $dm = $container->get('doctrine.odm.mongodb.document_manager');
        //finding the total matched result result on limit basis
        $data = $dm->getRepository('NManagerNotificationBundle:EmailNotification')->find($notification_id);
        if ($data) {
            $sender_id   = $data->getSenderUserid();
            $receiver_id = $data->getReceiverUserid();
         
            //sender user profile.
            $sender_user_service           = $container->get('user_object.service');
            $sender_user_object            = $sender_user_service->UserObjectService($sender_id);
            
            //receiver user profile.
            $receiver_user_service         = $container->get('user_object.service');
            $receiver_user_object          = $receiver_user_service->UserObjectService($receiver_id);
            $message_data = array(
                                 'id'=>$data->getId(),
                                 'email_from'=>$data->getEmailFrom(),
                                 'email_to'=>$data->getEmailTo(),
                                 'read_value'=>$data->getReadvalue(),
                                 'delete_value'=>$data->getDeletevalue(),
                                 'message_type'=>$data->getMessageType(),
                                 'message'=>$data->getMessage(),
                                 'subject'=>$data->getSubject(),
                                 'date'=>$data->getDate(),
                                 'sender_info'=>$sender_user_object,
                                 'receiver_info'=>$receiver_user_object
            );
            $return_result_return = array('data' => $message_data, 'success' => 1);
            echo json_encode($return_result_return);
            exit();
        } else {
            $return_result_return = array('data' => $data, 'success' => 0);
            echo json_encode($return_result_return);
            exit();
        }
        return $return_result_return;
    }
}
