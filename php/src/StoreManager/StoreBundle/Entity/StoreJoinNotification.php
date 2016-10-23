<?php

namespace StoreManager\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StoreJoinNotification
 */
class StoreJoinNotification
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $senderId;

    /**
     * @var integer
     */
    private $receiverId;

    /**
     * @var integer
     */
    private $storeId;

    /**
     * @var \DateTime
     */
    private $createdAt;


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
     * Set senderId
     *
     * @param integer $senderId
     * @return StoreJoinNotification
     */
    public function setSenderId($senderId)
    {
        $this->senderId = $senderId;
    
        return $this;
    }

    /**
     * Get senderId
     *
     * @return integer 
     */
    public function getSenderId()
    {
        return $this->senderId;
    }

    /**
     * Set receiverId
     *
     * @param integer $receiverId
     * @return StoreJoinNotification
     */
    public function setReceiverId($receiverId)
    {
        $this->receiverId = $receiverId;
    
        return $this;
    }

    /**
     * Get receiverId
     *
     * @return integer 
     */
    public function getReceiverId()
    {
        return $this->receiverId;
    }

    /**
     * Set storeId
     *
     * @param integer $storeId
     * @return StoreJoinNotification
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return StoreJoinNotification
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}