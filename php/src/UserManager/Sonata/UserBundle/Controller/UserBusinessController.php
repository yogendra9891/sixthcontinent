<?php

namespace UserManager\Sonata\UserBundle\Controller;

use UserManager\Sonata\UserBundle\Entity\UserConnection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\Locale\Locale;
use UserManager\Sonata\UserBundle\Entity\UserSkills;
use UserManager\Sonata\UserBundle\Document\BusinessKeyword;
use UserManager\Sonata\UserBundle\Document\StudyList;

class UserBusinessController extends FOSRestController
{
    protected $suggestion_limit = 20;
    
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
    * Get Parent Category List
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function getBusinessCategoryListAction(Request $request){
        
        //get request object
        $freq_obj = $request->get('reqObj');

        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //check required params
        $required_params =  array('lang_code');
        $this->checkRequiredParams($de_serialize, $required_params);
        $data = array();
        //validating params
        if($de_serialize['lang_code']){
            $allowed_code = array('en','it');
            if(!in_array($de_serialize['lang_code'], $allowed_code)){
                $res_data = array('code' => '132', 'message' => 'INVALID_LANGUAGE_CODE', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
        } else {
            $res_data = array('code' => '132', 'message' => 'INVALID_LANGUAGE_CODE', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
        //get cat id
        $cat_id = isset($de_serialize['cat_id'])?$de_serialize['cat_id']:0;
        $type   = isset($de_serialize['type'])? $de_serialize['type'] : ''; //for showing the category name.
        $em = $this->getDoctrine()->getManager();
        
        //check for type and showing the category name.
        if ($type != '') {
            $searchData = $em
                    ->getRepository('UserManagerSonataUserBundle:BusinessCategory')
                    ->getCategoryName($de_serialize['lang_code'], $cat_id);
            if (count($searchData)) {
                $Searched_data = $searchData[0];
                $data = array('id'=>$Searched_data['id'], 'category_name'=>$Searched_data['category_name'],
                    'image'=>$Searched_data['image'],'image_thumb'=>$Searched_data['image_thumb']);
            }
            // Set data for search
            $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
            echo json_encode($final_data);
            exit;            
        }
        $searchData = $em
                ->getRepository('UserManagerSonataUserBundle:BusinessCategory')
                ->getParentCategoryList($de_serialize['lang_code'],$cat_id);
        
        // Set data for search
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $searchData);
        echo json_encode($final_data);
        exit;
    }
    
    /**
    * Get Category Keyword List
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function getKeyWordListAction(Request $request){
        //set default offset
        $offset = 0;
        $limit = $this->suggestion_limit;
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $searchText = trim($object_info->keyword);
        $categoryId = $object_info->category_id;
        //get entity manager object
        $searchData = array();
        $searche_result = array();
        // check empty search
        if(!empty($searchText)){
            // mongo odm
            $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
//            $searchData = $dm->getRepository('UserManagerSonataUserBundle:BusinessKeyword')->getBusiessKeyword($categoryId, $searchText, $limit);
            $searchData = $dm->getRepository('UserManagerSonataUserBundle:BusinessKeyword')->getBusiessKeywordByText($searchText, $limit);
            // Set data for search
            $searche_result['category_id'] = $categoryId;
            $searche_result['limit'] = $limit;
            $searche_result['keyword'] = array();
            if(!empty($searchData)){
                foreach ($searchData as $key => $value) {
                    $searche_result['keyword'][] = array(
                        'id' => $value->getId(),
                        'name' => $value->getKeyword()
                        );
                }
            }
        }
        
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $searche_result);
        echo json_encode($final_data);
        exit;
    }
    
    /**
    * User Skills
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function createAndUpdateUserSkillsAction(Request $request){
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        // Check empty data
        if(empty($de_serialize['skills']) || empty($de_serialize['user_id'])){
            $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        $data = array();
        $data['userId']   = $de_serialize['user_id'];
        $data['skills']   = $de_serialize['skills'];
        //get entity manager object
        $em = $this->getDoctrine()->getManager();
        $skills_result = $em
                     ->getRepository('UserManagerSonataUserBundle:UserSkills')
                     ->createAndSaveSkill($data);
        if(!empty($skills_result)){
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $skills_result);
        } else {
            $data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => array());
        }
        echo $this->encodeData($data);
        exit;
    }
    
    /**
    * Get User Skills
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function getUserSkillsListAction(Request $request){
        //set default offset
        $offset = 0;
        $limit = $this->suggestion_limit;
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        $user_id = trim($object_info->user_id);
        //get entity manager object
        $searchData = array();
        $skills_result = array();
        // check empty search
        if(!empty($user_id)){
            $em = $this->getDoctrine()->getManager();
            $skillData = $em
                    ->getRepository('UserManagerSonataUserBundle:UserSkills')
                    ->findOneBy(array('userId' => $user_id));
            // Set data for search
            if(!empty($skillData)){
                $skills_result['skills']['user_id'] = $skillData->getUserId();
                $skills_result['skills']['skills'] = $skillData->getSkills();
            } else {
                $skills_result['skills']['skills'] = '';
            }
        }
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $skills_result);
        echo json_encode($final_data);
        exit;
    }
    
    /**
    * Get Category List All
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function getBusinessCategoryListAllAction(Request $request){
        // check empty search
        $em = $this->getDoctrine()->getManager();
        $searchData = $em
                ->getRepository('UserManagerSonataUserBundle:BusinessCategory')
                ->getCategoryList();
        $final_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $searchData);
        echo json_encode($final_data);
        exit;
    }
    
    /**
    * Insert Category List 
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function postInsertbussinesscategoriesAction(Request $request){
        
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('UserManagerSonataUserBundle:BusinessCategory');
        $InsertCategoryList = $category->InsertCategoryList($de_serialize['data']);
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
        
    }
    
    /**
    * Update Category List 
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function postUpdatebussinesscategoriesAction(Request $request){
        
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('UserManagerSonataUserBundle:BusinessCategory');
        $UpdateCategoryList = $category->UpdateCategoryList($de_serialize['data']);
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
        
    }
    
    
    /**
    * Add Category
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function addBusinessCategoryKeywordAction(Request $request){
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        // mongo odm
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        if(isset($object_info->keyword_id)){
            $keywordArr = array();
            $keywordId = $object_info->keyword_id;
            $bKeyword = $dm->getRepository('UserManagerSonataUserBundle:BusinessKeyword')->findOneById($keywordId);
            //set group fields
            $bKeyword->setCategoryId('0');
            $bKeyword->setKeyword($object_info->keyword);
            //persist the group object
            $dm->persist($bKeyword);
            //save the group info
            $dm->flush();
            // response
            $keywordArr = array(
                'keyword_id' => $object_info->keyword_id,
                'category_id' => $object_info->category_id,
                'keyword' => $object_info->keyword
            );
            $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $keywordArr);
            echo json_encode($resp_data);
            exit();
        } else {
            $keywordArr = array();
            $keywordArr = $object_info->data['keyword'];
            // Save Keyword
            foreach ($keywordArr as $value) {
                //get group object
                $bKeyword = new BusinessKeyword();
                //set group fields
                $bKeyword->setCategoryId('0');
                $bKeyword->setKeyword($value['keyword']);
                //persist the group object
                $dm->persist($bKeyword);
                //save the group info
                $dm->flush();
                $dm->clear();
            }
            $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $keywordArr);
            echo json_encode($resp_data);
            exit();
        }
    }
    
    /**
    * Add StudyList
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function addStudyListAction(Request $request){
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        $object_info = (object) $de_serialize; //convert an array into object.
        
        //$keyword_id = trim($object_info->keyword_id);
        // mongo odm
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        if(isset($object_info->id)){
            $sListArr = array();
            $listId = $object_info->id;
            $sListData = $dm->getRepository('UserManagerSonataUserBundle:StudyList')->findOneById($listId);
            //set group fields
            $sListData->setName($object_info->name);
            $sListData->setType($object_info->type);
            //persist the group object
            $dm->persist($sListData);
            //save the group info
            $dm->flush();
            // response
            $sListArr = array(
                'id' => $object_info->id,
                'name' => $object_info->name,
                'type' => $object_info->type
            );
            $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $sListArr);
            echo json_encode($resp_data);
            exit();
        } else {
            $sListArr = array();
            $sListArr = $object_info->data['category'];
            // Save Keyword
            foreach ($sListArr as $value) {
                //get group object
                $bKeyword = new StudyList();
                //set group fields
                $bKeyword->setName($value['name']);
                $bKeyword->setType($value['type']);
                //persist the group object
                $dm->persist($bKeyword);
                //save the group info
                $dm->flush();
                $dm->clear();
            }
            $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $sListArr);
            echo json_encode($resp_data);
            exit();
        }
    }
    
    /**
    * Checking Required Params In json
    * @param $user_params array json array send by user
    * @param $required_params array required params array
    */
    public function checkRequiredParams($user_params, $required_params) {
        
        foreach($required_params as $param){
            if (!array_key_exists($param, $user_params)) {
                $final_data = array('code' => 130, 'message' => 'PARAMS_MISSING', 'data' => array());
                echo json_encode($final_data);
                exit; 
            }  
        }
        
    }
    
    /**
    * Insert Category Code List 
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function postInsertbussinesscategorycodesAction(Request $request){
        
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('UserManagerSonataUserBundle:BusinessCategoryCode');
        $InsertCategoryCodeList = $category->InsertCategoryCodeList($de_serialize['data']);
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
        
    }
    
    /**
    * Update Category Code List 
    * @param \Symfony\Component\HttpFoundation\Request $request
    * @return string
    */
    public function postUpdatebussinesscategorycodesAction(Request $request){
        
        //get request object
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);
        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        $em = $this->getDoctrine()->getManager();
        $category = $em->getRepository('UserManagerSonataUserBundle:BusinessCategoryCode');
        $UpdateCategoryCodeList = $category->UpdateCategoryCodeList($de_serialize['data']);
        
        $res_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => array());
        echo json_encode($res_data);
        exit;
        
    }
    
    public function postAddbusinesskeywordsAction(Request $request){
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); 
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeData($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }

        //end to get request object
        //parameter check start
        $object_info = (object) $de_serialize; //convert an array into object.

        $required_parameter = array('user_id', 'keyword');
        //checking for parameter missing.
        $chk_error = $this->checkParamsAction($required_parameter, $object_info);
        if ($chk_error) {
            return array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => array());
        }
        $keyword = $de_serialize['keyword'];
        $user_id = $de_serialize['user_id'];

