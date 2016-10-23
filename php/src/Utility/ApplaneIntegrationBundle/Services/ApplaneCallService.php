<?php

namespace Utility\ApplaneIntegrationBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Utility\CurlBundle\Services\CurlRequestService;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Applane\WrapperApplaneBundle\Controller\ApplaneController;

// service method  class
class ApplaneCallService implements ApplaneConstentInterface {

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
        //$this->request   = $request;
    }
    
    
    /**
     *  function for calling the applane services through the curl service
     * @param type $data
     * @param type $api
     * @param type $queryParam
     * @return type
     */
    public function callApplaneService($data, $api, $queryParam) {
        
        $response = $call_type = '';
        try {
            $call_type = $this->container->getParameter('tx_system_call'); //get parameters for applane calls.
        } catch (\Exception $ex) {

        }
         
        if ($call_type == 'APPLANE') {
            $applane_user_token = $this->container->getParameter('applane_user_token');
            $serviceUrl = $this->container->getParameter('base_applane_url'). $api;
            $client = new CurlRequestService();
            $response =  $client->setUrl($serviceUrl)
                                ->setRequestType('POST')
                                ->setHeader('content-type', 'application/x-www-form-urlencoded')
                                ->setParam('code', $applane_user_token)
                                ->setParam($queryParam, $data)
                                ->send()
                                ->getResponse();
            //write logs for request and response.
            try {
                $applane_service = $this->container->get('appalne_integration.callapplaneservice');
                $applane_service->writeTransactionLogs($data, $response);            
            } catch (\Exception $ex) {

            }
        }
        return $response;
    }
    
    /**
     * Get data format
     * @param array $response_data
     * @return array
     */
    public function getMongoDataFormatInsert($response_data,$collection,$type){
        $final_data = (object)array('$'.$type =>array((object)($response_data)), '$collection'=> $collection);
        return json_encode($final_data);
        //return array($final_data);
    }

        /**
     * Get data format
     * @param array $response_data
     * @return array
     */
    public function getMongoDataFormatInsert1($group_data, $filter_data, $collection, $filter, $group){
        $final_data = (object)array('$'.$filter =>array((object)($filter_data)),'$'.$group =>array((object)($group_data)), '$collection'=> $collection);
        return json_encode($final_data);
        //return array($final_data);
    }
    
    /**
     * Get data format
     * @param array $response_data
     * @return array
     */
    public function getMongoDataFormatUpdate($response_data,$collection,$type){
        $final_data = (object)array('$'.$type =>array((object)($response_data)), '$collection'=> $collection);
        return json_encode($final_data,JSON_NUMERIC_CHECK);
        //return array($final_data);
    }
    
    /**
     * Get shop revenue from applane
     * @param int $shopid
     * @return int
     */
    public function getShopRevenueFromApplane($shopid){
        //$shopid = '551e6792236f510813100730';
        $data = $this->prepareGetShopRevenueData($shopid);
        //$data = '{"$collection":"sixc_transactions","$filter":{"shop_id":"551e6792236f510813100730"},"$group":{"_id":null,"total_income":{"$sum":"$total_income"},"payble_value":{"$sum":"$payble_value"},"new_upto_50_value":{"$sum":"$new_upto_50_value"},"checkout_value":{"$sum":"$checkout_value"},"$fields":false}}';
        $api = 'query';
        $queryParam = 'query';
        $response = $this->callApplaneService($data, $api, $queryParam);
        $decode_data = json_decode($response);
        //get total income
        $revenue = 0;
        if(isset($decode_data->response->result[0]->total_income)){
             $revenue = $decode_data->response->result[0]->total_income;
        }
        return $revenue;
    }

    /**
     * Prepare data
     * @param int $shopid
     * @return string
     */
    public function prepareGetShopRevenueData($shopid){
        $shopid = (string)$shopid;
        
        $group = (object)array(
            '_id'=>'',
            'total_income' => (object)array('$sum' => '$total_income'),
            'payble_value' => (object)array('$sum' => '$payble_value'),
            'new_upto_50_value' => (object)array('$sum' => '$new_upto_50_value'),
            'checkout_value' => (object)array('$sum' => '$checkout_value'),
            '$fields' => false
            );
        $collection_data =  'sixc_transactions';
        $filter_data = (object)array('status' => 'Approved','shop_id' => (string)$shopid,
                                     'transaction_type_id' => (object)array(
                                         '$in' => array('553209267dfd81072b176bba','553209267dfd81072b176bbc','553209267dfd81072b176bc0')
                                     )
            );
        
        $final_data = (object)array(
            '$collection' => $collection_data,
            '$filter' => $filter_data,
            '$group' => $group
            //'$fields' => false
        );
       return json_encode($final_data);

    }

    /**
     * get citizen income
     * @param int $citizen_id
     * @return int $citizen_income
     */
    public function getCitizenIncome($citizen_id) { 
        $citizen_id = (string)$citizen_id; //convert into string
        $data = $this->prepareApplaneDataCitizenIcome($citizen_id);
        $citizen_income = $credit = 0;
        $applane_resp = $this->callApplaneService($data, self::URL_QUERY, self::QUERY_CODE);
        $appalne_decode_resp = json_decode($applane_resp);
        if (isset($appalne_decode_resp->response->result[0]->amount)) {
            $citizen_income = $appalne_decode_resp->response->result[0]->amount;
        }
        if (isset($appalne_decode_resp->response->result[0]->credit)) {
            $credit = $appalne_decode_resp->response->result[0]->credit;
        }
        $res_data = array('citizen_income'=>$credit, 'credit'=>$citizen_income); //pass credit in citizen income.
        return $res_data;
    }
    
    /**
     * Prepare the applane data for citizen income
     * @param array $citizen_id
     * @return array
     */
    public function prepareApplaneDataCitizenIcome($citizen_id) {  
        $data = array(
            '$collection'=>self::SIX_CONTINENT_CITIZEN_BUCKS_COLLECTION,
            '$filter'=> (object)array(
                    'citizen_id'=>(string)$citizen_id
            ),
            '$group'=> (object)array(
                    '_id'=>null,
                    'amount'=> array('$sum'=>'$amount'),
                    'debit'=>  array('$sum'=>'$debit'),
                    'credit'=> array('$sum'=>'$credit')
           )           
       );
       $response_data = json_encode($data);
       return $response_data;
    }
    
    /**
     *  function for gettingthe users credits on the stores
     * @param type $store_ids
     * @param type $user_id
     */
    public function getUserCreditOnStore($store_ids,$user_id) {
        
        //$data = $this->prepareApplaneDataStoreFav($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_insert = self::ACTION_INSERT;
        $url_invoke = self::CITIZEN_CREDIT_INVOKE;
        $query_update = self::QUERY_UPDATE;
        $collection = self::SIX_CONTINENT_CUSTOMER_CHOICE;
        $final_data = $this->prepareShopCitizenCreditData($store_ids,$user_id);
        $applane_resp = $applane_service->callApplaneService($final_data,$url_invoke,self::CITIZEN_CREDIT_QUERY);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';
        $data = array();
        if($status == 'error') {
            return $data;
        }
        $store_datas  =   $appalne_decode_resp->response;    
        foreach($store_datas as $store_data) {
            $data[$store_data->_id] = isset($store_data->total_credit) ? round($store_data->total_credit) : 0;
        }
        return $data;
    }
    
    /**
     * write logs for transaction system integration
     * @param json $request
     * @param json $response
     * @return boolean
     */
    public function writeTransactionLogs($request, $response) {
        $logs_dir = __DIR__.'../../../../app/logs/transaction.log';
        try {
            @\mkdir($logs_dir, 0777, true);
            $this->container->get('monolog.logger.channel1')->info($request);
            $this->container->get('monolog.logger.channel1')->info($response);
        } catch (\Exception $ex) {

        }
        return true;
    }
    
     /**
     * write logs for transaction system integration
     * @param json $request
     * @param json $response
     * @return boolean
     */
    public function writeAllLogs($handler, $request, $response) {
        try {
            $handler->info($request);
            $handler->info($response);
        } catch (\Exception $ex) {

        }
        return true;
    }
    
    /**
     *  function for preparing the data for getting the citizen credit on the shop
     * @param type $store_ids
     * @param type $user_id
     */
    public function prepareShopCitizenCreditData($store_ids,$user_id) {
        $data = array();
        $data['shop_id'] = $store_ids;
        $data['citizen_id'] = (string)$user_id;
        $data['date'] = date('Y-m-d').'T00:00:00Z';
        $data = (object) $data;
        $data =  json_encode($data);
        return "[".$data."]";
        
    }

    /**
     * get citizen income
     * @param array $data
     * @return array $appalne_decode_resp
     */
    public function gettransactiondata($data) {
        $final_data      = $this->prepareApplaneDataStoreImportTransaction($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_resp    = $applane_service->callApplaneService($final_data, self::URL_QUERY, self::QUERY_CODE);
        //maintain log
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data_request = "Transaction Import Request Data:".json_encode($final_data);
        $monolog_data_response = "Transaction Import Response Data:".json_encode($applane_resp);
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
        
        $appalne_decode_resp = json_decode($applane_resp);
        return $appalne_decode_resp;
    }
    
    /**
     * Prepare the applane data for store import transaction
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreImportTransaction($data){
        $data = array(
            '$collection'=>self::SIX_CONTINENT_SHOP_INCOME_COLLECTION,
            '$filter'=> (object)array(
                'date'=>(object)array('$gte'=>$data['start_date'], '$lt'=>$data['end_date']),
                'transaction_type_id' => (object)array(
                                         '$in' => array(self::TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID_SIX_PERCENT, self::TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID_TEN_PERCENT)
                                     )
                )
            );
        $response_data = json_encode($data);
        return $response_data;
    }
    
    /**
     * get purchase data from transaction system
     * @param array $data
     * @return array $appalne_decode_resp
     * {"$collection":"sixc_citizens_cards",
        "$filter":{"transaction_id.id":{"$in":["",""]},"transaction_id.is_auto_confirm": {"$in": [false,null]},"inactive":{"$in":[false,null]},"type":"551ce49e2aa8f00f20d93293"},"$fields":{
        "inactive":1,"date":1,"card_code":1,"card_no":1,"credit":1,"shop_id":1,"citizen_id":1}}
     * 
     */
    public function getpurchasetransactiondata($data) {
        $final_data      = $this->prepareApplaneDataStorePurchaseTransaction($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_resp    = $applane_service->callApplaneService($final_data, self::URL_QUERY, self::QUERY_CODE);
        $appalne_decode_resp = json_decode($applane_resp);
        return $appalne_decode_resp;
    }
    
    /**
     * Prepare the applane data for store import transaction
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStorePurchaseTransaction($data){
        $data = array(
            '$collection'=>self::SIX_CONTINENT_CITIZEN_CARDS_COLLECTION,
            '$filter'=> (object)array(
                'type'=>'551ce49e2aa8f00f20d93293',
                'transaction_id.id'=>(object)array('$in'=>$data['transaction_ids']),
	        'transaction_id.is_auto_confirm'=>(object)array('$in'=>array(false, null)),
                'inactive'=>(object)array('$in'=>array(false, null))),
            '$fields'=>(object)array('inactive'=>1,'date'=>1, 'card_code'=>1, 'card_no'=>1, 'credit'=>1, 'shop_id'=>1, 'citizen_id'=>1)
            );
        $response_data = json_encode($data);
        return $response_data;
    }
    
    /**
     * get sales data from transaction system
     * @param array $data
     * @return array $appalne_decode_resp
     */
    public function getsalestransactiondata($data) {
        $final_data      = $this->prepareApplaneDataStoreSalesTransaction($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $applane_resp    = $applane_service->callApplaneService($final_data, self::URL_QUERY, self::QUERY_CODE);
        $appalne_decode_resp = json_decode($applane_resp);
        return $appalne_decode_resp;
    }
    
    /**
     * Prepare the applane data for store import transaction
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreSalesTransaction($data){
        $data = array(
            '$collection'=>self::TRANSACTION_COLLECTION,
            '$filter'=> (object)array(
                'status'=>self::TRANSACTION_COLLECTION_STATUS,
                'transaction_type_id'=>(object)array('$in'=>array(self::TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID1, self::TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID2,
                    self::TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID3, self::TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID4)),
                'date'=>(object)array('$gte'=>$data['start_date'], '$lt'=>$data['end_date'])),
            '$group'=>(object)array( 
                       '_id'=> (object)array('shop_id'=> '$shop_id._id', 'transaction_type_id'=> '$transaction_type_id._id'),
                       'transaction_type_id'=>(object)array('$first'=> '$transaction_type_id'),
                       'shop_id'=>(object)array('$first'=> '$shop_id'),
                       'date'=>(object)array('$first'=> '$date'),
                       'total_income'=>(object)array('$sum'=> '$total_income'),
                       'vat'=>(object)array('$sum'=> '$vat'),
                       'total_vat_income'=>(object)array('$sum'=> '$total_vat_income'),
                       'checkout_value'=>(object)array('$sum'=> '$checkout_value'),
                      )
            );
        $response_data = json_encode($data);
        return $response_data;
    } 
    
    /**
     * On shop buy cards on applane
     * @param array $data
     */
    public function buyShopOfferCard($data)
    {
        $result_array    = array('transaction_id'=>'');
        $result_array["code"] = 1065;
        $result_array["message"] =  'TRANSACTION_IS_NOT_INITIATED_IN_TRANSACTION_SYSTEM';
        $final_data      = $this->prepareApplaneDataToBuyCard($data);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $url_update   = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $appalne_decode_resp = json_decode($applane_resp);
//        if (isset($appalne_decode_resp->response->avail_coupn)) {
//           $avail_coupn   = (array)$appalne_decode_resp->response->avail_coupn;
//           $coupon_result = $avail_coupn['$insert'][0];
//           $result_array['transaction_id'] = $coupon_result->_id;
//           $result_array['cash_amount']    = $coupon_result->payable;
//           $result_array['total_amount']   = $coupon_result->coupon_value;
//           $result_array['discount']       = $coupon_result->discount;
//           $result_array['transaction_inner_id'] = $coupon_result->transaction_id->id;
//        } else {
//            $result_array['transaction_id'] = '';
//        }
        if (isset($appalne_decode_resp->code)) {
            if ($appalne_decode_resp->code == ApplaneConstentInterface::APPLANE_SUCCESS_CODE) {
                $avail_coupn   = (array)$appalne_decode_resp->response->avail_coupn;
                $coupon_result = $avail_coupn['$insert'][0];
                $result_array['transaction_id'] = $coupon_result->_id;
                $result_array['cash_amount']    = $coupon_result->payable;
                $result_array['total_amount']   = $coupon_result->coupon_value;
                $result_array['discount']       = $coupon_result->discount;
                $result_array['transaction_inner_id'] = $coupon_result->transaction_id->id;
                $result_array['checkout_value']       = $coupon_result->checkout_value;
                $result_array['vat_checkout_value']       = $coupon_result->vat_checkout_value;
                $result_array['total_vat_checkout_value'] = $coupon_result->total_vat_checkout_value;
                $result_array['bucket_value'] = isset($coupon_result->bucket_value) ? $coupon_result->bucket_value : 0;
                $result_array['new_ci_used'] = isset($coupon_result->bucket_value) ? $coupon_result->bucket_value : 0;
                //$result_array['bucket_value'] = 50;
            } else if (isset($appalne_decode_resp->code) && isset($appalne_decode_resp->message)) {
                $result_array["code"] = $appalne_decode_resp->code;
                $result_array["message"] =  $appalne_decode_resp->message;               
            }
        }
        return $result_array;
    }

    /**
     * Preapare applane data for shop buy 100% card.
     * @param array $data
     * @return array
     * [{"$insert":[{"citizen_id":{"_id":"30038"},"do_transaction":"With credits","offer_id":{"_id":"553f7574625f0756712833c2"},"status":"Initiated"}],"$collection":"avail_coupn","$fields":{"checkout_value":1,"vat_checkout_value":1,
     * "total_vat_checkout_value":1,"status":1,"payable":1,"transaction_id":1,"discount":1,"coupon_value":1}}]
     */
    public function prepareApplaneDataToBuyCard($data)
    {
        $data = (object)array(
            '$insert'=> array((object)array(
                        'citizen_id'=>(object)array('_id'=>(string)$data['user_id']),
                        'do_transaction'=>$data['do_transaction'],
                        'offer_id'=>(object)array('_id'=>(string)$data['offer_id']),
                        'status'=>$data['status']
                      )),
            '$collection'=>self::SIX_CONTINENT_COUPON_COLLECTION,
            '$fields'=>(object)array('checkout_value'=> 1, 'vat_checkout_value'=>1, 'total_vat_checkout_value'=>1, 'status'=>1, 'payable'=>1, 'transaction_id'=>1, 'discount'=>1, 'coupon_value'=>1,'bucket_value'=>1),
            );
        $response_data = json_encode(array($data));
        return $response_data; 
    }
    
    /**
     * update status in transaction system when a user purchasing 100% card
     * @param string $transaction_id
     * @param string $applane_status
     */
    public function updateShoppingCardStatus($transaction_id, $applane_status, $handler) {
        $data            = $this->prepareApplaneDataupdateShoppingCardStatus($transaction_id, $applane_status);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $url_update    = self::URL_UPDATE;
        $query_update  = self::QUERY_UPDATE;
        $applane_resp  = $applane_service->callApplaneService($data, $url_update, $query_update);
        $this->writeAllLogs($handler, 'Applane Request: '.$data, 'Applane response: '.$applane_resp);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';        
    }
    
    /**
     * prepare applane data for update status of 100% shopping card
     * @param string $transaction_id
     * @param string $applane_status(Approved/Rejected)
     */
    public function prepareApplaneDataupdateShoppingCardStatus($transaction_id, $applane_status) {
        $data = (object)array(
            '$update'=> array(
                      (object)array('_id'=>$transaction_id, '$set'=>(object)array('status'=>$applane_status))
                      ),
            '$collection'=>self::SIX_CONTINENT_COUPON_COLLECTION,
            '$fields'=>(object)array('status'=>1),
            );
        $response_data = json_encode(array($data));
        return $response_data;        
    }
    
    /**
     * Shop subscription update
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function onShopSubscriptionAddAction($shop_id)
    {
        $insert_id = 0;
        $data = $this->prepareApplaneDataStoreSubscriptionUpdate($shop_id);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $action_insert = self::ACTION_INSERT;
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $final_data = $applane_service->getMongoDataFormatInsert($data, self::TRANSACTION_COLLECTION, $action_insert);
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        //$applane_resp = '{"response":{"sixc_transactions":{"$insert":[{"transaction_type_id":{"_id":"553209267dfd81072b176bb8","code":"FTCOME","name":"Monthly Subscription","sub_code":"COM","description2":"IN ABBONAMENTO MENSILE","description1":"ACQ. PACCHETTO PUBBLICITARIO 39EU","vat":5},"shop_id":{"_id":"1495","name":"Laura Deangelis"},"status":"Approved","Value":39,"total_income":39,"_id":"5541ec6d92fecbd07818e889","id":"1430383725067","date":"2015-04-30T08:48:45.067Z","discount_details":[{"card_no":"39 Debit","type":{"_id":"553fd25eee6b086c40163fea","name":"39 Debit"},"balance_amount":0,"_id":"5541ec6d92fecbd07818e88a"}],"is_freeze":true,"__history":{"__createdOn":"2015-04-30T08:48:45.099Z","__lastUpdatedOn":"2015-04-30T08:48:45.099Z","__createdBy":{"_id":"536a1da7d386e802007a28da"},"__lastUpdatedBy":{"_id":"536a1da7d386e802007a28da"}}}]}},"status":"ok","code":200,"serverTime":72,"serviceLogId":"5541ec6d92fecbd07818e887"}';
        $appalne_decode_resp = json_decode($applane_resp);
        //get insert id
        if(isset($appalne_decode_resp->response->sixc_transactions)){
        $res_data = $appalne_decode_resp->response->sixc_transactions;
        $res_data2 = (array)$res_data;
         if(isset($res_data2['$insert'][0]->_id)){
            $insert_id = $res_data2['$insert'][0]->_id;
         }
        }
        
        //write log
        $handler = $this->container->get('monolog.logger.subscription_log');
        $applane_service->writeAllLogs($handler, $final_data, $applane_resp); 
        //end log
        return $insert_id;
    }
    
    /**
     * Preapare applane data
     * @param array $data
     * @return array
     */
    public function prepareApplaneDataStoreSubscriptionUpdate($shop_id)
    {
        $subscription_fee = $this->container->getParameter('subscription_fee'); //reg fee
        $subscription_fee = $subscription_fee / 100;
        $transaction_type_id = array(
          "_id" => "553209267dfd81072b176bb8"  
        );
        
        $shop_id = array(
          "_id" => (string)$shop_id  
        );
        
        $response_data = array(
            'transaction_type_id' => $transaction_type_id,
            'shop_id' => $shop_id,
            'status' => "Approved",
            'transaction_value' => $subscription_fee,
            'checkout_value' => $subscription_fee
            );      
        return $response_data ;
    }
    
     /**
     * Get shop revenue from applane
     * @param int $shopid
     * @return int
     */
    public function getShopRevenueFromApplaneByDate($shopid){
        //$shopid = '551e6792236f510813100730';
        $data = $this->prepareGetShopRevenueDataByDate($shopid);
        //$data = '{"$collection":"sixc_transactions","$filter":{"shop_id":"551e6792236f510813100730"},"$group":{"_id":null,"total_income":{"$sum":"$total_income"},"payble_value":{"$sum":"$payble_value"},"new_upto_50_value":{"$sum":"$new_upto_50_value"},"checkout_value":{"$sum":"$checkout_value"},"$fields":false}}';
        $api = 'query';
        $queryParam = 'query';
        $response = $this->callApplaneService($data, $api, $queryParam);
        //write log
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.recurring');
        $monolog_data_request = "getShopRevenueFromApplane Request Data:".json_encode($data);
        $monolog_data_response = "getShopRevenueFromApplane Response Data:".$response;
        $applane_service->writeAllLogs($handler, $monolog_data_request, $monolog_data_response); 
        
        $decode_data = json_decode($response);
        //get total income
        $revenue = 0;
        if(isset($decode_data->response->result[0]->total_income)){
             $revenue = $decode_data->response->result[0]->total_income;
        }
        return $revenue;
    }

    /**
     * Prepare data
     * @param int $shopid
     * @return string
     */
    public function prepareGetShopRevenueDataByDate($shopid){
        $shopid = (string)$shopid;
        $group = array(
            '_id'=>'',
            'total_income' => (object)array('$sum' => '$total_income'),
            'payble_value' => (object)array('$sum' => '$payble_value'),
            'new_upto_50_value' => (object)array('$sum' => '$new_upto_50_value'),
            'checkout_value' => (object)array('$sum' => '$checkout_value')
            );
        $collection_data =  'sixc_transactions';
        $filter_data = (object)array('shop_id' => (string)$shopid,
                                     'date' => (object)array('$gte' => self::SHOP_INCOME_START_DATE),
                                     'transaction_type_id' => (object)array(
                                        // '$in' => array('553209267dfd81072b176bba','553209267dfd81072b176bbc','553209267dfd81072b176bc0')
                                         '$in' => array('553209267dfd81072b176bba','553209267dfd81072b176bbc') //opening balance removed
                                     )
            );
        
        $final_data = array(
            '$collection' => $collection_data,
            '$filter' => $filter_data,
            '$group' => $group,
            '$fields' => false
        );
        
       return json_encode($final_data);

    }
    
    /**
     * Get user infos from applane
     * @param int $userid
     * @return string
     */
    public function getUsersInfoFromApplane($userid){
        $data = $this->prepareGetUserInfoData($userid);
        $api = 'query';
        $queryParam = 'query';
        $response = $this->callApplaneService($data, $api, $queryParam);
        $decode_data = json_decode($response);
        return $decode_data;
    }
    
    /**
     * Preapare applane request to fetch users
     * @return type
     */
    public function prepareGetUserInfoData($userids)
    {
        $collection_data =  'sixc_citizens';
        $filter_data = (object)array( '_id' => (object)array(
                                         //'$in' => array('30310')
                                        '$in' => $userids 
                                     ));
        
        $final_data = array(
            '$collection' => $collection_data,
            '$filter' => $filter_data,
            '$fields' => false
        );
       return json_encode($final_data);
    }
    
      /**
     * Get shops infos from applane
     * @param int $shopid
     * @return string
     */
    public function getShopsInfoFromApplane($shopid){
        $data = $this->prepareGetShopInfoData($shopid);
        $api = 'query';
        $queryParam = 'query';
        $response = $this->callApplaneService($data, $api, $queryParam);
        $decode_data = json_decode($response);
        return $decode_data;
    }
    
    /**
     * Preapare applane request to fetch shops
     * @return type
     */
    public function prepareGetShopInfoData($shopids)
    {
        $collection_data =  'sixc_shops';
        $filter_data = (object)array( '_id' => (object)array(
                                         //'$in' => array('1','2','65202')
                                         '$in' => $shopids 
                                     ));
        
        $final_data = array(
            '$collection' => $collection_data,
            '$filter' => $filter_data,
            '$fields' => false
        );
       return json_encode($final_data);
    }
    
    /**
     *  function for calling the applane services through the curl service
     * @param type $data
     * @param type $api
     * @param type $queryParam
     * @return type
     */
    public function callApplaneServiceWithParams($api, $queryParams) {

        $response = $call_type = '';
        try {
            $call_type = $this->container->getParameter('tx_system_call'); //get parameters for applane calls.
        } catch (\Exception $ex) {
            
        }

        if ($call_type == 'APPLANE') {
            $applane_user_token = $this->container->getParameter('applane_user_token');
            $serviceUrl = $this->container->getParameter('base_applane_url') . $api;
            $client = new CurlRequestService();
            $response_structs = $client->setUrl($serviceUrl)
                    ->setHeader('content-type', 'application/x-www-form-urlencoded');

            foreach ($queryParams as $key => $value) {
                $response_structs->setParam($key, $value);
            }
            $response = $response_structs->setParam('code', $applane_user_token)
                    ->send()
                    ->getResponse();
            //write logs for request and response.
            try {
                $applane_service = $this->container->get('appalne_integration.callapplaneservice');
                //$applane_service->writeTransactionLogs($data, $response);            
            } catch (\Exception $ex) {
                
            }
        }
        return $response;
    }
    
    /**
     *  function for getting the offer details from applane based on offer id
     * @param type $offer_id
     * @return type
     */
    public function getOffersDetails($offer_id) {
        $applane = new ApplaneController();
        $applane_data = $this->prepareApplaneDataForOffer($offer_id);
        $offerJson = $applane->process('query', array('query' => $applane_data));
        $offers = json_decode($offerJson, true);
        $offer_details = isset($offers['response']['result']) ? $offers['response']['result'] : array();
        $offer_detail = isset($offer_details[0]) ? $offer_details[0] : array();
        return $offer_detail;
    }

    /**
     *  function for preapring the data for offer details 
     * @param type $offer_id
     * @return type
     */
    private function prepareApplaneDataForOffer($offer_id) {
        $data = array();
        $data['$collection'] = self::OFFERS_COLLECTION;
        $data['$filter'] = (object) array('_id' => $offer_id);
        $data = json_encode($data);
        return $data;
    }

    /**
     * initiate the transaction on transaction system
     * @param type $user_id
     * @param type $ci_used
     * @param type $checkout_value
     * @param type $app_name
     * @param type $connect_transaction_id
     * @param type $total_amount
     * @param type $payble_amount
     * @param type $applane_status
     */
    public function initiateConnectTransaction($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status) {
        $connect_sixthcontinent_service = $this->container->get('sixth_continent_connect.connect_app');
        $final_data = $this->prepareApplaneConnectTransaction($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status);
        //getting the varibles from the interface
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $applane_insert_id = 0;
        $applane_resp = $this->callApplaneService($final_data, $url_update, $query_update);
        $decoded_data = json_decode($applane_resp);
        if (isset($decoded_data->code)) {
            if ($decoded_data->code == ApplaneConstentInterface::APPLANE_SUCCESS_CODE) {
                $applane_res = (array)$response = $decoded_data->response->sixc_transactions;
                $applane_insert_id = $applane_res['$insert'][0]->_id;
            }
        }
        $connect_sixthcontinent_service->__createLog('Applane response'. $applane_resp);
        return $applane_insert_id;
    }
    
    //[{"$insert":[{"transaction_type_id":{"_id":"558b9b6b3176a9736e7745ea"},"status":"Initiated","citizen_id":{"_id":"551ea7eafb6cfebf1311de28"},"transaction_value":12,"new_upto_50_value":5,"payble_value":7,"checkout_value":1.2,"app_name":"test","scct_id":1234}],"$collection":"sixc_transactions"}]
    public function prepareApplaneConnectTransaction($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status) {
        $connect_sixthcontinent_service = $this->container->get('sixth_continent_connect.connect_app');
        $data = (object)array(
            '$insert'=> array(
                      'transaction_type_id'=>(object)array('_id'=>ApplaneConstentInterface::CONNECT_TRANSACTION_ID),
                      'status'=>$applane_status,
                      'citizen_id'=>(object)array('_id'=>(string)$user_id),
                      'transaction_value'=>$total_amount,
                      'new_upto_50_value'=>$ci_used,
                      'payble_value'=>$payble_amount,
                      'checkout_value'=>$checkout_value,
                      'app_name'=>$app_name,
                      'scct_id'=>$connect_transaction_id
                      ),
            '$collection'=>self::TRANSACTION_COLLECTION,
            );
        $response_data = json_encode(array($data));
        $connect_sixthcontinent_service->__createLog('Applane request'. $response_data);
        return $response_data;             
    }
    
    /**
     * Updaate the transaction status on applane
     * @param string $transaction_system_id
     * @param string $applane_status
     * @return boolean
     */
    public function UpdateConnectTransactionStatus($transaction_system_id, $applane_status) {
        $connect_sixthcontinent_service = $this->container->get('sixth_continent_connect.connect_app');
        $connect_sixthcontinent_service->__createLog('Entering to class [Utility\ApplaneIntegrationBundle\Services\ApplaneCallService] and function [UpdateConnectTransactionStatus] Transaction status with txid:'.$transaction_system_id. ' status:'.$applane_status);
        $final_data = $this->prepareConnectTransactionStatusUpdate($transaction_system_id, $applane_status);
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $applane_resp = $this->callApplaneService($final_data, $url_update, $query_update);
        $connect_sixthcontinent_service->__createLog('Applane response'. $applane_resp);
        return true;
    }
    
    /**
     * prepare the data of applane transaction status
     * @param string $transaction_system_id
     * @param string $applane_status
     * {"$update":{"_id":"558bd70838104fbb76735ea4","$set":{"status":"Approved"}},"$collection":"sixc_transactions"}
     */
    public function prepareConnectTransactionStatusUpdate($transaction_system_id, $applane_status) {
        $connect_sixthcontinent_service = $this->container->get('sixth_continent_connect.connect_app');
        $data = (object)array(
            '$update'=> (object)array(
                      '_id'=>$transaction_system_id,'$set'=>(object)array('status'=>$applane_status)
                      
                      ),
            '$collection'=>self::TRANSACTION_COLLECTION,
            );
        $response_data = json_encode($data);
        $connect_sixthcontinent_service->__createLog('Applane request'. $response_data);
        return $response_data;  
    }
    
    /**
<<<<<<< HEAD
     * function for getting the details for shopping card up to 100% from applane
     * @param type $transaction_id
     */
    public function getShoppingCardUPTO100Details($transaction_id) {
        try {
            $this->_log('[Entering into class Utility/ApplaneIntegrationBundle/Services/ApplaneCallService.php and function getShoppingCardUPTO100Details with transaction_id:]'.$transaction_id,'paypal_shopping_logs');
            $data = $this->prepareApplaneDataShoppingCardDetails($transaction_id);
            $this->_log('[Query hit applane for the transaction details]'.$data,'paypal_shopping_logs');
            $applane_service = $this->container->get('appalne_integration.callapplaneservice');
            //getting the varibles from the interface
            $url_update = self::URL_QUERY;
            $query_update = self::URL_QUERY;
            
            $applane_resp = $applane_service->callApplaneService($data, $url_update, $query_update);
            $this->_log('[Response from applane for the transaction_id:'.$transaction_id.':'.  json_encode($applane_resp).']','paypal_shopping_logs');
            $appalne_decode_resp = json_decode($applane_resp, true);
            $final_data = $this->prepareShoppingCardDetail($appalne_decode_resp);
            $this->_log('[Data prepare from applane response for the transaction_id:'.$transaction_id.':'.  json_encode($final_data).']','paypal_shopping_logs');
            return $final_data;
        } catch (\Exception $ex) {
            $this->_log('[Exception occured for the transaction_id:'.$transaction_id.':'.  $ex->getMessage().']','paypal_shopping_logs');
            $final_data = array();
            return $final_data;
        }
    }

    /**
     *  function for preparing the query for the shopping card details
     * @param type $transaction_id
     * @return type
     */
    private function prepareApplaneDataShoppingCardDetails($transaction_id) {
        $data = array();
        $data['$collection'] = ApplaneConstentInterface::SIX_CONTINENT_COUPON_COLLECTION;
        $data['$parameters'] = (object) array();
        $data['$filter'] = array("_id" => $transaction_id);
        $final_data = json_encode($data);
        return $final_data;
    }

    /**
     *  function for preparing the data for the shopping card up to 100%
     * @param type $appalne_decode_resp
     * @return type
     */
    public function prepareShoppingCardDetail($appalne_decode_resp) {
        $applane_resp = isset($appalne_decode_resp['response']['result'][0]) ? $appalne_decode_resp['response']['result'][0] : array();
        $final_data = array();
        $final_data['total_value'] = isset($applane_resp['coupon_value']) ? $applane_resp['coupon_value'] : 0;
        $final_data['shop_contribution'] = isset($applane_resp['discount']) ? $applane_resp['discount'] : 0;
        $final_data['sixth_contribution'] = isset($applane_resp['bucket_value']) ? $applane_resp['bucket_value'] : 0;
        $final_data['paybal_amount'] = isset($applane_resp['payable']) ? $applane_resp['payable'] : 0;
        $final_data['card_number'] = isset($applane_resp['transaction_id']['id']) ? $applane_resp['transaction_id']['id'] : '';
        $final_data['transaction_id'] = isset($applane_resp['transaction_id']['_id']) ? $applane_resp['transaction_id']['_id'] : '';
        return $final_data;
    }
    
    /**
     *  function for writting the logs
     * @param type $sMessage
     */
    public function _log($sMessage,$handler){
        $monoLog = $this->container->get('monolog.logger.'.$handler);
        $monoLog->info($sMessage);
    }

    /**
     * Get SHop followers
     * @return type
     */
    public function getShopsFollowersInfoFromApplane($shopids)
    {
        $data = $this->prepareGetShopFollowersInfoData($shopids);
        $api = self::QUERY_CODE;
        $queryParam = self::QUERY_CODE;
        $response = $this->callApplaneService($data, $api, $queryParam);
        $decode_data = json_decode($response);
        return $decode_data;
    }
    
     /**
     * Preapare applane request to fetch shops
     * @return type
     */
    public function prepareGetShopFollowersInfoData($shopids)
    {
        $collection_data =  self::SIX_CONTINENT_CUSTOMER_CHOICE;
        $filter_data = (object)array( 'shop_id' => (object)array(
                                        // '$in' => array('1495', '23848')
                                         '$in' => $shopids
                                     ),
             'is_following' => true);
        
        $final_data = array(
            '$collection' => $collection_data,
            '$filter' => $filter_data,
            '$fields' => false
        );
       return json_encode($final_data);
    }
    
     /**
     * Get SHop followers
     * @return type
     */
    public function getShopsFavsInfoFromApplane($shopids)
    {
        $data = $this->prepareGetShopFavsInfoData($shopids);
        $api = self::QUERY_CODE;
        $queryParam = self::QUERY_CODE;
        $response = $this->callApplaneService($data, $api, $queryParam);
        $decode_data = json_decode($response);
        return $decode_data;
    }
    
     /**
     * Preapare applane request to fetch shops
     * @return type
     */
    public function prepareGetShopFavsInfoData($shopids)
    {
        $collection_data =  self::SIX_CONTINENT_CUSTOMER_CHOICE;
        $filter_data = (object)array( 'shop_id' => (object)array(
                                        // '$in' => array('1495', '23848')
                                         '$in' => $shopids
                                     ),
             'is_favourate' => true);
        
        $final_data = array(
            '$collection' => $collection_data,
            '$filter' => $filter_data,
            '$fields' => false
        );
       return json_encode($final_data);
    }
    
    /**
     * Get SHop followers
     * @return type
     */
    public function getShopUsersFollowersInfoFromApplane($ids)
    {
        $data = $this->prepareGetShopUserFollowersInfoData($ids);
        $api = self::QUERY_CODE;
        $queryParam = self::QUERY_CODE;
        $response = $this->callApplaneService($data, $api, $queryParam);
        $decode_data = json_decode($response);
        return $decode_data;
    }
    
     /**
     * Preapare applane request to fetch shops
     * @return type
     */
    public function prepareGetShopUserFollowersInfoData($ids)
    {
        $collection_data =  self::SIX_CONTINENT_CUSTOMER_CHOICE;
        $filter_data = (object)array( '_id' => (object)array(
                                        // '$in' => array('1495', '23848')
                                         '$in' => $ids
                                     ),
             'is_following' => true);
        
        $final_data = array(
            '$collection' => $collection_data,
            '$filter' => $filter_data,
            '$fields' => false
        );
       return json_encode($final_data);
    }
    
    /**
     * Get SHop followers
     * @return type
     */
    public function getShopUsersFavsInfoFromApplane($ids)
    {
        $data = $this->prepareGetShopUserFavsInfoData($ids);
        $api = self::QUERY_CODE;
        $queryParam = self::QUERY_CODE;
        $response = $this->callApplaneService($data, $api, $queryParam);
        $decode_data = json_decode($response);
        return $decode_data;
    }
    
     /**
     * Preapare applane request to fetch shops
     * @return type
     */
    public function prepareGetShopUserFavsInfoData($ids)
    {
        $collection_data =  self::SIX_CONTINENT_CUSTOMER_CHOICE;
        $filter_data = (object)array( '_id' => (object)array(
                                        // '$in' => array('1495', '23848')
                                         '$in' => $ids
                                     ),
             'is_favourate' => true);
        
        $final_data = array(
            '$collection' => $collection_data,
            '$filter' => $filter_data,
            '$fields' => false
        );
       return json_encode($final_data);
    }
    
    /**
     * Buy Ecommerce Product
     */
    public function buyEcommerceProductCard($applane_data)
    {
        $buyEcommerceProductService = $this->container->get('buy_ecommerce_product.ecommerce');
        $result_array    = array('transaction_id'=>'');
        $result_array["code"] = 1065;
        $result_array["message"] =  'TRANSACTION_IS_NOT_INITIATED_IN_TRANSACTION_SYSTEM';
        $final_data = $applane_data;
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $url_update   = self::SERVICE."/".self::BUYNOW;
        $query_update = self::PARAMETERS;
        $applane_resp = $applane_service->callApplaneService($final_data, $url_update, $query_update);
        $buyEcommerceProductService->__createLog('Applane request '.$final_data);
        $buyEcommerceProductService->__createLog('Applane response: '.$applane_resp);
        $appalne_decode_resp = json_decode($applane_resp);
        if (isset($appalne_decode_resp->code)) {
            if ($appalne_decode_resp->code == ApplaneConstentInterface::APPLANE_SUCCESS_CODE) {
                $transaction   = $appalne_decode_resp->response;
                $result_array['transaction_id'] = $transaction->_id;
                $result_array['cash_amount']    = $transaction->payable;
                $result_array['total_amount']   = $transaction->coupon_value;
                $result_array['discount']       = $transaction->discount;
                $result_array['transaction_inner_id'] = $transaction->transaction_id->id;
                $result_array['checkout_value']       = (isset($transaction->checkout_value)) ? $transaction->checkout_value : 0;
                $result_array['vat_checkout_value']       = (isset($transaction->vat_checkout_value)) ? $transaction->vat_checkout_value : 0;
                $result_array['total_vat_checkout_value'] = (isset($transaction->total_vat_checkout_value)) ? $transaction->total_vat_checkout_value : 0;
                $result_array['bucket_value'] = isset($transaction->bucket_value) ? $transaction->bucket_value : 0;
                $result_array['order_id'] = (isset($transaction->order_id->_id)) ? $transaction->order_id->_id : '';
                //$result_array['bucket_value'] = 50;
            } else if (isset($appalne_decode_resp->code) && isset($appalne_decode_resp->message)) {
                $result_array["code"] = $appalne_decode_resp->code;
                $result_array["message"] =  $appalne_decode_resp->message;               
            }
        }
        return $result_array;
    }
    
     /**
     * update status in transaction system when a user purchasing ecommerce product
     * @param string $transaction_id
     * @param string $applane_status
     */
    public function updateEcommerceProductStatus($transaction_id, $applane_status, $payment_status, $paypal_sender_transaction_id, $sender_paypal_email, $paypal_reciver_transaction_id) {
        $buyEcommerceProductService = $this->container->get('buy_ecommerce_product.ecommerce');
        $data            = $this->prepareApplaneDataUpdateEcommerceProductStatus($transaction_id, $applane_status, $payment_status, $paypal_sender_transaction_id, $sender_paypal_email, $paypal_reciver_transaction_id);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $url_update    = self::URL_UPDATE;
        $query_update  = self::QUERY_UPDATE;
        $applane_resp  = $applane_service->callApplaneService($data, $url_update, $query_update);
        $buyEcommerceProductService->__createLog('Applane Request: '.$data, 'Applane response: '.$applane_resp);
        $appalne_decode_resp = json_decode($applane_resp);
        $status = isset($appalne_decode_resp->status)?$appalne_decode_resp->status:'error';        
    }
    
    /**
     * prepare applane data for update status of ecommerce product
     * @param string $transaction_id
     * @param string $applane_status(Approved/Rejected)
     */
    public function prepareApplaneDataUpdateEcommerceProductStatus($transaction_id, $applane_status, $payment_status, $paypal_sender_transaction_id, $sender_paypal_email, $paypal_reciver_transaction_id) {
        $data = (object)array(
            '$update'=> array(
                      (object)array(
                          '_id'=>$transaction_id, 
                          '$set'=>(object)array('status'=>$applane_status, 
                                                'payment_status_id'=> array('$query' => array('status'=>$payment_status)),
                                                'paypal_sender_transaction_id' => $paypal_sender_transaction_id,
                                                'sender_paypal_email' => $sender_paypal_email,
                                                'paypal_reciver_transaction_id' => $paypal_reciver_transaction_id
                                               )
                          )
                      ),
            '$collection'=>self::SIX_CONTINENT_COUPON_COLLECTION,
            '$fields'=>(object)array('status'=>1),
            );
        $response_data = json_encode(array($data));
        return $response_data;        
    }
    
    /**
     * get offer detail service.
     * @param string $offer_id
     * @param string $handler
     */
    public function getOfferDetail($offer_id, $handler) {
        $result_data = array('_id'=>'', 'value'=>'', 'discount'=>'');
        $data            = $this->prepareApplaneOfferDetail($offer_id);
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        //getting the varibles from the interface
        $url_query    = self::URL_QUERY;
        $applane_resp  = $applane_service->callApplaneService($data, $url_query, $url_query);
        $this->writeAllLogs($handler, 'Applane Request for Offer deatil: '.$data, 'Applane response for offer detail: '.$applane_resp);
        $appalne_decode_resp = json_decode($applane_resp);
        if (isset($appalne_decode_resp->response)) {
            if (isset($appalne_decode_resp->response->result) && (isset($appalne_decode_resp->response->result[0]))) {
                $result_data['_id'] = $appalne_decode_resp->response->result[0]->_id;
                $result_data['value'] = $appalne_decode_resp->response->result[0]->value;
                $result_data['discount'] = $appalne_decode_resp->response->result[0]->discount;
            }
        }
        return $result_data;
    }
    
    /**
     * get offer detail
     * @param type $offer_id
     * @return json string
     */
    public function prepareApplaneOfferDetail($offer_id) {
        $data = (object)array(
            '$collection'=>self::OFFERS_COLLECTION,
            '$filter'=>(object)array('_id'=>(string)$offer_id),
            '$fields'=>(object)array('discount'=>1, 'value'=>1),
            );
        $response_data = json_encode($data);
        return $response_data; 
    }
    
    /**
     * initiate the transaction on transaction system for tamoil
     * @param type $user_id
     * @param type $ci_used
     * @param type $checkout_value
     * @param type $app_name
     * @param type $connect_transaction_id
     * @param type $total_amount
     * @param type $payble_amount
     * @param type $applane_status
     */
    public function initiateTamoilOfferPurchaseTransaction($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status) {
        $offer_purchase_sixthcontinent_service = $this->container->get('sixth_continent_connect.purchasing_offer_transaction');
        $final_data = $this->prepareApplaneTamoilOfferTransaction($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status);
        //getting the varibles from the interface
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $transaction_data = array('_id'=>0, 'id'=>'');
        $applane_resp = $this->callApplaneService($final_data, $url_update, $query_update);
        $decoded_data = json_decode($applane_resp);
        if (isset($decoded_data->code)) {
            if ($decoded_data->code == ApplaneConstentInterface::APPLANE_SUCCESS_CODE) {
                $applane_res = (array)$response = $decoded_data->response->sixc_transactions;
                $transaction_data['_id'] = $applane_res['$insert'][0]->_id;
                $transaction_data['id']  = $applane_res['$insert'][0]->id;
            }
        }
        $offer_purchase_sixthcontinent_service->__createLog('Applane offer purchase initiate response: '. $applane_resp);
        return $transaction_data;
    }
    
    //[{"$insert":[{"transaction_type_id":{"_id":"558b9b6b3176a9736e7745ea"},"status":"Initiated","citizen_id":{"_id":"551ea7eafb6cfebf1311de28"},"transaction_value":12,"new_upto_50_value":5,"payble_value":7,"checkout_value":1.2,"app_name":"test","scct_id":1234}],"$collection":"sixc_transactions"}]
    public function prepareApplaneTamoilOfferTransaction($user_id, $ci_used, $checkout_value, $app_name, $connect_transaction_id, $total_amount, $payble_amount, $applane_status) {
        $offer_purchase_sixthcontinent_service = $this->container->get('sixth_continent_connect.purchasing_offer_transaction');
        $data = (object)array(
            '$insert'=> array(
                      'transaction_type_id'=>(object)array('_id'=>ApplaneConstentInterface::CONNECT_TRANSACTION_ID),
                      'status'=>$applane_status,
                      'citizen_id'=>(object)array('_id'=>(string)$user_id),
                      'transaction_value'=>$total_amount,
                      'new_upto_50_value'=>$ci_used,
                      'payble_value'=>$payble_amount,
                      'checkout_value'=>$checkout_value,
                      'app_name'=>$app_name,
                      'scct_id'=>$connect_transaction_id
                      ),
            '$collection'=>self::TRANSACTION_COLLECTION,
            );
        $response_data = json_encode(array($data));
        $offer_purchase_sixthcontinent_service->__createLog('Applane offer purchase request: '. $response_data);
        return $response_data;             
    }
    
    /**
     * Updaate the transaction status on applane
     * @param string $transaction_system_id
     * @param string $applane_status
     * @return boolean
     */
    public function updateTamoilOfferPurchaseTransaction($transaction_system_id, $applane_status) {
        $offer_sixthcontinent_service = $this->container->get('sixth_continent_connect.purchasing_offer_transaction');
        $offer_sixthcontinent_service->__createLog('Entering to class [Utility\ApplaneIntegrationBundle\Services\ApplaneCallService] and function [updateTamoilOfferPurchaseTransaction] Transaction status with txid:'.$transaction_system_id. ' status:'.$applane_status);
        $final_data = $this->prepareTamoilTransactionStatusUpdate($transaction_system_id, $applane_status);
        $url_update = self::URL_UPDATE;
        $query_update = self::QUERY_UPDATE;
        $applane_resp = $this->callApplaneService($final_data, $url_update, $query_update);
        $offer_sixthcontinent_service->__createLog('Applane transaction update response: '. $applane_resp);
        return true;
    }
    
    /**
     * prepare the data of applane transaction status
     * @param string $transaction_system_id
     * @param string $applane_status
     * {"$update":{"_id":"558bd70838104fbb76735ea4","$set":{"status":"Approved"}},"$collection":"sixc_transactions"}
     */
    public function prepareTamoilTransactionStatusUpdate($transaction_system_id, $applane_status) {
        $offer_sixthcontinent_service = $this->container->get('sixth_continent_connect.purchasing_offer_transaction');
        $data = (object)array(
            '$update'=> (object)array(
                      '_id'=>$transaction_system_id,'$set'=>(object)array('status'=>$applane_status)
                      
                      ),
            '$collection'=>self::TRANSACTION_COLLECTION,
            );
        $response_data = json_encode($data);
        $offer_sixthcontinent_service->__createLog('Applane transaction update request: '. $response_data);
        return $response_data;  
    }
}
