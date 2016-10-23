<?php

namespace Payment\PaymentProcessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShopGiftCards
 */
class ShopGiftCards
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var integer
     */
    private $giftCardAmount;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var integer
     */
    private $isUsed;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return ShopGiftCards
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     * @return ShopGiftCards
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
    
        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer 
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * Set giftCardAmount
     *
     * @param integer $giftCardAmount
     * @return ShopGiftCards
     */
    public function setGiftCardAmount($giftCardAmount)
    {
        $this->giftCardAmount = $giftCardAmount;
    
        return $this;
    }

    /**
     * Get giftCardAmount
     *
     * @return integer 
     */
    public function getGiftCardAmount()
    {
        return $this->giftCardAmount;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return ShopGiftCards
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set isUsed
     *
     * @param integer $isUsed
     * @return ShopGiftCards
     */
    public function setIsUsed($isUsed)
    {
        $this->isUsed = $isUsed;
    
        return $this;
    }

    /**
     * Get isUsed
     *
     * @return integer 
     */
    public function getIsUsed()
    {
        return $this->isUsed;
    }
}