        if (empty($keyword)) {
            $res_data = array('code' => 100, 'message' => 'EMPTY_KEYWORD', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
        if (empty($user_id)) {
            $res_data = array('code' => 100, 'message' => 'EMPTY_KEYWORD', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
        
        $data = array();
        $searchData = $dm->getRepository('UserManagerSonataUserBundle:BusinessKeyword')
                ->findOneBy(array('keyword'=>$keyword));
        if($searchData){
            $data = array(
              'keyword' => array(
                    'name'=> $searchData->getKeyword(),
                    'id' => $searchData->getId()
                )
            );
            $res_data = array('code' => 100, 'message' => 'ALREADY_EXISTS', 'data' => $data);
            echo json_encode($res_data);
            exit;
        }
        try{
            $bKeyword = new BusinessKeyword();
            //set group fields
            $bKeyword->setCategoryId('0');
            $bKeyword->setKeyword($keyword);
            //persist the group object
            $dm->persist($bKeyword);
            //save the group info
            $dm->flush();
            $dm->clear();
            $data = array(
              'keyword' => array(
                    'name'=> $keyword,
                    'id' => $bKeyword->getId()
                )
            );
            $res_data = array('code' => 101, 'message' => 'SUCCESS', 'data' => $data);
        }catch(\Exception $e){
            $res_data = array('code' => 100, 'message' => 'ERROR_OCCURED', 'data' => $data);
        }
        
        echo json_encode($res_data);
        exit();
        
    }
    
    /**
     * Check for enabled user
     * @param string $username
     * @return boolean
     */
    public function checkActiveUserProfile($uid) {
        //get user manager
        $um = $this->get('fos_user.user_manager');

        //get user detail
        $user = $um->findUserBy(array('id' => $uid));
        if (!$user) {
            return false;
        }
        $user_check_enable = $user->isEnabled();

        return $user_check_enable;
    }
    
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
    
}
