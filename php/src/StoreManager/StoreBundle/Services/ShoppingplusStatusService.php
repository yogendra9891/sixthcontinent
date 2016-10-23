<?php
namespace StoreManager\StoreBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use StoreManager\StoreBundle\Document\ShoppingplusStatus;

// service method class for privacy setting object.
class ShoppingplusStatusService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
    //define the required params

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container)
    {
        $this->em        = $em;
        $this->dm        = $dm;
        $this->container = $container;
        //$this->request   = $request;
    }
   
    /**
     * finding the Shoppingplus setting array object.
     * @param none
     * @return array
     */
   public function ShoppingplusStatus($register_id,$type,$status,$sh_status,$sh_error_desc,$step)
   {
      $shop_id = $register_id;
      $register_type = $type;
      $status = 0;
      $error_code = $sh_status;
      $error = $sh_error_desc;
      $dm = $this->dm;
      $shopping_plus = new ShoppingplusStatus();
      $shopping_plus->setRegisterId($shop_id);
      $shopping_plus->setStatus($status);
      $shopping_plus->setEntityType($register_type);
      $time = new \DateTime("now");
      $shopping_plus->setCreated($time);
      $shopping_plus->setErrorCode($error_code);
      $shopping_plus->setErrorDesc($error);
      $shopping_plus->setStep($step);
      $dm->persist($shopping_plus);
      $dm->flush();
     
   }
   
   /**
     * Decoding the json string to object
     * @param json string $encode_object
     * @return object $decode_object
     */
    public function decodeObjectAction($encode_object) {
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
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeObjectAction($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
 }
