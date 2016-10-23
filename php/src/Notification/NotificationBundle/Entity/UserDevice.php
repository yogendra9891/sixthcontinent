<?php

namespace Notification\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserDevice
 */
class UserDevice
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
    private $deviceId;

    /**
     * @var string
     */
    private $deviceType;

    /**
     * @var \DateTime
     */
    private $createdAt;
    
    /**
     * @var string
     */
    private $deviceLang;


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
     * @return UserDevice
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
     * Set deviceId
     *
     * @param string $deviceId
     * @return UserDevice
     */
    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;
    
        return $this;
    }

    /**
     * Get deviceId
     *
     * @return string 
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * Set deviceType
     *
     * @param string $deviceType
     * @return UserDevice
     */
    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;
    
        return $this;
    }

    /**
     * Get deviceType
     *
     * @return string 
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserDevice
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
     * @var string
     */
    private $appId;

    /**
     * @var string
     */
    private $appType;


    /**
     * Set appId
     *
     * @param string $appId
     * @return UserDevice
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    
        return $this;
    }

    /**
     * Get appId
     *
     * @return string 
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Set appType
     *
     * @param string $appType
     * @return UserDevice
     */
    public function setAppType($appType)
    {
        $this->appType = $appType;
    
        return $this;
    }

    /**
     * Get appType
     *
     * @return string 
     */
    public function getAppType()
    {
        return $this->appType;
    }
    
    
    /**
     * Set deviceLang
     *
     * @param string $deviceLang
     * @return UserDevice
     */
    public function setDeviceLang($deviceLang)
    {
        $this->deviceLang = $deviceLang;
    
        return $this;
    }

    /**
     * Get deviceLang
     *
     * @return string 
     */
    public function getDeviceLang()
    {
        return $this->deviceLang;
    }
    /**
     * @var string
     */
    private $uniqueDeviceID;


    /**
     * Set uniqueDeviceID
     *
     * @param string $uniqueDeviceID
     * @return UserDevice
     */
    public function setUniqueDeviceID($uniqueDeviceID)
    {
        $this->uniqueDeviceID = $uniqueDeviceID;
    
        return $this;
    }

    /**
     * Get uniqueDeviceID
     *
     * @return string 
     */
    public function getUniqueDeviceID()
    {
        return $this->uniqueDeviceID;
    }
}