<?php

namespace Newsletter\NewsletterBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as CrudaController;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery as ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Newsletter\NewsletterBundle\Entity\Newslettertrack;
use Newsletter\NewsletterBundle\Entity\Template;
use UserManager\Sonata\UserBundle\Entity\User;


class CRUDController extends CrudaController {

     public function batchActionNewsletter(ProxyQueryInterface $selectedModelQuery)
    {
        
        if ($this->admin->isGranted('EDIT') === false || $this->admin->isGranted('DELETE') === false) {
            throw new AccessDeniedException();
        }
        
        $modelManager = $this->admin->getModelManager();
 
        $selectedModels = $selectedModelQuery->execute();
        $new_data = json_decode($_POST['data']);
       
        $user_id_arr = $new_data->idx;
        $template_id = $new_data->newsid;
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
        
        $users_email = array();
        try {
            foreach ($user_id_arr as $record) {
                $user_em = $this->getDoctrine()->getManager();
                $user_temp_res = $user_em
                        ->getRepository('UserManagerSonataUserBundle:User')
                        ->find($record);       
                $email_to_send = '';
                $reciever_id = '';
                if($user_temp_res){
                    $users_email[] = $user_temp_res->getEmail();  
                    $email_to_send = $user_temp_res->getEmail();
                    $reciever_id = $user_temp_res->getId();
                }
                            
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
                $tracker = 'http://'.$base_url."/trackemail/apiweb/$sort";
                $email_body_send = '';
                $email_body_send =  $email_body.'<img alt="" src="'.$tracker.'" width="1" height="1" border="0" />';
                
                $sixthcontinent_admin_email = 
                array(
                    $this->container->getParameter('sixthcontinent_admin_email') => $this->container->getParameter('sixthcontinent_admin_email_from') 
                );
                $message = \Swift_Message::newInstance()
                    ->setSubject($email_title)
                    ->setFrom($sixthcontinent_admin_email)
                    ->setTo(array($email_to_send))
                    ->setBody($email_body_send, 'text/html');
                
                if($this->container->get('mailer')->send($message))
                {                    
                    $track_em = $this->getDoctrine()->getManager();
                    $email_track = new Newslettertrack();
                    $email_track->setSenderId($login_user->getId());
                    $email_track->setRecevierId($reciever_id);
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
                    $email_track->setRecevierId($reciever_id);
                    $email_track->setTemplateId($template_id);
                    $email_track->setSentStatus(0);
                    $email_track->setOpenStatus(0);
                    $email_track->setToken($sort);
                    $email_track->setCreatedAt(new \DateTime());
                    $track_em->persist($email_track);
                    $track_em->flush();
                }
            }
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('sonata_flash_error', $e->getMessage());
 
            return new RedirectResponse($this->admin->generateUrl('list',$this->admin->getFilterParameters()));
        }
        return $this->redirect($this->generateUrl('newsletter_newsletter_status'));
        
    }

    
}