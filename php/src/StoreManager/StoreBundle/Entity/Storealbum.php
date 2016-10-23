<?php

namespace StoreManager\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Storealbum
 */
class Storealbum
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $storeId;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var string
     */
    private $storeAlbumName;

    /**
     * @var string
     */
    private $storeAlbumDesc;

    /**
     * @var integer
     */
    private $privacySetting;
    
    /**
     * @var \DateTime
     */
    private $storeAlbumCreted;

    /**
     * @var \DateTime
     */
    private $storeAlbumUpdated;
   
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set storeId
     *
     * @param integer $storeId
     * @return Storealbum
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    
        return $this;
    }

    /**
     * Get storeId
     *
     * @return integer 
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return Storealbum
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set storeAlbumName
     *
     * @param string $storeAlbumName
     * @return Storealbum
     */
    public function setStoreAlbumName($storeAlbumName)
    {
        $this->storeAlbumName = $storeAlbumName;
    
        return $this;
    }

    /**
     * Get storeAlbumName
     *
     * @return string 
     */
    public function getStoreAlbumName()
    {
        return $this->storeAlbumName;
    }

    /**
     * Set storeAlbumDesc
     *
     * @param string $storeAlbumDesc
     * @return Storealbum
     */
    public function setStoreAlbumDesc($storeAlbumDesc)
    {
        $this->storeAlbumDesc = $storeAlbumDesc;
    
        return $this;
    }

    /**
     * Get storeAlbumDesc
     *
     * @return string 
     */
    public function getStoreAlbumDesc()
    {
        return $this->storeAlbumDesc;
    }

    /**
     * Set storeAlbumCreted
     *
     * @param \DateTime $storeAlbumCreted
     * @return Storealbum
     */
    public function setStoreAlbumCreted($storeAlbumCreted)
    {
        $this->storeAlbumCreted = $storeAlbumCreted;
    
        return $this;
    }

    /**
     * Get storeAlbumCreted
     *
     * @return \DateTime 
     */
    public function getStoreAlbumCreted()
    {
        return $this->storeAlbumCreted;
    }

    /**
     * Set storeAlbumUpdated
     *
     * @param \DateTime $storeAlbumUpdated
     * @return Storealbum
     */
    public function setStoreAlbumUpdated($storeAlbumUpdated)
    {
        $this->storeAlbumUpdated = $storeAlbumUpdated;
    
        return $this;
    }

    /**
     * Get storeAlbumUpdated
     *
     * @return \DateTime 
     */
    public function getStoreAlbumUpdated()
    {
        return $this->storeAlbumUpdated;
    }
    
    /**
     * Set privacySetting
     *
     * @param integer $privacySetting
     * @return Storealbum
     */
    public function setPrivacySetting($privacySetting)
    {
        $this->privacySetting = $privacySetting;
    
        return $this;
    }

    /**
     * Get privacySetting
     *
     * @return integer 
     */
    public function getPrivacySetting()
    {
        return $this->privacySetting;
    }
    
}