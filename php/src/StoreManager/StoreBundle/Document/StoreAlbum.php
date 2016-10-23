<?php

namespace StoreManager\StoreBundle\Document;



/**
 * StoreManager\StoreBundle\Document\StoreAlbum
 */
class StoreAlbum
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $store_id
     */
    protected $store_id;

    /**
     * @var string $store_album_name
     */
    protected $store_album_name;

    /**
     * @var int $user_id
     */
    protected $user_id;

    /**
     * @var string $store_album_desc
     */
    protected $store_album_desc;

    /**
     * @var \DateTime
     */
    protected $store_album_creted;

    /**
     * @var \DateTime
     */
    protected $store_album_updated;


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
     * Set storeId
     *
     * @param string $storeId
     * @return self
     */
    public function setStoreId($storeId)
    {
        $this->store_id = $storeId;
        return $this;
    }

    /**
     * Get storeId
     *
     * @return string $storeId
     */
    public function getStoreId()
    {
        return $this->store_id;
    }

    /**
     * Set storeAlbumName
     *
     * @param string $storeAlbumName
     * @return self
     */
    public function setStoreAlbumName($storeAlbumName)
    {
        $this->store_album_name = $storeAlbumName;
        return $this;
    }

    /**
     * Get storeAlbumName
     *
     * @return string $storeAlbumName
     */
    public function getStoreAlbumName()
    {
        return $this->store_album_name;
    }

    /**
     * Set userId
     *
     * @param int $userId
     * @return self
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;
        return $this;
    }

    /**
     * Get userId
     *
     * @return int $userId
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set storeAlbumDesc
     *
     * @param string $storeAlbumDesc
     * @return self
     */
    public function setStoreAlbumDesc($storeAlbumDesc)
    {
        $this->store_album_desc = $storeAlbumDesc;
        return $this;
    }

    /**
     * Get storeAlbumDesc
     *
     * @return string $storeAlbumDesc
     */
    public function getStoreAlbumDesc()
    {
        return $this->store_album_desc;
    }

    /**
     * Set updatedAt 
     *
     * @param \DateTime $storeAlbumUpdated
     * @return self
     */
    public function setStoreAlbumUpdated($storeAlbumUpdated)
    {
        $this->store_album_updated = $storeAlbumUpdated;
        return $this;
    }

    /**
     * Get storeAlbumUpdated
     *
     * @return \DateTime $storeAlbumUpdated
     */
    public function getStoreAlbumUpdated()
    {
        return $this->store_album_updated;
    }

    /**
     * Set storeAlbumCreted
     *
     * @param \DateTime  $storeAlbumCreted
     * @return self
     */
    public function setStoreAlbumCreted($storeAlbumCreted)
    {
        $this->store_album_creted = $storeAlbumCreted;
        return $this;
    }

    /**
     * Get storeAlbumCreted
     *
     * @return \DateTime $storeAlbumCreted
     */
    public function getStoreAlbumCreted()
    {
        return $this->store_album_creted;
    }
    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var date $created_at
     */
    protected $created_at;


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
}
