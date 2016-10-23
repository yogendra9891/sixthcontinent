<?php
namespace Acme\GiftBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Define the soap client
 * @author admin
 *
 */
class ClientController extends Controller
{
   
    /*
    *  Soap client action for gift sending...
    *  parameters : post data
    *  
    */
    public function sendgiftAction()
    {
    	$request = $this->getRequest();
    	$encode_object = $request->get('reqObj');    	
   	$data = json_decode($encode_object, true);
        unset($data['reqObj']);

   /*     $data = array("MOVIMENTOADD"=>array('IDMOVIMENTO' => '1909124', 'IDCARD'=>'2', 'IDPDV'=>'3', 'DATA'=>'20141222120902', 'IMPORTODIGITATO'=>'4', 'CREDITOSTORNATO' => '5', 'RCUTI' => '6', 'SHUTI' =>'7' ,
        'PSUTI' => '8', 'GCUTI' => '9', 'GCRIM' => '10', 'MOUTI'=>'10' ));
        $data = (object)$data;
        $url =  $this->getRequest()->getUriForPath(''); 
        $url_server = $url.'/gift?wsdl'; // path for server

        $client = new \nusoap_client($url_server, true); // with the nusoapbundle
        $response = $client->call('MOVIMENTOADD', array($data)); // calling soap service method
       
        $error = $client->getError();
        if ($error)
        {
            echo "<h2>Constructor error</h2><pre>" . $error . "</pre>";
        }
        if ($client->fault) {
            echo "<h2>Fault</h2><pre>";
            print_r($response);
            echo "</pre>";
        }
        else {
            $error = $client->getError();
            if ($error) {
                echo "<h2>Error</h2><pre>" . $error . "</pre>";
            }
            else {
                echo "<h2>Working</h2>";
       //         echo $response;
            }
        }
        echo "<h2>Request</h2>";
        echo "<pre>" . htmlspecialchars($client->request, ENT_QUOTES) . "</pre>"; // getting soap request
        echo "<h2>Response</h2>";
        echo "<pre>" . htmlspecialchars($client->response, ENT_QUOTES) . "</pre>"; // getting soap response
        
        $msg = $client->response;
        $final_url = $this->getBaseUri(); //in web folder gift.wsdl
        $logpath =  "../errors.log";
	error_log($msg, 3, $logpath); //writing the logs..
                */
        exit;
    }
    
    	/**
	 * Function to retrieve current applications base URI for WSDL
	 */
	private function getBaseUri()
	{    // get the router context to retrieve URI information
		$context = $this->get('router')->getContext();
		// return scheme, host and base URL
		return $context->getScheme().'://'.$context->getHost().$context->getBaseUrl();
	}
	
}
?>
