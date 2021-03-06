<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserCategoryKeywordsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserCategoryKeywordsRepository extends EntityRepository
{
    
    /*
    * Insert user selected category keywords 
    */
    public function InsertCategoryKeywords($data)
    {


        $keywords = new UserCategoryKeywords();
        
        if($data['id']){
            $keywords = $this->find($data['id']);    
            if (!$keywords) {
                $data = array('code' => 131, 'message' => 'INVALID_DATA', 'data' => array());
                echo json_encode($data);
                exit;
            }
        }
          
        $keywords->setUserId($data['user_id']);
        $keywords->setCategoryId($data['category_id']);
        $keywords->setKeywords($data['keywords']);
               
        $em = $this->getEntityManager();
        $em->persist($keywords);
        $em->flush();
        
        $categoryKeywords = array();

        $categoryKeywords['id'] = $keywords->getId();
        $categoryKeywords['user_id'] = $keywords->getUserId();
        $categoryKeywords['category_id'] = $keywords->getCategoryId();
        $categoryKeywords['keywords'] = $keywords->getKeywords();
        
        $categoryName = $this->getCategoryName($categoryKeywords['category_id'],$data['lang_code']);
        
        if($categoryName){
            $categoryKeywords['category_name'] = $categoryName[0]['category_name'];
        } else {
            $categoryKeywords['category_name'] = '';
        }
        
        
        return $categoryKeywords;
         
    }
    
    /*
     * getting All category wise inserted  keywords
    */
    public function getCategoryKeywords($user_id,$lang_code)
    {
       
        $qb = $this->createQueryBuilder('catks');
        $query = $qb
                ->select('catks.id, catks.userId, catks.categoryId, catks.keywords, code.categoryName as categoryName ')
                ->innerJoin('UserManagerSonataUserBundle:BusinessCategory', 'kys', 'WITH', 'kys.id = catks.categoryId')
                ->innerJoin('UserManagerSonataUserBundle:BusinessCategoryCode', 'code', 'WITH', 'kys.name = code.categoryCode')
                ->where('catks.userId =:user_id','code.langCode =:lang_code')
                ->setParameter('user_id',$user_id )
                ->setParameter('lang_code',$lang_code )
                ->getQuery();
        
        $userKeywords = $query->getResult();
        
        $response = array();
        foreach($userKeywords as $keyword)  
        {
            $array = array();
            
            $array['id'] = $keyword['id'];
            $array['user_id'] = $keyword['userId'];
            $array['category_id'] = $keyword['categoryId'];
            $array['keywords'] = $keyword['keywords'];
            $array['category_name'] = $keyword['categoryName'];
            
            $response[] = $array;
            
        }

        return $response;
    }
    
    /*
    * Deleting User Intrested Category 
    */
    public function DeleteUserCategory($id, $user_id)
    {
        
        $UserCategory = $this->findOneBy(array('id'=>$id, 'userId'=>$user_id ));
        if (!$UserCategory)
        {
            $data = array('code' => 131, 'message' => 'INVALID_DATA', 'data' => array());
            echo json_encode($data);
            exit;
        }
        
        $em = $this->getEntityManager();
        $em->remove($UserCategory);
        $em->flush();
        
        return true;
    }

    /*
    * Getting category name depending upon language code and id  
    */
    public function getCategoryName($id, $lang_code)
    {
        
        $qb = $this->_em->createQueryBuilder();
        $query = $qb->select('code.categoryName as category_name')
                ->from('UserManagerSonataUserBundle:BusinessCategory', 'cat')
                ->innerJoin('UserManagerSonataUserBundle:BusinessCategoryCode', 'code', 'WITH', 'code.categoryCode = cat.name')
                ->where('code.langCode =:langCode','cat.id=:id')
                ->setParameter('langCode',$lang_code )
                ->setParameter('id',$id )
                ->getQuery();
        $response = $query->getResult();
       
        return $response;
    }
    
    
}
