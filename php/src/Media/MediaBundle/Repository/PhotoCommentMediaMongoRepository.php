<?php

namespace Media\MediaBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * CommentMediaMongoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PhotoCommentMediaMongoRepository extends DocumentRepository
{
     /**
     * publish media for a comment
     * @param array $media_id
     * @return boolean
     */
    public function publishCommentMediaImage($media_id){
            $this->createQueryBuilder('mediacomment')
                    ->update()
                    ->multiple(true)
                    ->field('media_status')->set(1)
                    ->field('id')->in($media_id)
                    ->getQuery()
                    ->execute();
            
          return true;
    }
     /**
     * removing the comment media data
     * @param type $comment_id
     * @return boolean
     */
    public function removeDashboardPostCommentsMedia($comment_id)
    {
            $qb = $this->createQueryBuilder();
            $qb->remove()
                ->field('comment_id')->equals($comment_id)
                ->getQuery()
                ->execute();
            return true;
    }
    /**
     * Finding the comment media
     * @param array $comment_ids
     * @return document object
     */
    public function findCommentMedia($comment_ids) {
            $qb     = $this->createQueryBuilder('u');
            $result = $qb->field('media_status')->equals(1)
                         ->field('comment_id')->in($comment_ids)
                         ->getQuery()
                         ->execute()
                         ->toArray(false);
            return $result;    
    }
}