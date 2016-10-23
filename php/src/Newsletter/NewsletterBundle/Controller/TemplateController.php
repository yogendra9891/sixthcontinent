<?php

namespace Newsletter\NewsletterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\MessageBundle\Provider\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
use Newsletter\NewsletterBundle\Entity\Newslettertrack;
use Newsletter\NewsletterBundle\Entity\Template;
use Symfony\Component\Console\Helper\Table;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery as ProxyQueryInterface;

class TemplateController extends Controller
{
    /**
     * 
     * @param Request $request
     * @return type
     */
    public function sendnewsletterAction(Request $request)
    {
        // create a task and give it some dummy data for this example
        
        $form1 = $this->createFormBuilder();
        $dm = $this->getDoctrine()->getManager();

        //fire the query in User Repository
        $results = $dm
        ->getRepository('UserManagerSonataUserBundle:User')
        ->findAll();
        
        $form1->add('selectall', 'checkbox', array(
                        'label'     => 'Select All',
                        'required'  => false,
                    ));
        foreach($results as $result) {
            $form1->add($result->getId(), 'checkbox', array(
                'label'     => $result->getUsername(),
                'required'  => false,
            ));
        }
        
        $em = $this->getDoctrine()->getManager();
        $template_res = $em
		->getRepository('NewsletterNewsletterBundle:Template')
		->findAll();
        $choise_arr = array();
        foreach($template_res as $record)
        {
            $choise_arr[$record->getId()] = $record->getTitle();
        }
        $form1->add('Select_Email_Template', 'choice', array(
            'choices'   => $choise_arr,
            'required'  => true,
        ));
            $form = $form1->add('Send Newsletter', 'submit')
            ->getForm();
        
            
        $form->handleRequest($request);

        if ($form->isValid()) {
           
             $data = $form->getData();
             $template_id = $data['Select_Email_Template'];
             $user_ids = array();
             $users_email = array();
             foreach($data as $key=>$record)
             {
                 if($key == 'selectall')
                 {
                     continue;
                 }
                 if($record == 1 && $key != 'Select_Email_Template')
                 {
                    $user_ids[] = $key;
                    $user_em = $this->getDoctrine()->getManager();
                    $user_res = $user_em
                        ->getRepository('UserManagerSonataUserBundle:User')
                        ->find($key);
                    $users_email[] = $user_res->getEmail();                    
                 }
             }
            $temp_em = $this->getDoctrine()->getManager();
            $template_res = $temp_em
                    ->getRepository('NewsletterNewsletterBundle:Template')
                    ->find($template_id);
            
            $email_title = $template_res->getTitle();
            $email_body = $template_res->getBody();
            
            $login_user = $this->container->get('security.context')
                        ->getToken()
                        ->getUser();
            $from_email = $login_user->getEmail();
            
            foreach($user_ids as $user_id)
            {
                $user_send_em = $this->getDoctrine()->getManager();
                $user_send_res = $user_send_em
                    ->getRepository('UserManagerSonataUserBundle:User')
                    ->find($user_id);
                
                $d=date ("d");
                $m=date ("m");
                $y=date ("Y");
                $t=time();
                $dmt=$d+$m+$y+$t;    
                $ran= rand(0,10000000);
                $dmtran= $dmt+$ran;
                $un=  uniqid();
                $dmtun = $dmt.$un;
                $mdun = md5($dmtran.$un);
                $sort=substr($mdun, 16); 
                $base_url = $this->get('request')->getHost().$this->get('request')->getBasepath();
                $tracker = 'http://'.$base_url."/admin/newsletter/trackemail/$sort";

                $email_body .= '<img alt="" src="'.$tracker.'" width="1" height="1" border="0" />';
                
                $sixthcontinent_admin_email = 
                array(
                    $this->container->getParameter('sixthcontinent_admin_email') => $this->container->getParameter('sixthcontinent_admin_email_from') 
                );
                $message = \Swift_Message::newInstance()
                    ->setSubject($email_title)
                    ->setFrom($sixthcontinent_admin_email)
                    ->setTo(array($user_send_res->getEmail()))
                    ->setBody($email_body, 'text/html');
                
                if($this->container->get('mailer')->send($message))
                {                    
                    $track_em = $this->getDoctrine()->getManager();
                    $email_track = new Newslettertrack();
                    $email_track->setSenderId($login_user->getId());
                    $email_track->setRecevierId($user_id);
                    $email_track->setTemplateId($template_id);
                    $email_track->setSentStatus(1);
                    $email_track->setOpenStatus(0);
                    $email_track->setToken($sort);
                    $email_track->setCreatedAt(new \DateTime());
                    $track_em->persist($email_track);
                    $track_em->flush();
                }else{
                    $track_em = $this->getDoctrine()->getManager();
                    $email_track = new Newslettertrack();
                    $email_track->setSenderId($login_user->getId());
                    $email_track->setRecevierId($user_id);
                    $email_track->setTemplateId($template_id);
                    $email_track->setSentStatus(0);
                    $email_track->setOpenStatus(0);
                    $email_track->setToken($sort);
                    $email_track->setCreatedAt(new \DateTime());
                    $track_em->persist($email_track);
                    $track_em->flush();
                }
                
            }
            return $this->redirect($this->generateUrl('newsletter_newsletter_status'));
           
        }    
        $pool = $this->get('sonata.admin.pool');       
        return $this->render('NewsletterNewsletterBundle:Default:sendnewsletter.html.twig', array(
            'form' => $form->createView(),'admin_pool'=>$pool,
        ));
    }
    
