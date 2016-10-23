<?php

namespace StoreManager\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserToStore
 */
class UserToStore
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
    private $childStoreId;

    /**
     * @var string
     */
    private $role;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var integer
     */
    private $userId;


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
     * @return UserToStore
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
     * Set childStoreId
     *
     * @param integer $childStoreId
     * @return UserToStore
     */
    public function setChildStoreId($childStoreId)
    {
        $this->childStoreId = $childStoreId;
    
        return $this;
    }

    /**
     * Get childStoreId
     *
     * @return integer 
     */
    public function getChildStoreId()
    {
        return $this->childStoreId;
    }

    /**
     * Set role
     *
     * @param string $role
     * @return UserToStore
     */
    public function setRole($role)
    {
        $this->role = $role;
    
        return $this;
    }

    /**
     * Get role
     *
     * @return string 
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserToStore
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

    /**
     * Set userId
     *
     * @param integer $userId
     * @return UserToStore
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
}