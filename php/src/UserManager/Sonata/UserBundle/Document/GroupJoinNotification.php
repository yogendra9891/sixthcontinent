<?php

namespace UserManager\Sonata\UserBundle\Document;



/**
 * UserManager\Sonata\UserBundle\Document\GroupJoinNotification
 */
class GroupJoinNotification
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
     * @var string $group_id
     */
    protected $group_id;


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
     * Set groupId
     *
     * @param string $groupId
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
     * @return string $groupId
     */
    public function getGroupId()
    {
        return $this->group_id;
    }
    /**
     * @var string $user_role
     */
    protected $user_role;

    /**
     * @var date $created_at
     */
    protected $created_at;


    /**
     * Set userRole
     *
     * @param string $userRole
     * @return self
     */
    public function setUserRole($userRole)
    {
        $this->user_role = $userRole;
        return $this;
    }

    /**
     * Get userRole
     *
     * @return string $userRole
     */
    public function getUserRole()
    {
        return $this->user_role;
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
}
