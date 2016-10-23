<?php

namespace PostFeeds\PostFeedsBundle\Document;



/**
 * PostFeeds\PostFeedsBundle\Document\ShopTagFeeds
 */
class ShopTagFeeds
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var collection $shop_info
     */
    protected $shop_info;

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
     * Set shopInfo
     *
     * @param collection $shopInfo
     * @return self
     */
    public function setShopInfo($shopInfo)
    {
        $this->shop_info = $shopInfo;
        return $this;
    }

    /**
     * Get shopInfo
     *
     * @return collection $shopInfo
     */
    public function getShopInfo()
    {
        return $this->shop_info;
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
