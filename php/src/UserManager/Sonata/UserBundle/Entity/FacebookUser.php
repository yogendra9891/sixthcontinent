<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FacebookUser
 */
class FacebookUser
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
    private $facebookId;


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
     * @return FacebookUser
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
     * Set facebookId
     *
     * @param string $facebookId
     * @return FacebookUser
     */
    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;
    
        return $this;
    }

    /**
     * Get facebookId
     *
     * @return string 
     */
    public function getFacebookId()
    {
        return $this->facebookId;
    }
    /**
     * @var string
     */
    private $facebookAccessToken;

    
    /**
     * @var string
     */
    private $expiryTime;

    /**
     * @var boolean
     */
    private $syncStatus;


    /**
     * Set facebookAccessToken
     *
     * @param string $facebookAccessToken
     * @return FacebookUser
     */
    public function setFacebookAccessToken($facebookAccessToken)
    {
        $this->facebookAccessToken = $facebookAccessToken;
    
        return $this;
    }

    /**
     * Get facebookAccessToken
     *
     * @return string 
     */
    public function getFacebookAccessToken()
    {
        return $this->facebookAccessToken;
    }


    /**
     * Set expiryTime
     *
     * @param string $expiryTime
     * @return FacebookUser
     */
    public function setExpiryTime($expiryTime)
    {
        $this->expiryTime = $expiryTime;
    
        return $this;
    }

    /**
     * Get expiryTime
     *
     * @return string 
     */
    public function getExpiryTime()
    {
        return $this->expiryTime;
    }

    /**
     * Set syncStatus
     *
     * @param boolean $syncStatus
     * @return FacebookUser
     */
    public function setSyncStatus($syncStatus)
    {
        $this->syncStatus = $syncStatus;
    
        return $this;
    }

    /**
     * Get syncStatus
     *
     * @return boolean 
     */
    public function getSyncStatus()
    {
        return $this->syncStatus;
    }
    
    /**
     * @var boolean
     */
    private $publishActions;
    
    /**
     * Set publishActions
     *
     * @param boolean $publishActions
     * @return FacebookUser
     */
    public function setPublishActions($publishActions)
    {
        $this->publishActions = $publishActions;
    
        return $this;
    }

    /**
     * Get publishActions
     *
     * @return boolean 
     */
    public function getPublishActions()
    {
        return $this->publishActions;
    }
}