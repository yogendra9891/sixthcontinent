<?php

namespace PostFeeds\PostFeedsBundle\Document;



/**
 * PostFeeds\PostFeedsBundle\Document\RatingFeeds
 */
class RatingFeeds
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
     * @var hash $user_info
     */
    protected $user_info;

    /**
     * @var string $item_id
     */
    protected $item_id;

    /**
     * @var int $rate
     */
    protected $rate;

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
     * Set userInfo
     *
     * @param hash $userInfo
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
     * @return hash $userInfo
     */
    public function getUserInfo()
    {
        return $this->user_info;
    }

    /**
     * Set itemId
     *
     * @param string $itemId
     * @return self
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;
        return $this;
    }

    /**
     * Get itemId
     *
     * @return string $itemId
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set rate
     *
     * @param int $rate
     * @return self
     */
    public function setRate($rate)
    {
        $this->rate = $rate;
        return $this;
    }

    /**
     * Get rate
     *
     * @return int $rate
     */
    public function getRate()
    {
        return $this->rate;
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
