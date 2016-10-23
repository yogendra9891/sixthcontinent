<?php

namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Acme\DemoBundle\Form\ContactType;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
// use Acme\DemoBundle\Entity\UserMedia;
#use Acme\DemoBundle\Document\UserMedia;
#use Acme\DemoBundle\Document\Check;
use Doctrine\ORM\EntityManager;

use FOS\MessageBundle\Controller\MessageController as BaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerAware;
use FOS\MessageBundle\Provider\ProviderInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use JMS\Serializer\SerializerBuilder as JMSR;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Symfony\Component\Form\FormTypeInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use FOS\MessageBundle\EntityManager\ThreadManager;
use FOS\MessageBundle\ModelManager\ThreadManager as ThreadMsg;
use FOS\MessageBundle\DocumentManager\ThreadManager as ThreadMsgDoc;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DemoController extends Controller
{
    
    protected $user_media_path = '/uploads/users/media/original/';
    protected $user_media_path_thumb = 'uploads/users/media/thumb/';

    /**
     * @Route("/", name="_demo")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/hello/{name}", name="_demo_hello")
     * @Template()
     */
    public function helloAction($name)
    {
        return array('name' => $name);
    }

	
    
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
     * Create 
     */
    public function createclientAction()
    {
        $this->configure();
    	$clientManager = $this->getContainer()->get('fos_oauth_server.client_manager.default');
    	$client = $clientManager->createClient();
    	$client->setRedirectUris(array('http://www.example1.com'));
    	$client->setAllowedGrantTypes(array('token', 'authorization_code', 'password'));
    	$clientManager->updateClient($client);
    	
    	$data = array(
    	'client_id'     => $client->getPublicId(),
    	'client_secret'     => $client->getSecret(),
    	'redirect_uri'  => $client->getRedirectUris(),
    	'response_type' => 'code'
    	);
    	
    	//print_r($data);
    	//die;
    }

    /**
     * @Route("/contact", name="_demo_contact")
     * @Template()
     */
    public function contactAction(Request $request)
    {
        $form = $this->createForm(new ContactType());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $mailer = $this->get('mailer');
            $request->getSession()->getFlashBag()->set('notice', 'Message sent!');

            return new RedirectResponse($this->generateUrl('_demo'));
        }

        return array('form' => $form->createView());
    }
    
    
    public function decodeDataAction($req_obj)
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

                if (!(((($ext == 'jpg' || $ext == 'gif' || $ext == 'png') &&
                        ($_FILES['user_media']['type'][$key] == 'image/jpeg'  || 
                        $_FILES['user_media']['type'][$key] == 'image/gif'    || 
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
        public function getAppData(Request $request)
        {
	      $content = $request->getContent();
             $dataer = (object)$this->decodeDataAction($content);

             $app_data = $dataer->reqObj;
             $req_obj = $app_data; 
             return $req_obj;
        }

   /**
    * Call api/upload action
    * @param Request $request	
    * @return array
    */
    public function postUploadAction(Request $request)
  { 

                //Code start for getting the request
                $data = array();
                $freq_obj = $request->get('reqObj');
                $fde_serialize = $this->decodeDataAction($freq_obj);

                if(isset($fde_serialize)){
                   $de_serialize = $fde_serialize;
                } else {
                   $de_serialize = $this->getAppData($request);
                }
                //Code end for getting the request
                $object_info = (object)$de_serialize; //convert an array into object.
                $user_id = (int)$object_info->user_id;
                if ($this->getRequest()->getMethod() === 'POST') {
                $file_error = $this->checkFileTypeAction(); //checking the file type extension.
                if ($file_error) {
                    return array('code'=>100, 'message'=>'Only images and video are allowed', 'data'=>$data);
                }
               
                
                 /*********** user media data*************************/
                  //  $user_media_id = $StorePost->getId();    
                $dm = $this->get('doctrine.odm.mongodb.document_manager');
                        foreach($_FILES['user_media']['tmp_name'] as $key => $tmp_name )
                        {
                          $original_media_name = $_FILES['user_media']['name'][$key];
                          if (!empty($original_media_name)) { //if file name is not exists means file is not present.
                                $user_media = time().$_FILES['user_media']['name'][$key]; 
                                $user_media_type =  $_FILES['user_media']['type'][$key];
                                $user_media_type = explode('/',$user_media_type);
                                $user_media_type = $user_media_type[0];
                                
                                $user_media = new UserMedia();
                                $user_media->setUserid($user_id);
                                $user_media->setName($user_media);
                                $user_media->setContenttype($user_media_type);                     
                                $user_media->upload($user_id,$key,$user_media);
                                $dm->persist($user_media);
                                $dm->flush();
                                if($user_media_type =='image'){    
                                $mediaOriginalPath = $this->getBaseUri() . $this->user_media_path . $user_id . '/';
                                $thumbDir           = $this->getBaseUri() . $this->user_media_path_thumb . $user_id . '/';
                                $this->createThumbnail($user_media, $mediaOriginalPath, $thumbDir, $user_id);
                                return array('code'=>101, 'message'=>'success','data'=>$data);
                           }
                        }
                    }
    }
  }
	
	/**
     * Functionality return Media list
     * @param json $object
     * @return array
     */
   /* public function postListmediaAction(Request $request)
    { 
        $data= array();
        $request = $this->getRequest();
        $encode_object = $request->get('reqObj');
        //decoding the object.
        $object_info = $this->decodeDataAction($encode_object);
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $medias = $this->get('doctrine_mongodb')->getRepository('AcmeDemoBundle:UserMedia')->findBy(
          array('userid' => (int)$object_info->user_id)
        );
        // return array('data'=> $medias);
        return array('code'=>'101','msg'=>'suceess','data'=>$medias);
        
        
    }*/
	
	/**
     * Functionality return Media edit option
     * @param json $object
     * @return array
     */
	/* 
	public function editmedia($media_object_info)
	{ 
	        /* echo'<pre>';  print_r($_FILES); print_r($object_info); echo'</pre>';*/
           /*     $data =array(); 
                $userMedia = new UserMedia();
                if ($this->getRequest()->getMethod() === 'POST') 
                { 
                 $medianame = time().$_FILES['form']['name']['file'];
                 $mediatype = $_FILES['form']['type']['file'];
                 $mediatype = explode('/',$mediatype);
                 $mediatype = $mediatype[0];
                 $dm = $this->get('doctrine.odm.mongodb.document_manager');
                 $mediaId   = $media_object_info->id;
                 $dm = $this->get('doctrine_mongodb')->getManager();
                $image = $dm->getRepository('AcmeDemoBundle:UserMedia')->find($mediaId );
                if (!$image) {
                throw $this->createNotFoundException('No Media found for id '.$mediaId );
                }
                 $image->setName($medianame);
                 $UserMedia = new UserMedia();
                 $userMedia->upload();
                 $dm->persist($UserMedia);
                 $dm->flush();
                 $data[]='Media is updated successfully';
                 return array('code'=>'101','msg'=>'suceess','data'=>$data);
                }   
	}
	/**
	 * deleting the media on user_id and media_id basis.
	 * @param request object
	 * @param json
	 */
/*	public function postDeletemediaAction()
	{
                $data =array();
	  	$request = $this->getRequest();
		$encode_object = $request->get('reqObj');
		//decoding the object.
		$media_object_info = $this->decodeDataAction($encode_object);
		// echo'<pre>'; print_r($encode_object); echo '</pre>';
                $mediaid = $media_object_info->media_id;
                $dm     = $this->get('doctrine.odm.mongodb.document_manager');
                $medias = $dm->getRepository('AcmeDemoBundle:UserMedia')->find($mediaid);
                $filename = $medias->getname();
                // file delete 
                $document_root = $request->server->get('DOCUMENT_ROOT');
                $BasePath = $request->getBasePath();
                $file_location = $document_root.$BasePath; // getting sample directory path
                $fileLocation = $file_location.'/uploads/Documents/1/image/'.$filename; // getting sample directory path for 'details.txt' file
                if(unlink($fileLocation));
                // file delete 
                if (!$medias) { // in case record is not exists.
                 return 0;
                }
                $dm->remove($medias);
                        $dm->flush();
               //  return "true";
                $data[]='Media is deleted successfully';
                return array('code'=>101, 'message'=>'success','data'=>$data);
	}
	 /**
     * searching media 
     * @param object $current_media_object(text for search)
     * @return array
     */
 /*   public function postSearchmediaAction()
    { 
	    $request = $this->getRequest();
	    $encode_object = $request->get('reqObj');
	    //decoding the object.
	    $media_object_info = $this->decodeDataAction($encode_object);
            $limit    = $media_object_info->limit_size; 
            $offset   = $media_object_info->limit_start; 
            $text     = $media_object_info->search_text;
            $user_id  = (int)$media_object_info->user_id;

            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            $data =  $this->get('doctrine_mongodb')->getRepository('AcmeDemoBundle:UserMedia')
                       ->searchByMediaNameOrOther($text, $user_id, $offset, $limit);
            //finding the total count
            $data_count = $this->get('doctrine_mongodb')->getRepository('AcmeDemoBundle:UserMedia')
            ->searchByMediaNameOrOtherCount($text, $user_id);
            $final_array = array('messages'=>$data, 'count'=>$data_count);
            //  return $final_array;
            return array('code'=>'101','msg'=>'suceess','data'=>$data,'count'=>count($final_array));
    }
	
	/**
	 * deleting the media on user_id and media_id basis.
	 * @param request object
	 * @param json
	 */
/*	public function deletealbumAction()
	{
	    $data =array();
            $request = $this->getRequest();
            $encode_object = $request->get('reqObj');
            //decoding the object.
            $media_object_info = $this->decodeDataAction($encode_object);
            $document_root = $request->server->get('DOCUMENT_ROOT');
	    $BasePath = $request->getBasePath();
	    $file_location = $document_root.$BasePath; // getting sample directory path
	    $image_album_location = $file_location.'/uploads/Documents/1/image';
	    if($image_album_location) {
		 // $image_album_location = $file_location.'/uploads/Documents/2/image';
		  array_map('unlink', glob($image_album_location.'/*'));
	          rmdir($image_album_location);
                  $data[]='Album is deleted sucessfully';
	   }
           return array('code'=>'101','msg'=>'suceess','data'=>$data);
	}
        
        /**
     * create thumbnail for  a image.
     * @param type $filename
     * @param string $media_original_path
     * @param string $thumb_dir
     * @param string $user_id
     */
    public function createThumbnail($filename, $media_original_path, $thumb_dir, $user_id) {  
        $path_to_thumbs_directory = __DIR__."/../../../../web/uploads/users/media/thumb/".$user_id."/";
     //   $path_to_thumbs_directory = $thumb_dir;
	$path_to_image_directory  = $media_original_path;
	$final_width_of_image = 100;  
        if(preg_match('/[.](jpg)$/', $filename)) {  
            $im = imagecreatefromjpeg($path_to_image_directory . $filename);  
        } else if (preg_match('/[.](jpeg)$/', $filename)) {  
            $im = imagecreatefromgif($path_to_image_directory . $filename);  
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
}