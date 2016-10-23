<?php

namespace AdminUserManager\AdminUserManagerBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as CrudaController;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery as ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use UserManager\Sonata\UserBundle\Entity\CitizenUser;
use UserManager\Sonata\UserBundle\Entity\UserMultiProfileRepository;
use Symfony\Component\HttpFoundation\Session\Session;
use Notification\NotificationBundle\Document\UserNotifications;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Sonata\AdminBundle\Export;
use Exporter\Writer\XlsWriter;

class CRUDController extends CrudaController {
 protected $filename = 'test1.xls';
    /**
     * Add profile as broker
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionBroker(ProxyQueryInterface $selectedModelQuery) {
        $selectedModels = $selectedModelQuery->execute();
        foreach ($selectedModels as $selectedModel) {
            $this->setMultiProfile($selectedModel, '24');
        }
        
        return new RedirectResponse($this->admin->generateUrl('list', array('filter' => $this->admin->getFilterParameters())));
    }
    
    /**
     * Add Profile as ambassador
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionAmbassador(ProxyQueryInterface $selectedModelQuery) {
        if(isset($_POST['data'])){
            $data = json_decode($_POST['data']);
            foreach($data->idx as $record){
                 $this->setProfileCitizen($record, 25);
            }
        }
        
        $selectedModels = $selectedModelQuery->execute();       
        
        return new RedirectResponse($this->admin->generateUrl('list', array('type' => "ambassador")));
    }
    /**
     * Add Profile as citizenrole
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionCitizenrole(ProxyQueryInterface $selectedModelQuery) {
        if(isset($_POST['data'])){
            $data = json_decode($_POST['data']);
            foreach($data->idx as $record){
                 $this->setProfileCitizen($record, 22);
            }
        }
        $selectedModels = $selectedModelQuery->execute();       
        return new RedirectResponse($this->admin->generateUrl('list', array('type' => "citizen")));
    }
    
    /**
     * Add Profile as citizen/citizen writer/ambassador
     * @param \Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery $selectedModelQuery
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchActionCitizenwriter(ProxyQueryInterface $selectedModelQuery) {
       
        
        if(isset($_POST['data'])){
            $data = json_decode($_POST['data']);
            foreach($data->idx as $record){
                 $this->setProfileCitizen($record, 23);
            }           
        }
        $selectedModels = $selectedModelQuery->execute();       
        
        return new RedirectResponse($this->admin->generateUrl('list', array('type' => "citizenwriter")));
    }

      /**
     * Switch in muliprofile from citizen/citizenwriter/ambassador
     * @param type $user_id
     * @param type $role_id
     * @return boolean
     */
    public function setProfileCitizen($user_id, $role_id) {
      
        //get entity manager object
        $em = $this->container->get('doctrine')->getEntityManager();
        
        $user_citizen_multiprofile = $em
                ->getRepository('UserManagerSonataUserBundle:CitizenUser')
                ->findOneBy(array('userId' => $user_id));
        
        
        $user_citizen_multiprofile->setRoleId($role_id);
        $em->persist($user_citizen_multiprofile);
        $em->flush();
        return true;
    }
    /**
     * Set multi profile for broker
     * @param type $selectedModel
     * @param type $type
     * @return boolean
     */
    public function setMultiProfile($selectedModel, $type) {
        //initilise the data array
        $data = array();
        $id = $selectedModel->getId();
        $first_name = $selectedModel->getFirstName();
        $last_name = $selectedModel->getLastName();
        $gender = $selectedModel->getGender();
        $bdate = $selectedModel->getBirthDate();
        $phone = $selectedModel->getPhone();
        $ccode = $selectedModel->getCountry();
        $street = $selectedModel->getStreet();
        $user_id = $selectedModel->getUserId();
        $email = $selectedModel->getEmail();
        //get entity manager object
        $em = $this->container->get('doctrine')->getEntityManager();
        
        $user_multiprofile_exist = $em
                ->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                ->findOneBy(array('userId' => $user_id, 'profileType' => $type, 'isActive' =>1));
        
        if(count($user_multiprofile_exist)>0){
            return true;
            exit;
        }
        $user_multiprofile = $em
                ->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                ->findOneBy(array('id' => $id, 'profileType' => $type));

        if (count($user_multiprofile) == 0) {
            $user_multiprofile = new UserMultiProfile();
            $user_multiprofile->setFirstName($first_name);


            $user_multiprofile->setLastName($last_name);


            $user_multiprofile->setGender($gender);

            //get birth date date object
            $btime = new \DateTime('now');


            $user_multiprofile->setBirthDate($btime);


            $user_multiprofile->setPhone($phone);


            $user_multiprofile->setCountry($ccode);


            $user_multiprofile->setStreet($street);
            $user_multiprofile->setEmail($email);
        }


        $user_multiprofile->setUserId($user_id);
        $user_multiprofile->setProfileType($type);

        $user_multiprofile->setIsActive(1);
        $time = new \DateTime("now");

        $user_multiprofile->setCreatedAt($time);

        $user_multiprofile->setUpdatedAt($time);
        $user_multiprofile->setProfileSetting(1); //by deafault profile will be public
        $em->persist($user_multiprofile);
        $em->flush();

        return true;
    }

    
    /**
     * Set multi profile for broker
     * @param type $selectedModel
     * @param type $type
     * @return boolean
     */
    public function setAmbassadorMultiProfile($selectedModel, $type) {
        //initilise the data array
        $data = array();
        $id = $selectedModel->getId();
        $first_name = $selectedModel->getFirstName();
        $last_name = $selectedModel->getLastName();
        $gender = $selectedModel->getGender();
        $bdate = $selectedModel->getBirthDate();
        $phone = $selectedModel->getPhone();
        $ccode = $selectedModel->getCountry();
        $street = $selectedModel->getStreet();
        $user_id = $selectedModel->getUserId();
        $email = $selectedModel->getEmail();
        //get entity manager object
        $em = $this->container->get('doctrine')->getEntityManager();
        
        $user_multiprofile_exist = $em
                ->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                ->findOneBy(array('userId' => $user_id, 'profileType' => $type, 'isActive' =>1));
        
        if(count($user_multiprofile_exist)>0){
            return true;
            exit;
        }
        $user_multiprofile = $em
                ->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                ->findOneBy(array('id' => $id));

       
        if (count($user_multiprofile) == 0) {
            $user_multiprofile = new UserMultiProfile();
            $user_multiprofile->setFirstName($first_name);


            $user_multiprofile->setLastName($last_name);


            $user_multiprofile->setGender($gender);

            //get birth date date object
            $btime = new \DateTime('now');


            $user_multiprofile->setBirthDate($btime);


            $user_multiprofile->setPhone($phone);


            $user_multiprofile->setCountry($ccode);


            $user_multiprofile->setStreet($street);
            $user_multiprofile->setEmail($email);
        }


        $user_multiprofile->setUserId($user_id);
        $user_multiprofile->setProfileType($type);

        $user_multiprofile->setIsActive(1);
        $time = new \DateTime("now");

        $user_multiprofile->setCreatedAt($time);

        $user_multiprofile->setUpdatedAt($time);
        $user_multiprofile->setProfileSetting(1); //by deafault profile will be public
        $em->persist($user_multiprofile);
        $em->flush();

        return true;
    }
    
