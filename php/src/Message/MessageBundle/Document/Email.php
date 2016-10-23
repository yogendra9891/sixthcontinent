<?php

namespace Message\MessageBundle\Document;



/**
 * Message\MessageBundle\Document\Email
 */
class Email
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var int $sender_id
     */
    protected $sender_id;

    /**
     * @var int $receiver_id
     */
    protected $receiver_id;

    /**
     * @var string $body
     */
    protected $body;

    /**
     * @var string $subject
     */
    protected $subject;

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
     * Set body
     *
     * @param string $body
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
     * @return string $body
     */
    public function getBody()
    {
        return $this->body;
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
}
