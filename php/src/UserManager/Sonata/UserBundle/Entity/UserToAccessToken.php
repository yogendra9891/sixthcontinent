<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserToAccessToken
 */
class UserToAccessToken
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $accessToken;


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
     * Set accessToken
     *
     * @param string $accessToken
     * @return UserToAccessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    
        return $this;
    }

    /**
     * Get accessToken
     *
     * @return string 
     */
    public function getAccessToken()
    {
        return $this->accessToken;
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
    
     /**
     * @var integer
     */
    private $ipAddress = '';


    /**
     * Get id
     *
     * @return integer 
     */
    public function getIPAddress()
    {
        return $this->ipAddress;
    }


    /**
     * Set accessToken
     *
     * @param string $accessToken
     * @return UserToAccessToken
     */
    public function setIPAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    
        return $this;
    }
}
