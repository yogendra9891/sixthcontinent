<?php

namespace Post\PostBundle\Document;



/**
 * Post\PostBundle\Document\CommentMedia
 */
class CommentMedia
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $comment_id
     */
    protected $comment_id;

    /**
     * @var string $media_name
     */
    protected $media_name;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var string $is_active
     */
    protected $is_active;

    /**
     * @var int $is_featured
     */
    protected $is_featured;

    /**
     * @var date $created_at
     */
    protected $created_at;

    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var string $file
     */
    protected $file;

    /**
     * @var string $path
     */
    protected $path;
    /**
     * @var int $image_type
     */
    protected $image_type;


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
     * Set commentId
     *
     * @param string $commentId
     * @return self
     */
    public function setCommentId($commentId)
    {
        $this->comment_id = $commentId;
        return $this;
    }

    /**
     * Get commentId
     *
     * @return string $commentId
     */
    public function getCommentId()
    {
        return $this->comment_id;
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
     * Set isActive
     *
     * @param string $isActive
     * @return self
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;
        return $this;
    }

    /**
     * Get isActive
     *
     * @return string $isActive
     */
    public function getIsActive()
    {
        return $this->is_active;
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
     * Set file
     *
     * @param string $file
     * @return self
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * Get file
     *
     * @return string $file
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
    
     public function upload($message_id, $key, $file_name)
    { 
       // create directory having title of postId. 
       // since post id is string so directory name would be string type
        $source= $_FILES['commentfile']['tmp_name'][$key];
        $pre_upload_media_dir =  __DIR__."/../../../../web/uploads/documents/groups/comments/original/";
        $upload_media_dir = $pre_upload_media_dir.$message_id.'/';
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir . $file_name);
        } 
        else { 
            $destination = \mkdir($pre_upload_media_dir.$message_id, 0777, true);
            $upload_media_dir = $pre_upload_media_dir.$message_id.'/';
            move_uploaded_file($source, $upload_media_dir . $file_name);
            
        }
    }
}
