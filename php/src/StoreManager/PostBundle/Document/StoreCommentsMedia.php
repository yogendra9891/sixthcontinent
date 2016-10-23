<?php

namespace StoreManager\PostBundle\Document;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * StoreManager\PostBundle\Document\StoreCommentsMedia
 */
class StoreCommentsMedia
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $store_comment_id
     */
    protected $store_comment_id;

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
     * @var int $is_featured
     */
    protected $is_featured;

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
     * Set storeCommentId
     *
     * @param string $storeCommentId
     * @return self
     */
    public function setStoreCommentId($storeCommentId)
    {
        $this->store_comment_id = $storeCommentId;
        return $this;
    }

    /**
     * Get storeCommentId
     *
     * @return string $storeCommentId
     */
    public function getStoreCommentId()
    {
        return $this->store_comment_id;
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
    public function setFile(UploadedFile $file= null)
    {
        $this->file = $file;
        return $this;
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
     * Set imageType
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
   
    public function upload($comment_id, $key, $file_name)
    { 
       // create directory having title of postId. 
       // since post id is string so directory name would be string type
        $source= $_FILES['commentfile']['tmp_name'][$key];
        $pre_upload_media_dir =  __DIR__."/../../../../web/uploads/documents/store/comments/original/";
        $upload_media_dir = $pre_upload_media_dir.$comment_id.'/';
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir . $file_name);
        } 
        else { 
            $destination = \mkdir($pre_upload_media_dir.$comment_id, 0777, true);
            $upload_media_dir = $pre_upload_media_dir.$comment_id.'/';
            move_uploaded_file($source, $upload_media_dir . $file_name);
        }
    }
    
    public function upload1($comment_id, $key, $file_name)
    { 
       // create directory having title of postId. 
       // since post id is string so directory name would be string type
        $source= $_FILES['group_media']['tmp_name'];
        $pre_upload_media_dir =  __DIR__."/../../../../web/uploads/documents/store/comments/original/";
        $upload_media_dir = $pre_upload_media_dir.$comment_id.'/';
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir . $file_name);
        } 
        else { 
            $destination = \mkdir($pre_upload_media_dir.$comment_id, 0777, true);
            $upload_media_dir = $pre_upload_media_dir.$comment_id.'/';
            move_uploaded_file($source, $upload_media_dir . $file_name);
            
        }
    }
}
