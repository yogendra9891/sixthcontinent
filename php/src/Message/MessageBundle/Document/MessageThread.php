<?php

namespace Message\MessageBundle\Document;



/**
 * Message\MessageBundle\Document\MessageThread
 */
class MessageThread
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var int $created_by
     */
    protected $created_by;

    /**
     * @var string $thread_type
     */
    protected $thread_type;

    /**
     * @var collection $group_members
     */
    protected $group_members;

    /**
     * @var collection $delete_by
     */
    protected $delete_by;

    /**
     * @var date $created_at
     */
    protected $created_at;

    /**
     * @var date $updated_at
     */
    protected $updated_at;


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
     * Set name
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set createdBy
     *
     * @param int $createdBy
     * @return self
     */
    public function setCreatedBy($createdBy)
    {
        $this->created_by = $createdBy;
        return $this;
    }

    /**
     * Get createdBy
     *
     * @return int $createdBy
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * Set threadType
     *
     * @param string $threadType
     * @return self
     */
    public function setThreadType($threadType)
    {
        $this->thread_type = $threadType;
        return $this;
    }

    /**
     * Get threadType
     *
     * @return string $threadType
     */
    public function getThreadType()
    {
        return $this->thread_type;
    }

    /**
     * Set groupMembers
     *
     * @param collection $groupMembers
     * @return self
     */
    public function setGroupMembers($groupMembers)
    {
        $this->group_members = $groupMembers;
        return $this;
    }

    /**
     * Get groupMembers
     *
     * @return collection $groupMembers
     */
    public function getGroupMembers()
    {
        return $this->group_members;
    }

    /**
     * Set deleteBy
     *
     * @param collection $deleteBy
     * @return self
     */
    public function setDeleteBy($deleteBy)
    {
        $this->delete_by = $deleteBy;
        return $this;
    }

    /**
     * Get deleteBy
     *
     * @return collection $deleteBy
     */
    public function getDeleteBy()
    {
        return $this->delete_by;
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
}
