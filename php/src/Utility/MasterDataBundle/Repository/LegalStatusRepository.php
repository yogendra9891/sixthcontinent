<?php

namespace Utility\MasterDataBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LegalStatusRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LegalStatusRepository extends EntityRepository
{
    
   /**
    * LegalStatus List
    * @return object array
    */
    public function getLegalStatusList($langCode, $parent_id){ 
        try{
        //object of query builder.
        $qb = $this->createQueryBuilder('c');
        $query = $qb->select('c.id, code.legalstatusName as language_string')
                ->innerJoin('MasterDataBundle:LegalStatusCodeByLang', 'code', 'WITH', 'code.legalstatusCode = c.name')
                ->where('code.langCode =:langCode','c.parent=:parentId', 'c.status =:status')
                ->setParameter('langCode',$langCode )
                ->setParameter('parentId',$parent_id )
                ->setParameter('status',1)
                ->getQuery();
        $response = $query->getResult();
        return $response;
        }catch(\Exception $e){
            return array();
        }
    }
}
