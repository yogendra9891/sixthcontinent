<?php

namespace UserManager\Sonata\UserBundle\Document;



/**
 * UserManager\Sonata\UserBundle\Document\UserToGroup
 */
class UserToGroup
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var int $user_id
     */
    protected $user_id;

    /**
     * @var int $group_id
     */
    protected $group_id;
    
    /**
     * @var int $is_blocked
     */
    protected $is_blocked;


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
     * Set userId
     *
     * @param int $userId
     * @return self
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;
        return $this;
    }

    /**
     * Get userId
     *
     * @return int $userId
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set groupId
     *
     * @param int $groupId
     * @return self
     */
    public function setGroupId($groupId)
    {
        $this->group_id = $groupId;
        return $this;
    }

    /**
     * Get groupId
     *
     * @return int $groupId
     */
    public function getGroupId()
    {
        return $this->group_id;
    }
    
    /**
     * Set isBlocked
     *
     * @param int $isBlocked
     * @return self
     */
    public function setIsBlocked($isBlocked)
    {
        $this->is_blocked = $isBlocked;
        return $this;
    }

    /**
     * Get isBlocked
     *
     * @return int $isBlocked
     */
    public function getIsBlocked()
    {
        return $this->is_blocked;
    }
}
