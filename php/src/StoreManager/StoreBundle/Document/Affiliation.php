<?php

namespace StoreManager\StoreBundle\Document;



/**
 * StoreManager\StoreBundle\Document\Affiliation
 */
class Affiliation
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
     * @var string $store_id
     */
    protected $store_id;

    /**
     * @var date $affiliation_date
     */
    protected $affiliation_date;


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
     * Set storeId
     *
     * @param string $storeId
     * @return self
     */
    public function setStoreId($storeId)
    {
        $this->store_id = $storeId;
        return $this;
    }

    /**
     * Get storeId
     *
     * @return string $storeId
     */
    public function getStoreId()
    {
        return $this->store_id;
    }

    /**
     * Set affiliationDate
     *
     * @param date $affiliationDate
     * @return self
     */
    public function setAffiliationDate($affiliationDate)
    {
        $this->affiliation_date = $affiliationDate;
        return $this;
    }

    /**
     * Get affiliationDate
     *
     * @return date $affiliationDate
     */
    public function getAffiliationDate()
    {
        return $this->affiliation_date;
    }
}
