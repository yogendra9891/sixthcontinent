<?php

namespace UserManager\Sonata\UserBundle\Document;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * StoreManager\PostBundle\Document\StorePostsMedia
 */
class GroupMedia
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $group_id
     */
    protected $group_id;
    
    /**
     * @var string $albumid
     */
    protected $albumid;
    /**
     * @var int $is_featured
     */
    protected $is_featured;
    
    /**
     * @var int $profile_image
     */
    protected $profile_image;
    /**
    /**
     * @var string $media_name
     */
    protected $media_name;

    /**
     * @var string $media_type
     */
    protected $media_type;

    /**
     * @var date $media_created
     */
    protected $media_created;

    /**
     * @var date $media_updated
     */
    protected $media_updated;

    /**
     * @var int $media_status
     */
    protected $media_status;

    /**
     * @var file $file
     */
    protected $file;

    /**
     * @var string $path
     */
    protected $path;
    
     /**
     * @var string $x
     */
    protected $x;
    
    /**
     * @var string $y
     */
    protected $y;
    /**
     * @var collection $tagged_friends
     */
    protected $tagged_friends;
    /**
     * @var date $created_at
     */
    protected $created_at;

    
    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set groupId
     *
     * @param string $groupId
     * @return self
     */
    public function setGroupId($groupId)
    {
        $this->group_id = $groupId;
        return $this;
    }

    /**
     * Get groupId
     *
     * @return string $groupId
     */
    public function getGroupId()
    {
        return $this->group_id;
    }
    
       /**
     * Set albumid
     *
     * @param string $albumid
     * @return self
     */
    
    public function setAlbumid($albumid)
    {
        $this->albumid = $albumid;
        return $this;
    }

    /**
     * Get albumid
     *
     * @return string $albumid
     */
    public function getAlbumid()
    {
        return $this->albumid;
    }
   /**
     * Set isFeatured
     *
     * @param int $isFeatured
     * @return self
     */
    public function setIsFeatured($isFeatured)
    {
        $this->is_featured = $isFeatured;
        return $this;
    }

    /**
     * Get isFeatured
     *
     * @return int $isFeatured
     */
    public function getIsFeatured()
    {
        return $this->is_featured;
    }

    /**
     * Set mediaName
     *
     * @param string $mediaName
     * @return self
     */
    public function setMediaName($mediaName)
    {
        $this->media_name = $mediaName;
        return $this;
    }

    /**
     * Get mediaName
     *
     * @return string $mediaName
     */
    public function getMediaName()
    {
        return $this->media_name;
    }

    /**
     * Set mediaType
     *
     * @param string $mediaType
     * @return self
     */
    public function setMediaType($mediaType)
    {
        $this->media_type = $mediaType;
        return $this;
    }

    /**
     * Get mediaType
     *
     * @return string $mediaType
     */
    public function getMediaType()
    {
        return $this->media_type;
    }

    /**
     * Set mediaCreated
     *
     * @param date $mediaCreated
     * @return self
     */
    public function setMediaCreated($mediaCreated)
    {
        $this->media_created = $mediaCreated;
        return $this;
    }

    /**
     * Get mediaCreated
     *
     * @return date $mediaCreated
     */
    public function getMediaCreated()
    {
        return $this->media_created;
    }

    /**
     * Set mediaUpdated
     *
     * @param date $mediaUpdated
     * @return self
     */
    public function setMediaUpdated($mediaUpdated)
    {
        $this->media_updated = $mediaUpdated;
        return $this;
    }

    /**
     * Get mediaUpdated
     *
     * @return date $mediaUpdated
     */
    public function getMediaUpdated()
    {
        return $this->media_updated;
    }

    /**
     * Set mediaStatus
     *
     * @param int $mediaStatus
     * @return self
     */
    public function setMediaStatus($mediaStatus)
    {
        $this->media_status = $mediaStatus;
        return $this;
    }

    /**
     * Get mediaStatus
     *
     * @return int $mediaStatus
     */
    public function getMediaStatus()
    {
        return $this->media_status;
    }

    /**
     * Set file
     *
     * @param file $file
     * @return self
     */
     public function setFile(UploadedFile $file = null)
    {
         $this->file = $file;
    }

    /**
     * Get file
     *
     * @return file $file
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return self
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Get path
     *
     * @return string $path
     */
    public function getPath()
    {
        return $this->path;
    }
    
        /**
     * Set x
     *
     * @param string $x
     * @return self
     */
    public function setX($x)
    {
        $this->x = $x;
        return $this;
    }

    /**
     * Get x
     *
     * @return string $x
     */
    public function getX()
    {
        return $this->x;
    }
    /**
     * Set y
     *
     * @param string $y
     * @return self
     */
    public function setY($y)
    {
        $this->y = $y;
        return $this;
    }

    /**
     * Get y
     *
     * @return string $y
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Set profileImage
     *
     * @param int $profileImage
     * @return self
     */
    public function setProfileImage($profileImage)
    {
        $this->profile_image = $profileImage;
        return $this;
    }

    /**
     * Get profileImage
     *
     * @return int $profileImage
     */
    public function getProfileImage()
    {
        return $this->profile_image;
    }
    
    public function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/groups/original';
    }
    
    public function upload($group_id,$group_media_name)
    {
       // create directory having title of postId. 
       // since post id is string so directory name would be string type
        $source= $_FILES['group_media']['tmp_name'];
      //  $file_name = $_FILES['group_media']['name'];
        $file_name = $group_media_name;
        $pre_upload_media_dir =  __DIR__."/../../../../../web/uploads/groups/original/";
        $upload_media_dir = $pre_upload_media_dir.$group_id.'/';
        
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir .$file_name);
        } 
        else { 
            $destination = \mkdir($pre_upload_media_dir.$group_id, 0777, true);
            $upload_media_dir = $pre_upload_media_dir.$group_id.'/';
            move_uploaded_file($source, $upload_media_dir .$file_name);
        }
    }
    
     public function albummediaupload($group_id,$key,$file_name,$album_id)
    {
        $source= $_FILES['group_media']['tmp_name'][$key];
        //$file_name = $_FILES['user_media']['name'][$key];
        $pre_upload_media_dir =  __DIR__."/../../../../../web/uploads/groups/original/";
        if($album_id){
         $upload_media_dir = $pre_upload_media_dir.$group_id.'/'.$album_id.'/';  
        } else {
         $upload_media_dir = $pre_upload_media_dir.$group_id.'/';
        }
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir .$file_name);
        } 
        else { 
            $destination = \mkdir($pre_upload_media_dir.$group_id.'/'.$album_id, 0777, true);
            move_uploaded_file($source, $upload_media_dir .$file_name);
        }
    }
    
    
    /**
     * upload the image for store profile
     * @param int $group_id
     * @param type $file_name
     */
    public function groupProfileImageUpload($group_id,$file_name)
    {
       // create directory having title of postId. 
       // since post id is string so directory name would be string type
        $source= $_FILES['group_media']['tmp_name'];
        $pre_upload_media_dir =  __DIR__."/../../../../../web/uploads/groups/original/";
        $upload_media_dir = $pre_upload_media_dir.$group_id.'/';
        
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir .$file_name);
        } 
        else { 
            $destination = \mkdir($pre_upload_media_dir.$group_id, 0777, true);
            $upload_media_dir = $pre_upload_media_dir.$group_id.'/';
            move_uploaded_file($source, $upload_media_dir .$file_name);
        }
    }
    
    /**
     * @var int $image_type
     */
    protected $image_type;


    /**
     * Set imageType
     *
     * @param int $imageType
     * @return self
     */
    public function setImageType($imageType)
    {
        $this->image_type = $imageType;
        return $this;
    }
    
    /**
     * Set createdAt
     *
     * @param date $createdAt
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return date $createdAt
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Get imageType
     *
     * @return int $imageType
     */
    public function getImageType()
    {
        return $this->image_type;
    }
    /**
     * Set taggedFriends
     *
     * @param collection $taggedFriends
     * @return self
     */
    public function setTaggedFriends($taggedFriends)
    {
        $this->tagged_friends = $taggedFriends;
        return $this;
    }
        
        
    /*    
     * Get taggedFriends
     *
     * @return collection $taggedFriends
     */
    public function getTaggedFriends()
    {
        return $this->tagged_friends;
    }
    /**
     * @var int $vote_count
     */
    protected $vote_count;

    /**
     * @var int $vote_sum
     */
    protected $vote_sum;

    /**
     * @var float $avg_rating
     */
    protected $avg_rating;

    /**
     * @var UserManager\Sonata\UserBundle\Document\GroupMediaRating
     */
    protected $rate = array();

    public function __construct()
    {
        $this->rate = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set voteCount
     *
     * @param int $voteCount
     * @return self
     */
    public function setVoteCount($voteCount)
    {
        $this->vote_count = $voteCount;
        return $this;
    }

    /**
     * Get voteCount
     *
     * @return int $voteCount
     */
    public function getVoteCount()
    {
        return $this->vote_count;
    }

    /**
     * Set voteSum
     *
     * @param int $voteSum
     * @return self
     */
    public function setVoteSum($voteSum)
    {
        $this->vote_sum = $voteSum;
        return $this;
    }

    /**
     * Get voteSum
     *
     * @return int $voteSum
     */
    public function getVoteSum()
    {
        return $this->vote_sum;
    }

    /**
     * Set avgRating
     *
     * @param float $avgRating
     * @return self
     */
    public function setAvgRating($avgRating)
    {
        $this->avg_rating = $avgRating;
        return $this;
    }

    /**
     * Get avgRating
     *
     * @return float $avgRating
     */
    public function getAvgRating()
    {
        return $this->avg_rating;
    }

    /**
     * Add rate
     *
     * @param UserManager\Sonata\UserBundle\Document\GroupMediaRating $rate
     */
    public function addRate(\UserManager\Sonata\UserBundle\Document\GroupMediaRating $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param UserManager\Sonata\UserBundle\Document\GroupMediaRating $rate
     */
    public function removeRate(\UserManager\Sonata\UserBundle\Document\GroupMediaRating $rate)
    {
        $this->rate->removeElement($rate);
    }

    /**
     * Get rate
     *
     * @return Doctrine\Common\Collections\Collection $rate
     */
    public function getRate()
    {
        return $this->rate;
    }
    /**
     * @var Media\MediaBundle\Document\AlbumMediaComment
     */
    protected $comment = array();


    /**
     * Add comment
     *
     * @param Media\MediaBundle\Document\AlbumMediaComment $comment
     */
    public function addComment(\Media\MediaBundle\Document\AlbumMediaComment $comment)
    {
        $this->comment[] = $comment;
    }

    /**
     * Remove comment
     *
     * @param Media\MediaBundle\Document\AlbumMediaComment $comment
     */
    public function removeComment(\Media\MediaBundle\Document\AlbumMediaComment $comment)
    {
        $this->comment->removeElement($comment);
    }

    /**
     * Get comment
     *
     * @return Doctrine\Common\Collections\Collection $comment
     */
    public function getComment()
    {
        return $this->comment;
    }
}
