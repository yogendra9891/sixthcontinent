<?php
namespace UserManager\Sonata\UserBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use UserManager\Sonata\UserBundle\Document\Group;
use UserManager\Sonata\UserBundle\Document\GroupMedia;
use UserManager\Sonata\UserBundle\Document\UserToGroup;
use UserManager\Sonata\UserBundle\Document\GroupJoinNotification;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use UserManager\Sonata\UserBundle\Document\UserPhoto;
use Notification\NotificationBundle\Document\UserNotifications;
use Notification\NotificationBundle\NManagerNotificationBundle;

class RestGroupController extends Controller {

    protected $group_media_path = '/uploads/groups/original/';
    protected $group_media_path_thumb = '/uploads/groups/thumb/';
    protected $group_cover_media_path_thumb = '/uploads/groups/thumb_cover_crop/';
    protected $group_media_path_crop_thumb = 'uploads/groups/thumb_crop/';


    // image path
    protected $miss_param = '';
    protected $crop_image_width = 200;
    protected $crop_image_height = 200;
    protected $resize_image_width = 200;
    protected $resize_image_height = 200;
    protected $resize_cover_image_width = 910; //902
    protected $resize_cover_image_height = 400; //320
    protected $original_resize_image_width = 910;
    protected $original_resize_image_height = 910;

    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
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
    * Get User Manager of FOSUSER bundle
    * @return Obj
    */
    protected function getUserManager()
    {
            return $this->container->get('fos_user.user_manager');
    }

   /**
    * Checking for file extension
    * @param $_FILE
    * @return int $file_error
    */
    private function checkFileTypeAction()
    {
        $file_error = 0;
        $file_name = basename($_FILES['group_media']['name']);
        $ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
        if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png' || $ext == 'jpeg') &&
                        ($_FILES['group_media']['type'] == 'image/jpeg'   ||
                         $_FILES['group_media']['type'] == 'image/jpg'    ||
                         $_FILES['group_media']['type'] == 'image/gif'    ||
                         $_FILES['group_media']['type'] == 'image/png'))) ||
                        (preg_match('/^.*\.(mp4|mov|mpg|mpeg|wmv|mkv)$/i', $file_name)))) {
                        $file_error = 1;
                        return $file_error;
                } else{
                        $file_error = 0;
                        return $file_error;
                }

         // return $file_error;
    }

     /**
    * Function to retrieve current applications base URI
    */
     public function getBaseUri()
     {
         // get the router context to retrieve URI information
         $context = $this->get('router')->getContext();
         // return scheme, host and base URL
         return $context->getScheme().'://'.$context->getHost().$context->getBaseUrl().'/';
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
     * Craete group
     * @param Request $request
     * @return array;
     */
    public function postCreategroupsAction(Request $request) {
        //initilise the array

        $data = array();
        //get request object
        //$req_obj = $request->get('reqObj');
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end

        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('group_name','group_description','user_id','group_status');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        /*
        $x = $de_serialize['x'];
        $y = $de_serialize['y'];
        $width = $de_serialize['width'];
        $height = $de_serialize['height'];
        if ($width < $this->crop_image_width or $height < $this->crop_image_height) {
            return array('code' => 100, 'message' => 'you must choose an image greater of 200x200', 'data' => $data);
        }
      *
      */
        //call the deseralizer
        //$de_serialize = $this->decodeData($req_obj);
        //get group title
        $group_title = $de_serialize['group_name'];
        //get group description
        $group_desc = $de_serialize['group_description'];
        //get group owner id
        $group_owner_id = $de_serialize['user_id'];
        //get group status
        //1 for public, 2 for private
        $group_status = $de_serialize['group_status'];

        if ($group_owner_id == "") {
            $res_data = array('code' => 115, 'message' => 'GROUP_OWNER_ID_REQUIRED', 'data' => array());
            return $res_data;
        }

        if ($group_title == "") {
            $res_data = array('code' => 116, 'message' => 'GROUP_TITLE_IS_REQUIRED', 'data' => array());
            return $res_data;
        }

        //check for group status
        //1 for public
        //2 for private
        if (!in_array($group_status, array(1, 2))) {
            $res_data = array('code' => 116, 'message' => 'GROUP_STATUS_IS_INAVALID', 'data' => array());
            return $res_data;
        }

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($group_owner_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //$container = UserManagerSonataUserBundle::getContainer();
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //get group object
        $group = new Group();
        //set group fields
        $group->setTitle($group_title);
        $group->setDescription($group_desc);
        $group->setOwnerId($group_owner_id);
        $group->setCreatedAt(time());
        $group->setUpdatedAt(time());
        $group->setGroupStatus($group_status);

        //persist the group object
        $dm->persist($group);
        //save the group info
        $dm->flush();

        //assign the user in UserToGroup Table
        //get usertogroup object
        $usertogroup = new UserToGroup();
        $usertogroup->setUserId($group_owner_id);
        $usertogroup->setGroupId($group->getId());
        //persist the group object
        $dm->persist($usertogroup);
        //save the group info
        $dm->flush();
        //get ACL code for Group owner
        $group_owner_acl_code = $this->getGroupOwnerAclCode();
        //Acl Operation
        $um = $this->container->get('fos_user.user_manager');
        $user_obj = $um->findUserBy(array('id' => $group_owner_id));

        $current_rate = 0;
        $is_rated = false;
        foreach($group->getRate() as $rate)
        {
            if($rate->getUserId() == $group_owner_id )
            {
                $current_rate = $rate->getRate();
                $is_rated = true;
                break;
            }
        }


        $aclManager = $this->get('problematic.acl_manager');
        $aclManager->setObjectPermission($group, $group_owner_acl_code, $user_obj);
        // $aclManager->addObjectPermission($group, MaskBuilder::MASK_OWNER);
        $resp_data = array('code' => '101', 'message' => 'GROUP_IS_CREATED',
        'data' => array(
            'group_id'=>$group->getId(),
            'group_status'=>$group_status,
            'avg_rate'=>round($group->getAvgRating(),1),
            'no_of_votes'=>(int) $group->getVoteCount(),
            'current_user_rate'=>$current_rate,
            'is_rated'=>$is_rated));
        echo json_encode($resp_data);
        exit();
    }

    /**
     * Edit group
     * @param Request $request
     * @return array;
     */
    public function postUpdategroupsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        //$req_obj = $request->get('reqObj');
        //call the deseralizer
        //$de_serialize = $this->decodeData($req_obj);

        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end
        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('group_id','group_name','group_description','user_id','group_status');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        //get group id
        $group_id = $de_serialize['group_id'];
        //get group title
        $group_title = $de_serialize['group_name'];
        //get group description
        $group_desc = $de_serialize['group_description'];
        //get group owner id
       // $group_owner_id = $de_serialize['group_owner_id'];
        //get group owner id
        $user_id = $de_serialize['user_id'];

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get User Role
        $mask_id = $this->userGroupRole($group_id, $user_id);
        //check for Access Permission
        //only owner and admin can edit the group
        $allow_group = array('15', '7');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }

        //get group status
        //1 for public, 2 for private
        $group_status = $de_serialize['group_status'];

        if ($user_id == "") {
            $res_data = array('code' => 111, 'message' => 'USER_ID_REQUIRED', 'data' => array());
            return $res_data;
        }

       /* if ($group_owner_id == "") {
            $res_data = array('code' => 115, 'message' => 'Group owner id required', 'data' => array());
            return $res_data;
        }
        *
        */

        //check for group status
        //1 for public
        //2 for private

        if ($group_status != "") {
            if (!in_array($group_status, array(1, 2))) {
                $res_data = array('code' => 116, 'message' => 'GROUP_STATUS_IS_INAVALID', 'data' => array());
                return $res_data;
            }
        }


        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $group = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id)); //@TODO Add group owner id in AND clause.

        if (!$group) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        //set group object
        //check for group title
        if ($group_title != "") {
           $group->setTitle($group_title);

        }

        //check group description
        if ($group_desc != "") {
            $group->setDescription($group_desc);
        }

        //set updated at time
        $group->setUpdatedAt(time());
        //check for group status(public or private)
        if ($group_status != "") {
            $group->setGroupStatus($group_status);
        }
        //persist the group object
        $dm->persist($group);
        //save the group info
        $dm->flush();
        $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($resp_data);
        exit();
    }


    /**
     * List user's group
     * @param Request $request
     * @return array;
     */
//    public function postGetusergroupsAction(Request $request) {
//        //initilise the array
//        $data = array();
//        //get request object
//
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeData($freq_obj);
//
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }
//
//        //Code repeat end
//
//        //check parameter
//        $object_info = (object) $de_serialize; //convert an array into object.
//
//        $required_parameter = array('user_id');
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
//        }
//        //end check parameter
//
//        //get group owner id
//        //$group_owner_id = (int) $de_serialize['group_owner_id'];
//        //get user login id
//        $user_id = (int) $de_serialize['user_id'];
//        //check if user is active or not
//        $user_check_enable = $this->checkActiveUserProfile($user_id);
//
//        if ($user_check_enable == false) {
//            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
//            return $res_data;
//        }
//
//        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
//            //get limit size
//            $limit_size = (int) $de_serialize['limit_size'];
//            if ($limit_size == "") {
//                $limit_size = 20;
//            }
//            //get limit offset
//            $limit_start = (int) $de_serialize['limit_start'];
//            if ($limit_start == "") {
//                $limit_start = 0;
//            }
//        } else {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
//        }
//
//
//        if ($user_id == "") {
//            $res_data = array('code' => 115, 'message' => 'GROUP_OWNER_ID_REQUIRED', 'data' => array());
//            return $res_data;
//        }
//        $user_service_owner    = $this->get('user_object.service');
//        // get documen manager object
//        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
//        $groups = $dm
//                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
//                ->getMemberGroupList($user_id);
//        if (!$groups) {
//            $res_data = array('code' => 100, 'message' => 'NO_GROUP_FOUND', 'data' => $data);
//            return $res_data;
//        }
//        //get data
//
//         $user_info = array();
//        //get data
//        foreach ($groups as $group) {
//            $group_id = $group['id'];
//             //get group detail
//             $group_info = $dm
//                ->getRepository('UserManagerSonataUserBundle:Group')
//                ->findOneBy(array('id'=>$group_id));
//
//              //check for delete status
//              $is_delete = $group_info->getIsDelete();
//              if($is_delete == 1){
//                 continue;
//              }
//             // get media information related to this group .i.e
//             // get information from group media table where group_id = $group_id
//             $group_medias = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupMedia')
//                         ->findBy(array('group_id' => $group_id));
//
//            // get the profile image of group
//            $group_medias_profile_img = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupMedia')
//                       ->findOneBy(array('group_id' => $group_id,'profile_image'=>1));
//            $profile_img_original = "";
//            $profile_img_thumb = "";
//            if($group_medias_profile_img){
//                $profile_img_original = $this->getS3BaseUri() . $this->group_media_path . $group_id . '/'.$group_medias_profile_img->getMediaName();
//                $profile_img_thumb = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$group_medias_profile_img->getMediaName();
//            }
//
//            //get group members
//            $group_members = $dm
//                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
//                ->findBy(array('group_id' => $group_id));
//            unset($user_info);
//            $user_info = array();
//            foreach ($group_members as $group_member) {
//                $user_id = $group_member->getUserId();
//                //get user role
//                //get User Role
//                $umask_id = $this->userGroupRole($group_id, $user_id);
//
//                $um = $this->container->get('fos_user.user_manager');
//                $user_bobj = $um->findUserBy(array('id' => $user_id));
//                //$user_obj = $this->getUserAllInfo($user_id);
//                //code start for getting the user object..
//                $user_id        = $user_bobj->getId();
//                $user_service   = $this->get('user_object.service');
//                $user_object    = $user_service->UserObjectService($user_id);
//                $user_object['role'] = $umask_id;
//                //code end for getting the user object..
//               // $user_info[] = array('user_id' => $user_bobj->getId(), 'user_name' => $user_bobj->getUsername(),'role'=>$umask_id);
//                $user_info[] = $user_object;
//            }
//
//             $media_data= array();
//             foreach($group_medias as $group_media)
//             {
//               $media_id    = $group_media->getId();
//               $media_name  = $group_media->getMediaName();
//               $media_type  = $group_media->getMediaType();
//               $group_id    = $group_info->getId();
//
//               $thumb_dir    = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$media_name;
//
//               $media_data = array('id'=>$media_id,
//                                      'media_name'=>$media_name,
//                                      'media_type'=>$media_type,
//                                      'media_path'=>$thumb_dir,
//                                     );
//             }
//            $group_title = $group_info->getTitle();
//            $group_description = $group_info->getDescription();
//            $group_creation_date = $group_info->getCreatedAt();
//            $group_owner_id = $group_info->getOwnerId();
//            //call the service for user object.
//
//            $owner_object          = $user_service_owner->UserObjectService($group_owner_id);
//
//            $data[] = array('group_id' => $group_id,
//                'owner_id' => $group_owner_id,
//                'owner_info' => $owner_object,
//                'group_title' => $group_title,
//                'group_description' => $group_description,
//                'created_at' => $group_creation_date,
//                'group_status' => $group_info->getGroupStatus(),
//                'members' => $user_info,
//                'media_info'=>$media_data,
//                'profile_img_original'=>$profile_img_original,
//                'profile_img_thumb'=>$profile_img_thumb
//            );
//        }
//        //reverse the array
//        $data = array_reverse($data);
//        $group_size = count($data);
//        $group_output = array_slice($data, $limit_start, $limit_size);
//        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('groups' => $group_output, 'size' => $group_size));
//        echo json_encode($resp_data);
//        exit();
//    }



    /**
     * List user's group
     * @param Request $request
     * @return array;
     */
    public function postGetmemberusergroupsAction(Request $request) {
        //initilise the array
        $group_info_array = array();
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id','member_type');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get group owner id
        //$group_owner_id = (int) $de_serialize['group_owner_id'];
        //get user login id
        $user_id = (int) $de_serialize['user_id'];

        $member_type = $de_serialize['member_type'];
        $allow_member_type = array('7', '1');

        if (!in_array($member_type, $allow_member_type)) {
            $resp_data = array('code' => '126', 'message' => 'INVALID_MEMBER_TYPE', 'data' => array());
            return $resp_data;
        }
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
        //get limit size
        $limit_size = (int) $de_serialize['limit_size'];
        if ($limit_size == "") {
            $limit_size = 20;
        }
        //get limit offset
        $limit_start = (int) $de_serialize['limit_start'];
        if ($limit_start == "") {
            $limit_start = 0;
        }
        } else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }

        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $group_list = $dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->getMemberGroupList($user_id);

        if (!$group_list) {
            $res_data = array('code' => 100, 'message' => 'NO_GROUP_FOUND', 'data' => $data);
            return $res_data;
        }
        //get data

        foreach ($group_list as $group) {
            $group_id = $group['id'];

            //get user role for this group
            //get User Role
           $mask_id = $this->userGroupRole($group_id, $user_id);
           if($mask_id == $member_type){
           //get group inf0
            $group_info = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id'=>$group_id));
            $id = $group_info->getId();
            $title = $group_info->getTitle();
            $description = $group_info->getDescription();
            $owner_id = $group_info->getOwnerId();
            $group_status = $group_info->getGroupStatus();
            //$user_obj = $this->getUserAllInfo($user_id);
            $group_info_array[] = array('group_id'=>$id, 'title'=>$title, 'description'=>$description, 'owner_id'=>$owner_id, 'group_status'=>$group_status, 'role_id'=>$mask_id);
           }

           }

        $group_size = count($group_info_array);
        $group_output = array_slice($group_info_array, $limit_start, $limit_size);
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('groups' => $group_output, 'size' => $group_size));
        echo json_encode($resp_data);
        exit();
    }

    /**
     * Delete user's group
     * @param Request $request
     * @return array;
     */
    public function postDeleteusergroupsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        //$req_obj = $request->get('reqObj');
        //call the deseralizer
        //$de_serialize = $this->decodeData($req_obj);
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id','group_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get group id (commented by Ankur since it gives undefined variable notice
       // $group_owner_id = (int) $de_serialize['group_owner_id'];
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //get group id
        $group_id = $de_serialize['group_id'];
        //check for group id
        if ($group_id == "") {
            $res_data = array('code' => 116, 'message' => 'GROUP_ID_REQUIRED', 'data' => $data);
            return $res_data;
        }
        //get User Role
        $mask_id = $this->userGroupRole($group_id, $user_id);
        //check for Access Permission
        //only owner can delete the group
        $allow_group = array('15');
        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }

        //digital delete
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $group = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id)); //@TODO add group owner id in AND clause.

        if (!$group) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        //digital delete
        $group->setIsDelete(1);
        //persist the group object
        $dm->persist($group);
        //save the group info
        $dm->flush();
        // delete media of that group start
        $group_media =  $this->get('doctrine_mongodb')
                         ->getRepository('UserManagerSonataUserBundle:GroupMedia')
                         ->removeGroupMedia($group_id);
        if ($group_media) {
            $document_root = $request->server->get('DOCUMENT_ROOT');
            $base_path = $request->getBasePath();
            $file_location = $document_root.$base_path; // getting sample directory path
            $image_album_location = $file_location.'/uploads/groups/original/'.$group_id.'/';
            $thumbnail_album_location = $file_location.'/uploads/groups/thumb/'.$group_id.'/';
            if(file_exists($image_album_location))
            {
              //  array_map('unlink', glob($image_album_location.'/*'));
              //  rmdir($image_album_location);
            }
            if(file_exists($thumbnail_album_location))
            {
              //  array_map('unlink', glob($thumbnail_album_location.'/*'));
             //   rmdir($thumbnail_album_location);
            }
               return array('code'=>101, 'message'=>'SUCCESS','data'=>$data);
        } else {
             return array('code'=>100, 'message'=>'FAILURE', 'data'=>$data);
        }
         // delete media of that group end
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($resp_data);
        exit();
    }

    /**
     * Search User
     * @param Request $request
     * @return array
     */
