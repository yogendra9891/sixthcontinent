<?php

namespace StoreManager\StoreBundle\Document;



/**
 * StoreManager\StoreBundle\Document\BillerCycleLog
 */
class BillerCycleLog
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $from_id
     */
    protected $from_id;

    /**
     * @var string $to_id
     */
    protected $to_id;

    /**
     * @var string $shop_id
     */
    protected $shop_id;

    /**
     * @var string $shop_obj
     */
    protected $shop_obj;

    /**
     * @var string $message
     */
    protected $message;

    /**
     * @var string $message_type
     */
    protected $message_type;

    /**
     * @var string $message_status
     */
    protected $message_status;

    /**
     * @var boolean $is_active
     */
    protected $is_active;

    /**
     * @var string $date_group
     */
    protected $date_group;

    /**
     * @var date $created_at
     */
    protected $created_at;

    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var string $send_type
     */
    protected $send_type;

    /**
     * @var string $msg_code
     */
    protected $msg_code;


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
     * Set fromId
     *
     * @param string $fromId
     * @return self
     */
    public function setFromId($fromId)
    {
        $this->from_id = $fromId;
        return $this;
    }

    /**
     * Get fromId
     *
     * @return string $fromId
     */
    public function getFromId()
    {
        return $this->from_id;
    }

    /**
     * Set toId
     *
     * @param string $toId
     * @return self
     */
    public function setToId($toId)
    {
        $this->to_id = $toId;
        return $this;
    }

    /**
     * Get toId
     *
     * @return string $toId
     */
    public function getToId()
    {
        return $this->to_id;
    }

    /**
     * Set shopId
     *
     * @param string $shopId
     * @return self
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;
        return $this;
    }

    /**
     * Get shopId
     *
     * @return string $shopId
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set shopObj
     *
     * @param string $shopObj
     * @return self
     */
    public function setShopObj($shopObj)
    {
        $this->shop_obj = $shopObj;
        return $this;
    }

    /**
     * Get shopObj
     *
     * @return string $shopObj
     */
    public function getShopObj()
    {
        return $this->shop_obj;
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
     * Set messageStatus
     *
     * @param string $messageStatus
     * @return self
     */
    public function setMessageStatus($messageStatus)
    {
        $this->message_status = $messageStatus;
        return $this;
    }

    /**
     * Get messageStatus
     *
     * @return string $messageStatus
     */
    public function getMessageStatus()
    {
        return $this->message_status;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return self
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;
        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean $isActive
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * Set dateGroup
     *
     * @param string $dateGroup
     * @return self
     */
    public function setDateGroup($dateGroup)
    {
        $this->date_group = $dateGroup;
        return $this;
    }

    /**
     * Get dateGroup
     *
     * @return string $dateGroup
     */
    public function getDateGroup()
    {
        return $this->date_group;
    }

    /**
     * Set createdAt
     *
     * @param date $createdAt
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return date $createdAt
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt
     *
     * @param date $updatedAt
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return date $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set sendType
     *
     * @param string $sendType
     * @return self
     */
    public function setSendType($sendType)
    {
        $this->send_type = $sendType;
        return $this;
    }

    /**
     * Get sendType
     *
     * @return string $sendType
     */
    public function getSendType()
    {
        return $this->send_type;
    }

    /**
     * Set msgCode
     *
     * @param string $msgCode
     * @return self
     */
    public function setMsgCode($msgCode)
    {
        $this->msg_code = $msgCode;
        return $this;
    }

    /**
     * Get msgCode
     *
     * @return string $msgCode
     */
    public function getMsgCode()
    {
        return $this->msg_code;
    }
    /**
     * @var string $msg_subject
     */
    protected $msg_subject;


    /**
     * Set msgSubject
     *
     * @param string $msgSubject
     * @return self
     */
    public function setMsgSubject($msgSubject)
    {
        $this->msg_subject = $msgSubject;
        return $this;
    }

    /**
     * Get msgSubject
     *
     * @return string $msgSubject
     */
    public function getMsgSubject()
    {
        return $this->msg_subject;
    }
}
