<?php

namespace Utility\MasterDataBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * VisibilityCodeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VisibilityCodeRepository extends EntityRepository
{
   /**
    * Visibility List
    * @return object array
    */
    public function getVisibilityList($langCode){ 
        try{
        //object of query builder.
        $qb = $this->createQueryBuilder('c');
        $query = $qb->select('c.id, code.visibilityName as language_string')
                ->innerJoin('MasterDataBundle:VisibilityCodeByLang', 'code', 'WITH', 'code.visiblityCode = c.name')
                ->where('code.langCode =:langCode','c.status =:status')
                ->setParameter('langCode',$langCode )
                ->setParameter('status',1)
                ->getQuery();
        $response = $query->getResult();
        return $response;
        }catch(\Exception $e){
            return array();
        }
    }
}