//    public function postSearchgroupAction(Request $request) {
//        //initilise the array
//        $data = array();
//        $user_info = array();
//        $is_sent = 0;
//        //get request object
//       // $req_obj = $request->get('reqObj');
//        //call the deseralizer
//        //$de_serialize = $this->decodeData($req_obj);
//        //Code repeat start
//        $freq_obj = $request->get('reqObj');
//        $fde_serialize = $this->decodeData($freq_obj);
//
//        if (isset($fde_serialize)) {
//            $de_serialize = $fde_serialize;
//        } else {
//            $de_serialize = $this->getAppData($request);
//        }
//
//        //Code repeat end
//
//        //check parameter
//        $object_info = (object) $de_serialize; //convert an array into object.
//
//        $required_parameter = array('user_id');
//        //checking for parameter missing.
//        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
//        if ($chk_error) {
//                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
//        }
//        //end check parameter
//
//        //get user login id
//        $user_id = (int) $de_serialize['user_id'];
//        $cuser_id = (int) $de_serialize['user_id'];
//
//        //search text
//        $group_name = "";
//        if (isset($de_serialize['group_name'])){
//        $group_name = $de_serialize['group_name'];
//        }
//
//        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
//        //limit start
//        $limit_start = (($de_serialize['limit_start'] == "") ? 0 : $de_serialize['limit_start']);
//        //limit length
//        $limit_length = (($de_serialize['limit_size'] == "") ? 20 : $de_serialize['limit_size']);
//         } else {
//            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
//        }
//        //check if user is active or not
//        $user_check_enable = $this->checkActiveUserProfile($user_id);
//        if ($user_check_enable == false) {
//            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
//            return $res_data;
//        }
//
//        // get documen manager object
//        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
//        $group_list = $dm
//                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
//                ->getGroupList($user_id);
//
//        $group_search_results = $dm
//                ->getRepository('UserManagerSonataUserBundle:Group')
//                ->getGroupSearchList($group_list, $group_name, $limit_start, $limit_length);
//
//        //get data
//        foreach ($group_search_results as $group) {
//            $group_id = $group->getId();
//
//          // get media information related to this group .i.e
//            // get information from group media table where group_id = $group_id
//            $group_medias = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupMedia')
//                        ->findBy(array('group_id' => $group_id));
//
//            // get the profile image of group
//            $group_medias_profile_img = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupMedia')
//                               ->findOneBy(array('group_id' => $group_id,'profile_image'=>1));
//            $profile_img_original = "";
//            $profile_img_thumb = "";
//            if($group_medias_profile_img){
//                    $profile_img_original = $this->getS3BaseUri() . $this->group_media_path . $group_id . '/'.$group_medias_profile_img->getMediaName();
//                    $profile_img_thumb = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$group_medias_profile_img->getMediaName();
//            }
//
//            $media_data= array();
//            foreach($group_medias as $group_media)
//            {
//              $media_id    = $group_media->getId();
//              $media_name  = $group_media->getMediaName();
//              $media_type  = $group_media->getMediaType();
//              $group_id    = $group->getId();
//
//              $thumb_dir    = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$media_name;
//
//              $media_data = array('id'=>$media_id,
//                                     'media_name'=>$media_name,
//                                     'media_type'=>$media_type,
//                                     'media_path'=>$thumb_dir,
//                                    );
//            }
//
//            $group_title = $group->getTitle();
//            $group_description = $group->getDescription();
//            $group_creation_date = $group->getCreatedAt();
//            $group_owner_id = $group->getOwnerId();
//            $group_status = $group->getGroupStatus();
//            $is_member = 0;
//            //$member = $dm
//               // ->getRepository('UserManagerSonataUserBundle:UserToGroup')
//               // ->findOneBy(array('group_id'=>$group_id,'user_id'=>$user_id));
//            //if(!$member){
//              //  $is_member = 0;
//           // }
//
//            $user_service_owner    = $this->get('user_object.service');
//            $owner_object          = $user_service_owner->UserObjectService($group_owner_id);
//
//            //get group members
//            $group_members = $dm
//                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
//                ->findBy(array('group_id' => $group_id));
//            unset($user_info);
//            $user_info = array();
//            foreach ($group_members as $group_member) {
//            $user_id = $group_member->getUserId();
//            if($user_id == $cuser_id){
//            $is_member = 1;
//            }
//            //get user role
//            //get User Role
//            $umask_id = $this->userGroupRole($group_id, $user_id);
//
//            $um = $this->container->get('fos_user.user_manager');
//            $user_bobj = $um->findUserBy(array('id' => $user_id));
//
//            $user_id        = $user_bobj->getId();
//            $user_service   = $this->get('user_object.service');
//            $user_object    = $user_service->UserObjectService($user_id);
//            $user_object['role'] = $umask_id;
//
//            //$user_obj = $this->getUserAllInfo($user_id);
//            //$user_info[] = array('user_id' => $user_bobj->getId(), 'user_name' => $user_bobj->getUsername(),'role'=>$umask_id);
//            $user_info[] = $user_object;
//            }
//             //check for group invitaion
//            if ($is_member == 0) {
//                // get documen manager object
//                $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
//                $group_notification_check = $dm
//                        ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
//                        ->findOneBy(array('sender_id' => $cuser_id, 'group_id' => $group_id));
//
//                if (count($group_notification_check) == 0) {
//                   $is_sent = 0;
//                }else{
//                    $is_sent = 1;
//                }
//            }
//            $data[] = array('group_id' => $group_id,
//                'owner_id' => $group_owner_id,
//                'owner_info' => $owner_object,
//                'group_title' => $group_title,
//                'group_description' => $group_description,
//                'created_at' => $group_creation_date,
//                'group_status' => $group_status,
//                'is_member' => $is_member,
//                'is_sent' => $is_sent,
//                'members' => $user_info,
//                'media_info'=>$media_data,
//                'profile_img_original'=>$profile_img_original,
//                'profile_img_thumb'=>$profile_img_thumb
//            );
//        }
//        //get search count
//        $group_search_results_count = $dm
//                ->getRepository('UserManagerSonataUserBundle:Group')
//                ->getGroupSearchListCount($group_list, $group_name);
//
//        //checking if there is joined group for logined user.
//        $is_my_group = 0;
//        $my_groups = $dm
//                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
//                ->getMemberGroupList($cuser_id);
//        if ($my_groups) {
//            $is_my_group = 1;
//        }
//
//        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('groups' => $data, 'size' => $group_search_results_count, 'my_group' => $is_my_group));
//        echo json_encode($res_data);
//        exit();
//    }

    /**
     * Guest can make request to join public group
     * @param Request $request
     * @return array
     */
    public function postJoinpublicgroupsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        //$req_obj = $request->get('reqObj');
        //call the deseralizer
        //$de_serialize = $this->decodeData($req_obj);
         //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id','group_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;

        //get group id
        $group_id = $de_serialize['group_id'];
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //get search count
        $group_join = $dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->joinPublicGroup($user_id, $group_id);

        if ($group_join > 0) {
            $res_data = array('code' => 117, 'message' => 'ALREADY_THE_MEMBER_OF_GROUP', 'data' => $data);
            return $res_data;
        }
        //check if user has already requested to join
        $group_rejoin = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->findOneBy(array('sender_id' => $user_id, 'group_id' => $group_id));

        if (count($group_rejoin) > 0) {
            $res_data = array('code' => 118, 'message' => 'REQUEST_IS_PENDING_FOR_OWNER_APPROVAL', 'data' => $data);
            return $res_data;
        }

        $group_owner_id = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->getGroupOwnerId($group_id);

        //join the user in group
        //assign the user in GroupJoinNotification Table
        //get GroupJoinNotification object
        $usertogroup = new GroupJoinNotification();
        $usertogroup->setSenderId($user_id);
        $usertogroup->setReceiverId($group_owner_id);
        $usertogroup->setGroupId($group_id);
        $usertogroup->setUserRole(3); //Friend Role
        $usertogroup->setCreatedAt(time());
        //persist the group object
        $dm->persist($usertogroup);
        //save the group info
        $dm->flush();

        //send mail
        $from_id = $user_id;
        $to_id = $group_owner_id;

        //get group name
        $group_detail = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id));

        $title      = $group_detail->getTitle();
        $group_type = $group_detail->getGroupStatus();
        // get the profile image of club
        $group_medias_profile_img = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupMedia')
                           ->findOneBy(array('group_id' => $group_id,'profile_image'=>1));

        $profile_img_thumb = "";
        if($group_medias_profile_img){
                $album_id = $group_medias_profile_img->getAlbumid();
                if ($album_id) {
                  $profile_img_thumb = $aws_path . $this->group_media_path_thumb . $group_id .'/'.$album_id.'/'.$group_medias_profile_img->getMediaName();
                } else {
                  $profile_img_thumb = $aws_path . $this->group_media_path_thumb . $group_id .'/'.$group_medias_profile_img->getMediaName();
                }
        }

        $email_template_service =  $this->container->get('email_template.service'); //email template service.

        $postService = $this->get('post_detail.service');
        $receiver = $postService->getUserData($to_id, true);
        //get locale
        $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);

        //for mail template..
        $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
        $club_profile_url     = $this->container->getParameter('club_profile_url'); //club profile url


        $sender = $postService->getUserData($from_id);
        $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

        $href = $angular_app_hostname.$club_profile_url.'/'.$group_id.'/'.$group_type; //href for club profile
        $link = $email_template_service->getLinkForMail($href,$locale); //making the link html from service
        $mail_sub  = sprintf($lang_array['CLUB_PUBLIC_JOIN_REQUEST_SUBJECT']);
        $mail_body = sprintf($lang_array['CLUB_PUBLIC_JOIN_REQUEST_BODY'], ucwords($sender_name), ucwords($title));
        $mail_text = sprintf($lang_array['CLUB_PUBLIC_JOIN_REQUEST_TEXT'], ucwords($sender_name), ucwords($title));
        $bodyData      = $mail_text."<br><br>".$link;

        // HOTFIX NO NOTIFY MAIL
        //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $profile_img_thumb, 'CLUB_JOIN');
        //return success
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($resp_data);
        exit();
    }

    /**
     * Get notification list
     * @param Request $request
     */
    public function postGetgroupjoinnotificationsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object

        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //@TODOcheck for active member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $group_notification_id = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->getGroupJoinNotifications($user_id);

        if (count($group_notification_id) == 0) {
            //no notification found
            //return success
            $res_data = array('code' => 119, 'message' => 'NO_NOTIFICATION', 'data' => $data);
            return $res_data;
        }
        //if found then get the notification details
        $group_notification_id_detail = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->getGroupJoinNotificationsDetail($group_notification_id);

        $group_notification_data = array();
        foreach ($group_notification_id_detail as $group_notifications) {
            $sender_id      = $group_notifications['sender_id'];
            //call the serviec for user object.
            $user_service   = $this->get('user_object.service');
            $user_object    = $user_service->UserObjectService($sender_id);
            $group_notifications['sender_info'] = $user_object;
            $group_notification_data[] = $group_notifications;
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $group_notification_data);
        echo json_encode($res_data);
        exit;
    }

    /**
     * Get notification list for specific group
     * @param Request $request
     */
    public function postGetgroupnotificationsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object

        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code repeat end

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'group_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get user login id
        $user_id = (int) $de_serialize['user_id'];

        //get user login id
        $group_id = $de_serialize['group_id'];

        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $group = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id)); //@TODO Add group owner id in AND clause.
        //if group not found
        if(!$group){
            return array('code'=>'100','message'=>'ERROR_OCCURED','data'=>$data);
        }
        $group_owner_id = $group->getOwnerId();
        //only group owner or group admin can invite the user
        //get User Role
        $mask_id = $this->userGroupRole($group_id, $user_id);

        //check for Access Permission
        $allow_group = array('15', '7');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }
        //end to check permission

        //@TODOcheck for active member

        $group_notification_id = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->getGroupNotifications($group_id, $group_owner_id);

        if (count($group_notification_id) == 0) {
            //no notification found
            //return success
            $res_data = array('code' => 119, 'message' => 'NO_NOTIFICATION', 'data' => $data);
            return $res_data;
        }
        //if found then get the notification details
        $group_notification_id_detail = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->getGroupJoinNotificationsDetail($group_notification_id);

        $group_notification_data = array();
        //iterate the result for sending the user object.
        foreach ($group_notification_id_detail as $group_notifications) {
            $sender_id      = $group_notifications['sender_id'];
            //call the serviec for user object.
            $user_service   = $this->get('user_object.service');
            $user_object    = $user_service->UserObjectService($sender_id);
            $group_notifications['sender_info'] = $user_object;
            $group_notification_data[]          = $group_notifications;
        }
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $group_notification_data);
        echo json_encode($resp_data);
        exit();
    }

    /**
     * Get notification list
     * @param Request $request
     */
    public function postResponsegroupjoinsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'request_id', 'sender_id', 'group_id', 'response', 'request_type');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get user login id
        $user_id = $de_serialize['user_id'];

        $notification_from_id = $user_id;
        //get request id
        $request_id = $de_serialize['request_id'];


        //get Group id
        $group_id = $de_serialize['group_id'];
        //get sender id
        $sender_id = $de_serialize['sender_id'];
        $notification_to_id = $sender_id;
        //response
        //1 for accept. 2 for deny
        $response = $de_serialize['response'];
        //check for response
        $allow_res = array('1','2');

        if(!in_array($response, $allow_res)){
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED1', 'data' => $data);
            return $res_data;
        }
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //get request type
        $request_type = $de_serialize['request_type']; //admin or user
        //check for request id
        $allow_req = array('admin','user');

        if(!in_array($request_type, $allow_req)){
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED2', 'data' => $data);
            return $res_data;
        }
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //get group admin id
        $group_admin = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id));

        if (!$group_admin) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        $group_admin_id = $group_admin->getOwnerId();


        //check if notifications existes
        $group_response = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->findOneBy(array('id' => $request_id));
        if (!$group_response) {
            //check if user is group member
            $res = $this->checkGroupMember($sender_id, $group_id, $user_id, $request_type, $group_admin_id);

        }

        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket     = $this->container->getParameter('aws_bucket');
        $aws_path       = $aws_base_path.'/'.$aws_bucket;

        // get the profile image of club
        $group_medias_profile_img = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupMedia')
                               ->findOneBy(array('group_id' => $group_id, 'profile_image'=>1));

            $profile_img_thumb = "";
            if($group_medias_profile_img){
                $album_id = $group_medias_profile_img->getAlbumid();
                if ($album_id) {
                    $profile_img_thumb = $aws_path . $this->group_media_path_thumb . $group_id .'/'.$album_id.'/'.$group_medias_profile_img->getMediaName();
                } else {
                    $profile_img_thumb = $aws_path . $this->group_media_path_thumb . $group_id .'/'.$group_medias_profile_img->getMediaName();
                }
            }
            //get group name
            $group_detail = $dm->getRepository('UserManagerSonataUserBundle:Group')
                                ->findOneBy(array('id' => $group_id));

            $group_type = $group_detail->getGroupStatus();
            //for mail template parameters..
            //get the local parameters in parameters file.
            $locale = $this->container->getParameter('locale');
            $language_const_array = $this->container->getParameter($locale);
            $email_template_service =  $this->container->get('email_template.service');
            $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
            $club_profile_url   = $this->container->getParameter('club_profile_url'); //club profile url
            $href = $angular_app_hostname.$club_profile_url.'/'.$group_id.'/'.$group_type; //complete club profile url.

        //response request
        if ($response == 2) {
            //reject the request
            $group_response = $dm
                    ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                    ->findOneBy(array('id' => $request_id));

            $dm->remove($group_response);
            $dm->flush();

              //update in notification table
            $msgtype = 'group';
            $msg = 'reject';

            $add_notification = $this->saveUserNotification($notification_from_id, $notification_to_id, $group_id, $msgtype, $msg);

            //send mail
            $from_id = $notification_from_id;
            $to_id = $notification_to_id;

            $postService = $this->get('post_detail.service');
            $receiver = $postService->getUserData($to_id, true);
            //get locale
            $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);

            $sender = $postService->getUserData($from_id);
            $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

            $title     = $group_detail->getTitle();
            if ($group_type == 1) { //public group.
                $mail_sub  = sprintf($lang_array['CLUB_PUBLIC_JOIN_REJECT_SUBJECT']);
                $mail_body = sprintf($lang_array['CLUB_PUBLIC_JOIN_REJECT_BODY'], ucwords($sender_name), ucwords($title));
                $mail_text = sprintf($lang_array['CLUB_PUBLIC_JOIN_REJECT_TEXT'], ucwords($sender_name), ucwords($title));
            } else if ($group_type == 2) { //private group
                $mail_sub  = sprintf($lang_array['CLUB_PRIVATE_JOIN_REJECT_SUBJECT']);
                $mail_body = sprintf($lang_array['CLUB_PRIVATE_JOIN_REJECT_BODY'], ucwords($sender_name), ucwords($title));
                $mail_text = sprintf($lang_array['CLUB_PRIVATE_JOIN_REJECT_TEXT'], ucwords($sender_name), ucwords($title));
            }

            $link =  $email_template_service->getLinkForMail($href,$locale); //making the link html from service
            $bodyData      = $mail_text."<br><br>".$link;

            // HOTFIX NO NOTIFY MAIL
            //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $profile_img_thumb, 'CLUB_JOIN');

            $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        }

        if ($response == 1) {
            //accept the request
            //assign the user in UserToGroup Table
            //get usertogroup object
            $usertogroup = new UserToGroup();
            //check for request type
            //if invitaion for public group
            if ($request_type == "admin") {
                //check if sender is not admin
                $senderIsMember = $dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                                ->isActiveMember($sender_id, $group_id);
                if($senderIsMember){
                    $sender_id = $user_id;
                }
//                if ($group_admin_id == $sender_id) {
//                    $sender_id = $user_id;
//                }
                $usertogroup->setUserId($sender_id);
            }
            //if invitaion for private group
            if ($request_type == "user") {
                $usertogroup->setUserId($user_id);
            }
            $usertogroup->setGroupId($group_id);
            //persist the group object
            $dm->persist($usertogroup);
            //save the group info
            $dm->flush();
            //remove the notification
            $group_response = $dm
                    ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                    ->findOneBy(array('id' => $request_id));
            if (!$group_response) {
                $error = array('code' => '101', 'message' => 'ERROR_OCCURED', 'data' => array());
                return $error;
            }
            $user_role = $group_response->getUserRole();
            $dm->remove($group_response);
            $dm->flush();
            //add the user as friend of the group
            //if invitaion for public group
            if ($request_type == "admin") {
                if ($group_admin_id == $sender_id) {
                    $assigned_user_id = $user_id;
                } else {
                    $assigned_user_id = $sender_id;
                }
            }

            //if invitaion for private group
            if ($request_type == "user") {
                $assigned_user_id = $user_id;
            }
            $role_resp = $this->assignUserGroupRole($group_id, $assigned_user_id, $user_role);

             //update in notification table
            $msgtype = 'group';
            $msg = 'accept';
            $n_to = $de_serialize['sender_id'];

            $add_notification = $this->saveUserNotification($notification_from_id, $n_to, $group_id, $msgtype, $msg);

            //send mail
            $from_id = $notification_from_id;
            $to_id = $n_to;

            $postService = $this->get('post_detail.service');
            $receiver = $postService->getUserData($to_id, true);
            //get locale
            $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
            $lang_array = $this->container->getParameter($locale);

            $sender = $postService->getUserData($from_id);
            $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

            $title      = $group_detail->getTitle();
            if ($group_type == 1) { //public group.
                $mail_sub   = sprintf($lang_array['CLUB_PUBLIC_JOIN_ACCEPT_SUBJECT']);
                $mail_body  = sprintf($lang_array['CLUB_PUBLIC_JOIN_ACCEPT_BODY'], ucwords($sender_name), ucwords($title));
                $mail_text  = sprintf($lang_array['CLUB_PUBLIC_JOIN_ACCEPT_TEXT'], ucwords($sender_name), ucwords($title));
            } else if ($group_type == 2) { //private group
                $mail_sub   = sprintf($lang_array['CLUB_PRIVATE_JOIN_ACCEPT_SUBJECT']);
                $mail_body  = sprintf($lang_array['CLUB_PRIVATE_JOIN_ACCEPT_BODY'], ucwords($sender_name), ucwords($title));
                $mail_text  = sprintf($lang_array['CLUB_PUBLIC_JOIN_ACCEPT_TEXT'], ucwords($sender_name), ucwords($title));
            }

            $link =  $email_template_service->getLinkForMail($href,$locale); //making the link html from service
            $bodyData      = $mail_text."<br><br>".$link;

            // HOTFIX NO NOTIFY MAIL
            //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $profile_img_thumb, 'CLUB_JOIN');

            $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        }
    }

    /**
     *
     * @param type $sender_id
     * @param type $group_id
     * @param type $user_id
     * @param type $request_type
     * @param type $group_admin_id
     */
    public function checkGroupMember($sender_id, $group_id, $user_id, $request_type, $group_admin_id) {
        $data = array();
        if ($request_type == "admin") {
                //check if sender is not admin
                if ($group_admin_id == $sender_id) {
                    $friend_id = $user_id;
                }else{
                    $friend_id = $sender_id;
                }
            }
            //if invitaion for private group
            if ($request_type == "user") {
                $friend_id = $user_id;
            }

        $group_id = (string)$group_id;
        $friend_id = (int)$friend_id;
         // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $group_join = $dm ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->joinPrivateGroup($friend_id, $group_id);

        if ($group_join == 0) {
            $res_data = array('code' => 117, 'message' => 'ALREADY_THE_MEMBER_OF_GROUP', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }else{
            $res_data = array('code' => 182, 'message' => 'NOTIFICATION_NOT_FOUND', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

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
        $notification->setMessageStatus('U');
        $time = new \DateTime("now");
        $notification->setDate($time);
        $notification->setIsRead('0');
        $notification->setItemId($item_id);
        $dm->persist($notification);
        $dm->flush();
        return true;
    }


     /**
     * send email for notification on activation
     * @param type $mail_sub
     * @param type $from_id
     * @param type $to_id
     * @param type $mail_body
     * @return boolean
     */
    public function sendEmailNotification($mail_sub,$from_id,$to_id,$mail_body){
        $userManager = $this->getUserManager();
        $from_user = $userManager->findUserBy(array('id' => (int)$from_id));
        $to_user = $userManager->findUserBy(array('id' => (int)$to_id));
        $sixthcontinent_admin_email = $this->container->getParameter('sixthcontinent_admin_email');
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
     * Assign group role
     * @param int $group_id
     * @param int $user_id
     */
    public function assignUserGroupRole($group_id, $user_id, $user_role) {           // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $group = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id)); //@TODO Add group owner id in AND clause.
        //get ACL code for Group friend
        if ($user_role == 3) {
            $group_acl_code = $this->getGroupFriendAclCode();
        }
        if ($user_role == 2) {
            $group_acl_code = $this->getGroupAdminAclCode();
        }
        //Acl Operation
        $um = $this->container->get('fos_user.user_manager');
        $user_obj = $um->findUserBy(array('id' => $user_id));
        $aclManager = $this->get('problematic.acl_manager');
        $aclManager->setObjectPermission($group, $group_acl_code, $user_obj);
        return true;
    }

    /**
     * Guest can make request to join public group
     * @param Request $request
     * @return array
     */
    public function postJoinprivategroupsAction(Request $request) {
        //initilise the array
        $data = array();
        $group_rejoin = array();
        //get request object
       // $req_obj = $request->get('reqObj');
        //call the deseralizer
        //$de_serialize = $this->decodeData($req_obj);
        //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end

         //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'group_id', 'friend_id', 'access_role');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //get group id
        $group_id = $de_serialize['group_id'];
        //get friend id
        $friend_id = (int) $de_serialize['friend_id'];
        //get access_role
        $access_role = $de_serialize['access_role'];

        //check for role
        $allow_roles = array('2','3');
        if(!in_array($access_role, $allow_roles)){
             $res_data = array('code' => 100, 'message' => 'SOME_ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        //only group owner or group admin or group member can invite the user
        //get User Role
        $mask_id = $this->userGroupRole($group_id, $user_id);
        //check for Access Permission
        $allow_group = array('15', '7', '1');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }
        //end to check permission

        // normal group member can not give a role
        if ($mask_id=='1' and $access_role!='3') {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }

        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        //get search count
        $group_join = $dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->findOneBy(array('user_id'=>(int)$friend_id, 'group_id'=>$group_id));
        if($group_join){
            if($group_join->getIsBlocked()){
                $res_data = array('code' => 119, 'message' => 'MEMBER_OF_GROUP_IS_BLOCKED', 'data' => $data);
                return $res_data;
            }else{
                $res_data = array('code' => 117, 'message' => 'ALREADY_THE_MEMBER_OF_GROUP', 'data' => $data);
                return $res_data;
            }
        }

        //check if already invited
        $group_rejoin = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->findOneBy(array('receiver_id' => $friend_id, 'group_id' => $group_id));
        if (count($group_rejoin) > 0) {
            $res_data = array('code' => 118, 'message' => 'REQUEST_IS_PENDING_FOR_USER_APPROVAL', 'data' => $data);
            return $res_data;
        }
        //join the user in group
        //assign the user in GroupJoinNotification Table
        //get GroupJoinNotification object
        $usertogroup = new GroupJoinNotification();
        $usertogroup->setSenderId($user_id);
        $usertogroup->setReceiverId($friend_id);
        $usertogroup->setGroupId($group_id);
        $usertogroup->setUserRole($access_role);
        $usertogroup->setCreatedAt(time());
        //persist the group object
        $dm->persist($usertogroup);
        //save the group info
        $dm->flush();
        $_notificationId = $usertogroup->getId();
        //send mail
        $from_id = $user_id;
        $to_id = $friend_id;

        //get group name
        $group_detail = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id));

        $title      = $group_detail->getTitle();
        $group_type = $group_detail->getGroupStatus();

        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;

        // get the profile image of club
        $group_medias_profile_img = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupMedia')
                           ->findOneBy(array('group_id' => $group_id,'profile_image'=>1));

        $profile_img_thumb = ""; //initialize the profile thumb.
        if($group_medias_profile_img){
                $album_id = $group_medias_profile_img->getAlbumid();
                if ($album_id) {
                  $profile_img_thumb = $aws_path . $this->group_media_path_thumb . $group_id .'/'.$album_id.'/'.$group_medias_profile_img->getMediaName();
                } else {
                  $profile_img_thumb = $aws_path . $this->group_media_path_thumb . $group_id .'/'.$group_medias_profile_img->getMediaName();
                }
        }

        //for email template
        $email_template_service =  $this->container->get('email_template.service');
        $postService = $this->get('post_detail.service');
        $receiver = $postService->getUserData($to_id, true);
        //get locale
        $locale = !empty($receiver[$to_id]['current_language']) ? $receiver[$to_id]['current_language'] : $this->container->getParameter('locale');
        $lang_array = $this->container->getParameter($locale);

        $angular_app_hostname = $this->container->getParameter('angular_app_hostname'); //angular app host
        $club_profile_url   = $this->container->getParameter('club_profile_url'); //club profile url

        $sender = $postService->getUserData($from_id);
        $sender_name = trim(ucfirst($sender['first_name']).' '.ucfirst($sender['last_name']));

        //$href        = $angular_app_hostname.$club_profile_url.'/'.$group_id.'/'.$group_type;
        $linkAccept = $email_template_service->getClubInvitationUrl(array('action'=>1, 'senderId'=>$from_id, 'clubId'=>$group_id,'clubType'=>$group_type, 'notiId'=>$_notificationId), 'accept', true, $locale);
        $linkReject = $email_template_service->getClubInvitationUrl(array('action'=>2, 'senderId'=>$from_id, 'clubId'=>$group_id,'clubType'=>$group_type, 'notiId'=>$_notificationId), 'reject', true, $locale);
        //$link        = $email_template_service->getLinkForMail($href); //making the link html from service
        $link = $linkAccept. "<br><br>". $linkReject;
        //this is calling when a group owner invite a user for public/private group
        if ($group_type == 2) { //private group
            $mail_sub    = sprintf($lang_array['CLUB_INVITE_REQUEST_SUBJECT']);
            $mail_body   = sprintf($lang_array['CLUB_INVITE_REQUEST_BODY'], ucwords($sender_name), ucwords($title));
        } else { //public group
            $mail_sub    = sprintf($lang_array['CLUB_INVITE_REQUEST_SUBJECT']);
            $mail_body   = sprintf($lang_array['CLUB_INVITE_REQUEST_BODY'], ucwords($sender_name), ucwords($title));
        }
        $mail_text   = sprintf($lang_array['CLUB_INVITE_REQUEST_TEXT'], ucwords($sender_name), ucwords($title));

        $bodyData      = $mail_text."<br><br>".$link;

        // HOTFIX NO NOTIFY MAIL
        //$emailResponse = $email_template_service->sendMail($receiver, $bodyData, $mail_body, $mail_sub, $profile_img_thumb, 'CLUB_JOIN');

        //return success
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($resp_data);
        exit();
    }

    /**
     * Get Group Detail
     */
    public function postGetgroupdetailsAction(Request $request) {
        //initilise the array
        $data = array();
        $resp_data = array();
        $is_sent = 0;
        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;

        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'group_id', 'group_status');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get group status
        $group_status = $de_serialize['group_status'];
        if (!in_array($group_status, array('1','2'))) {
            $resp_data = array('code' => '100', 'message' => 'ERROR_OCCURED', 'data' => array());
            return $resp_data;
        }
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        $cuser_id = (int) $de_serialize['user_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        //get user login id
        $group_id = $de_serialize['group_id'];

        if($group_status == 2){
        //check for ACL, For private group only member can view group.
        //get User Role
        $mask_id = $this->userGroupRole($group_id, $user_id);
        //check for Access Permission
        $allow_group = array('15', '7', '1');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }
        //end to check permission
        }
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

         $is_member = 0;
            //$member = $dm
               // ->getRepository('UserManagerSonataUserBundle:UserToGroup')
               // ->findOneBy(array('group_id'=>$group_id,'user_id'=>$user_id));
           // if(!$member){
               // $is_member = 0;
           // }

        // get the profile image of group
        $group_medias_profile_img = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupMedia')
                           ->findOneBy(array('group_id' => $group_id,'profile_image'=>1));
        $profile_img_original = "";
        $profile_img_thumb = "";
        $profile_img_cover = "";
        $media_id ='';
        $x = '';
        $y = '';
        if($group_medias_profile_img){
                $album_id = $group_medias_profile_img->getAlbumid();
                $media_id  = $group_medias_profile_img->getId() ;
                $x = $group_medias_profile_img->getX();
                $y = $group_medias_profile_img->getY();
                if($album_id){
                $profile_img_original = $aws_path . $this->group_media_path . $group_id .'/'.$album_id.'/'.$group_medias_profile_img->getMediaName();
                $profile_img_thumb = $aws_path . $this->group_media_path_thumb . $group_id .'/'.$album_id.'/'.$group_medias_profile_img->getMediaName();
               // $profile_img_cover = $aws_path . $this->group_cover_media_path_thumb . $group_id .'/'.$album_id.'/'.$group_medias_profile_img->getMediaName();
                $profile_img_cover = $aws_path . $this->group_media_path_thumb . $group_id .'/coverphoto/'.$group_medias_profile_img->getMediaName();
                }else{
                $profile_img_original = $aws_path . $this->group_media_path . $group_id .'/'.$group_medias_profile_img->getMediaName();
                $profile_img_thumb = $aws_path . $this->group_media_path_thumb . $group_id .'/'.$group_medias_profile_img->getMediaName();
               // $profile_img_cover = $aws_path . $this->group_cover_media_path_thumb . $group_id .'/'.$group_medias_profile_img->getMediaName();
                $profile_img_cover = $aws_path . $this->group_media_path_thumb . $group_id . '/coverphoto/'.$group_medias_profile_img->getMediaName();
                }
                // $profile_img_cover = $aws_path . $this->group_media_path_thumb . $group_id . '/coverphoto/'.$group_medias_profile_img->getMediaName();
               // $profile_img_cover = $aws_path . $this->group_cover_media_path_thumb . $group_id .'/'.$group_medias_profile_img->getMediaName();
        }

        //get group members
        $group_members = $dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->findClubMembers($group_id);
        $isBlocked = $dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->isBlockedMember($cuser_id, $group_id);
        $user_info = array();
        foreach ($group_members as $group_member) {
            $_user_id = $group_member->getUserId();
            if($_user_id == $cuser_id){
                $is_member = 1;
            }
            //get User Role
            $umask_id = $this->userGroupRole($group_id, $_user_id);

            $um = $this->container->get('fos_user.user_manager');
            $user_bobj = $um->findUserBy(array('id' => $_user_id));

            //call the service for user object.
            $user_service   = $this->get('user_object.service');
            $user_object    = $user_service->UserObjectService($_user_id);
            $user_object['role'] = $umask_id;
            //$user_obj = $this->getUserAllInfo($_user_id);
            //$user_info[] = array('user_id' => $user_bobj->getId(), 'user_name' => $user_bobj->getUsername(),'role'=>$umask_id);
            $user_info[] = $user_object;
        }

        $is_member = $isBlocked ? 2 : $is_member;

        if($is_member!=1 and $group_status==2){
            $resp_data = array('code' => '1043', 'message' => 'INVALID _GRANT', 'data' => array());
            return $resp_data;
        }

        //get group detail
        $group_detail = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id));

        if(!$group_detail){
            return array('code'=>'120', 'message' =>'NO_GROUP_FOUND' , 'data'=>$data);
        }


        // group media information

        $group_medias = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                         ->findBy(array('group_id' => $group_id));
        $media_name= '';
        $group_media_name = '';
        $group_media_thumb_path='';
        $album_image_type = '';
        $album_image_type_media = '';

        if($group_medias){
            foreach($group_medias as $group_media){

                $media_name =  $group_media->getMediaName();
                $album_image_type_media = $group_media->getImageType();

            }
            $album_image_type = $album_image_type_media;
            $group_media_name = $media_name;

            $group_media_thumb_path    = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$media_name;
        }

        $group_name = $group_detail->getTitle();
        //$group_id = $group_detail->getId();
        $group_desc = $group_detail->getDescription();

        $group_status = $group_detail->getGroupStatus();
        //get group owner id
        $owner_id = $group_detail->getOwnerId();

        //get user role to group
        //get User Role
        $cmask_id = $this->userGroupRole($group_id, $cuser_id);

	$owner_user_service           = $this->get('user_object.service');
        $owner_user_object            = $owner_user_service->UserObjectService($owner_id);

        //check for group invitaion
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $group_notification_check = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->findOneBy(array('sender_id' => $cuser_id, 'group_id' => $group_id));
        $response_pending = 0;
        if (count($group_notification_check) == 0) {
            $group_notification_rcv = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->findOneBy(array('receiver_id' => $cuser_id, 'group_id' => $group_id));
            if(count($group_notification_rcv)>0){
                $response_pending = 1;
            }
            $is_sent = 0;
        } else {
            $is_sent = 1;
        }

        /*Current User Rate*/
        $current_rate = 0;
        $is_rated = false;
        foreach($group_detail->getRate() as $rate)
        {
            if($rate->getUserId() == $user_id)
            {
                $current_rate = $rate->getRate();
                $is_rated = true;
                break;
            }
        }
        /*End Here*/
        //get group info array
        $resp_data = array
        (
            'id' => $group_id,
            'title' => $group_name,
            'description' => $group_desc,
            'media_id'=>$media_id,
            'media_name'=>$group_media_name,
            'media_path'=>$group_media_thumb_path,
            'image_type'=>$album_image_type ,
            'members' => $user_info,
            'is_member'=>$is_member,
            'role'=>$cmask_id,
            'group_status'=>$group_status,
            'owner_id'=>$owner_id,
            'owner_info'=>$owner_user_object,
            'profile_img_original'=>$profile_img_original,
            'profile_img_thumb'=>$profile_img_thumb,
            'is_sent'=>$is_sent,
            'profile_img_cover'=>$profile_img_cover,
            'x_cord'=>$x,
            'y_cord'=>$y,
            'avg_rate'=>round($group_detail->getAvgRating(),1),
            'no_of_votes'=>(int) $group_detail->getVoteCount(),
            'current_user_rate'=>$current_rate,
            'is_rated'=>$is_rated,
            'is_invited'=>$response_pending

        );
        //return data
        $resp_msg = array('code' => '101', 'message' => 'SUCCESS', 'data' => $resp_data);
        echo json_encode($resp_msg);
        exit();
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
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    public function encodeData($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $jsonContent = $serializer->encode($req_obj, 'json');
        return $jsonContent;
    }

    /**
     * Check for enabled user
     * @param string $username
     * @return boolean
     */
    public function checkActiveUserProfile($uid) {
        //get user manager
        $um = $this->container->get('fos_user.user_manager');

        //get user detail
        $user = $um->findUserBy(array('id' => $uid));
        if (!$user) {
            return false;
        }
        $user_check_enable = $user->isEnabled();

        return $user_check_enable;
    }

    /**
     * Get Group Owner ACL code
     * @return int
     */
    public function getGroupOwnerAclCode() {
        $builder = new MaskBuilder();
        $builder
                ->add('view')
                ->add('create')
                ->add('delete')
                ->add('edit');
        return $builder->get();
    }

    /**
     * Get Group Admin ACL code
     * @return int
     */
    public function getGroupAdminAclCode() {
        $builder = new MaskBuilder();
        $builder
                ->add('view')
                ->add('create')
                ->add('edit');
        return $builder->get();
    }

    /**
     * Get Group Admin ACL code
     * @return int
     */
    public function getGroupFriendAclCode() {
        $builder = new MaskBuilder();
        $builder
                ->add('view');
        return $builder->get();
    }

    /**
     * Get User role for group
     * @param int $group_id
     * @param int $user_id
     * @return int
     */
    public function userGroupRole($group_id, $user_id) {
        $mask = 21; //guest: Not group member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $group = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->findOneBy(array('id' => $group_id)); //@TODO Add group owner id in AND clause.
        //if group not found
        if(!$group){
            return $mask;
        }
        $aclProvider = $this->container->get('security.acl.provider');

        $objectIdentity = ObjectIdentity::fromDomainObject($group); //entity

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

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return int
     */
    public function postAssignroletogroupsAction(Request $request) {
        //initilise the array
        $data = array();
        $group = array();
        //get request object
        //$req_obj = $request->get('reqObj');
        //call the deseralizer
        //$de_serialize = $this->decodeData($req_obj);
       //Code repeat start
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'friend_id', 'acl_access', 'group_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //get friend id
        $friend_id = (int) $de_serialize['friend_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        //get acl access
        $acl_access = $de_serialize['acl_access'];
        $allow_access = array('2', '3'); //2 for admin, 3 for friend
        if (!in_array($acl_access, $allow_access)) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }

        //get group id
        $group_id = $de_serialize['group_id'];

        //only group owner or group admin can invite the user
        //get User Role
        $mask_id = $this->userGroupRole($group_id, $user_id);
        //check for Access Permission
        $allow_group = array('15');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }
        //end to check permission

        //check if friend is the member of the group
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $group = $dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->findOneBy(array('group_id' => $group_id, 'user_id' => $friend_id));
        if (!$group) {
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
            return $res_data;
        }elseif($group->getIsBlocked()){
            $res_data = array('code' => 119, 'message' => 'MEMBER_OF_GROUP_IS_BLOCKED', 'data' => $data);
            return $res_data;
        }
        //check if user is the member of the group
        if (count($group) == 0) {
            $res_data = array('code' => 200, 'message' => 'PLEASE_ASSIGN_THE_USER_AS_GROUP_MEMBER', 'data' => $data);
            return $res_data;
        }

        //assign the role
        $this->assignUserGroupRole($group_id, $friend_id, $acl_access);
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit();
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


    /**
     * crop from x, y for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $group_id
     */
    public function cropFromRemote($filename, $media_original_path, $thumb_dir, $group_id, $x, $y, $width_crop, $height_crop) {

        $original_filename = $filename;
        //thumbnail image directory
        $path_to_thumbs_center_directory  = __DIR__."/../../../../../web/uploads/groups/thumb_crop/".$group_id."/";
        //thumbnail image name with path
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory.$filename;

        $filename = $media_original_path.$filename; //original image name with path


        if (preg_match('/[.](jpg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
            $image = imagecreatefromgif($filename);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
            $image = imagecreatefrompng($filename);
        }
        // Get dimensions of the original image
        list($current_width, $current_height) = getimagesize($filename);

        // The x and y coordinates on the original image where we
        // will begin cropping the image
        $width  = imagesx($image);
        $height = imagesy($image);

        //crop image height and width.
        $crop_image_width  = $width_crop;
        $crop_image_height = $height_crop;

        //left/top for crop the image from x,y
        $left = $x;
        $top  = $y;

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width  = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left, $top, $crop_width, $crop_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);//100 is quality
    }

    /**
     * create thumbnail for  a store profile image from crop image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $group_id
     */
    public function createThumbFromCrop($filename, $media_original_path, $thumb_dir, $group_id) {
        $path_to_thumbs_directory = __DIR__."/../../../../../web/uploads/groups/thumb/".$group_id."/";
        //   $path_to_thumbs_directory = $thumb_dir;
        $path_to_image_directory = $media_original_path;
        $final_width_of_image = $this->crop_image_width;
        if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        }
        $ox = imagesx($im);
        $oy = imagesy($im);
        $nx = $final_width_of_image;
        $ny = floor($oy * ($final_width_of_image / $ox)); //always getting the same 200 because source image is square
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresized($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        imagejpeg($nm, $path_to_thumbs_directory . $filename);
    }

    /**
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $group_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $group_id) {
        $path_to_thumbs_directory = __DIR__."/../../../../../web/uploads/groups/thumb/".$group_id."/";
     //   $path_to_thumbs_directory = $thumb_dir;
	$path_to_image_directory  = $media_original_path;
	$final_width_of_image = 200;
        if(preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        }
        $ox = imagesx($im);
        $oy = imagesy($im);
        $nx = $final_width_of_image;
        $ny = floor($oy * ($final_width_of_image / $ox));
        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresized($nm, $im, 0,0,0,0,$nx,$ny,$ox,$oy);
        if(!file_exists($path_to_thumbs_directory)) {
          if(!mkdir($path_to_thumbs_directory, 0777, true)) {
               die("There was a problem. Please try again!");
          }
           }
        imagejpeg($nm, $path_to_thumbs_directory . $filename);
    }

    /**
     * create thumbnail for  a user profile image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $user_id
     */
    public function createProfileImageThumbnail($filename, $media_original_path, $thumb_dir, $group_id) {
        //thumb progile image path
        $path_to_thumbs_directory = __DIR__."/../../../../../web/uploads/groups/thumb_crop/".$group_id."/";
        $path_to_image_directory = $media_original_path;

        $original_img = $path_to_image_directory . $filename;
        //get crop image width and height
        $thumb_width = $this->crop_image_width;
        $thumb_height = $this->crop_image_height;

        if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        }
        $ox = imagesx($im);
        $oy = imagesy($im);

        //getting aspect ratio
        $original_aspect = $ox / $oy;
        $thumb_aspect = $thumb_width / $thumb_height;

        if ($original_aspect >= $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $thumb_height;
            $new_width = $ox / ($oy / $thumb_height);
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
        }

        $nx = $new_width;
        $ny = $new_height;

        $nm = imagecreatetruecolor($nx, $ny);
        imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }
    }

     /**
     * crop from x, y for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $post_id
     */
    public function cropProfileImage($filename, $media_original_path, $thumb_dir, $group_id) {
        $x = 0;
        $y = 0;
        $width_crop = 200;
        $height_crop = 200;
        $original_filename = $filename;

        //thumbnail image name with path
        $path_to_thumbs_center_directory = __DIR__."/../../../../../web/uploads/groups/thumb/".$group_id."/";
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory.$filename;

        $path_to_thumbsmedia_directory = __DIR__."/../../../../../web/uploads/groups/thumb_crop/".$group_id."/";

        $filename = $path_to_thumbsmedia_directory.$filename; //original image name with path

        if (preg_match('/[.](jpg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
            $image = imagecreatefromgif($filename);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
            $image = imagecreatefrompng($filename);
        }
        // Get dimensions of the original image
        list($current_width, $current_height) = getimagesize($filename);

        // The x and y coordinates on the original image where we
        // will begin cropping the image
        $width  = imagesx($image);
        $height = imagesy($image);

        //crop image height and width.
        $crop_image_width  = $width_crop;
        $crop_image_height = $height_crop;

        //left/top for crop the image from x,y
        $left = $x;
        $top  = $y;

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width  = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left, $top, $crop_width, $crop_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        if (preg_match('/[.](jpg)$/', $original_filename)) {
            imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
          imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
           imagegif($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
           imagepng($canvas, $path_to_thumbs_center_image_path, 0);
        }
        //imagejpeg($canvas, $path_to_thumbs_center_image_path, 100);//100 is quality
    }

    /**
     *
     * @param type $filename
     * @param type $media_original_path
     * @param type $thumb_dir
     * @param type $group_id
     */
    public function createClubImageThumbnail($filename, $media_original_path, $thumb_dir, $group_id)
    {

        $path_to_thumbs_directory = __DIR__."/../../../../../web/uploads/groups/thumb_crop/".$group_id."/";
     //   $path_to_thumbs_directory = $thumb_dir;
	$path_to_image_directory  = $media_original_path;
	//get crop image width and height
        $thumb_width = $this->resize_image_width;
        $thumb_height = $this->resize_image_height;

        if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        }
        $ox = imagesx($im);
        $oy = imagesy($im);

        //getting aspect ratio
        $original_aspect = $ox / $oy;
        $thumb_aspect = $thumb_width / $thumb_height;

        if ($original_aspect >= $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $thumb_height;
            $new_width = $ox / ($oy / $thumb_height);
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
        }

        $nx = $new_width;
        $ny = $new_height;

        $nm = imagecreatetruecolor($nx, $ny);
        //code for create png
        $image_upload = $this->get('amazan_upload_object.service');
        $image_upload->createPngImage($nm, $im, $nx, $ny, $ox, $oy);

    //    imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }

    }

    /**
     * crop from x, y for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $group_id
     */
    public function cropClubProfileImage($filename, $media_original_path, $thumb_dir, $group_id) {
        $x = 0;
        $y = 0;
        $width_crop = $this->crop_image_width;
        $height_crop = $this->crop_image_height;
        $original_filename = $filename;

        //thumbnail image name with path
        $path_to_thumbs_center_directory = __DIR__."/../../../../../web/uploads/groups/thumb/".$group_id."/";
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory.$filename;

        $path_to_thumbsmedia_directory = __DIR__ . "/../../../../../web/uploads/groups/thumb_crop/" . $group_id . "/";


        $filename = $path_to_thumbsmedia_directory.$filename; //original image name with path

        if (preg_match('/[.](jpg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
            $image = imagecreatefromgif($filename);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
            $image = imagecreatefrompng($filename);
        }
        // Get dimensions of the original image
        list($current_width, $current_height) = getimagesize($filename);

        // The x and y coordinates on the original image where we
        // will begin cropping the image
        $width  = imagesx($image);
        $height = imagesy($image);

        //crop image height and width.
        $crop_image_width  = $width_crop;
        $crop_image_height = $height_crop;

        //left/top for crop the image from x,y
        $left = $x;
        $top  = $y;

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width  = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        //code for png start
	$background = imagecolorallocate($canvas, 0, 0, 0);
        imagecolortransparent($canvas, $background);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        //code for png end
        imagecopy($canvas, $image, 0, 0, $left, $top, $crop_width, $crop_height);

       // imagecopy($canvas, $image, 0, 0, $left, $top, $crop_width, $crop_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        if (preg_match('/[.](jpg)$/', $original_filename)) {
           imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
          imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
           imagegif($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
           imagepng($canvas, $path_to_thumbs_center_image_path, 0);
        }
        //imagejpeg($canvas, $path_to_thumbs_center_image_path, 100);//100 is quality
        //upload on amazon
        $s3imagepath = "uploads/groups/thumb/".$group_id;
        $image_local_path = $filename;
        $this->s3imageUpload($s3imagepath, $image_local_path, $original_filename);
    }


    /**
     *
     * @param type $user_id
     */
    public function getUserAllInfo($user_id)
    {

        $group_members = array();
       //get group members
        //get entity manager object
       $em = $this->getDoctrine()->getManager();
            $group_members = $em
                ->getRepository('UserManagerSonataUserBundle:UserMultiProfile')
                ->getMultiUserInfo($user_id);
            if($group_members){

                 $profile_img = $this->getUserProfileImage($user_id, 1);
                 $group_members['profile_img'] = $profile_img;
                return $group_members;
            }
            $group_members['profile_img'] = array();
           return $group_members;

            //get profile image

    }

    /**
     * Get  profile image
     * @param type $user_id
     * @param type $profile_type
     * @return string
     */
    public function getUserProfileImage($user_id, $profile_type)
    {
        $type = 1;

        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $userPhoto = new UserPhoto();
        $user_image = $dm
                ->getRepository('UserManagerSonataUserBundle:UserPhoto')
                ->findOneBy(array("user_id" => (int)$user_id, "profile_type"=>(int)$type));

        if(!$user_image){
            return '';
        }
        $image_id = $user_image->getPhotoId();

        //get image from table
        $user_profile_image = $dm
                ->getRepository('MediaMediaBundle:UserMedia')
                ->findOneBy(array("id" => $image_id));
        if(!$user_profile_image){
            return '';
        }

        $media_name = $user_profile_image->getName();
        $album_id =  $user_profile_image->getAlbumid();

        if($album_id == ""){
        $mediaPath   = $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$media_name;
        $thumbDir    = $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$media_name;
        }else{
        $mediaPath   = $this->getS3BaseUri() . $this->user_media_album_path . $user_id . '/'.$album_id.'/'.$media_name;
        $thumbDir    = $this->getS3BaseUri() . $this->user_media_album_path_thumb . $user_id . '/'.$album_id.'/'.$media_name;
        }

        $profile_img = array('original' => $mediaPath, 'thumb' =>$thumbDir);

       return $profile_img;
    }

    /**
     * uploading the club profile image.
     * @param request object
     * @return json
     */
    public function postUploadclubprofileimagesAction(Request $request) {

        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        $device_request_type=$freq_obj['device_request_type'];
        if($device_request_type=='mobile'){  //for mobile if images are uploading.
           $de_serialize= $freq_obj;
        }else{  //this handling for with out image.
            if (isset($fde_serialize)) {
                $de_serialize = $fde_serialize;
            } else {
                $de_serialize = $this->getAppData($request);
            }
        }
        //Code end for getting the request

        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('group_id');

        /* @var $group_id type */
        $group_id = $object_info->group_id;

        $data = array();
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        $file_error = $this->checkFileTypeAction(); //checking the file type extension.
        if ($file_error) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
        }
         $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
         $group = $dm
                    ->getRepository('UserManagerSonataUserBundle:Group')
                    ->findOneBy(array('id' => $group_id));
        if(!$group) {
            return array('code' => 100, 'message' => 'GROUP_DOES_NOT_EXIT', 'data' => $data);
        }

        if (!isset($_FILES['group_media'])) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
        }
        $original_media_name = @$_FILES['group_media']['name'];
        if (empty($original_media_name)) {
            return array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
        }
        $group_id = $object_info->group_id;
        //code for aws s3 server path
        $aws_base_path  = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $aws_path = $aws_base_path.'/'.$aws_bucket;

        if (!empty($original_media_name)) { //if file name is not exists means file is not present.

            //check for image size
            $getfilename = $_FILES['group_media']['tmp_name'];
            list($width, $height) = getimagesize($getfilename);
            $check_resize_width = $this->resize_image_width;
            $check_resize_height = $this->resize_image_height;
            if($width<$check_resize_width or $height<$check_resize_height){
                 return array('code' => 140, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE_WITH_WIDTH_GREATER_THAN_200_AND_HEIGHT_GREATER_THAN_200', 'data' => $data);
            }

            $group_media_name = time() . strtolower(str_replace(' ', '', $_FILES['group_media']['name']));

            //clean image name
            $clean_name = $this->get('clean_name_object.service');
            $group_media_name = $clean_name->cleanString($group_media_name);
            //end image name

            $group_media_type = $_FILES['group_media']['type'];
            $group_media_type = explode('/', $group_media_type);
            $group_media_type = $group_media_type[0];

            $group_media_dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $group_media = $dm
                    ->getRepository('UserManagerSonataUserBundle:GroupMedia')
                    ->findOneBy(array('group_id' => $group_id));
            //get image id
            if(!$group_media){
            $group_media = new GroupMedia();
            }
            $group_media->setGroupId($group_id);
            $group_media->setMediaName($group_media_name);
            $group_media->setMediaType($group_media_type);
            $group_media->setProfileImage(1);
            $group_media->setIsFeatured(0);
            $group_media->setAlbumid('');
            $group_media->setX(''); // save x co-ordinate null
            $group_media->setY(''); // save y co-ordinate null

            $group_media->groupProfileImageUpload($group_id, $group_media_name); //uploading the files
            $group_media_dm->persist($group_media);
            $group_media_dm->flush();
            $media_id = $group_media->getId();

            if ($group_media_type == 'image') {
                    $media_original_path         = __DIR__."/../../../../../web" . $this->group_media_path . $group_id . '/';
                    $thumb_dir                   = __DIR__."/../../../../../web" . $this->group_media_path_thumb . $group_id . '/';
                    $cover_img_path = $aws_path . $this->group_media_path_thumb . $group_id . '/coverphoto/'.$group_media_name;
                   // $cover_img_path_thumb = $aws_path . $this->group_cover_media_path_thumb . $group_id . '/'.$group_media_name;
                    $cover_img_path_thumb = $aws_path . $this->group_media_path_thumb . $group_id . '/'.$group_media_name;
                    $cover_original_img_path = $aws_path .$this->group_media_path . $group_id . '/'.$group_media_name;
                    $resizeOriginalDir = __DIR__."/../../../../../web" . $this->group_media_path . $group_id . '/';
                    //upload on amazon

                    //rotate the image if orientaion is not actual.
                    if (preg_match('/[.](jpg)$/', $group_media_name) || preg_match('/[.](jpeg)$/', $group_media_name)) {
                    $image_rotate_service = $this->get('image_rotate_object.service');
                    $image_rotate = $image_rotate_service->ImageRotateService($media_original_path . $group_media_name);
                    }
                    //end of image rotate

                    $this->resizeOriginal($group_media_name, $media_original_path, $resizeOriginalDir, $group_id);
                    $this->createClubImageThumbnail($group_media_name, $media_original_path, $thumb_dir, $group_id);
                    $this->cropClubProfileImage($group_media_name, $media_original_path, $thumb_dir, $group_id);

                    //set cover image
                    //$this->resizeOriginal($group_media_name, $media_original_path, $resizeOriginalDir, $group_id);
                    $this->createClubCoverImageThumbnail($group_media_name, $media_original_path, $thumb_dir, $group_id);
                   // $this->cropClubCoverProfileImage($group_media_name, $media_original_path, $thumb_dir, $group_id);
            }
          //  $data = array('cover_image_path' => $cover_img_path, 'profile_img_original' =>$cover_original_img_path);
            $data = array('cover_thumb_image_path' => $cover_img_path, 'profile_img_original' =>$cover_original_img_path,'media_id'=>$media_id);
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($res_data);
            exit();
        } else {
            $res_data = array('code' => 100, 'message' => 'YOU_MUST_CHOOSE_A_IMAGE', 'data' => $data);
            echo json_encode($res_data);
            exit();
        }
    }

    /**
     * Uplaod on s3 server
     */
    public function s3imageUpload($s3imagepath, $image_local_path, $filename)
    {
        $amazan_service = $this->get('amazan_upload_object.service');
        $image_url = $amazan_service->ImageS3UploadService($s3imagepath, $image_local_path, $filename);
        return $image_url;
    }
    /**
     * Create club cover image
     * @param type $filename
     * @param type $media_original_path
     * @param type $thumb_dir
     * @param type $group_id
     */

    public function createClubCoverImageThumbnail($filename, $media_original_path, $thumb_dir, $group_id)
    {
        $path_to_thumbs_directory = __DIR__."/../../../../../web/uploads/groups/thumb/".$group_id. '/coverphoto/';
	$path_to_image_directory  = $media_original_path;
	//get crop image width and height
        $thumb_width = $this->resize_cover_image_width;
        $thumb_height = $this->resize_cover_image_height;

        if (preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        }
        $ox = imagesx($im);
        $oy = imagesy($im);

        //getting aspect ratio
        $original_aspect = $ox / $oy;
        $thumb_aspect = $thumb_width / $thumb_height;

        if ($original_aspect >= $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $thumb_height;
            $new_width = $ox / ($oy / $thumb_height);
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
        }

        $nx = $new_width;
        $ny = $new_height;

        $nm = imagecreatetruecolor($nx, $ny);
        //code for create png
        $image_upload = $this->get('amazan_upload_object.service');
        $image_upload->createPngImage($nm, $im, $nx, $ny, $ox, $oy);

    //    imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename);
        }

        //upload on amazon
       $s3imagepath = "uploads/groups/thumb/" . $group_id. '/coverphoto';
       $image_local_path = $path_to_thumbs_directory.$filename;
       $url = $this->s3imageUpload($s3imagepath, $image_local_path, $filename);

    }

    /**
     * crop from x, y for a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $group_id
     */
    public function cropClubCoverProfileImage($filename, $media_original_path, $thumb_dir, $group_id) {
        $x = 0;
        $y = 0;
        $width_crop = $this->resize_cover_image_width;
        $height_crop = $this->resize_cover_image_height;
        $original_filename = $filename;

        //thumbnail image name with path
        $path_to_thumbs_center_directory = __DIR__."/../../../../../web/uploads/groups/thumb/".$group_id."/coverphoto/";
        $path_to_thumbs_center_image_path = $path_to_thumbs_center_directory.$filename;

        $path_to_thumbsmedia_directory = __DIR__ . "/../../../../../web/uploads/groups/thumb_cover_crop/" . $group_id . "/";


        $filename = $path_to_thumbsmedia_directory.$filename; //original image name with path

        if (preg_match('/[.](jpg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
            $image = imagecreatefromjpeg($filename);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
            $image = imagecreatefromgif($filename);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
            $image = imagecreatefrompng($filename);
        }
        // Get dimensions of the original image
        list($current_width, $current_height) = getimagesize($filename);

        // The x and y coordinates on the original image where we
        // will begin cropping the image
        $width  = imagesx($image);
        $height = imagesy($image);

        //crop image height and width.
        $crop_image_width  = $width_crop;
        $crop_image_height = $height_crop;

         //left/top for crop the image from x,y

        $left = $width / 2;
        $left1 = $left - ($crop_image_width / 2);
        $top = $height / 2;
        $top1 = $top - ($crop_image_height / 2);

        //get thumb image width and height according to the image thumb size
        //This will be the final size of the image (e.g. how many pixels left and down we will be going)
        $crop_width  = $crop_image_width;
        $crop_height = $crop_image_height;

        // Resample the image
        $canvas = imagecreatetruecolor($crop_width, $crop_height);
        imagecopy($canvas, $image, 0, 0, $left1, $top1, $crop_width, $crop_height);
        //create the directory of post if not exists
        if (!file_exists($path_to_thumbs_center_directory)) {
            if (!mkdir($path_to_thumbs_center_directory, 0777, true)) {
                die("There was a problem. Please try again!");
            }
        }
        if (preg_match('/[.](jpg)$/', $original_filename)) {
           imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](jpeg)$/', $original_filename)) {
          imagejpeg($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](gif)$/', $original_filename)) {
           imagegif($canvas, $path_to_thumbs_center_image_path, 75);
        } else if (preg_match('/[.](png)$/', $original_filename)) {
           imagepng($canvas, $path_to_thumbs_center_image_path, 0);
        }
        //imagejpeg($canvas, $path_to_thumbs_center_image_path, 100);//100 is quality
        //upload on amazon
        $s3imagepath = "uploads/groups/thumb/".$group_id."/coverphoto";
        $image_local_path = __DIR__."/../../../../../web/uploads/groups/thumb/".$group_id."/coverphoto/".$original_filename;
        $this->s3imageUpload($s3imagepath, $image_local_path, $original_filename);
    }

    /**
     * unjoin a club
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return int
     */
    public function postUnjoinclubsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        $freq_obj      = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'club_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //get club id
        $club_id = $de_serialize['club_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 85, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        //mongodb doctrine object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $group_info = $dm->getRepository('UserManagerSonataUserBundle:Group')
                          ->findOneBy(array('id'=>$club_id));
        if (!$group_info) {
            $res_data = array('code' => 88, 'message' => 'CLUB_IS_NOT_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        //getting the owner id of club.
        $club_owner_id = $group_info->getOwnerId();

        //check a user is owner he can't unjoin the club
        if ($club_owner_id == $user_id) {
            $res_data = array('code' => 87, 'message' => 'CLUB_CAN_NOT_UNJOIN_YOU_ARE_OWNER', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        //finding the usertoclub object
        $user_group = $dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                            ->findOneBy(array('group_id' => $club_id, 'user_id'=>$user_id));

        if (!$user_group) {
            $res_data = array('code' => 86, 'message' => 'YOU_ARE_NOT_MEMEBER_OF_CLUB', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }elseif($user_group->getIsBlocked()){
            $res_data = array('code' => 119, 'message' => 'MEMBER_OF_GROUP_IS_BLOCKED', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        //remove the object
        $dm->remove($user_group);
        $dm->flush();
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit;
    }

    /**
     * resize original for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $album_id
     */
    public function resizeOriginal($filename, $media_original_path, $org_resize_dir, $group_id) {
        $path_to_thumbs_directory = $org_resize_dir;
        $path_to_image_directory = $media_original_path;
        //$final_width_of_image = 200;
        //get image thumb width
        $thumb_width = $this->original_resize_image_width;
        $thumb_height = $this->original_resize_image_height;

    /*
     * if(preg_match('/[.](jpg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            $im = imagecreatefromgif($path_to_image_directory . $filename);
        } else if (preg_match('/[.](png)$/', $filename)) {
            $im = imagecreatefrompng($path_to_image_directory . $filename);
        }
    */
        $image_data = file_get_contents($path_to_image_directory . $filename);
        $im = imagecreatefromstring($image_data);
        $ox = imagesx($im);
        $oy = imagesy($im);

        //check if image size is less than defined limit size
       // if($ox > $thumb_width || $oy > $thumb_height){
        if($ox > $thumb_width && $oy > $thumb_height){
        //getting aspect ratio
        $original_aspect = $ox / $oy;
        $thumb_aspect = $thumb_width / $thumb_height;

        if ($original_aspect >= $thumb_aspect) {
            // If image is wider than thumbnail (in aspect ratio sense)
            $new_height = $thumb_height;
            $new_width = $ox / ($oy / $thumb_height);
             //check if new width is less than minimum width
             if($new_width > $thumb_width){
                       $new_width = $thumb_width;
                       $new_height = $oy / ($ox / $thumb_width);
               }
        } else {
            // If the thumbnail is wider than the image
            $new_width = $thumb_width;
            $new_height = $oy / ($ox / $thumb_width);
             //check if new height is less than minimum height
            if($new_height > $thumb_height){
                       $new_height = $thumb_height;
                       $new_width = $ox / ($oy / $thumb_height);
               }
        }
        $nx = $new_width;
        $ny = $new_height;
        }else{
        //set original image size
        $nx = $ox;
        $ny = $oy;
        }
        $nm = imagecreatetruecolor($nx, $ny);
        //code for create png
        $image_upload = $this->get('amazan_upload_object.service');
        $image_upload->createPngImage($nm, $im, $nx, $ny, $ox, $oy);

        //imagecopyresampled($nm, $im, 0, 0, 0, 0, $nx, $ny, $ox, $oy);
        if (!file_exists($path_to_thumbs_directory)) {
            if (!mkdir($path_to_thumbs_directory, 0777, true)) {
                die("THERE_WAS_A_PROBLEM_PLEASE_TRY_AGAIN");
            }
        }
        //imagejpeg($nm, $path_to_thumbs_directory . $filename);
         if (preg_match('/[.](jpg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](jpeg)$/', $filename)) {
           imagejpeg($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](gif)$/', $filename)) {
            imagegif($nm, $path_to_thumbs_directory . $filename,75);
        } else if (preg_match('/[.](png)$/', $filename)) {
            imagepng($nm, $path_to_thumbs_directory . $filename,9);
        }

        $s3imagepath = "uploads/groups/original/".$group_id;
        //$image_local_path = __DIR__."/../../../../../web" . $this->group_media_path . $group_id . '/' .$filename;

       $image_local_path = $path_to_thumbs_directory.$filename;
       //upload on amazon
       $this->s3imageUpload($s3imagepath, $image_local_path, $filename);

    }

    /**
     * Get the club list those that are invited by me and those that are invited to me
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function postGetinviteclubsAction(Request $request)
    {
        //initilise the array
        $data = array();
        $group_ids_array = array();
        $sender_array = array();
        $receiver_array = array();
        $group_notfiy_array_limit = array();
        $unique_users = array();
        //get request object

        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }

        //end check parameter
        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
        //limit start
        $limit_start = (($de_serialize['limit_start'] == "") ? 0 : $de_serialize['limit_start']);
        //limit length
        $limit_length = (($de_serialize['limit_size'] == "") ? 20 : $de_serialize['limit_size']);
         } else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }

        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        $cuser_id = (int) $de_serialize['user_id'];

        //@TODOcheck for active member
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $group_notification_id = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->getAllGroupRequests($user_id);

        if (count($group_notification_id) == 0) {
            //no notification found
            //return success
            $res_data = array('code' => 119, 'message' => 'NO_NOTIFICATION', 'data' => $data);
            return $res_data;
        }

        //get receiver and sender array
        foreach($group_notification_id as $group_notification_single){
             //check if group has deleted
             $group_object_ida = $group_notification_single['group_id'];
             $check_active_group = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->checkActiveGroup($group_object_ida);

            if($check_active_group){
            $group_notfiy_array[] = array('group_id'=>$group_notification_single['group_id'],
                'sender_id' => $group_notification_single['sender_id'] ,
                'receiver_id'=>$group_notification_single['receiver_id'],
                'request_id'=>$group_notification_single['request_id']
                    );
            $sender_array[] = $group_notification_single['sender_id'];
            $receiver_array[] = $group_notification_single['receiver_id'];
            $group_ids_array[] = $group_notification_single['group_id'];
        }
        }

        //get group detail
         $notification_group_details = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->getNotificationGroupDetail($group_ids_array);

         if($notification_group_details ){
             foreach($notification_group_details as $notification_group_detail){
             $group_id = $notification_group_detail->getId();
             $group_details_object[$group_id] = $notification_group_detail;
             }
         }

        $total_user = array_merge($sender_array,$receiver_array);
        $unique_users = array_unique($total_user);
        //call the serviec for user object.
        $user_service   = $this->get('user_object.service');
        $user_objects    = $user_service->MultipleUserObjectService($unique_users);

//        //if found then get the notification details
//        $group_notification_id_detail = $dm
//                ->getRepository('UserManagerSonataUserBundle:Group')
//                ->getAllGroupJoinNotificationsDetail($group_notification_id);
//
        //get group member count
//         $group_members_count = $dm
//                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
//                ->getGroupMemberCount($group_ids_array);
//
//        if($group_members_count){
//            $count = 0;
//            foreach($group_members_count as $group_members_single_count){
//                $gid = $group_members_single_count->getGroupId();
//                $count++;
//                $grp_member_count[$gid] = $count;
//            }
//            print_r($grp_member_count);
//            die;
//        }

         if($notification_group_details ){
             foreach($notification_group_details as $notification_group_detail){
             $group_id = $notification_group_detail->getId();
             $group_details_object[$group_id] = $notification_group_detail;
             }
         }
        $total_notifictions = count($group_notfiy_array);
        //prepare limit array
        $group_notfiy_array_limit = array_splice($group_notfiy_array, $limit_start, $limit_length);

        //get total record found

        foreach($group_notfiy_array_limit as $group_notifications){
              $group_object_id = $group_notifications['group_id'];
              $member_count = 0;
              $group = $group_details_object[$group_object_id]; //

              $sender_id      = $group_notifications['sender_id'];
              $receiver_id      = $group_notifications['receiver_id'];
              $sender_info    = $user_objects[$sender_id];
              $receiver_info    = $user_objects[$receiver_id];
              $request_id = $group_notifications['request_id'];
              $group_id = $group->getId();


          // get media information related to this group .i.e
            // get information from group media table where group_id = $group_id
            $group_medias = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupMedia')
                        ->findBy(array('group_id' => $group_id));

            // get the profile image of group
            $group_medias_profile_img = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupMedia')
                               ->findOneBy(array('group_id' => $group_id,'profile_image'=>1));
            $profile_img_original = "";
            $profile_img_thumb = "";
            if($group_medias_profile_img){
                    $profile_img_original = $this->getS3BaseUri() . $this->group_media_path . $group_id . '/'.$group_medias_profile_img->getMediaName();
                    $profile_img_thumb = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$group_medias_profile_img->getMediaName();
            }

            $media_data= array();
//            foreach($group_medias as $group_media)
//            {
//              $media_id    = $group_media->getId();
//              $media_name  = $group_media->getMediaName();
//              $media_type  = $group_media->getMediaType();
//              $media_group_id    = $group->getId();
//
//              $thumb_dir    = $this->getS3BaseUri() . $this->group_media_path_thumb . $media_group_id . '/'.$media_name;
//
//              $media_data = array('id'=>$media_id,
//                                     'media_name'=>$media_name,
//                                     'media_type'=>$media_type,
//                                     'media_path'=>$thumb_dir,
//                                    );
//            }

            $group_title = $group->getTitle();
            $group_description = $group->getDescription();
            $group_creation_date = $group->getCreatedAt();
            $group_owner_id = $group->getOwnerId();
            $group_status = $group->getGroupStatus();

            //get group members
            $member_count = $dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->findClubMembersCount($group_id);

            $current_rate = 0;
            $is_rated = false;
            foreach($group->getRate() as $rate)
            {
                if($rate->getUserId() == $group_owner_id )
                {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
            $data[] = array('group_id' => $group_id,
                'owner_id' => $group_owner_id,
                'owner_info' => $user_objects[$group_owner_id],
                'sender_info' => $sender_info,
                'receiver_info' => $receiver_info,
                'group_title' => $group_title,
                'group_description' => $group_description,
                'created_at' => $group_creation_date,
                'group_status' => $group_status,
                'member_count' => $member_count,
                //'media_info'=>$media_data,
                'profile_img_original'=>$profile_img_original,
                'profile_img_thumb'=>$profile_img_thumb,
                'request_id' =>$request_id,
                'avg_rate'=>round($group->getAvgRating(),1),
                'no_of_votes'=>(int) $group->getVoteCount(),
                'current_user_rate'=>$current_rate,
                'is_rated'=>$is_rated
            );
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data, 'size' => $total_notifictions
                );
        echo json_encode($res_data);
        exit;
    }

    /**
     * Remove club member by club admin
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return int
     */
    public function postRemoveclubmembersAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object
        $freq_obj      = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('owner_id','member_id', 'club_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get member id
        $member_id = $de_serialize['member_id'];

        //get owner id
        $owner_id = (int)$de_serialize['owner_id'];
        //get club id
        $club_id = $de_serialize['club_id'];

        $blockMember = isset($de_serialize['is_blocked']) ? (int)$de_serialize['is_blocked'] : '';

        //check if owner is active or not
        $user_check_enable = $this->checkActiveUserProfile($owner_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 85, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        //check if owner is the club owner
        //mongodb doctrine object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $group_info = $dm->getRepository('UserManagerSonataUserBundle:Group')
                          ->findOneBy(array('id'=>$club_id));
        if (!$group_info) {
            $res_data = array('code' => 79, 'message' => 'YOU_ARE_NOT_OWNER_OF_CLUB', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        //only group owner or group admin can delete the user
        //get User Role
        $mask_id = $this->userGroupRole($club_id, $owner_id);
        //check for Access Permission
        $allow_group = array('15', '7');

        if (!in_array($mask_id, $allow_group)) {
            $resp_data = array('code' => '500', 'message' => 'PERMISSION_DENIED', 'data' => array());
            return $resp_data;
        }

        //getting the owner id of club.
        $club_owner_id = $group_info->getOwnerId();

        //check a user is owner he can't unjoin the club
        if ($club_owner_id == $member_id) {
            $res_data = array('code' => 80, 'message' => 'CLUB_CAN_NOT_UNJOIN_YOU_ARE_OWNER', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        $member_id = (int)$member_id;
        //finding the usertoclub object
        $user_group = $dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                            ->findOneBy(array('group_id' => $club_id, 'user_id'=>$member_id));

        if (!$user_group) {
            $res_data = array('code' => 78, 'message' => 'USER_IS_NOT_MEMEBER_OF_CLUB', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }elseif ($user_group->getIsBlocked()) {
            $res_data = array('code' => 119, 'message' => 'MEMBER_OF_GROUP_IS_BLOCKED', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }

        if($blockMember===1){
            $user_group->setIsBlocked(1);
            $dm->persist($user_group);
        }else{
            //remove the object
            $dm->remove($user_group);
        }
        $dm->flush();

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($res_data);
        exit;
    }

    /**
     * List user's group
     * @param Request $request
     * @return array;
     */
    public function postGetusergroupsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object

        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get group owner id
        //$group_owner_id = (int) $de_serialize['group_owner_id'];
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
            //get limit size
            $limit_size = (int) $de_serialize['limit_size'];
            if ($limit_size == "") {
                $limit_size = 20;
            }
            //get limit offset
            $limit_start = (int) $de_serialize['limit_start'];
            if ($limit_start == "") {
                $limit_start = 0;
            }
        } else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }


        if ($user_id == "") {
            $res_data = array('code' => 115, 'message' => 'GROUP_OWNER_ID_REQUIRED', 'data' => array());
            return $res_data;
        }
        $user_service_owner    = $this->get('user_object.service');
        $em = $this->getDoctrine()->getManager();
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $groups = $dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->getMemberGroupList($user_id);
        if (!$groups) {
            $res_data = array('code' => 100, 'message' => 'NO_GROUP_FOUND', 'data' => $data);
            return $res_data;
        }

        //getting the group ids.
        $group_ids = array_map(function($groups) {
            return "{$groups['id']}";
        }, $groups);

        //getting the group info...
        $groups_info = $dm->getRepository('UserManagerSonataUserBundle:Group')
                         ->getGroupInfo($group_ids);

        //get group owner user ids..
        $group_owner_user_ids =  array_map(function($groups_user_info) {
            return $groups_user_info->getOwnerId();
            }, $groups_info);
        $users = array();
        foreach ($group_owner_user_ids as $user) {
            $users[] = $user;
        }
        $group_user_owners = array_unique($users);
        //getting the user object service.
        $user_service   = $this->get('user_object.service');
        //$group_owner_user_object    = $user_service->MultipleUserObjectService($group_user_owners);

        //finding the group profile images..
        $group_profile_medias = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                                   ->getGroupProfileMediasInfo($group_ids);
        //finding the group media including the profile image.. no need to find it because front end is using just group profile image thumb.
       // $group_media_info = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
        //                       ->getGroupMediaInfo($group_ids);

        //finding the users of groups.
        $group_members_user = $dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                                 ->findGroupMemberUser($group_ids);
        $_users = array();
        $groupMemberIds = array();
        $group_members_user = is_array($group_members_user) ? $group_members_user : array();
        if(!empty($group_members_user)){
            foreach ($group_members_user as $g=>$g_members) {
                foreach($g_members as $g_m){
                     $_u = $g_m->getUserId();
                     $_users[] = $_u;
                    $groupMemberIds[$g][] = $_u;
                }
            }
        }

        $_users = array_merge($_users, $group_user_owners, array($user_id));
        $_users = array_unique($_users);
        $usersDataObject = $em->getRepository('UserManagerSonataUserBundle:User')
                ->findBy(array('id' => $_users));
        $usersDataArray = $user_service->getUserObjectToArray($usersDataObject, true);
        $userObjectWithId = $user_service->getPreparedUserObjectWithId($usersDataObject);
        $user_info = array();
        //get data
        foreach ($groups as $group) {
            $group_id = $group['id'];

            //get group detail from array...
            $group_info = $groups_info[$group_id];

            //check for delete status
            $is_delete = $group_info->getIsDelete();
            if($is_delete == 1){
                continue;
            }
            // get media information related to this group .i.e, commented because front end team just using only group profile image thumb., no media is using.
            //$group_medias = isset($group_media_info[$group_id]) ? $group_media_info[$group_id] : array();

            // get the profile image of group
            $group_medias_profile_img = isset($group_profile_medias[$group_id]) ? $group_profile_medias[$group_id] : '';

            $profile_img_original = $profile_img_thumb = ''; //initialize the variables.
            if($group_medias_profile_img){
                $profile_img_original = $this->getS3BaseUri() . $this->group_media_path . $group_id . '/'.$group_medias_profile_img->getMediaName();
                $profile_img_thumb = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$group_medias_profile_img->getMediaName();
            }

            //get group members
            $group_members = isset($group_members_user[$group_id]) ? $group_members_user[$group_id] : array();
            unset($user_info);
            $_gMembers = isset($groupMemberIds[$group_id]) ? $groupMemberIds[$group_id] : array();
            $memberPermissions = $this->getUsersGroupRole($group_info, $_gMembers, $userObjectWithId);
            $user_info = array();
            foreach ($group_members as $group_member) {
                $user_id = $group_member->getUserId();
                //get User Role
                //$umask_id = $this->userGroupRole($group_id, $user_id);
                $umask_id = isset($memberPermissions[$user_id]) ? $memberPermissions[$user_id] : 21; // default guest
                //$um = $this->container->get('fos_user.user_manager');
                //$user_bobj = $um->findUserBy(array('id' => $user_id));
                //$user_obj = $this->getUserAllInfo($user_id);
                //code start for getting the user object..
                //$user_id        = $user_bobj->getId();
                //$user_service   = $this->get('user_object.service');
                //$user_object    = $user_service->UserObjectService($user_id);
                $user_object = isset($usersDataArray[$user_id]) ? $usersDataArray[$user_id] : array();
                $user_object['role'] = $umask_id;
                //code end for getting the user object..
               // $user_info[] = array('user_id' => $user_bobj->getId(), 'user_name' => $user_bobj->getUsername(),'role'=>$umask_id);
                $user_info[] = $user_object;
            }

            /* $media_data= array(); //commented because front end team is not using they are only using group profile thumb.
             foreach($group_medias as $group_media)
             {
               $media_id    = $group_media->getId();
               $media_name  = $group_media->getMediaName();
               $media_type  = $group_media->getMediaType();
               $group_id    = $group_info->getId();

               $thumb_dir    = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$media_name;

               $media_data[] = array('id'=>$media_id,
                                      'media_name'=>$media_name,
                                      'media_type'=>$media_type,
                                      'media_path'=>$thumb_dir,
                                     );
             } */
            $group_title = $group_info->getTitle();
            $group_description = $group_info->getDescription();
            $group_creation_date = $group_info->getCreatedAt();
            $group_owner_id = $group_info->getOwnerId();
            //call the service for user object.

            $owner_object  = isset($usersDataArray[$group_owner_id]) ? $usersDataArray[$group_owner_id] : array();
            $current_rate = 0;
            $is_rated = false;
            foreach($group_info->getRate() as $rate)
            {
                if($rate->getUserId() == $group_owner_id )
                {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
            $data[] = array('group_id' => $group_id,
                'owner_id' => $group_owner_id,
                'owner_info' => $owner_object,
                'group_title' => $group_title,
                'group_description' => $group_description,
                'created_at' => $group_creation_date,
                'group_status' => $group_info->getGroupStatus(),
                'members' => $user_info,
               // 'media_info'=>$media_data,
                'profile_img_original'=>$profile_img_original,
                'profile_img_thumb'=>$profile_img_thumb,
                'avg_rate'=>round($group_info->getAvgRating(),1),
                'no_of_votes'=>(int) $group_info->getVoteCount(),
                'current_user_rate'=>$current_rate,
                'is_rated'=>$is_rated
            );
        }


        //reverse the array
        $data = array_reverse($data);
        $group_size = count($data);
        $group_output = array_slice($data, $limit_start, $limit_size);
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array(
            'groups' => $group_output,
            'size' => $group_size
                ));
        echo json_encode($resp_data);
        exit();
    }

    /**
     * Search User
     * @param Request $request
     * @return array
     */
    public function postSearchgroupAction(Request $request) {
        //initilise the array
        $data = array();
        $user_info = array();
        $is_sent = 0;
        //get request object

        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        $cuser_id = (int) $de_serialize['user_id'];

        //search text
        $group_name = "";
        if (isset($de_serialize['group_name'])){
        $group_name = $de_serialize['group_name'];
        }

        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
        //limit start
        $limit_start = (($de_serialize['limit_start'] == "") ? 0 : $de_serialize['limit_start']);
        //limit length
        $limit_length = (($de_serialize['limit_size'] == "") ? 20 : $de_serialize['limit_size']);
         } else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);
        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }
        $em = $this->getDoctrine()->getManager();
        // get document manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        //find the current user group(public/private)
        $group_list = $dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                         ->getGroupList($user_id);

        //find all the public group and users groups.
        $group_search_results = $dm->getRepository('UserManagerSonataUserBundle:Group')
                                   ->getGroupSearchList($group_list, $group_name, $limit_start, $limit_length);

        //getting the group ids.
        $group_ids = array_map(function($groups) {
            return "{$groups->getId()}";
        }, $group_search_results);

        //get group owner user ids..
        $group_owner_user_ids =  array_map(function($group_search_results) {
            return $group_search_results->getOwnerId();
            }, $group_search_results);

        $users = array();
        foreach ($group_owner_user_ids as $user) {
            $users[] = $user;
        }
        $group_user_owners = array_unique($users); //make group owner array unique.

        //getting the user object service.
        $user_service   = $this->get('user_object.service');
        //$group_owner_user_object    = $user_service->MultipleUserObjectService($group_user_owners);

        //finding the group profile images..
        $group_profile_medias = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                                   ->getGroupProfileMediasInfo($group_ids);
        //finding the members of groups.
        $group_members_user = $dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                                 ->findGroupMemberUser($group_ids);
        //finding the blocked members of groups.
        $blocked_group_members = $dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                                 ->getBlockedMembers($group_ids);

        //get group notification sent information
        $group_notification_sent = $dm->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                                      ->getGroupNotification($cuser_id, $group_ids);
        $_users = array();
        $groupMemberIds = array();
        $group_members_user = is_array($group_members_user) ? $group_members_user : array();
        if(!empty($group_members_user)){
            foreach ($group_members_user as $g=>$g_members) {
                foreach($g_members as $g_m){
                     $_u = $g_m->getUserId();
                     $_users[] = $_u;
                    $groupMemberIds[$g][] = $_u;
                }
            }
        }

        $_users = array_merge($_users, $group_user_owners, array($user_id));
        $_users = array_unique($_users);
        $usersDataObject = $em->getRepository('UserManagerSonataUserBundle:User')
                ->findBy(array('id' => $_users));
        $usersDataArray = $user_service->getUserObjectToArray($usersDataObject, true);
        $userObjectWithId = $user_service->getPreparedUserObjectWithId($usersDataObject);
        //prepare data
        foreach ($group_search_results as $group) {
            $group_id = $group->getId();

          // get media information related to this group .i.e
            // get information from group media table where group_id = $group_id, this information is not used on front end.
//            $group_medias = $this->get('doctrine_mongodb')->getRepository('UserManagerSonataUserBundle:GroupMedia')
//                        ->findBy(array('group_id' => $group_id));

            // get the profile image of group
            $group_medias_profile_img = isset($group_profile_medias[$group_id]) ? $group_profile_medias[$group_id] : '';
            $profile_img_original = $profile_img_thumb = '';
            if($group_medias_profile_img){
                    $profile_img_original = $this->getS3BaseUri() . $this->group_media_path . $group_id . '/'.$group_medias_profile_img->getMediaName();
                    $profile_img_thumb    = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$group_medias_profile_img->getMediaName();
            }

           /* $media_data= array();
            foreach($group_medias as $group_media)
            {
              $media_id    = $group_media->getId();
              $media_name  = $group_media->getMediaName();
              $media_type  = $group_media->getMediaType();
              $group_id    = $group->getId();

              $thumb_dir    = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$media_name;

              $media_data = array('id'=>$media_id,
                                     'media_name'=>$media_name,
                                     'media_type'=>$media_type,
                                     'media_path'=>$thumb_dir,
                                    );
            } */

            $group_title = $group->getTitle();
            $group_description = $group->getDescription();
            $group_creation_date = $group->getCreatedAt();
            $group_owner_id = $group->getOwnerId();
            $group_status = $group->getGroupStatus();
            $is_member = 0;
            //$member = $dm
               // ->getRepository('UserManagerSonataUserBundle:UserToGroup')
               // ->findOneBy(array('group_id'=>$group_id,'user_id'=>$user_id));
            //if(!$member){
              //  $is_member = 0;
           // }

            $owner_object          = isset($usersDataArray[$group_owner_id]) ? $usersDataArray[$group_owner_id] : array();

            //get group members
            $group_members = isset($group_members_user[$group_id]) ? $group_members_user[$group_id] : array(); //check if any user is member of this group.
            unset($user_info);
            $user_info = array();
            $_gMembers = isset($groupMemberIds[$group_id]) ? $groupMemberIds[$group_id] : array();
            $memberPermissions = $this->getUsersGroupRole($group, $_gMembers, $userObjectWithId);
            foreach ($group_members as $group_member) {
                $user_id = $group_member->getUserId();
                if ($user_id == $cuser_id) {
                   $is_member = 1;
                }
                //get user role
                //get User Role
                //commented because it was running single query for each memebr
                //$umask_id = $this->userGroupRole($group_id, $user_id);
                $umask_id = isset($memberPermissions[$user_id]) ? $memberPermissions[$user_id] : 21; // default guest

//                $um = $this->container->get('fos_user.user_manager');
//                $user_bobj = $um->findUserBy(array('id' => $user_id));
//
//                $user_id        = $user_bobj->getId();
//                $user_service   = $this->get('user_object.service');
//                $user_object    = $user_service->UserObjectService($user_id);
                $user_object = isset($usersDataArray[$user_id]) ? $usersDataArray[$user_id] : array();
                $user_object['role'] = $umask_id;

                //$user_obj = $this->getUserAllInfo($user_id);
                //$user_info[] = array('user_id' => $user_bobj->getId(), 'user_name' => $user_bobj->getUsername(),'role'=>$umask_id);
                $user_info[] = $user_object;
            }

            $is_member = (isset($blocked_group_members[$group_id]) and in_array($cuser_id, $blocked_group_members[$group_id])) ? 2 : $is_member;
             //check for group invitaion
            if ($is_member == 0) {
                //this is the sent notification sent.
                if (isset($group_notification_sent[$group_id])) { //if current user sent the request to join the group
                    $is_sent = 1;
                } else {
                    $is_sent = 0;
                }
            }
            /*Current Rate*/
            $current_rate = 0;
            $is_rated = false;
            foreach($group->getRate() as $rate)
            {
                if($rate->getUserId() == $group_owner_id)
                {
                    $current_rate = $rate->getRate();
                    $is_rated = true;
                    break;
                }
            }
            /*End here current rate*/
            $data[] = array('group_id' => $group_id,
                'owner_id' => $group_owner_id,
                'owner_info' => $owner_object,
                'group_title' => $group_title,
                'group_description' => $group_description,
                'created_at' => $group_creation_date,
                'group_status' => $group_status,
                'is_member' => $is_member,
                'is_sent' => $is_sent,
                'members' => $user_info,
                //'media_info'=>$media_data,
                'profile_img_original'=>$profile_img_original,
                'profile_img_thumb'=>$profile_img_thumb,
                'avg_rate'=>round($group->getAvgRating(),1),
                'no_of_votes'=>(int) $group->getVoteCount(),
                'current_user_rate'=>$current_rate,
                'is_rated'=>$is_rated
            );
        }
        //get search count
        $group_search_results_count = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->getGroupSearchListCount($group_list, $group_name);

        //checking if there is joined group for logined user.
        $is_my_group = 0;
        // code is commented because user groups list is also getting above, no need to query again
        $user_groups = $dm
                ->getRepository('UserManagerSonataUserBundle:UserToGroup')
                ->getMemberGroupList($cuser_id);
        $groups = array();

        foreach($user_groups as $user_group){
            $groups[] = $user_group['id'];
        }
        $my_groups = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->getActiveGroups($groups);
       // $my_groups = array_intersect($group_list, $group_ids);

        if (!empty($my_groups)) {
            $is_my_group = 1;
        }

        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array(
            'groups' => $data,
            'size' => $group_search_results_count,
            'my_group' => $is_my_group
            ));
        echo json_encode($res_data);
        exit();
    }

    /**
     * List friends user's group
     * @param Request $request
     * @return array;
     */
    public function postGetfriendgroupsAction(Request $request) {
        //initilise the array
        $data = array();
        //get request object

        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id','friend_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //end check parameter

        //get group owner id
        //$group_owner_id = (int) $de_serialize['group_owner_id'];
        //get user login id
        $user_id = (int) $de_serialize['user_id'];
        $current_user_id = (int) $de_serialize['user_id'];
        //check if user is active or not
        $user_check_enable = $this->checkActiveUserProfile($user_id);

        if ($user_check_enable == false) {
            $res_data = array('code' => 100, 'message' => 'ACCOUNT_IS_NOT_ACTIVE', 'data' => $data);
            return $res_data;
        }

        if (isset($de_serialize['limit_start']) && isset($de_serialize['limit_size'])) {
            //get limit size
            $limit_size = (int) $de_serialize['limit_size'];
            if ($limit_size == "") {
                $limit_size = 20;
            }
            //get limit offset
            $limit_start = (int) $de_serialize['limit_start'];
            if ($limit_start == "") {
                $limit_start = 0;
            }
        } else {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_LIMIT', 'data' => $data);
        }


        if ($user_id == "") {
            $res_data = array('code' => 115, 'message' => 'GROUP_OWNER_ID_REQUIRED', 'data' => array());
            return $res_data;
        }

        //get friend id
        $friend_id = $de_serialize['friend_id'];

        $user_service_owner    = $this->get('user_object.service');
        // get documen manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
        $groups = $dm
                ->getRepository('UserManagerSonataUserBundle:Group')
                ->getFriendGroupList($user_id,$friend_id);
        if (!$groups) {
            $res_data = array('code' => 100, 'message' => 'NO_GROUP_FOUND', 'data' => $data);
            return $res_data;
        }

        //getting the group ids.
        $group_ids = array_map(function($groups) {
            return "{$groups['id']}";
        }, $groups);

        //getting the group info...
        $groups_info = $dm->getRepository('UserManagerSonataUserBundle:Group')
                         ->getGroupInfo($group_ids);

        //get group owner user ids..
        $group_owner_user_ids =  array_map(function($groups_user_info) {
            return $groups_user_info->getOwnerId();
            }, $groups_info);
        $users = array();
        foreach ($group_owner_user_ids as $user) {
            $users[] = $user;
        }
        $group_user_owners = array_unique($users);
        //getting the user object service.
        $user_service   = $this->get('user_object.service');
        $group_owner_user_object    = $user_service->MultipleUserObjectService($group_user_owners);

        //finding the group profile images..
        $group_profile_medias = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                                   ->getGroupProfileMediasInfo($group_ids);
        //finding the group media including the profile image.. no need to find it because front end is using just group profile image thumb.
       // $group_media_info = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
        //                       ->getGroupMediaInfo($group_ids);

        //get group notification sent information
        $group_notification_sent = $dm->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                                      ->getGroupNotification($current_user_id, $group_ids);

        //finding the users of groups.
        $group_members_user = $dm->getRepository('UserManagerSonataUserBundle:UserToGroup')
                                 ->findGroupMemberUser($group_ids);
        $user_info = array();
        //get data
        foreach ($groups as $group) {
            $group_id = $group['id'];

            //get group detail from array...
            $group_info = $groups_info[$group_id];

            //check for delete status
            $is_delete = $group_info->getIsDelete();
            if($is_delete == 1){
                continue;
            }
            // get media information related to this group .i.e, commented because front end team just using only group profile image thumb., no media is using.
            //$group_medias = isset($group_media_info[$group_id]) ? $group_media_info[$group_id] : array();

            //get current user membership status
            $group_members = isset($group_members_user[$group_id]) ? $group_members_user[$group_id] : array(); //check if any user is member of this group.

            // get the profile image of group
            $group_medias_profile_img = isset($group_profile_medias[$group_id]) ? $group_profile_medias[$group_id] : '';

            $profile_img_original = $profile_img_thumb = ''; //initialize the variables.
            if($group_medias_profile_img){
                $profile_img_original = $this->getS3BaseUri() . $this->group_media_path . $group_id . '/'.$group_medias_profile_img->getMediaName();
                $profile_img_thumb = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$group_medias_profile_img->getMediaName();
            }

            //get group members
            $group_members = isset($group_members_user[$group_id]) ? $group_members_user[$group_id] : array();
            unset($user_info);
            $user_info = array();
            $is_member = 0;
            $is_sent = 0;
            foreach ($group_members as $group_member) {
                $user_id = $group_member->getUserId();
                //get User Role
                $umask_id = $this->userGroupRole($group_id, $user_id);

                $um = $this->container->get('fos_user.user_manager');
                $user_bobj = $um->findUserBy(array('id' => $user_id));
                //$user_obj = $this->getUserAllInfo($user_id);
                //code start for getting the user object..
                $user_id        = $user_bobj->getId();
                $user_service   = $this->get('user_object.service');
                $user_object    = $user_service->UserObjectService($user_id);
                $user_object['role'] = $umask_id;

                if ($user_id == $current_user_id) {
                   $is_member = 1;
                }

                //check for group invitaion
                if ($is_member == 0) {
                    //this is the sent notification sent.
                    if (isset($group_notification_sent[$group_id])) { //if current user sent the request to join the group
                        $is_sent = 1;
                    } else {
                        $is_sent = 0;
                    }
                }


                //code end for getting the user object..
               // $user_info[] = array('user_id' => $user_bobj->getId(), 'user_name' => $user_bobj->getUsername(),'role'=>$umask_id);
                $user_info[] = $user_object;
            }

            /* $media_data= array(); //commented because front end team is not using they are only using group profile thumb.
             foreach($group_medias as $group_media)
             {
               $media_id    = $group_media->getId();
               $media_name  = $group_media->getMediaName();
               $media_type  = $group_media->getMediaType();
               $group_id    = $group_info->getId();

               $thumb_dir    = $this->getS3BaseUri() . $this->group_media_path_thumb . $group_id . '/'.$media_name;

               $media_data[] = array('id'=>$media_id,
                                      'media_name'=>$media_name,
                                      'media_type'=>$media_type,
                                      'media_path'=>$thumb_dir,
                                     );
             } */
            $group_title = $group_info->getTitle();
            $group_description = $group_info->getDescription();
            $group_creation_date = $group_info->getCreatedAt();
            $group_owner_id = $group_info->getOwnerId();
            //call the service for user object.

            $owner_object  = isset($group_owner_user_object[$group_owner_id]) ? $group_owner_user_object[$group_owner_id] : array();

            $data[] = array('group_id' => $group_id,
                'owner_id' => $group_owner_id,
                'owner_info' => $owner_object,
                'group_title' => $group_title,
                'group_description' => $group_description,
                'created_at' => $group_creation_date,
                'group_status' => $group_info->getGroupStatus(),
                'members' => $user_info,
                'is_member' => $is_member,
                'is_sent' => $is_sent,
               // 'media_info'=>$media_data,
                'profile_img_original'=>$profile_img_original,
                'profile_img_thumb'=>$profile_img_thumb
            );
        }
        //reverse the array
        $data = array_reverse($data);
        $group_size = count($data);
        $group_output = array_slice($data, $limit_start, $limit_size);
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('groups' => $group_output, 'size' => $group_size));
        echo json_encode($resp_data);
        exit();
    }

    /**
     * Cancel club invitations
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postCancelclubinvitationsAction(Request $request)
    {
        //initilise the array
        $data = array();
        //get request object

        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //Code repeat end

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.
        $required_parameter = array('user_id','request_id');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
                return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
        }
        //check if user is the sender
        // get document manager object
        $dm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $user_id = (int)$object_info->user_id;
        $notification_id = $object_info->request_id;

        //find the current user group(public/private)
        $notification_sender = $dm->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                         ->findOneBy(array('sender_id'=>$user_id, 'id'=>$notification_id));
        if(!$notification_sender){
            $resp_data = array('code' => 167, 'message' => 'NOT_THE_CLUB_REQUEST_SENDER', 'data' => $data);
            echo json_encode($resp_data);
            exit();
        }

        //remove the notifications
        $group_response = $dm
                ->getRepository('UserManagerSonataUserBundle:GroupJoinNotification')
                ->findOneBy(array('id' => $notification_id));

        $dm->remove($group_response);
        $dm->flush();
        $resp_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        echo json_encode($resp_data);
        exit();

    }

     /*
     * save x and y cordinate for club cover image thumb
     */
    public function postGetclubcovermediacoordinatesAction(Request $request){

        //initilise the array
        $data = array();
        //get request object

        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //check parameter
        $object_info = (object) $de_serialize; //convert an array into object.

        //check required params
        $required_parameter =  array('media_id','x','y');

        $media_id  = $object_info->media_id;
        $x         = $object_info->x;
        $y         = $object_info->y;

        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER ' . $this->miss_param, 'data' => $data);
        }
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $media = $dm->getRepository('UserManagerSonataUserBundle:GroupMedia')
                   ->find($media_id);
        if (!$media) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
            echo json_encode($res_data);
            exit;
        }

        // save x and y cordinate in mongo database
        $media->setX($x);
        $media->setY($y);
        $dm->persist($media);
        $dm->flush();

        $media_data = array(
                       'media_id'=>$media_id,
                       'x_cord'=>$media->getX(),
                       'y_cord'=>$media->getY()
                    );
        $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $media_data);
        echo json_encode($res_data);
        exit();
    }

    /**
     * Get User role for group
     * @param object $group
     * @param array $user_ids
     * @return array
     */
    public function getUsersGroupRole($group, array $user_ids, $usersObject) {
        $mask = 21; //guest: Not group member

        //if group not found
        if(!$group or empty($user_ids)){
            return $mask;
        }
        $aclProvider = $this->container->get('security.acl.provider');

        $objectIdentity = ObjectIdentity::fromDomainObject($group); //entity

        try {
            $acl = $aclProvider->findAcl($objectIdentity);
        } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
            $acl = $aclProvider->createAcl($objectIdentity);
        }
        //Acl Operation
        $userMasks = array();
        foreach($user_ids as $user_id){
            $user_obj = $usersObject[$user_id];
            // retrieving the security identity of the currently logged-in user
            $securityIdentity = UserSecurityIdentity::fromAccount($user_obj);
            foreach ($acl->getObjectAces() as $ace) {
                if ($ace->getSecurityIdentity()->equals($securityIdentity)) {
                    $userMasks[$user_obj->getId()] = $ace->getMask();
                    break;
                }
            }
        }

        $maskedUsersKey = array_keys($userMasks);
        $leftUsers = array_diff($user_ids, $maskedUsersKey);
        if(!empty($leftUsers)){
            foreach($leftUsers as $lU){
                $userMasks[$lU] = 21; //guest
            }
        }
        return $userMasks;
    }
}
