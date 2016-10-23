<?php

namespace Media\MediaBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * UserMediaMongoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserMediaMongoRepository extends DocumentRepository {

    /**
     * Search media 
     * @param string $text
     * @param int $user_id
     * @param int $offset
     * @param int $limit
     * @return object array
     */
    public function searchByMediaNameOrOther($text, $user_id, $offset, $limit) {
        $qb = $this->createQueryBuilder('m');
        $result = $qb->field('userid')->equals($user_id)
                ->field('name')->equals(new \MongoRegex('/.*' . $text . '.*/i'))
                ->limit($limit)
                ->skip($offset)
                ->getQuery()
                ->execute()
                ->toArray(false);
        return $result;
    }

    /**
     * Search notification messages count
     * @param string $text
     * @param int $user_id
     * @return object array
     */
    public function searchByMediaNameOrOtherCount($text, $user_id) {
        $qb = $this->createQueryBuilder('m');
        $result = $qb->field('userid')->equals($user_id)
                ->field('name')->equals(new \MongoRegex('/.*' . $text . '.*/i'))
                ->hydrate(false)
                ->getQuery()
                ->execute()
                ->toArray(false);
        return count($result);
    }

    /* public function removeUserMedias($user_id)
      {
      $qb = $this->createQueryBuilder();
      $qb->remove()
      ->field('userid')->equals($user_id)
      ->getQuery()
      ->execute();
      return true;
      }
     */

    /**
     * removing the album media data
     * @param type $album_id
     * @return boolean
     */
    public function removeAlbumMedia($album_id) {
        $qb = $this->createQueryBuilder();
        $qb->remove()
                ->field('albumid')->equals($album_id)
                ->getQuery()
                ->execute();
        return true;
    }

    /**
     * Publish the album image
     * @param array $images
     * @return boolean
     */
    public function publishAlbumImage($images,$tagged_friends = array()) { 
        $qb = $this->createQueryBuilder()
                ->update()
                ->multiple(true)
                ->field('enabled')->set(1)
                ->field('tagged_friends')->set($tagged_friends)
                ->field('id')->in($images)
                ->getQuery()
                ->execute();
        return true;
    }

    /**
     * Get Album media count
     * @param int $album_id
     * @return int
     */
    public function getUserAlbumMediaCount($album_id) {

        $qb = $this->createQueryBuilder()
                ->count()
                ->field('albumid')->equals($album_id)
                ->field('enabled')->equals(1)
                ->getQuery();

        $result = $qb->execute();
        return $result;
    }
/**
    * 
    * @param type $profile_img_id
    * @return type
    */
   public function findUserMedia($profile_img_id) {
            $results = array();
            //get all groups assigned to user
            $qb      = $this->createQueryBuilder();
            $results =  $qb ->field('id')->equals($profile_img_id)
                        ->getQuery()
                        ->execute()
                        ->toArray(false);
            return $results;
   }
   
   /**
    * Find multiple users profile media info
    * @param array $media_ids
    * @return document object
    */
   public function findUserProfileMediaInfo($media_ids) {
        $qb = $this->createQueryBuilder();
        $results = $qb->field('id')->in($media_ids)
                       ->getQuery()
                       ->execute()
                       ->toArray(false);
        return $results;       
   }
   
   /**
    * Get album media count and featured image for user album
    * @param array $album_ids_array
    * @param int $user_id
    */
   public function getAlbumMediaInfo( $album_ids_array ,$user_id){
        $results = array();
        $album_info = array();
        $qb = $this->createQueryBuilder();
        $results = $qb
                       ->field('albumid')->in($album_ids_array)
                       ->field('userid')->equals($user_id)
                       ->field('enabled')->equals(1)
                       ->getQuery()
                       ->execute()
                       ->toArray(false);
        
        foreach($results as $result){
            $album_id = $result->getAlbumid();
            $album_info[$album_id][] = $result;
        }
        return $album_info;       
   }
   
   /**
    * Get album media count and featured image for friend album
    * @param array $album_ids_array
    * @param int $friend_id
    */
   public function getFriendAlbumMediaInfo( $album_ids_array ,$friend_id){
        $results = array();
        $album_info = array();
        $qb = $this->createQueryBuilder();
        $results = $qb
                       ->field('albumid')->in($album_ids_array)
                       ->field('userid')->equals($friend_id)
                       ->field('enabled')->equals(1)
                       ->getQuery()
                       ->execute()
                       ->toArray(false);
        
        foreach($results as $result){
            $album_id = $result->getAlbumid();
            $album_info[$album_id][] = $result;
        }
       // return $album_info[$album_id][0]->getId(); 
        return $album_info;
   }
   
