<?php

namespace Transaction\TransactionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserGiftCardPurchased
 */
class UserGiftCardPurchased
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $giftCardId;

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
     * @var integer
     */
    private $date;

    /**
     * @var integer
     */
    private $dataJob;


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
     * Set giftCardId
     *
     * @param integer $giftCardId
     * @return UserGiftCardPurchased
     */
    public function setGiftCardId($giftCardId)
    {
        $this->giftCardId = $giftCardId;
    
        return $this;
    }

    /**
     * Get giftCardId
     *
     * @return integer 
     */
    public function getGiftCardId()
    {
        return $this->giftCardId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return UserGiftCardPurchased
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
     * @return UserGiftCardPurchased
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
     * @return UserGiftCardPurchased
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
     * @param integer $date
     * @return UserGiftCardPurchased
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return integer 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set dataJob
     *
     * @param integer $dataJob
     * @return UserGiftCardPurchased
     */
    public function setDataJob($dataJob)
    {
        $this->dataJob = $dataJob;
    
        return $this;
    }

    /**
     * Get dataJob
     *
     * @return integer 
     */
    public function getDataJob()
    {
        return $this->dataJob;
    }
    /**
     * @var integer
     */
    private $remainingGiftCard = 0;


    /**
     * Set remainingGiftCard
     *
     * @param integer $remainingGiftCard
     * @return UserGiftCardPurchased
     */
    public function setRemainingGiftCard($remainingGiftCard)
    {
        $this->remainingGiftCard = $remainingGiftCard;
    
        return $this;
    }

    /**
     * Get remainingGiftCard
     *
     * @return integer 
     */
    public function getRemainingGiftCard()
    {
        return $this->remainingGiftCard;
    }
}