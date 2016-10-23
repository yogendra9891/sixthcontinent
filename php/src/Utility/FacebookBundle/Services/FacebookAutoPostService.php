<?php
namespace Utility\FacebookBundle\Services;

require_once dirname(dirname(__FILE__)).'/Resources/FacebookSdk/autoload.php';
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Facebook\GraphObject;
use Facebook\FacebookRequestException;
use Facebook\FacebookSDKException;
use Facebook\Entities\AccessToken;

class FacebookAutoPostService {
    protected $appId;
    protected $appSecret;
    protected $accessToken;
    protected $session;
    protected $dm;
    protected $em;
    protected $container;
    private $title;
    private $message;
    private $link;
    private $image;
    private $description;


    /**
     * 
     * @param EntityManager $em
     * @param DocumentManager $dm
     * @param Container $container
     */
    public function __construct(EntityManager $em = null, DocumentManager $dm = null, Container $container=null) {
        $this->em        = $em;
        $this->dm        = $dm;
        $this->container = $container;
        $this->init();
    }
    
    public function init(){
        $facebook = $this->container->getParameter('facebook_app');
        $this->appId = $facebook['appId'];
        $this->appSecret = $facebook['appSecret'];
        return $this;
    }
    
    public function setAccessToken($accessToken){
        $this->accessToken = $accessToken;
        $this->getSession();
        return $this;
    }
    
    public function setTitle($title){
        $this->title = $title;
        return $this;
    }
    
    public function setMessage($message){
        $this->message = $message;
        return $this;
    }
    
    public function setTargetLink($link){
        $this->link = $link;
        return $this;
    }
    
    public function setImageUrl($image){
        $this->image = $image;
        return $this;
    }
    
    public function setDescription($desc){
        $this->description = $desc;
        return $this;
    }
    
    public function send(){
        $params = array();
        if(!empty($this->title)){
            $params["name"] = $this->title;
        }
        if(!empty($this->message)){
            $params["message"] = $this->message;
        }
        if(!empty($this->link)){
            $params["link"] = $this->link;
        }
        if(!empty($this->image)){
            $params["picture"] = $this->image;
        }
        if(!empty($this->description)){
            $params["description"] = $this->description;
        }
        
        
        if($this->session) {
            try {
             $request = new FacebookRequest(
                $this->session, 'POST', '/me/feed', $params
              );
              $response = $request->execute()->getGraphObject();
              
              $results = array(
                  'code'=>101,
                  'message'=>'success',
                  'post_id'=>$response->getProperty('id')
              );
              
            } catch(FacebookRequestException $e) {
                $results = array(
                  'code'=>$e->getCode(),
                  'message'=>$e->getMessage(),
                  'post_id'=>null,
              );

            }
            $this->_log(json_encode($results));

        }
 
        return isset($results) ? $results : array('code'=>100, 'message'=>'unable to connet with facebook', 'post_id'=>null);
    }
    
    protected function getSession(){
        FacebookSession::setDefaultApplication($this->appId, $this->appSecret);
        FacebookSession::enableAppSecretProof(false);
        $this->session = new FacebookSession($this->accessToken);
    }
    
    public function getExtendedAccessToken(){
        $response = array();
        try{
            $this->getSession();
            $accessToken = $this->session->getAccessToken();
            $longLivedAccessToken = $accessToken->extend();
            
            if(!$longLivedAccessToken){
               $response = array('code'=>100, 'message'=>'unable to get access token', 'access_token'=>$longLivedAccessToken);
               $this->_log(json_encode($response));
            }else{
                if(is_object($longLivedAccessToken)){
                    $_token = $this->getAccessTokenFromObject($longLivedAccessToken);
                    $_expiry = $longLivedAccessToken->getExpiresAt();
                    $expiry = $_expiry->getTimestamp();
                }else{
                    $_token = $longLivedAccessToken;
                    $expiry='';
                }
                $response = array('code'=>101, 'message'=>'success', 'access_token'=>$_token, 'expiry'=>$expiry);
            }
        }  catch (FacebookSDKException $e){
            $response = array('code'=>$e->getCode(), 'message'=>$e->getMessage(), 'access_token'=>'');
            $this->_log(json_encode($response));
        }
        return $response;
    }
    
    public function getAccessTokenInfo(){
        $accessTokenInfo = array();
        try {
          $accessToken = new AccessToken($this->accessToken);
          $accessTokenInfo = $accessToken->getInfo()->asArray();
          $accessTokenInfo['code'] = 101;
          $accessTokenInfo['message'] = 'success';
        } catch(FacebookSDKException $e) {
          $accessTokenInfo = array('code'=>$e->getCode(), 'message'=>$e->getMessage());
          $this->_log(json_encode($accessTokenInfo));
        }
        return $accessTokenInfo;
    }
    
    public function getAccessTokenFromObject($accessTokenObject){
        $session = new FacebookSession($accessTokenObject);
        return $session->getToken();
    }
    
    public function _log($sMessage){
        $monoLog = $this->container->get('monolog.logger.facebook_log');
        $monoLog->info($sMessage);
    }
    
    public function getPermissions(){
        $return = array();
        try{
            $request = new FacebookRequest(
                $this->session, 'GET', '/me/permissions'
            );
            $response = $request->execute()->getResponse();
            $return = array('code'=>101, 'message'=>'Success', 'data'=>$response->data);
        }catch(FacebookRequestException $e){
            $return = array('code'=>100, 'message'=>'Error', 'data'=>array('code'=>$e->getCode(), 'message'=>$e->getMessage()));
            $this->_log(json_encode($return));
        }
        return $return;
    }
    
    public function checkPermissions($permission){
        $result = $this->getPermissions();
        $isPermit = false;
        if(!empty($result) and $result['code']==101){
            foreach($result['data'] as $data){
                if($data->permission==$permission and $data->status=="granted"){
                    $isPermit=true;
                }
            }
        }
        return $isPermit;
    }
    
    public function deletePermissions(){
        $return = array();
        try{
            $request = new FacebookRequest(
                $this->session, 'DELETE', '/me/permissions'
            );
            $response = $request->execute()->getResponse();
            
            $return = array('code'=>101, 'message'=>'Success', 'data'=>array('is_deleted'=>$response->success));
        }catch(FacebookRequestException $e){
            $return = array('code'=>100, 'message'=>'Error', 'data'=>array('code'=>$e->getCode(), 'message'=>$e->getMessage()));
            $this->_log(json_encode($return));
        }
        return $return;
    }
    
}
