<?php

namespace StoreManager\PostBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * StoreCommentsMongoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class StoreCommentsMongoRepository extends DocumentRepository {

    /**
     * count comments
     * @param int $post_id
     * @param int $user_id
     * @return object array
     */
    public function listingTotalComments($post_id) {
        $qb = $this->createQueryBuilder('User');
        $result = $qb
                ->field('post_id')->equals($post_id)
                ->field('status')->equals(1)
                ->getQuery()
                ->execute()
                ->toArray(false);

        return $result;
    }

    public function getPostComments($postIds) {
        $results = array();
        $qb = $this->createQueryBuilder();
        $results = $qb->field('post_id')->in($postIds)
                ->field('status')->equals(1)
                 ->sort('comment_created_at','DESC')
                ->getQuery()
                ->execute()
                ->toArray(false);
        return $results;
    }
     /**
     * Edit the comment rate
     * @param type $rate_id
     * @return boolean
     */
    public function editCommentRate($rate_id, $arrayCommentRate, $comment_id) {
        $result = $this->createQueryBuilder('StoreComments')
                ->update()
                ->field('id')->equals($comment_id)
                ->field('rate.id')->equals($rate_id)
                ->field("rate.$")->set($arrayCommentRate)
                ->getQuery()
                ->execute();
        return true;
    }
    
    /**
     * finding the commennts of posts.
     * @param type $post_ids
     * @return document object.
     */
    public function getRecentPostsComments($post_ids,  $limitForEachPost=0) {
        $response = array();
        foreach ($post_ids as $post_id){
            $result = $this->findBy(array('post_id'=>$post_id, 'status'=>1), array('comment_created_at'=>'DESC'), $limitForEachPost);
            $response = array_merge($response, $result);
        }
            return $response;
    }

}
