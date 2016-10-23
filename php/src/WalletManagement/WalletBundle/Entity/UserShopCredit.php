<?php

namespace WalletManagement\WalletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserShopCredit
 */
class UserShopCredit
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
    private $totalShots;

    /**
     * @var integer
     */
    private $balanceShots;

    /**
     * @var integer
     */
    private $totalGiftCard;

    /**
     * @var integer
     */
    private $balanceGiftCard;

    /**
     * @var integer
     */
    private $totalMomosyCard;

    /**
     * @var integer
     */
    private $balanceMomosyCard;

    /**
     * @var \DateTime
     */
    private $createdAt;


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
     * @return UserShopCredit
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
     * @return UserShopCredit
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
     * Set totalShots
     *
     * @param integer $totalShots
     * @return UserShopCredit
     */
    public function setTotalShots($totalShots)
    {
        $this->totalShots = $totalShots;
    
        return $this;
    }

    /**
     * Get totalShots
     *
     * @return integer 
     */
    public function getTotalShots()
    {
        return $this->totalShots;
    }

    /**
     * Set balanceShots
     *
     * @param integer $balanceShots
     * @return UserShopCredit
     */
    public function setBalanceShots($balanceShots)
    {
        $this->balanceShots = $balanceShots;
    
        return $this;
    }

    /**
     * Get balanceShots
     *
     * @return integer 
     */
    public function getBalanceShots()
    {
        return $this->balanceShots;
    }

    /**
     * Set totalGiftCard
     *
     * @param integer $totalGiftCard
     * @return UserShopCredit
     */
    public function setTotalGiftCard($totalGiftCard)
    {
        $this->totalGiftCard = $totalGiftCard;
    
        return $this;
    }

    /**
     * Get totalGiftCard
     *
     * @return integer 
     */
    public function getTotalGiftCard()
    {
        return $this->totalGiftCard;
    }

    /**
     * Set balanceGiftCard
     *
     * @param integer $balanceGiftCard
     * @return UserShopCredit
     */
    public function setBalanceGiftCard($balanceGiftCard)
    {
        $this->balanceGiftCard = $balanceGiftCard;
    
        return $this;
    }

    /**
     * Get balanceGiftCard
     *
     * @return integer 
     */
    public function getBalanceGiftCard()
    {
        return $this->balanceGiftCard;
    }

    /**
     * Set totalMomosyCard
     *
     * @param integer $totalMomosyCard
     * @return UserShopCredit
     */
    public function setTotalMomosyCard($totalMomosyCard)
    {
        $this->totalMomosyCard = $totalMomosyCard;
    
        return $this;
    }

    /**
     * Get totalMomosyCard
     *
     * @return integer 
     */
    public function getTotalMomosyCard()
    {
        return $this->totalMomosyCard;
    }

    /**
     * Set balanceMomosyCard
     *
     * @param integer $balanceMomosyCard
     * @return UserShopCredit
     */
    public function setBalanceMomosyCard($balanceMomosyCard)
    {
        $this->balanceMomosyCard = $balanceMomosyCard;
    
        return $this;
    }

    /**
     * Get balanceMomosyCard
     *
     * @return integer 
     */
    public function getBalanceMomosyCard()
    {
        return $this->balanceMomosyCard;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserShopCredit
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}