    /**
     * Activate the broker profile
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function activateAction()
    { 
        $id = $this->get('request')->get($this->admin->getIdParameter());
       //get entity manager object
        $em = $this->container->get('doctrine')->getEntityManager();
      
        $user = $em->getRepository('UserManagerSonataUserBundle:User')
                ->findOneBy(array('id' => $id));
        
        $user->setBrokerProfileActive('1');
        $em->persist($user);
        $em->flush();
        //making the broker profile as active.
        $broker_user = $em->getRepository('UserManagerSonataUserBundle:BrokerUser')
                ->findOneBy(array('userId' => $id));
        $broker_active_old_status = $broker_user->getIsActive();
        $broker_user->setIsActive(1);
        $em->persist($broker_user);
        $em->flush();
        
        //making the entry for broker profile activation notification.
        $user = $this->get('security.context')->getToken()->getUser();
        $current_user_id = $user->getId();
        $broker_item_id  = $broker_user->getId();
        $broker_user_id  = $id;
        
        //check for a broker user was inactive then we are making notification entry and sending the mail.
        if ($broker_active_old_status == '0') {
            $msgtype = 'broker';
            $msg     = 'accept';
            $add_notification = $this->saveUserNotification($current_user_id, $broker_user_id, $broker_item_id, $msgtype, $msg);
            $mail_sub  = 'Attivazione Profilo Broker';
            $mail_body = 'Il tuo profilo broker è stato attivato';
            $this->sendEmailNotification($mail_sub, $current_user_id, $broker_user_id, $mail_body);
        }
        return new RedirectResponse($this->admin->generateUrl('list'));
    }
    
    /**
     * Finding the docs of a broker
     */
    public function documentsAction()
    { 
        $id = $this->get('request')->get($this->admin->getIdParameter());
        //get entity manager object
        $em = $this->container->get('doctrine')->getEntityManager();
        
        $user = $em
                ->getRepository('UserManagerSonataUserBundle:BrokerUser')
                ->findOneBy(array('userId' => $id));
        
        //initialize the variables..
        $ssn = $idcard = '';
        $ssn_link    = $idcard_link = '';
        $base_url    =  $this->getBaseUri();
        $s3_base_url = $this->getS3BaseUri();
        if ($user) {
            $ssn    = $user->getSsn();
            $idcard = $user->getIdCard();
            if (!empty($ssn)) {
               $ssn_link = $s3_base_url.'/uploads/users/contract/'.$id.'/'.$ssn;
            } 
            if (!empty($idcard)) {
              $idcard_link  = $s3_base_url.'/uploads/users/contract/'.$id.'/'.$idcard;
            }
        }
        $broker_base_url = $base_url.'admin/sonata/user/brokeruser/list';
        return $this->render('AdminUserManagerAdminUserManagerBundle:CRUD:document_link.html.twig',
                array('ssn'=>$ssn, 'idcard'=>$idcard, 'ssnlink'=>$ssn_link, 'idcardlink'=>$idcard_link, 'broker_list'=>$broker_base_url));
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
    * Save user notification
    * @param int $user_id
    * @param int $fid
    * @param string $msgtype
    * @param string $msg
    * @return boolean
    */
    public function saveUserNotification($user_id, $sender_id, $item_id, $msgtype, $msg){
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $notification = new UserNotifications();
        $notification->setFrom($user_id);
        $notification->setTo($sender_id);
        $notification->setMessageType($msgtype);
        $notification->setMessage($msg);
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $notification->setItemId($item_id);
        $dm->persist($notification);
        $dm->flush();
        return true;
    }
    /**
     * send email for notification on shop activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @return boolean
     */
    public function sendEmailNotification($mail_sub, $from_id, $to_id, $mail_body){
        $userManager = $this->getUserManager();
        $from_user   = $userManager->findUserBy(array('id' => (int)$from_id));
        $to_user     = $userManager->findUserBy(array('id' => (int)$to_id));
        $sixthcontinent_admin_email = 
        array(
            $this->container->getParameter('sixthcontinent_admin_email') => $this->container->getParameter('sixthcontinent_admin_email_from') 
        );
        $notification_msg = \Swift_Message::newInstance()
            ->setSubject($mail_sub)
            ->setFrom($sixthcontinent_admin_email)
            ->setTo(array($to_user->getEmail()))
            ->setBody($mail_body, 'text/html');
        
        if($this->container->get('mailer')->send($notification_msg)){            
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * Get User Manager of FOSUSER bundle
     * @return Obj
     */
    protected function getUserManager() {
        return $this->container->get('fos_user.user_manager');
    }
    /**
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket = $this->container->getParameter('aws_bucket');
        $full_path = $aws_base_path.'/'.$aws_bucket;
        return $full_path;
    }
    
    public function batchActionExporttd(ProxyQueryInterface $selectedModelQuery) {
      
         // ask the service for a Excel5
       $phpExcelObject = $this->container->get('phpexcel')->createPHPExcelObject();
       $phpExcelObject->setActiveSheetIndex(0)
           ->setCellValue('A1', 'Shop Id')
           ->setCellValue('B1', 'Shop Name')
           ->setCellValue('C1', 'Business Name')
           ->setCellValue('D1', 'Tot Dare')
           ->setCellValue('E1', 'Tot Quota')
           ->setCellValue('F1', 'Transaction Date ');     
        $em = $this->container->get('doctrine')->getEntityManager();
        
        
        $transactionshop_tableName = $em->getClassMetadata('StoreManagerStoreBundle:Transactionshop')->getTableName();
        $store_tableName = $em->getClassMetadata('StoreManagerStoreBundle:Store')->getTableName();
        $conn = $em->getConnection();
        
        
        if(isset($_POST['all_elements']) && $_POST['all_elements'] =='on' ) {
            $query = "SELECT ts.user_id as user_id,ts.tot_dare,ts.tot_quota,ts.data_movimento, s.name as name,s.business_name as business_name 
            FROM $transactionshop_tableName ts
            LEFT JOIN $store_tableName s ON ts.user_id = s.id";
        } else {
            $check_array = implode(',',$_POST['idx']);
             $query = "SELECT ts.user_id as user_id,ts.tot_dare,ts.tot_quota,ts.data_movimento, s.name as name,s.business_name as business_name 
            FROM $transactionshop_tableName ts
            LEFT JOIN $store_tableName s ON ts.user_id = s.id where ts.id IN ($check_array)";
        }
        
        $statement = $conn->prepare($query);
        $statement->execute();
        $results = $statement->fetchAll();
        $counter = 2;
        foreach ($results as $res) {
           
           $phpExcelObject->setActiveSheetIndex(0)
           ->setCellValue("A$counter", $res['user_id'])
           ->setCellValue("B$counter",  $res['name'])
           ->setCellValue("C$counter",  $res['business_name'])
           ->setCellValue("D$counter",  round($res['tot_dare']/1000000,2))
           ->setCellValue("E$counter",  round($res['tot_quota']/1000000,2))
           ->setCellValue("F$counter",  date('d-M-Y',$res['data_movimento']));  
           
           $counter++;
        }
       
       $phpExcelObject->getActiveSheet()->setTitle('TA_table_list');
       // Set active sheet index to the first sheet, so Excel opens this as the first sheet
       $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->container->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->container->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=TD-file.xls');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;        
     
    }
    
    public function batchActionExportta(ProxyQueryInterface $selectedModelQuery) {
         // ask the service for a Excel5
       $phpExcelObject = $this->container->get('phpexcel')->createPHPExcelObject();
       $phpExcelObject->setActiveSheetIndex(0)
           ->setCellValue('A1', 'Shop Id')
           ->setCellValue('B1', 'Shop Name')
           ->setCellValue('C1', 'Business Name')
           ->setCellValue('D1', 'Tot Avare')
           ->setCellValue('E1', 'Transaction Date ');     
        $em = $this->container->get('doctrine')->getEntityManager();
        $conn = $em->getConnection();
        $ta_tableName = $em->getClassMetadata('TransactionTransactionBundle:CitizenIncomeToPayToStore')->getTableName();
        $store_tableName = $em->getClassMetadata('StoreManagerStoreBundle:Store')->getTableName();
        
        if(isset($_POST['all_elements']) && $_POST['all_elements'] =='on' ) {
            $query = "SELECT ts.user_id as user_id,ts.tot_avere,ts.data_movimento, s.name as name,s.business_name as business_name 
            FROM $ta_tableName ts
            LEFT JOIN $store_tableName s ON ts.user_id = s.id";
        } else {
            $check_array = implode(',',$_POST['idx']);
            $query = "SELECT ts.user_id as user_id,ts.tot_avere,ts.data_movimento, s.name as name,s.business_name as business_name 
            FROM $ta_tableName ts
            LEFT JOIN $store_tableName s ON ts.user_id = s.id where ts.id IN ($check_array)";
        }
        $statement = $conn->prepare($query);
        $statement->execute();
        $results = $statement->fetchAll();
        $counter = 2;
        foreach ($results as $res) {
           $phpExcelObject->setActiveSheetIndex(0)
           ->setCellValue("A$counter", $res['user_id'])
           ->setCellValue("B$counter",  $res['name'])
           ->setCellValue("C$counter",  $res['business_name'])
           ->setCellValue("D$counter",  round($res['tot_avere']/1000000,2))
           ->setCellValue("E$counter",  date('d-M-Y',$res['data_movimento']));  
           
           $counter++;
        }
       
       $phpExcelObject->getActiveSheet()->setTitle('TA_table_list');
       // Set active sheet index to the first sheet, so Excel opens this as the first sheet
       $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->container->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->container->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=TA-file.xls');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');

        return $response;        
     
    }
    
    
}
