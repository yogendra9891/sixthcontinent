<?php
namespace Media\MediaBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

// service method class for privacy setting object.
class PrivacySettingService
{
    protected $em;
    protected $dm;
    protected $container;
    protected $request;
   // protected $public_album_setting  = 1;
    protected $friend_album_setting  = 2;
    protected $private_album_setting = 3;
    
    protected $personal_friend_album_setting  = 1;
    protected $professional_friend_album_setting  = 2;
    protected $public_album_setting = 3;
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
     * finding the privacy setting array object.
     * @param none
     * @return array
     */

   public function PrivacySettingService()
  {
     $privacy_setting = array(
         'public'=>$this->public_album_setting,
         'friend'=>$this->friend_album_setting,
         'private'=>$this->private_album_setting);
     return $privacy_setting;
  }

   
    /**
     * finding the privacy setting array object.
     * @param none
     * @return array
     */
    
   public function AlbumPrivacySettingService()
   {
        $privacy_setting = array(
                'personal'=>$this->personal_friend_album_setting,
                'professional'=>$this->professional_friend_album_setting,
                'public'=>$this->public_album_setting
            );
        return $privacy_setting;
   }
 }
