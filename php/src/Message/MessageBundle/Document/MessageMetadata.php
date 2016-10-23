<?php

namespace Message\MessageBundle\Document;



/**
 * Message\MessageBundle\Document\MessageMetadata
 */
class MessageMetadata
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var int $message_id
     */
    protected $message_id;

    /**
     * @var int $participant_id
     */
    protected $participant_id;

    /**
     * @var int $is_read
     */
    protected $is_read;


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
     * Set messageId
     *
     * @param int $messageId
     * @return self
     */
    public function setMessageId($messageId)
    {
        $this->message_id = $messageId;
        return $this;
    }

    /**
     * Get messageId
     *
     * @return int $messageId
     */
    public function getMessageId()
    {
        return $this->message_id;
    }

    /**
     * Set participantId
     *
     * @param int $participantId
     * @return self
     */
    public function setParticipantId($participantId)
    {
        $this->participant_id = $participantId;
        return $this;
    }

    /**
     * Get participantId
     *
     * @return int $participantId
     */
    public function getParticipantId()
    {
        return $this->participant_id;
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
}
