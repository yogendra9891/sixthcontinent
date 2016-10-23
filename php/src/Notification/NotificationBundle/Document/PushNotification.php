<?php

namespace Notification\NotificationBundle\Document;



/**
 * Notification\NotificationBundle\Document\PushNotification
 */
class PushNotification
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $push_type
     */
    protected $push_type;

    /**
     * @var int $sender_userid
     */
    protected $sender_userid;

    /**
     * @var int $receiver_userid
     */
    protected $receiver_userid;

    /**
     * @var int $readvalue
     */
    protected $readvalue;

    /**
     * @var int $deletevalue
     */
    protected $deletevalue;

    /**
     * @var string $message_type
     */
    protected $message_type;

    /**
     * @var string $message
     */
    protected $message;

    /**
     * @var string $subject
     */
    protected $subject;

    /**
     * @var date $date
     */
    protected $date;


    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set pushType
     *
     * @param string $pushType
     * @return self
     */
    public function setPushType($pushType)
    {
        $this->push_type = $pushType;
        return $this;
    }

    /**
     * Get pushType
     *
     * @return string $pushType
     */
    public function getPushType()
    {
        return $this->push_type;
    }

    /**
     * Set senderUserid
     *
     * @param int $senderUserid
     * @return self
     */
    public function setSenderUserid($senderUserid)
    {
        $this->sender_userid = $senderUserid;
        return $this;
    }

    /**
     * Get senderUserid
     *
     * @return int $senderUserid
     */
    public function getSenderUserid()
    {
        return $this->sender_userid;
    }

    /**
     * Set receiverUserid
     *
     * @param int $receiverUserid
     * @return self
     */
    public function setReceiverUserid($receiverUserid)
    {
        $this->receiver_userid = $receiverUserid;
        return $this;
    }

    /**
     * Get receiverUserid
     *
     * @return int $receiverUserid
     */
    public function getReceiverUserid()
    {
        return $this->receiver_userid;
    }

    /**
     * Set readvalue
     *
     * @param int $readvalue
     * @return self
     */
    public function setReadvalue($readvalue)
    {
        $this->readvalue = $readvalue;
        return $this;
    }

    /**
     * Get readvalue
     *
     * @return int $readvalue
     */
    public function getReadvalue()
    {
        return $this->readvalue;
    }

    /**
     * Set deletevalue
     *
     * @param int $deletevalue
     * @return self
     */
    public function setDeletevalue($deletevalue)
    {
        $this->deletevalue = $deletevalue;
        return $this;
    }

    /**
     * Get deletevalue
     *
     * @return int $deletevalue
     */
    public function getDeletevalue()
    {
        return $this->deletevalue;
    }

    /**
     * Set messageType
     *
     * @param string $messageType
     * @return self
     */
    public function setMessageType($messageType)
    {
        $this->message_type = $messageType;
        return $this;
    }

    /**
     * Get messageType
     *
     * @return string $messageType
     */
    public function getMessageType()
    {
        return $this->message_type;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return self
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get message
     *
     * @return string $message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set subject
     *
     * @param string $subject
     * @return self
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Get subject
     *
     * @return string $subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set date
     *
     * @param date $date
     * @return self
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Get date
     *
     * @return date $date
     */
    public function getDate()
    {
        return $this->date;
    }
}
