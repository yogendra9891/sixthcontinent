<?php
namespace Utility\RequestHandlerBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Notification\NotificationBundle\Document\UserNotifications;
use Notification\NotificationBundle\NManagerNotificationBundle;
use Monolog\Handler\MongoDBHandler;
use Monolog\Logger;

// service method class for user object.
class MonologRecordService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
  

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
     * Save user notification
     * @param int $user_id
     * @param int $fid
     * @param string $msgtype
     * @param string $msg
     * @return boolean
     */
    public function saveMonologRecords($string='') {
        
        //getting the conatiner object
        $container = NManagerNotificationBundle::getContainer();
        
        //write the monolog to mongodb
        $mongodbhost_host = $container->getParameter('mongodbhost_host'); //angular app host
        $mongodb_database_name = $container->getParameter('mongodb_database_name'); //angular app host
        $monolog_collection = $container->getParameter('monolog_collection'); //angular app host
        
        //create mongodb handler object
        $handler = new MongoDBHandler(new \Mongo($mongodbhost_host), $mongodb_database_name, $monolog_collection);
        $log = new Logger('monolog');
        
        $log->pushHandler($handler);
        
        //adding info type record
        $log->addInfo($string);
    } 
}