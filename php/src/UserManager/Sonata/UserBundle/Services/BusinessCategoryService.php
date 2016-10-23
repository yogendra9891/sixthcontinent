<?php
namespace UserManager\Sonata\UserBundle\Services;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class BusinessCategoryService {
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
    }
    
    public function getBusinessCategoriesByLangAndIds($lang, $ids=array()){
        $result = $this->em
                    ->getRepository('UserManagerSonataUserBundle:BusinessCategory')
                    ->getCategoryNamesByIds($lang, $ids);
        $response = array();
        if($result){
            foreach($result as $_result){
                $response[$_result['id']] = array(
                    'id'=> $_result['id'],
                    'name'=>$_result['category_name'],
                    'image'=>$_result['image'],
                    'image_thumb'=>$_result['image_thumb']
                    );
            }
        }
        return $response;
    }
    
}
