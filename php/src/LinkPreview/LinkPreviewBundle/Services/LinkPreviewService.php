<?php
namespace LinkPreview\LinkPreviewBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\UtilityBundle\Utils\Utility;
require_once(__DIR__ . '/../Resources/linkpreview/classes/LinkPreview.php');
require_once(__DIR__ . '/../Resources/linkpreview/classes/SetUp.php');
use LinkPreview;
use SetUp;

class LinkPreviewService {

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
     * 
     * @param type $data
     * @return type
     */
    public function getLinkPreview($data) {
        $this->__createLog('Entering in class [ LinkPreview\LinkPreviewBundle\Services\LinkPreviewService] and function [getLinkPreview] with request: ' . Utility::encodeData($data));
        SetUp::init();
        $text = $data["text"];
        $imageQuantity = $data["imagequantity"];
        $text = " " . str_replace("\n", " ", $text);
        $header = "";
        $linkPreview = new LinkPreview();
        $answer = $linkPreview->crawl($text, $imageQuantity, $header);
        $this->__createLog('Exiting from class [ LinkPreview\LinkPreviewBundle\Services\LinkPreviewService] and function [getLinkPreview] with response: ' . (string) $answer);
        return $answer;
    }
    
    /**
     * Create Create log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    public function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.linkpreview_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }

}
