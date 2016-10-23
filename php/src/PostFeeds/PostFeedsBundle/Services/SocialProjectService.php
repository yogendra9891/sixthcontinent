<?php
namespace PostFeeds\PostFeedsBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use FOS\UserBundle\Model\UserInterface;
use PostFeeds\PostFeedsBundle\Document\MediaFeeds;
use Utility\UtilityBundle\Utils\Utility;

class SocialProjectService {
    protected $em;
    protected $dm;
    protected $container;

   
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }
    
    /**
     * check social project is exists
     * @param type $social_project_id
     */
    public function checkSocialProject($social_project_id) {
        $this->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Services\SocialProjectService] and function [checkSocialProject] with social project id: '. $social_project_id);
        $dm = $this->dm;
        $type_info = $dm->getRepository('PostFeedsBundle:SocialProject')->findOneBy(array('id'=>$social_project_id, 'is_delete'=>0));
        if (empty($type_info)) {
            $this->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Services\SocialProjectService] and function [checkSocialProject] with social project id: '. $social_project_id);
            return false;
        }
        $this->__createLog('Entering into class [PostFeeds\PostFeedsBundle\Services\SocialProjectService] and function [checkSocialProject] with social project id: '. $social_project_id);    
        return true;
    }
    
    /**
     * Create  log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    public function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.postfeeds_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }
}