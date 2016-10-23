<?php

namespace Message\MessageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\MessageBundle\ModelManager\MessageManagerInterface;
    

class DefaultController extends Controller 
{
    public function indexAction($name)
    {
         $sender = $this->get('security.context')->getToken()->getUser();
         $threadBuilder = $this->get('fos_message.composer')->newThread();
         $threadBuilder
        ->addRecipient($this->getUser()) // Retrieved from your backend, your user manager or ...
        ->setSender($sender)
        ->setSubject('Test')
        ->setBody('Test message');
         //print_r($this->getUser());exit;
         //$composer = $this->get('fos_message.composer');
        //$message  = $composer->newThread()
//        ->setSender($this->getUser())
//        ->setSubject('myThread')
//        ->setBody('sdfsdfs');
//
//    $sender = $this->get('fos_message.sender');
    //$sender->send($message);
         
        $sender = $this->get('fos_message.sender');
        $sender->send($threadBuilder->getMessage());
        return $this->render('MessageMessageBundle:Default:index.html.twig', array('name' => $name));
    }
     public function sendMessageAction()
    {
         
     
         $sender = $this->get('security.context')->getToken()->getUser();
         $threadBuilder = $this->get('fos_message.composer')->newThread();
         $threadBuilder
        ->addRecipient($this->getUser()) // Retrieved from your backend, your user manager or ...
        ->setSender($sender)
        ->setSubject('Test')
        ->setBody('Test message');
         //print_r($this->getUser());exit;
         //$composer = $this->get('fos_message.composer');
        //$message  = $composer->newThread()
//        ->setSender($this->getUser())
//        ->setSubject('myThread')
//        ->setBody('sdfsdfs');
//
//    $sender = $this->get('fos_message.sender');
    //$sender->send($message);
         
        $sender = $this->get('fos_message.sender');
        $sender->send($threadBuilder->getMessage());
        return new Response('Message has been sent.');

    }
}
