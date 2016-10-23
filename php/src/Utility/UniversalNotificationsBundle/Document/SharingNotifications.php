<?php

namespace Utility\UniversalNotificationsBundle\Document;



/**
 * Utility\UniversalNotificationsBundle\Document\SharingNotifications
 */
class SharingNotifications
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
     * @var string $email
     */
    protected $email;

    /**
     * @var string $item_type
     */
    protected $item_type;

    /**
     * @var string $item_id
     */
    protected $item_id;

    /**
     * @var date $created_date
     */
    protected $created_date;

    /**
     * @var date $updated_date
     */
    protected $updated_date;

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
     * Set email
     *
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set itemType
     *
     * @param string $itemType
     * @return self
     */
    public function setItemType($itemType)
    {
        $this->item_type = $itemType;
        return $this;
    }

    /**
     * Get itemType
     *
     * @return string $itemType
     */
    public function getItemType()
    {
        return $this->item_type;
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
     * Set createdDate
     *
     * @param date $createdDate
     * @return self
     */
    public function setCreatedDate($createdDate)
    {
        $this->created_date = $createdDate;
        return $this;
    }

    /**
     * Get createdDate
     *
     * @return date $createdDate
     */
    public function getCreatedDate()
    {
        return $this->created_date;
    }

    /**
     * Set updatedDate
     *
     * @param date $updatedDate
     * @return self
     */
    public function setUpdatedDate($updatedDate)
    {
        $this->updated_date = $updatedDate;
        return $this;
    }

    /**
     * Get updatedDate
     *
     * @return date $updatedDate
     */
    public function getUpdatedDate()
    {
        return $this->updated_date;
    }
}
