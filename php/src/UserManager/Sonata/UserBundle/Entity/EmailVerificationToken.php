<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * JobsDetails
 */
class EmailVerificationToken
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var string
     */
    private $verificationToken;

   /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $updatedAt;

     /**
     * @var integer
     */
    private $isActive;
    
    /**
     * @var bigint
     */
    private $expiryAt;

   
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
     * Set userId
     *
     * @param integer $userId
     * @return JobsDetails
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
     * Set verificationToken
     *
     * @param string $verificationToken
     * @return verificationToken
     */
    public function setVerificationToken($verificationToken)
    {
        $this->verificationToken = $verificationToken;
    
        return $this;
    }
    
    /**
     * get verificationToken
     * @return string
     */
    public function getVerificationToken()
    {
        return $this->verificationToken;

    }
    
    
     /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return JobsDetails
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return JobsDetails
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set isActive
     *
     * @param integer $isActive
     * @return isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    
        return $this;
    }

    /**
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> 84f95086356c0030dbb899863760bad0a922a1ec
     * Get expiryAt
     *
     * @return bigint 
     */
    public function getExpiryAt()
    {
        return $this->expiryAt;
    }
    
    /**
     * Set expiryAt
     *
     * @param bigint expiryAt
     * @return expiryAt
     */
    public function setExpiryAt($expiryAt)
    {
        $this->expiryAt = $expiryAt;
    
        return $this;
    }

    /**
     * Get isActive
     *
     * @return integer 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

   

}