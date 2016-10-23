<?php

namespace Notification\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EmailNotification
 */
class EmailNotification
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $email_from;

    /**
     * @var string
     */
    private $email_to;

    /**
     * @var integer
     */
    private $sender_userid;

    /**
     * @var integer
     */
    private $receiver_userid;

    /**
     * @var string
     */
    private $message_type;

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
     * Set email_from
     *
     * @param string $emailFrom
     * @return EmailNotification
     */
    public function setEmailFrom($emailFrom)
    {
        $this->email_from = $emailFrom;
    
        return $this;
    }

    /**
     * Get email_from
     *
     * @return string 
     */
    public function getEmailFrom()
    {
        return $this->email_from;
    }

    /**
     * Set email_to
     *
     * @param string $emailTo
     * @return EmailNotification
     */
    public function setEmailTo($emailTo)
    {
        $this->email_to = $emailTo;
    
        return $this;
    }

    /**
     * Get email_to
     *
     * @return string 
     */
    public function getEmailTo()
    {
        return $this->email_to;
    }

    /**
     * Set sender_userid
     *
     * @param integer $senderUserid
     * @return EmailNotification
     */
    public function setSenderUserid($senderUserid)
    {
        $this->sender_userid = $senderUserid;
    
        return $this;
    }

    /**
     * Get sender_userid
     *
     * @return integer 
     */
    public function getSenderUserid()
    {
        return $this->sender_userid;
    }

    /**
     * Set receiver_userid
     *
     * @param integer $receiverUserid
     * @return EmailNotification
     */
    public function setReceiverUserid($receiverUserid)
    {
        $this->receiver_userid = $receiverUserid;
    
        return $this;
    }

    /**
     * Get receiver_userid
     *
     * @return integer 
     */
    public function getReceiverUserid()
    {
        return $this->receiver_userid;
    }

    /**
     * Set message_type
     *
     * @param string $messageType
     * @return EmailNotification
     */
    public function setMessageType($messageType)
    {
        $this->message_type = $messageType;
    
        return $this;
    }

    /**
     * Get message_type
     *
     * @return string 
     */
    public function getMessageType()
    {
        return $this->message_type;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return EmailNotification
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
     * @return EmailNotification
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
     * @return EmailNotification
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