<?php

namespace PostFeeds\PostFeedsBundle\Document;



/**
 * PostFeeds\PostFeedsBundle\Document\UserTagFeeds
 */
class UserTagFeeds
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var collection $user_info
     */
    protected $user_info;

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
     * Set userInfo
     *
     * @param collection $userInfo
     * @return self
     */
    public function setUserInfo($userInfo)
    {
        $this->user_info = $userInfo;
        return $this;
    }

    /**
     * Get userInfo
     *
     * @return collection $userInfo
     */
    public function getUserInfo()
    {
        return $this->user_info;
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
}
