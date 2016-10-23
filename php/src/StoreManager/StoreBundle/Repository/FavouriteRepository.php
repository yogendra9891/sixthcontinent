<?php

namespace StoreManager\StoreBundle\Repository;
use Doctrine\ORM\EntityRepository;

/**
 * FavouriteRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FavouriteRepository extends EntityRepository
{
    /**
     * Get shop followers
     * @param type $offset
     * @param type $limit
     * @return type
     */
    public function getShopFavs($shop_ids) {
        $result_res = array();
        $qb = $this->createQueryBuilder('c');
        $query = $qb->select('c.storeId, c.userId ')
                ->where(
                        $qb->expr()->In('c.storeId', ':s_id')
                )
                ->setParameter('s_id', $shop_ids)
                ->getQuery();
        $result_res = $query->getResult();
        if ($result_res) {
            return $result_res;
        }
        return $result_res;
    }
    
    /**
     * Get shop followers
     * @param type $offset
     * @param type $limit
     * @return type
     */
    public function getSingleShopFavs($shop_id) {
        $result_res = array();
        $qb = $this->createQueryBuilder('c');
        $query = $qb->select('c.storeId, c.userId, c.id')
                ->where(
                        $qb->expr()->eq('c.storeId', ':s_id')
                )
                ->setParameter('s_id', $shop_id)
                ->getQuery();
        $result_res = $query->getResult();
        if ($result_res) {
            return $result_res;
        }
        return $result_res;
    }
}