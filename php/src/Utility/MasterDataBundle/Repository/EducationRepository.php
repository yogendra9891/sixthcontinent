<?php

namespace Utility\MasterDataBundle\Repository;

use Doctrine\ORM\EntityRepository;


/**
 * EducationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EducationRepository extends EntityRepository
{
   /**
    * Education List
    * @return object array
    */
    public function getEducationList($langCode, $parent_id){ 
        try{
        //object of query builder.
        $qb = $this->createQueryBuilder('c');
        $query = $qb->select('c.id, code.educationName as language_string, c.code')
                ->innerJoin('MasterDataBundle:EducationCodeByLang', 'code', 'WITH', 'code.educationCode = c.name')
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
