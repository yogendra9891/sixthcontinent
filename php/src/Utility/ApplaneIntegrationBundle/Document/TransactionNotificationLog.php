<?php

namespace Utility\ApplaneIntegrationBundle\Document;



/**
 * Utility\ApplaneIntegrationBundle\Document\TransactionNotificationLog
 */
class TransactionNotificationLog
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var int $to_user_id
     */
    protected $to_user_id;

    /**
     * @var int $to_shop_id
     */
    protected $to_shop_id;

    /**
     * @var boolean $is_active
     */
    protected $is_active;

    /**
     * @var date $start_date
     */
    protected $start_date;

    /**
     * @var date $end_date
     */
    protected $end_date;

    /**
     * @var date $updated_date
     */
    protected $updated_date;

    /**
     * @var int $send_count
     */
    protected $send_count;

    /**
     * @var string $notification_type
     */
    protected $notification_type;


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
     * Set toUserId
     *
     * @param int $toUserId
     * @return self
     */
    public function setToUserId($toUserId)
    {
        $this->to_user_id = $toUserId;
        return $this;
    }

    /**
     * Get toUserId
     *
     * @return int $toUserId
     */
    public function getToUserId()
    {
        return $this->to_user_id;
    }

    /**
     * Set toShopId
     *
     * @param int $toShopId
     * @return self
     */
    public function setToShopId($toShopId)
    {
        $this->to_shop_id = $toShopId;
        return $this;
    }

    /**
     * Get toShopId
     *
     * @return int $toShopId
     */
    public function getToShopId()
    {
        return $this->to_shop_id;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return self
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;
        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean $isActive
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * Set startDate
     *
     * @param date $startDate
     * @return self
     */
    public function setStartDate($startDate)
    {
        $this->start_date = $startDate;
        return $this;
    }

    /**
     * Get startDate
     *
     * @return date $startDate
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * Set endDate
     *
     * @param date $endDate
     * @return self
     */
    public function setEndDate($endDate)
    {
        $this->end_date = $endDate;
        return $this;
    }

    /**
     * Get endDate
     *
     * @return date $endDate
     */
    public function getEndDate()
    {
        return $this->end_date;
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

    /**
     * Set sendCount
     *
     * @param int $sendCount
     * @return self
     */
    public function setSendCount($sendCount)
    {
        $this->send_count = $sendCount;
        return $this;
    }

    /**
     * Get sendCount
     *
     * @return int $sendCount
     */
    public function getSendCount()
    {
        return $this->send_count;
    }

    /**
     * Set notificationType
     *
     * @param string $notificationType
     * @return self
     */
    public function setNotificationType($notificationType)
    {
        $this->notification_type = $notificationType;
        return $this;
    }

    /**
     * Get notificationType
     *
     * @return string $notificationType
     */
    public function getNotificationType()
    {
        return $this->notification_type;
    }
}
