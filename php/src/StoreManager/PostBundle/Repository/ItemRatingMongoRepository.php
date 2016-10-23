<?php

namespace StoreManager\PostBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * ItemRatingMongoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ItemRatingMongoRepository extends DocumentRepository
{
       /**
        * Edit the item rate
        * @param type $rate_id
        * @return boolean
        */
        public function editPostRate($rate_id, $arrayPostRate, $item_id) {
            $result = $this->createQueryBuilder('post')
                    ->update()
                    ->field('item_id')->equals((string)$item_id)
                    ->field('rate.id')->equals($rate_id)
                    ->field("rate.$")->set($arrayPostRate)
                    ->getQuery()
                    ->execute();
            return true;
        }
}