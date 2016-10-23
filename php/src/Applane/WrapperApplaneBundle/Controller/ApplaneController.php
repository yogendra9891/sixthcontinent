<?php
namespace Applane\WrapperApplaneBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Utility\CurlBundle\Services\CurlRequestService;

class ApplaneController extends Controller
{
    protected  $base_applane_url = "http://beta.business.applane.com/rest/";
    
    protected  $applane_user_token = "5534ab36fa78adc424fea9e1";

    public function indexAction($name)
    {
        return $this->render('ApplaneWrapperApplaneBundle:Default:index.html.twig', array('name' => $name));
    }
    
    /**
     * execute the single query for transaction system integration
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postAppqueriesAction(Request $request)
    { 
        $query = $request->getContent();        
        $api = 'query';
        $queryParams[$api] = $query;
        $output = $this->process($api, $queryParams);        
        echo $output;
        exit;
    }
    
    /**
     * execute the single query for transaction system integration
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function offerDetailsAction(Request $request)
    { 
        $query = $request->getContent();        
        $api = 'query';
        $queryParams[$api] = $query;
        $output = $this->process($api, $queryParams);        
        echo $output;
        exit;
    }
    
    /**
     * execute the single query for transaction system integration
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function serviceAction(Request $request)
    { 
        $serviceName = $request->get('service_name');
        $queryStr = $request->query->all();
        $postData = $request->request->all();
        $queryParams = $postData+$queryStr;
        $api = 'service/'.$serviceName;
        $token = $this->_getContainer()->getParameter('applane_user_token');
        $output = $this->process($api, $queryParams, $token);        
        echo $output;
        exit;
    }
    
    
    /**
     * execute the batch queries
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postBatchqueriesAction(Request $request)
    { 
        $query = $request->getContent();        
        $api = 'batchquery';
        $queryParams['query'] = $query;
        $output = $this->process($api, $queryParams);   
        echo $output;
        exit;
    }
    
    /**
     * method for update
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function postAppupdatesAction(Request $request)
    {
        $query = $request->getContent();       
        $api = 'update';
        $queryParams[$api] = $query;
        $output = $this->process($api, $queryParams);
        echo $output;
        exit;
    }
    
    public function postInvokesAction(Request $request)
    { 
        $query = $request->getContent();
        $fde_serialize = $this->decodeData($query);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        $api = 'invoke';
        $queryParams['function'] = isset($de_serialize['function']) ? $de_serialize['function']: '';
        $queryParams['parameters'] = isset($de_serialize['parameters']) ? json_encode($de_serialize['parameters']): '';
        $output = $this->process($api, $queryParams);   
        echo $output;
        exit;
    }
    
    /**
     * call the applane service.
     * @param string $data
     * @param string $api
     * @param string $queryParam
     * @return json
     */
    public function process($api, array $queryParams, $token='')
    {
        $container = $this->_getContainer();
        $serviceUrl = $container->getParameter('base_applane_url') . $api;
        if(!empty($token)){
            $queryParams['code'] = $token;
        }else{
            $queryParams['code'] = $container->getParameter('applane_user_token');
        }
        $client = new CurlRequestService();
        $response = $client->setUrl($serviceUrl)
                ->setRequestType('POST')
//                ->setParam('code', $this->container->getParameter('applane_user_token'))
                ->setParams($queryParams)
                ->setHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->send()
                ->getResponse();
        //write logs for request and response.
        $this->_log('Request Data : '.json_encode($queryParams));
        $this->_log('Response Data : '.$response);
        
        return $response;
    }
    
    /**
     * Decode tha data
     * @param string $req_obj
     * @return array
     */
    protected function decodeData($req_obj) {
        //get serializer instance
        $serializer = new Serializer(array(), array(
            'json' => new JsonEncoder(),
            'xml' => new XmlEncoder()
        ));
        $jsonContent = $serializer->decode($req_obj, 'json');

        return $jsonContent;
    }
    
    /**
     * Get Url content
     * @param type $request
     * @return type
     */
    protected function getAppData(Request$request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeData($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
    
    public function _log($sMessage){
        $monoLog = $this->_getContainer()->get('monolog.logger.channel1');
        $monoLog->info($sMessage);
    }
    
    private function _getContainer(){
        global $kernel;
        return $this->container ? $this->container : $kernel->getContainer();
    }
    
}