    /**
     * 
     * @param type $trackid
     */
    public function trackemailAction($trackid)
    {
        $em = $this->getDoctrine()->getManager();
        $template_res = $em
		->getRepository('NewsletterNewsletterBundle:Newslettertrack')
		->findOneByToken($trackid);
        
        $template_res->setOpenStatus(1);
        $em->flush();
        return $this->redirect($this->generateUrl('newsletter_newsletter_status'));
    }
    
    /**
     * 
     * @param Request $request
     */
    public function resendnewsletterAction($trackid)
    {
       
        $em = $this->getDoctrine()->getManager();
        $template_res = $em
		->getRepository('NewsletterNewsletterBundle:Newslettertrack')
		->find($trackid);
        
        $user_receiver_em = $this->getDoctrine()->getManager();
        $user_receiver_res = $user_receiver_em
            ->getRepository('UserManagerSonataUserBundle:User')
            ->find($template_res->getRecevierId());
                
        $user_send_em = $this->getDoctrine()->getManager();
        $user_send_res = $user_send_em
            ->getRepository('UserManagerSonataUserBundle:User')
            ->find($template_res->getSenderId());
        
        $temp_em = $this->getDoctrine()->getManager();
        $template_to_res = $temp_em
                ->getRepository('NewsletterNewsletterBundle:Template')
                ->find($template_res->getTemplateId());
        $base_url = $this->get('request')->getHost().$this->get('request')->getBasepath();
       
        
        $d=date ("d");
        $m=date ("m");
        $y=date ("Y");
        $t=time();
        $dmt=$d+$m+$y+$t;    
        $ran= rand(0,10000000);
        $dmtran= $dmt+$ran;
        $un=  uniqid();
        $dmtun = $dmt.$un;
        $mdun = md5($dmtran.$un);
        $sort=substr($mdun, 16); 
        $base_url = $this->get('request')->getHost().$this->get('request')->getBasepath();
        $tracker = 'http://'.$base_url."/admin/newsletter/trackemail/$sort";

        $email_body = "";
        
        $email_body .= $template_to_res->getBody().'<img alt="" src="'.$tracker.'" width="1" height="1" border="0" />';
        $sixthcontinent_admin_email = 
        array(
            $this->container->getParameter('sixthcontinent_admin_email') => $this->container->getParameter('sixthcontinent_admin_email_from') 
        );
        $message = \Swift_Message::newInstance()
                    ->setSubject($template_to_res->getTitle())
                    ->setFrom($sixthcontinent_admin_email)
                    ->setTo(array($user_receiver_res->getEmail()))
                    ->setBody($email_body, 'text/html');
        $login_user = $this->container->get('security.context')
                        ->getToken()
                        ->getUser();
        if($this->container->get('mailer')->send($message))
        {                    
            $track_em = $this->getDoctrine()->getManager();
            $email_track = new Newslettertrack();
            $email_track->setSenderId($login_user->getId());
            $email_track->setRecevierId($template_res->getRecevierId());
            $email_track->setTemplateId($template_res->getTemplateId());
            $email_track->setSentStatus(1);
            $email_track->setOpenStatus(0);
            $email_track->setToken($sort);
            $email_track->setCreatedAt(new \DateTime());
            $track_em->persist($email_track);
            $track_em->flush();
        }else{
            $track_em = $this->getDoctrine()->getManager();
            $email_track = new Newslettertrack();
            $email_track->setSenderId($login_user->getId());
            $email_track->setRecevierId($template_res->getRecevierId());
            $email_track->setTemplateId($template_res->getTemplateId());
            $email_track->setSentStatus(0);
            $email_track->setOpenStatus(0);
            $email_track->setToken($sort);
            $email_track->setCreatedAt(new \DateTime());
            $track_em->persist($email_track);
            $track_em->flush();
        }
        return $this->redirect($this->generateUrl('newsletter_newsletter_status'));
        
        
    }
    /**
     * 
     * @return type array
     */
    public function statuslistAction()
    {
        $format = $this->getRequest();
        if($format->get('maxItemPerPage') ==''){
            $maxItemPerPage=10;
        }else{
            $maxItemPerPage=$format->get('maxItemPerPage');
        }
        $em = $this->getDoctrine()->getManager();
        $template_res = $em
		->getRepository('NewsletterNewsletterBundle:Newslettertrack')
		->findAll();
        $resutl_render = array();
        $i = 1;
        foreach($template_res as $record)
        {
            $user_send_em = $this->getDoctrine()->getManager();
            $user_send_res = $user_send_em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->find($record->getSenderId());
            
            $user_rece_em = $this->getDoctrine()->getManager();
            $user_receiver_res = $user_rece_em
                ->getRepository('UserManagerSonataUserBundle:User')
                ->find($record->GetRecevierId());
            
            $temp_em = $this->getDoctrine()->getManager();
            $template_res = $temp_em
                    ->getRepository('NewsletterNewsletterBundle:Template')
                    ->find($record->getTemplateId());
            
            $resutl_render[$i]['id'] = $record->getId();
            if($template_res){
                $resutl_render[$i]['template_title'] = $template_res->getTitle();
            }else{
                $resutl_render[$i]['template_title'] = '';
            }
            
            $resutl_render[$i]['serial_id'] = $i;
            $resutl_render[$i]['sender_id'] = $user_send_res->getUsername();
            if($user_receiver_res){
                $resutl_render[$i]['receiver_id'] = $user_receiver_res->getUsername();
            }else{
                $resutl_render[$i]['receiver_id'] = '';
            }
            
            if($record->getSentStatus() == 1)
            {
                $resutl_render[$i]['sent_status'] = 'Yes';
            }else{
                $resutl_render[$i]['sent_status'] = 'fail';
            }
            
            if($record->getOpenStatus() == 1)
            {
                $resutl_render[$i]['open_status'] = 'seen';
            }else{
                $resutl_render[$i]['open_status'] = 'unseen';
            }
            
            $resutl_render[$i]['created_at'] = $record->getCreatedAt()->format('d/m/Y h:i:s');
            $i++;
        }
        
        $em    = $this->get('doctrine.orm.entity_manager');
        $dql   = "SELECT a FROM NewsletterNewsletterBundle:Newslettertrack a";
        $query = $em->createQuery($dql);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1)/*page number*/,
            $maxItemPerPage/*limit per page*/
        );

            $pool = $this->get('sonata.admin.pool');
    // parameters to template
    return $this->render('NewsletterNewsletterBundle:Admin:statuslist.html.twig', array('pagination' => $pagination,'result' => $resutl_render,'admin_pool'=>$pool));
        
        
//        return $this->render('NewsletterNewsletterBundle:Admin:statuslist.html.twig', array(
//               'result' => $resutl_render,
//           ));
    }
   
    
}