    /**
     * Get tagged photos
     * @param aaray $user_ids
     * @return array
     */
    public function getTaggedPhotos($user_ids , $limit_start = 0 , $limit_end = 1 ){  
        $qb = $this->createQueryBuilder();
        
        $a = array( (string) $user_ids);
        $result = $qb->field('tagged_friends')->in( $a )
                ->limit($limit_end)
                ->skip($limit_start)
                ->getQuery()->execute()->toArray(false);

       // return $result[0]->getId();
        
         return $result;
    }
    
    /**
     * Get tagged photos count
     * @param aaray $user_ids
     * @return array
     */
    public function getTaggedPhotosCount($user_ids , $limit_start = 0 , $limit_end = 1 ){  
        $qb = $this->createQueryBuilder();
        
        $a = array( (string) $user_ids);
        $result = $qb->field('tagged_friends')->in( $a )
                ->count()
                ->getQuery()->execute();

        return $result;
    }
    
    /**
     * Get get media details according to id
     * @param array $media_ids
     * @return array
     */
    public function getMediaDetail($media_ids = array()){  
        $qb = $this->createQueryBuilder();
        
        $result = $qb->field('id')->in( $media_ids )
                  ->getQuery()->execute()->toArray(false);

        return $result;
    }
    
     /**
     * Edit the Album media rate
     * @param type $rate_id
     * @return boolean
     */
    public function editMediaRate($rate_id, $arrayMediaRate, $media_id) {
        $result = $this->createQueryBuilder('media')
                ->update()
                ->field('id')->equals($media_id)
                ->field('rate.id')->equals($rate_id)
                ->field("rate.$")->set($arrayMediaRate)
                ->getQuery()
                ->execute();
        return true;
    }
    
     /**
     * publish media for a comment
     * @param array $media_id
     * @return boolean
     */
    public function getCommentsOfMedia($media_id, $limit= 5, $resultWithCount=false,$start=null){
        $commentCount=0;
        if($resultWithCount){
            $qb = $this->createQueryBuilder('m')
                ->field('enabled')->equals(1)
                ->field('id')->equals($media_id)
                ->map('function() { var _length = this.comment ? this.comment.length : 0; emit("totalComment", _length); }')
                ->reduce('function(k, vals) {
                     var sum = 0;
                    vals.forEach(function(value) {
                        sum += value;
                    });
                    return sum;
                }');
            $comments = $qb->getQuery()->getSingleResult();
            $commentCount = $comments ? $comments['value'] : 0;
        }
        
        $query = $this->createQueryBuilder('mediacomments');
        if($commentCount>0){
        if(!is_null($start)){
            //pagination
            $offset = $commentCount - ($start+$limit);
            if($offset<0){
                $offset = 0;
                $limit = ($commentCount-$start)>0 ? ($commentCount-$start) : 0;
            }
            $query =  $query->selectSlice('comment', $offset, $limit);
            // view all 
           // $query =  $query->selectSlice('comment', $start, $limit);
        }else{
            $query =  $query->selectSlice('comment', -$limit);
        }
        
            $query = $query->field('comment.status')->equals(1);
        }
        $result = $query->field('enabled')->equals(1)
                     ->field('id')->equals($media_id)
                     ->getQuery()
                     ->getSingleResult();
        
          return $resultWithCount==true ? array('result'=>$result, 'size'=>$commentCount) : array('result'=>$result);
        
    }
    
    
    public function getCommentedUserIds($media_id){
        $qb = $this->createQueryBuilder('m')
                ->field('enabled')->equals(1)
                ->field('id')->equals($media_id)
                ->map('function() { 
                    var authors=[]; 
                    for(var i=0; i<this.comment.length; i++){
                        authors[i] = this.comment[i]["comment_author"];
                    }
                    emit(this._id, authors); }')
                ->reduce('function(k, vals) {
                    return vals;
                }');
            $comments = $qb->getQuery()->getSingleResult();
            return isset($comments['value']) ? $comments['value'] : array();
    }
    /**
     * 
     * @param type $media_id
     * @param type $comment_id
     * @return type
     */
    public function getCommentedMedias($media_id, $comment_id){
        $qb = $this->createQueryBuilder('m')
                ->field('enabled')->equals(1)
                ->field('id')->equals($media_id)
                ->field('comment.id')->equals($comment_id)
                ->map('function() { 
                    var medias=[]; 
                    var commentId = ObjectId("'.$comment_id.'");
                    for(var i=0; i<this.comment.length; i++){
                        var id = this.comment[i]._id;
                        if(commentId.toString()==id.toString()){
                            medias = this.comment[i]["medias"];
                        }
                    }
                    emit(this._id, medias); }')
                ->reduce('function(k, vals) {
                    return vals;
                }');
               
            $comments = $qb->getQuery()->getSingleResult();
            return isset($comments['value']) ? $comments['value'] : array();
    }
}
