<?php

namespace Message\MessageBundle\Document;



/**
 * Message\MessageBundle\Document\Message
 */
class Message
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var int $thread_id
     */
    protected $thread_id;

    /**
     * @var int $sender_id
     */
    protected $sender_id;

    /**
     * @var int $sender_userid
     */
    protected $sender_userid;

    /**
     * @var text $body
     */
    protected $body;

    /**
     * @var string $message_type
     */
    protected $message_type;

    /**
     * @var date $created_at
     */
    protected $created_at;


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
     * Set threadId
     *
     * @param int $threadId
     * @return self
     */
    public function setThreadId($threadId)
    {
        $this->thread_id = $threadId;
        return $this;
    }

    /**
     * Get threadId
     *
     * @return int $threadId
     */
    public function getThreadId()
    {
        return $this->thread_id;
    }

    /**
     * Set senderId
     *
     * @param int $senderId
     * @return self
     */
    public function setSenderId($senderId)
    {
        $this->sender_id = $senderId;
        return $this;
    }

    /**
     * Get senderId
     *
     * @return int $senderId
     */
    public function getSenderId()
    {
        return $this->sender_id;
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
     * Set body
     *
     * @param text $body
     * @return self
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Get body
     *
     * @return text $body
     */
    public function getBody()
    {
        return $this->body;
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
     * Set id
     *
     * @param int $id
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    /**
     * @var int $receiver_id
     */
    protected $receiver_id;

    /**
     * @var string $subject
     */
    protected $subject;

    /**
     * @var int $is_read
     */
    protected $is_read;

    /**
     * @var int $is_spam
     */
    protected $is_spam;


    /**
     * Set receiverId
     *
     * @param int $receiverId
     * @return self
     */
    public function setReceiverId($receiverId)
    {
        $this->receiver_id = $receiverId;
        return $this;
    }

    /**
     * Get receiverId
     *
     * @return int $receiverId
     */
    public function getReceiverId()
    {
        return $this->receiver_id;
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
     * Set isRead
     *
     * @param int $isRead
     * @return self
     */
    public function setIsRead($isRead)
    {
        $this->is_read = $isRead;
        return $this;
    }

    /**
     * Get isRead
     *
     * @return int $isRead
     */
    public function getIsRead()
    {
        return $this->is_read;
    }

    /**
     * Set isSpam
     *
     * @param int $isSpam
     * @return self
     */
    public function setIsSpam($isSpam)
    {
        $this->is_spam = $isSpam;
        return $this;
    }

    /**
     * Get isSpam
     *
     * @return int $isSpam
     */
    public function getIsSpam()
    {
        return $this->is_spam;
    }
    /**
     * @var date $updated_at
     */
    protected $updated_at;


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
     * @var string $profileType
     */
    protected $profile_type;
    /**
     * @var string $type
     */
    protected $type;
    
    /**
     * Set profileType
     *
     * @param string $profileType
     * @return self
     */
    public function setProfileType($profileType)
    {
        $this->profile_type = $profileType;
        return $this;
    }

    /**
     * Get profileType
     *
     * @return string $profileType
     */
    public function getProfileType()
    {
        return $this->profile_type;
    }
    
    
    /**
     * Set type
     *
     * @param string $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }
    
    
    /**
     * @var collection $deleteby
     */
    protected $deleteby;


    /**
     * Set deleteby
     *
     * @param collection $deleteby
     * @return self
     */
    public function setDeleteby($deleteby)
    {
        $this->deleteby = $deleteby;
        return $this;
    }

    /**
     * Get deleteby
     *
     * @return collection $deleteby
     */
    public function getDeleteby()
    {
        return $this->deleteby;
    }
    /**
     * @var collection $read_by
     */
    protected $read_by;


    /**
     * Set readBy
     *
     * @param collection $readBy
     * @return self
     */
    public function setReadBy($readBy)
    {
        $this->read_by = $readBy;
        return $this;
    }

    /**
     * Get readBy
     *
     * @return collection $readBy
     */
    public function getReadBy()
    {
        return $this->read_by;
    }
    /**
     * @var int $is_view
     */
    protected $is_view=0;


    /**
     * Set isView
     *
     * @param int $isView
     * @return self
     */
    public function setIsView($isView)
    {
        $this->is_view = $isView;
        return $this;
    }

    /**
     * Get isView
     *
     * @return int $isView
     */
    public function getIsView()
    {
        return $this->is_view;
    }
   
    
    /**
     * @var collection $is_view_by
     */
    protected $is_view_by;


    /**
     * Set isViewBy
     *
     * @param collection $isViewBy
     * @return self
     */
    public function setIsViewBy($isViewBy)
    {
        $this->is_view_by = $isViewBy;
        return $this;
    }

    /**
     * Get isViewBy
     *
     * @return collection $isViewBy
     */
    public function getIsViewBy()
    {
        return $this->is_view_by;
    }
}
