<?php

namespace Notification\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PushNotification
 */
class PushNotification
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $pushType;

    /**
     * @var integer
     */
    private $senderUserid;

    /**
     * @var integer
     */
    private $receiverUserid;

    /**
     * @var string
     */
    private $messageType;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var \DateTime
     */
    private $date;


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
     * Set pushType
     *
     * @param string $pushType
     * @return PushNotification
     */
    public function setPushType($pushType)
    {
        $this->pushType = $pushType;
    
        return $this;
    }

    /**
     * Get pushType
     *
     * @return string 
     */
    public function getPushType()
    {
        return $this->pushType;
    }

    /**
     * Set senderUserid
     *
     * @param integer $senderUserid
     * @return PushNotification
     */
    public function setSenderUserid($senderUserid)
    {
        $this->senderUserid = $senderUserid;
    
        return $this;
    }

    /**
     * Get senderUserid
     *
     * @return integer 
     */
    public function getSenderUserid()
    {
        return $this->senderUserid;
    }

    /**
     * Set receiverUserid
     *
     * @param integer $receiverUserid
     * @return PushNotification
     */
    public function setReceiverUserid($receiverUserid)
    {
        $this->receiverUserid = $receiverUserid;
    
        return $this;
    }

    /**
     * Get receiverUserid
     *
     * @return integer 
     */
    public function getReceiverUserid()
    {
        return $this->receiverUserid;
    }

    /**
     * Set messageType
     *
     * @param string $messageType
     * @return PushNotification
     */
    public function setMessageType($messageType)
    {
        $this->messageType = $messageType;
    
        return $this;
    }

    /**
     * Get messageType
     *
     * @return string 
     */
    public function getMessageType()
    {
        return $this->messageType;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return PushNotification
     */
    public function setMessage($message)
    {
        $this->message = $message;
    
        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return PushNotification
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    
        return $this;
    }

    /**
     * Get subject
     *
     * @return string 
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return PushNotification
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }
}
