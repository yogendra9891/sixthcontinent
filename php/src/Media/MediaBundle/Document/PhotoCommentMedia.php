<?php

namespace Media\MediaBundle\Document;



/**
 * Media\MediaBundle\Document\PhotoCommentMedia
 */
class PhotoCommentMedia
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $parent_id
     */
    protected $parent_id;

    /**
     * @var string $item_id
     */
    protected $item_id;

    /**
     * @var string $item_type
     */
    protected $item_type;
    
    /**
     * @var string $media_name
     */
    protected $media_name;

    /**
     * @var string $type
     */
    protected $type;

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
     * @var date $created_date
     */
    protected $created_date;

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
     * Set parentId
     *
     * @param string $parentId
     * @return self
     */
    public function setParentId($parentId)
    {
        $this->parent_id = $parentId;
        return $this;
    }

    /**
     * Get parentId
     *
     * @return string $parentId
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * Set itemId
     *
     * @param string $itemId
     * @return self
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;
        return $this;
    }

    /**
     * Get itemId
     *
     * @return string $itemId
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set itemType
     *
     * @param string $itemType
     * @return self
     */
    public function setItemType($itemType)
    {
        $this->item_type = $itemType;
        return $this;
    }

    /**
     * Get itemType
     *
     * @return string $itemType
     */
    public function getItemType()
    {
        return $this->item_type;
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
     * @var string $comment_id
     */
    protected $comment_id;


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
}
