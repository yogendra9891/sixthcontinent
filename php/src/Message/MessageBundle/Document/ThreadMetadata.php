<?php

namespace Message\MessageBundle\Document;



/**
 * Message\MessageBundle\Document\ThreadMetadata
 */
class ThreadMetadata
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
     * @var int $participant_id
     */
    protected $participant_id;

    /**
     * @var int $is_deleted
     */
    protected $is_deleted;

    /**
     * @var date $last_participant_message_date
     */
    protected $last_participant_message_date;


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
     * Set isDeleted
     *
     * @param int $isDeleted
     * @return self
     */
    public function setIsDeleted($isDeleted)
    {
        $this->is_deleted = $isDeleted;
        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return int $isDeleted
     */
    public function getIsDeleted()
    {
        return $this->is_deleted;
    }

    /**
     * Set lastParticipantMessageDate
     *
     * @param date $lastParticipantMessageDate
     * @return self
     */
    public function setLastParticipantMessageDate($lastParticipantMessageDate)
    {
        $this->last_participant_message_date = $lastParticipantMessageDate;
        return $this;
    }

    /**
     * Get lastParticipantMessageDate
     *
     * @return date $lastParticipantMessageDate
     */
    public function getLastParticipantMessageDate()
    {
        return $this->last_participant_message_date;
    }
    /**
     * @var date $last_message_date
     */
    protected $last_message_date;


    /**
     * Set lastMessageDate
     *
     * @param date $lastMessageDate
     * @return self
     */
    public function setLastMessageDate($lastMessageDate)
    {
        $this->last_message_date = $lastMessageDate;
        return $this;
    }

    /**
     * Get lastMessageDate
     *
     * @return date $lastMessageDate
     */
    public function getLastMessageDate()
    {
        return $this->last_message_date;
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
