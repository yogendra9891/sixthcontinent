<?php

namespace StoreManager\StoreBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * StoreMediaRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class StoreMediaRepository extends EntityRepository
{
    public function removeFeaturedImage($store_id){
        //create the query
        $query = $this->createQueryBuilder('sm')
                  ->update()
                  ->set('sm.isFeatured', '?1')
                  ->where('sm.storeId = ?2')
                  ->setParameter(1, 0)
                  ->setParameter(2, $store_id)
                 ->getQuery();
    $response = $query->getResult();
    return true;
    }
    
    public function deleteAlbumFromStore($album_id)
    {
        $isDeleted = $this->createQueryBuilder("album")
                    ->delete()
                    ->where('album.albumId  = :id')->setParameter("id", $album_id)
                    ->getQuery()->execute();

        return $isDeleted;
    }
    
    
    public function publishAlbumMedia($media_id){
        //create the query
        $qb = $this->createQueryBuilder('sm');
        $query =   $qb->update()
                 ->set('sm.mediaStatus', '1')
                  ->where(
                        $qb->expr()->In('sm.id', ':sid')
                    )
                  ->setParameter('sid', $media_id)
                 ->getQuery();
        $response = $query->getResult();
        return true;
    }
    public function getAllPublishAlbumMedia($media_id){
        $result = array();

        //create the query
        $qb = $this->createQueryBuilder('c');
        $query =  $qb->select('c')
                ->where(
                    $qb->expr()->In('c.id', ':sid')
                )
                ->setParameter('sid', $media_id)
                ->getQuery();

        $results = $query->getResult();
        return $results;
    }
    
    /**
     * Get Album media  count
     * @param int $album_id
     * @param int $store_id
     * @return int
     */
    public function getUserAlbumMediaCount($album_id,$store_id)
    {
     
         $qb = $this->createQueryBuilder('sm')
                    ->select('count(sm.id)')
                    ->where('sm.storeId = :sid')
                    ->andWhere('sm.albumId = :aid')
                    ->andWhere('sm.mediaStatus = :mstatus')
                    ->setParameter('sid', $store_id)
                    ->setParameter('aid', $album_id)
                    ->setParameter('mstatus', 1)
                    ->getQuery();
             
         $result = $qb->getResult();
         return $result[0][1];
    }
     /**
     * Get store media owner id
     *
     * @return integer 
     */
    public function getStoreOwnerId($media_id){
        //initialize the array
        $result=array();
        
        //create the query
        $query=$this->createQueryBuilder('c')
                ->select('us.userId')
                ->innerJoin('StoreManagerStoreBundle:UserToStore','us','WITH','c.storeId=us.storeId')
                ->where('c.id=:aid')
                ->setParameter('aid',$media_id)
                ->getQuery();
        $results = $query->getResult();
        return $results[0]['userId'];
    }
    
     /**
     * Get all shops
     * @param type $offset
     * @param type $limit
     * @return type
     */
    public function getRegistredShopsProfileImage($shop_media_ids) {
        $result_res = array();
        $qb = $this->createQueryBuilder('co');
        $query = $qb->select('c.id, co.storeId, co.albumId, co.imageName')
                ->leftJoin('StoreManagerStoreBundle:Store', 'c', 'WITH', 'c.id = co.storeId')
                ->where(
                        $qb->expr()->In('co.id', ':s_id')
                )
                ->setParameter('s_id', $shop_media_ids)
                ->getQuery();
        $result_res = $query->getResult();
        if ($result_res) {
            return $result_res;
        }
        return $result_res;
    }

    public function getSingleMediaImage($store_id){
      
     $sql= "select image_name from  StoreMedia where store_id = '$store_id' order by id desc limit 1";
     $stmt = $this->getEntityManager()
                ->getConnection()
                ->prepare($sql);
      $stmt->execute();
      $store_img = $stmt->fetchAll();

     foreach ($store_img as $img) {
         
         if($img['image_name'] != NULL){
              
             return $img['image_name'];
         }
         else
         {
            return '';
         }   
         
       }  
    }

    public function getCategoryAndMediaImage($cat_id){
      
       $sql= "select bc.id , bc.image, bc.image_thumb , bcc.category_code,bcc.category_name as catname from  BusinessCategory as bc 
           inner join BusinessCategoryCode as bcc on bcc.category_code = bc.name where bc.id = '$cat_id' limit 1";
      
       $stmt = $this->getEntityManager()
                ->getConnection()
                ->prepare($sql);
      $stmt->execute();
      return $stmt->fetchAll();

     }

}
