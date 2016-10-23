<?php
namespace UserManager\Sonata\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class CreateClientController extends ContainerAwareCommand
{
	
	
	/**
	 * Coomand configuration
	 * @see Symfony\Component\Console\Command.Command::configure()
	 */
	protected function configure()
	{
		$this
		->setName('acme:oauth-server:client:create')
		->setDescription('Creates a new client')
		->addOption(
				'redirect-uri',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Sets redirect uri for client. Use this option multiple times to set multiple redirect URIs.',
				null
		)
		->addOption(
				'grant-type',
				null,
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Sets allowed grant type for client. Use this option multiple times to set multiple grant types..',
				null
		);

	}

	/**
	 * Create client
	 * @param Request $request
	 */
	public function createclientAction(Request $request=null)
	{
		//initilise the data array
		$data = array();
		
		//get request object

                $freq_obj = $request->get('reqObj');
                $fde_serialize = $this->decodeData($freq_obj);

                if (isset($fde_serialize)) {
                    $de_serialize = $fde_serialize;
                } else {
                    $de_serialize = $this->getAppData($request);
                }
                $object_info = (object) $de_serialize; //convert an array into object.
                $required_parameter = array('redirect_url');
                //checking for parameter missing.
                $chk_error = $this->checkParamsAction($required_parameter, $object_info);
                if ($chk_error) {
      
                     $resp = array('code' => 100, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($this->miss_param), 'data' => $data);
                     echo json_encode($resp);
                     exit();
                }

                //call the deseralizer
		//$de_serialize = $this->decodeData($req_obj);
		if($de_serialize['redirect_url'] == ""){
			$resp = array('code'=>'100','msg'=>'URL_CAN_NOT_BE_EMPTY','data'=>array());
			$resp_encode = $this->encodeData($resp);
			echo $resp_encode;
			exit;
		}
		
		$this->configure();
		$clientManager = $this->getContainer()->get('fos_oauth_server.client_manager.default');
		$client = $clientManager->createClient();
		$client->setRedirectUris(array($de_serialize['redirect_url']));
		$client->setAllowedGrantTypes(array('token', 'authorization_code', 'password'));
		$clientManager->updateClient($client);

		$data = array(
				'client_id'     => $client->getPublicId(),
				'client_secret'     => $client->getSecret(),
				//'redirect_uri'  => $client->getRedirectUris(),
				'response_type' => 'code'
		);

//		$resp_encode = $this->encodeData($data);
//		echo $resp_encode;
//		exit;
                $resp_data = array('code' => '101', 'message' => 'SUCCESS', 'data' => $data);
                echo json_encode($resp_data);
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
	 * Decode tha data
	 * @param string $req_obj
	 * @return array
	 */
	public function decodeData($req_obj)
	{
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
	public function encodeData($req_obj)
	{
		//get serializer instance
		$serializer = new Serializer(array(), array(
				'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
				'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
		));
		$jsonContent = $serializer->encode($req_obj, 'json');
		return $jsonContent;
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