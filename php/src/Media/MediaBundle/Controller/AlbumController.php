<?php

namespace Media\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Media\MediaBundle\Document\UserMedia;
use Media\MediaBundle\Document\UserAlbum;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UserManager\Sonata\UserBundle\Entity\User;
use StoreManager\StoreBundle\Entity\Storealbum;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class AlbumController extends Controller
{
    protected $miss_param = '';
    
    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     * @return int
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
     * 
     * @param type $req_obj
     * @return type
     */
    public function decodeDataAction($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * Function to retrieve current applications base URI 
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        //return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl();
    }
    
    /**
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
       //finding the base path of aws and bucket name
       $aws_base_path = $this->container->getParameter('aws_base_path');
       $aws_bucket    = $this->container->getParameter('aws_bucket');
       $full_path     = $aws_base_path.'/'.$aws_bucket;
       return $full_path;
   }
   
   /**
     * Checking for file extension
     * @param $_FILE
     * @return int $file_error
     */
    private function checkFileTypeAction() {
        $file_error = 0;
        foreach ($_FILES['user_media']['tmp_name'] as $key => $tmp_name) {
            $file_name = basename($_FILES['user_media']['name'][$key]);
            //$filecheck = basename($_FILES['imagefile']['name']);
            if (!empty($file_name)) {
                $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
                //for video and images.

                if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                        ($_FILES['user_media']['type'][$key] == 'image/jpeg' ||
                        $_FILES['user_media']['type'][$key] == 'image/jpg' ||
                        $_FILES['user_media']['type'][$key] == 'image/gif' ||
                        $_FILES['user_media']['type'][$key] == 'image/png'))) ||
                        (preg_match('/^.*\.(mp4|mov|mpg|mpeg|wmv|mkv)$/i', $file_name)))) {
                    $file_error = 1;
                    break;
                }
            }
        }
        return $file_error;
    }

    /**
     * 
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeDataAction($content);

        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
    
    /**
     * Call api/editalbums action
     * @param Request $request	
     * @return array
     */
    public function postEditalbumsAction(Request $request) {
        
        $data = array();
        /** Code start for getting the request **/
        $request_object_service = $this->container->get('request_object.service');
        $de_serialize = $request_object_service->RequestObjectService($request);

        $object_info = (object) $de_serialize; //convert an array into object.
        
        $required_parameter = array('user_id','album_id','type','album_name');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        
        /** check for album type **/
        $type_array = array('shop','club','user');
        $album_type = $object_info->type;
        if(!in_array($album_type,$type_array)) {
            return array('code'=>100, 'message'=>'ALBUM_TYPE_IS_INVALID','data'=>$data); 
        }
        
        /** edit store album **/
        if($album_type == 'shop') {
           return $this->editStoreAlbum($object_info);
        }
        
        /** edit user album **/
        if($album_type == 'user') {
           return $this->editUserAlbum($object_info);
        }
        
        /** edit club album **/
        if($album_type == 'club') {
           return $this->editClubAlbum($object_info);
        }
       
    }
    
    /**
     * edit Club album
     * @param type $object_info
     */
    public function editClubAlbum($object_info) {
        $data = array();
        $user_id = (int) $object_info->user_id;
        $album_id = $object_info->album_id;
        $album_type = $object_info->type;
        $album_name = $object_info->album_name;        
        $album_desc = (isset($object_info->album_desc) ? $object_info->album_desc : '');
        
        /** check for club album **/
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $album = $dm->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                ->find($album_id);

        if (!$album) {
            return array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXITS', 'data' => $data);
        }
        
        /** check for owner of the group **/
        $group_id = $album->getGroupId();      
        
        $group_res = $dm
                    ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                    ->findOneBy(array("user_id" =>(int)$user_id,"group_id"=>$group_id)); 
        if(!$group_res) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }
           
        if ($album) {
            $album->setAlbumName($album_name);
            $album->setAlbumDesc($album_desc);
            $dm->persist($album);
            $dm->flush();
        }
        
        $data = array(
            'album_id' =>$album_id,
            'album_name' =>$album_name,
            'album_desc' =>$album_desc,
            'user_id' =>$user_id
        );
        
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($response_data);
        exit;
    }
    
    /**
     * edit user album
     * @param type $object_info
     */
    public function editUserAlbum($object_info) {
        $data = array();
        $user_id = (int) $object_info->user_id;
        $album_id = $object_info->album_id;
        $album_type = $object_info->type;
        $album_name = $object_info->album_name;        
        $album_desc = (isset($object_info->album_desc) ? $object_info->album_desc : '');
        
        //default privacy setting for album is public
        $album_privacy_setting = $object_info->privacy_setting = (isset($object_info->privacy_setting) ? ($object_info->privacy_setting) : 3);
        
        //getting the privacy setting array
        $privacy_setting_constant        = $this->get('privacy_setting_object.service');
        $privacy_setting_constant_result = $privacy_setting_constant->AlbumPrivacySettingService();
        

        if (!in_array($album_privacy_setting, $privacy_setting_constant_result)) {
            return array('code' => 100, 'message' => 'YOU_HAVE_PASSED_WRONG_PRIVACY_SETTING', 'data' => $data);
        }
        
        /** check for user album **/
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $album = $dm->getRepository('MediaMediaBundle:UserAlbum')->find($album_id);
        
        if (!$album) {
            return array('code' => 100, 'message' => 'ALBUM_DOES_NOT_EXISTS', 'data' => $data);
        }
        $album_owner_id = $album->getUserId();
        
        if($album_owner_id != $user_id) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }
        
        if ($album) {
            $album->setAlbumName($album_name);
            $album->setAlbumDesc($album_desc);
            $album->setPrivacySetting($album_privacy_setting);
            $dm->persist($album);
            $dm->flush();
        }
        
        $data = array(
            'id' =>$album_id,
            'album_name' =>$album_name,
            'album_description' =>$album_desc,
            'user_id' =>$user_id,
            'album_privacy' => $album_privacy_setting
        );
        
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($response_data);
        exit;
    }
    
    /**
     * edit shop album
     * @param type $object_info
     */
    public function editStoreAlbum($object_info) {
        $data = array();
        $user_id = (int) $object_info->user_id;
        $album_id = $object_info->album_id;
        $album_type = $object_info->type;
        $album_name = $object_info->album_name;        
        $album_desc = (isset($object_info->album_desc) ? $object_info->album_desc : '');
        
        
        /** get store id **/
        $em = $this->getDoctrine()->getManager();
        $store_album = $em
                ->getRepository('StoreManagerStoreBundle:Storealbum')
                ->findOneBy(array('id' => $album_id));
        
        if($store_album) {
            $store_id = $store_album->getStoreId();
        }
        
        /** get User Role **/
        $mask_id = $this->userStoreRole($store_id, $user_id);

        /** check for Access Permission **/
        $allow_group = array('15');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }
        
        $store_album->setStoreAlbumName($album_name);
        $store_album->setStoreAlbumDesc($album_desc);
        $em->persist($store_album);
        $em->flush();
        
        $data = array(
            'album_id' =>$album_id,
            'album_name' =>$album_name,
            'album_desc' =>$album_desc,
            'user_id' =>$user_id
        );
        
        $response_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($response_data);
        exit;
    }
    
    /**
     * Get User role for group
     * @param int $store_id
     * @param int $user_id
     * @return int
     */
    public function userStoreRole($store_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get entity manager object
        $em = $this->getDoctrine()->getManager();

        $store = $em
                ->getRepository('StoreManagerStoreBundle:Store')
                ->findOneBy(array('id' => $store_id)); //@TODO Add group owner id in AND clause.
        //if group not found
        if (!$store) {
            return $mask;
        }
        $aclProvider = $this->container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($store); //entity

        try {
            $acl = $aclProvider->findAcl($objectIdentity);
        } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
            $acl = $aclProvider->createAcl($objectIdentity);
        }
        //Acl Operation
        $um = $this->container->get('fos_user.user_manager');
        $user_obj = $um->findUserBy(array('id' => $user_id));
        // retrieving the security identity of the currently logged-in user
        $securityIdentity = UserSecurityIdentity::fromAccount($user_obj);

        foreach ($acl->getObjectAces() as $ace) {
            if ($ace->getSecurityIdentity()->equals($securityIdentity)) {
                $mask = $ace->getMask();
                break;
            }
        }
        return $mask;
    }
}
