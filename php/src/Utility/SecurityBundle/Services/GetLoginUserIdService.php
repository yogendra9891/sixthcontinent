<?php

namespace Utility\SecurityBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use UserManager\Sonata\UserBundle\Entity\UserToAccessToken;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

// service method class for user object.
class GetLoginUserIdService {

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
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
        //$this->request   = $request;
    }

    /**
     *  function for getting the login user id from the request object
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return Int login user id
     */
    public function getLoginUserIdFromRequest(Request $request) {

        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        $user_id = isset($de_serialize['login_user_id'])?$de_serialize['login_user_id']:false;
        return $user_id;
    }
    
    
    /**
     *  get login user id from the request object 
     * @param type $object_info
     * @return int User id
     */
    public function getLoginUserIdFromObject($object_info) {
        
        $request_obj = $object_info;
        $user_id = isset($request_obj->login_user_id)?$request_obj->login_user_id:false;
        return $user_id;
    }

    /**
     * Decode tha data
     * @param string $req_obj
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
     * Get Url content
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

}
