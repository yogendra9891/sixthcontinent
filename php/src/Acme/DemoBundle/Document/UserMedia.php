<?php

namespace Acme\DemoBundle\Document;



/**
 * Acme\DemoBundle\Document\UserMedia
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
     * @var string $description
     */
    protected $description;

    /**
     * @var int $enabled
     */
    protected $enabled;

    /**
     * @var string $contenttype
     */
    protected $contenttype;

    /**
     * @var int $width
     */
    protected $width;

    /**
     * @var int $height
     */
    protected $height;

    /**
     * @var int $content_size
     */
    protected $content_size;

    /**
     * @var int $copyright
     */
    protected $copyright;

    /**
     * @var string $author_name
     */
    protected $author_name;

    /**
     * @var int $cdn_is_flushable
     */
    protected $cdn_is_flushable;

    /**
     * @var int $cdn_status
     */
    protected $cdn_status;

    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var date $created_at
     */
    protected $created_at;

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
     * Set userid
     *
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
     * Set description
     *
     * @param string $description
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
     * @return string $description
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
     * Get enabled
     *
     * @return int $enabled
     */
    public function getEnabled()
    {
        return $this->enabled;
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
     * Set copyright
     *
     * @param int $copyright
     * @return self
     */
    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;
        return $this;
    }

    /**
     * Get copyright
     *
     * @return int $copyright
     */
    public function getCopyright()
    {
        return $this->copyright;
    }

    /**
     * Set authorName
     *
     * @param string $authorName
     * @return self
     */
    public function setAuthorName($authorName)
    {
        $this->author_name = $authorName;
        return $this;
    }

    /**
     * Get authorName
     *
     * @return string $authorName
     */
    public function getAuthorName()
    {
        return $this->author_name;
    }

    /**
     * Set cdnIsFlushable
     *
     * @param int $cdnIsFlushable
     * @return self
     */
    public function setCdnIsFlushable($cdnIsFlushable)
    {
        $this->cdn_is_flushable = $cdnIsFlushable;
        return $this;
    }

    /**
     * Get cdnIsFlushable
     *
     * @return int $cdnIsFlushable
     */
    public function getCdnIsFlushable()
    {
        return $this->cdn_is_flushable;
    }

    /**
     * Set cdnStatus
     *
     * @param int $cdnStatus
     * @return self
     */
    public function setCdnStatus($cdnStatus)
    {
        $this->cdn_status = $cdnStatus;
        return $this;
    }

    /**
     * Get cdnStatus
     *
     * @return int $cdnStatus
     */
    public function getCdnStatus()
    {
        return $this->cdn_status;
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
     * @param file $file
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
}