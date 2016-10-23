<?php

namespace Dashboard\DashboardManagerBundle\Document;



/**
 * Dashboard\DashboardManagerBundle\Document\DashboardPostMedia
 */
class DashboardPostMedia
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $post_id
     */
    protected $post_id;

    /**
     * @var string $media_name
     */
    protected $media_name;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var date $created_date
     */
    protected $created_date;
    
    /**
     * @var int $media_status
     */
    protected $media_status;
    
    /**
     * @var string $path
     */
    protected $path;
    
    /**
     * @var int $is_featured
     */
    protected $is_featured;

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
     * Set postId
     *
     * @param string $postId
     * @return self
     */
    public function setPostId($postId)
    {
        $this->post_id = $postId;
        return $this;
    }

    /**
     * Get postId
     *
     * @return string $postId
     */
    public function getPostId()
    {
        return $this->post_id;
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
     * Set type
     *
     * @param string $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set createdDate
     *
     * @param date $createdDate
     * @return self
     */
    public function setCreatedDate($createdDate)
    {
        $this->created_date = $createdDate;
        return $this;
    }

    /**
     * Get createdDate
     *
     * @return date $createdDate
     */
    public function getCreatedDate()
    {
        return $this->created_date;
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
    * upload the file
    * @param string $post_id
    * @param int $key
    * @param string $file_name
    */
    public function upload($post_id, $key, $file_name)
    { 
       // create directory having title of postId. 
       // since post id is string so directory name would be string type
        $source= $_FILES['postfile']['tmp_name'][$key];
        $pre_upload_media_dir =  __DIR__."/../../../../web/uploads/documents/dashboard/post/original/";
        $upload_media_dir = $pre_upload_media_dir.$post_id.'/';
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir . $file_name);
        } 
        else { 
            $destination = \mkdir($pre_upload_media_dir.$post_id, 0777, true);
            $upload_media_dir = $pre_upload_media_dir.$post_id.'/';
            move_uploaded_file($source, $upload_media_dir . $file_name);
            
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
     * Get imageType
     *
     * @return int $imageType
     */
    public function getImageType()
    {
        return $this->image_type;
    }
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
