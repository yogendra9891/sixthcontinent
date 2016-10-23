<?php
namespace StoreManager\StoreBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
// service method  class
class StoreAclService
{
    
    /**
     * Get Group Owner ACL code
     * @return int
     */
    public function getStoreOwnerAclCode() {
        $builder = new MaskBuilder();
        $builder
                ->add('view')
                ->add('create')
                ->add('delete')
                ->add('edit');
        return $builder->get();
    }

    /**
     * Get Group Admin ACL code
     * @return int
     */
    public function getStoreAdminAclCode() {
        $builder = new MaskBuilder();
        $builder
                ->add('view')
                ->add('create')
                ->add('edit');
        return $builder->get();
    }

    /**
     * Get Group Admin ACL code
     * @return int
     */
    public function getStoreFriendAclCode() {
        $builder = new MaskBuilder();
        $builder
                ->add('view');
        return $builder->get();
    }
    
}