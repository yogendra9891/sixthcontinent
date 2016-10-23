<?php

namespace Utility\UniversalNotificationsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * NotifictionManager
 */
class NotifictionManager
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
    private $userType;

    /**
     * @var string
     */
    private $notificationType;

    /**
     * @var integer
     */
    private $notificationSetting;


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
     * @return NotifictionManager
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
     * Set userType
     *
     * @param string $userType
     * @return NotifictionManager
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;
    
        return $this;
    }

    /**
     * Get userType
     *
     * @return string 
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * Set notificationType
     *
     * @param string $notificationType
     * @return NotifictionManager
     */
    public function setNotificationType($notificationType)
    {
        $this->notificationType = $notificationType;
    
        return $this;
    }

    /**
     * Get notificationType
     *
     * @return string 
     */
    public function getNotificationType()
    {
        return $this->notificationType;
    }

    /**
     * Set notificationSetting
     *
     * @param integer $notificationSetting
     * @return NotifictionManager
     */
    public function setNotificationSetting($notificationSetting)
    {
        $this->notificationSetting = $notificationSetting;
    
        return $this;
    }

    /**
     * Get notificationSetting
     *
     * @return integer 
     */
    public function getNotificationSetting()
    {
        return $this->notificationSetting;
    }
}
