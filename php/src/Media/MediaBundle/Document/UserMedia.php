<?php

namespace Media\MediaBundle\Document;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;



/**
 * Media\MediaBundle\Document\UserMedia
 */
class UserMedia
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var int $userid
     */
    protected $userid;
    /**
     * @var string $albumid
     */
    protected $albumid;
    /**
     * @var string $contenttype
     */
    protected $contenttype;
    /**
     * @var int $is_featured
     */
    protected $is_featured;
    /**
     * @var int $enabled
     */
    protected $enabled;

    /**
     * @var int $width
     */
    protected $width;

    /**
     * @var int $height
     */
    protected $height;

    /**
     * @var int $access_label
     */
    protected $access_label;

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
    
    protected $temp;
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
     * Set name
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * Set description
     *
     * @param longtext $description
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return longtext $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set enabled
     *
     * @param int $enabled
     * @return self
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Set userid
     * @param int $userid
     * @return self
     */
    public function setUserid($userid)
    {
        $this->userid = $userid;
        return $this;
    }

    /**
     * Get userid
     *
     * @return int $userid
     */
    public function getUserid()
    {
        return $this->userid;
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
     * Set contenttype
     *
     * @param string $contenttype
     * @return self
     */
    public function setContenttype($contenttype)
    {
        $this->contenttype = $contenttype;
        return $this;
    }

    /**
     * Get contenttype
     *
     * @return string $contenttype
     */
    public function getContenttype()
    {
        return $this->contenttype;
    }

    /**
     * Get enabled
     *
     * @return int $enabled
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set width
     *
     * @param int $width
     * @return self
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Get width
     *
     * @return int $width
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param int $height
     * @return self
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Get height
     *
     * @return int $height
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set accessLabel
     *
     * @param int $accessLabel
     * @return self
     */
    public function setAccessLabel($accessLabel)
    {
        $this->access_label = $accessLabel;
        return $this;
    }

    /**
     * Get accessLabel
     *
     * @return int $accessLabel
     */
    public function getAccessLabel()
    {
        return $this->access_label;
    }

    /**
     * Set file
     *
     * @param array $file
     * @return self
     */
     public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file
     *
     * @return array $file
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
	public function getAbsolutePath()
    {
        return null === $this->path? null : $this->getUploadRootDir().'/'.$this->path;
    }

    public function getWebPath()
    {
        return null === $this->path ? null : $this->getUploadDir().'/'.$this->path;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        $filetype = $this->getFile()->getMimeType();
        $filetype = explode('/',$filetype);
        if($filetype[0] == 'image')
            {
              return 'uploads/documents/1/image';
            }
        else {
            
            return 'uploads/documents/1/video';
        }
    }
    
    public function preUpload()
    {
        if (null !== $this->getFile()) {
            // do whatever you want to generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->path = $filename.'.'.$this->getFile()->guessExtension();
        }
    }
    
     public function upload($user_id,$key,$file_name,$album_id)
    {
        $source= $_FILES['user_media']['tmp_name'][$key];
        //$file_name = $_FILES['user_media']['name'][$key];
        $pre_upload_media_dir =  __DIR__."/../../../../web/uploads/users/media/original/";
        if($album_id){
          $upload_media_dir = $pre_upload_media_dir.$user_id.'/'.$album_id.'/';  
        } else {
         $upload_media_dir = $pre_upload_media_dir.$user_id.'/';
        }
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir .$file_name);
        } 
        else { 
            $destination = \mkdir($pre_upload_media_dir.$user_id.'/'.$album_id, 0777, true);
            move_uploaded_file($source, $upload_media_dir .$file_name);
        }
    }
   
    /**
     * Called before entity removal
     *
     * 
     */
    public function removeUpload()
    {
            if ($file = $this->getAbsolutePath()) {
                    unlink($file); 
            }
    }
    
    /**
     * uplaod the image for user profile.
     * @param type $user_id
     * @param type $key
     * @param type $file_name
     * @return boolean
     */
    public function profileImageUpload($user_id, $file_name)
    {
        $source= $_FILES['user_media']['tmp_name'];
        //$file_name = $_FILES['user_media']['name'][$key];
        $pre_upload_media_dir =  __DIR__."/../../../../web/uploads/users/media/original/";
        $upload_media_dir = $pre_upload_media_dir.$user_id.'/';  

        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir .$file_name);
        } 
        else { 
            $destination = \mkdir($pre_upload_media_dir.$user_id.'/', 0777, true);
            move_uploaded_file($source, $upload_media_dir .$file_name);
        }
    }

    /**
     * @var string $description
     */
    protected $description;

    /**
     * @var int $content_size
     */
    protected $content_size;

    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var date $created_at
     */
    protected $created_at;

    /**
     * @var int $image_type
     */
    protected $image_type;


    /**
     * Set contentSize
     *
     * @param int $contentSize
     * @return self
     */
    public function setContentSize($contentSize)
    {
        $this->content_size = $contentSize;
        return $this;
    }

    /**
     * Get contentSize
     *
     * @return int $contentSize
     */
    public function getContentSize()
    {
        return $this->content_size;
    }

    /**
     * Set updatedAt
     *
     * @param date $updatedAt
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return date $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
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
     * @var Media\MediaBundle\Document\UserMediaRating
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
     * @param Media\MediaBundle\Document\UserMediaRating $rate
     */
    public function addRate(\Media\MediaBundle\Document\UserMediaRating $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param Media\MediaBundle\Document\UserMediaRating $rate
     */
    public function removeRate(\Media\MediaBundle\Document\UserMediaRating $rate)
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
