<?php
namespace Utility\UniversalNotificationsBundle\Services;
use Utility\UniversalNotificationsBundle\Services\PushLogService;
use Utility\CurlBundle\Services\CurlRequestService;

class NodeJsPushService {
    
    private $accessKey = 'X1ixS7a4epAnrC55Mkq7wJ0ZtEeJERTtpGEWWs4wUmK6XQRTLqNg1TgEd36UAtwy';
    private $accessUrl = '';
    public $deviceTypes = array(
      'I'=>'apns' ,
      'A'=>'gcm'
    );
    private $data=array();
    private $devices=array();
    private $message=null;
    private $preparedData = array();
    private $feedbackUrl= null;
    
    public function __construct() {
        $this->accessUrl = $this->getContainer()->getParameter('push_notification_url');
        $this->feedbackUrl = $this->getContainer()->getParameter('symfony_base_url').'webapi/pushfeedbacks';
    }
    
    public function setData(array $data){
        $this->data = $data;
        return $this;
    }
    
    public function setMessage($message){
        $this->message = $message;
        return $this;
    }
    
    public function setFeedbackUrl($feedbackUrl){
        $this->feedbackUrl = $feedbackUrl;
        return $this;
    }
    public function setDevice(array $devices, $deviceType, $clientType){
        foreach($devices as $device){
            $this->devices[] = array(
              'token' =>$device['device_token'] ,
               'type'=> $this->deviceTypes[$deviceType],
               'app'=>  strtolower($clientType)
            );
        }
        return $this;
    }
    
    protected function prepareData(){
        $body = array(
            "devices"=>  $this->devices,
            "message"=> $this->message,
            "data"=>  $this->data,
            "feedbackUrl"=>$this->feedbackUrl,
        );
        $this->preparedData = $body;
        return $this;
    }
    
    protected function toJson(){
        $data = $this->preparedData;
        if(empty($data)){
            $this->prepareData();
            $data = $this->preparedData;
        }
        
        return json_encode($data);
    }
    
    protected function toArray(){
        $data = $this->preparedData;
        if(empty($data)){
            $this->prepareData();
            $data = $this->preparedData;
        }
        
        return $data;
    }

    public function send(){
        $data = $this->prepareData()->toJson();
        $devices = $this->devices;
        $logger = new PushLogService();
        if(!empty($devices)){
            $request = new CurlRequestService();
            $response = $request->setUrl($this->accessUrl)
                            ->setHeader('access_key', $this->accessKey)
                            ->setHeader('Content-Type', 'application/json')
                            ->setData($data)
                            ->send('POST')
                            ->getResponse();
            
            $logger->log('Request : '.$data.' Response : '.$response);
        }else{
            $logger->log('Push Failed: Devices not found to send push notification. Request : '.$data);
        }
    }
    
    private function getContainer(){
        global $kernel;
        return $kernel->getContainer();
    }
}

