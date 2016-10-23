<?php

namespace Acme\GiftBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
//use Acme\GiftBundle\Services\GiftService as GiftService;
//use Doctrine\ORM\EntityManager as EntityManager;

class GiftController extends Controller
{
	
	/**
	 * Function to retrieve current applications base URI for WSDL
	 */
	private function getBaseUri()
	{    // get the router context to retrieve URI information
		$context = $this->get('router')->getContext();
		// return scheme, host and base URL
		return $context->getScheme().'://'.$context->getHost().$context->getBaseUrl();
	}
	
	/**
	 * Soap server method for handling the response.
	 */
    public function indexAction()
    {
        ini_set("soap.wsdl_cache_enabled", "0"); // disabling WSDL cache
        ini_set("soap.wsdl_cache_ttl","0");
        
        $request = $this->getRequest();
        $scheme = $request->getScheme();
        $host = $request->getHttpHost();
        $base = $this->get('request')->getBasePath();
        $final_url = $this->getBaseUri().'/gift.wsdl'; //in web folder gift.wsdl
        $server = new \SoapServer($final_url);
        $server->setObject($this->get('gift_service')); // object of service method

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=ISO-8859-1');

        ob_start();
        $server->handle();
        $response->setContent(ob_get_clean());
        
        return $response; // return the response
    }
    

    /**
     * search the gift card
     * @param post data
     * @return json string with status code
     */
    public function postSearchcardsAction(Request $request)
    {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if(isset($fde_serialize)){
           $de_serialize = $fde_serialize;
        } else {
           $de_serialize = $this->getAppData($request);
        }
        //Code end for getting the request
        
        $object_info = (object)$de_serialize; //convert an array into object.

    	// set default limit and range 20, 0.
    	$limit  = (isset($object_info->limit_size))  ? $object_info->limit_size : 20;
    	$offset = (isset($object_info->limit_start)) ? $object_info->limit_start : 0;
    	
    	//get entity manager object
    	$em = $this->getDoctrine()->getManager();
    	//fire the query in User Repository
    	$results = $em->getRepository('AcmeGiftBundle:Movimen')
    	              ->searchByCardDetail($object_info, $offset, $limit);
    	
    	//counts the records
    	$result_counts = $em->getRepository('AcmeGiftBundle:Movimen')
    	                    ->searchByCardDetailCount($object_info);
    	
    	$final_array = array('gift_cards'=>$results, 'count'=>$result_counts);
    	if (is_array($results)) { //handling the success case
    		$error_code = 101;
    		$message = 'SUCCESS';
    	} else { //handling the failure case
    		$error_code = 100;
    		$message = 'FAILURE';
    	}
    	$last_array = array('code'=>$error_code, 'message'=>$message, 'data'=>$final_array);
    	
    	//return the response (below we have ignored some fileds in response).
    	//return array('code'=>$error_code, 'message'=>$message, 'data'=>$final_array);
    	//exit;

    	$normalizer = new GetSetMethodNormalizer();
		$normalizer->setIgnoredAttributes(array('offset', 'errors', 'lastErrors', 'location', 'timezone'));
		$encoder    = new JsonEncoder();
		$serializer = new Serializer(array($normalizer), array($encoder));
		$json       = $serializer->serialize($last_array, 'json');
		return new Response($json);
    }
    
    /**
     * Decoding the json string to object
     * @param json string $encode_object
     * @return object $decode_object
     */
    public function decodeObjectAction($encode_object)
    {
    	$serializer = new Serializer(array(), array(
    			'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
    			'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
    	));
    	$decode_object = $serializer->decode($encode_object, 'json');
    	return $decode_object;
    }
    
    /**
     * method for decoding the raw data.
     * @param type $request
     * @return type
    */
    public function getAppData(Request $request)
    {
	$content = $request->getContent();
        $dataer = (object)$this->decodeObjectAction($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data; 
        return $req_obj;
    }
}