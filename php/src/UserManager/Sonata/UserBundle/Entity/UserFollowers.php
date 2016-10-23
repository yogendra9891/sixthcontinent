<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserFollowers
 */
class UserFollowers
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
    private $toId;

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
     * @return UserFollowers
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
     * Set toId
     *
     * @param integer $toId
     * @return UserFollowers
     */
    public function setToId($toId)
    {
        $this->toId = $toId;
    
        return $this;
    }

    /**
     * Get toId
     *
     * @return integer 
     */
    public function getToId()
    {
        return $this->toId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserFollowers
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
