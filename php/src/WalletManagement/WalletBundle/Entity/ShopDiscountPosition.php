<?php

namespace WalletManagement\WalletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShopDiscountPosition
 */
class ShopDiscountPosition
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var string
     */
    private $totalDp;

    /**
     * @var string
     */
    private $balanceDp;

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
     * Set shopId
     *
     * @param integer $shopId
     * @return ShopDiscountPosition
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
     * Set totalDp
     *
     * @param string $totalDp
     * @return ShopDiscountPosition
     */
    public function setTotalDp($totalDp)
    {
        $this->totalDp = $totalDp;
    
        return $this;
    }

    /**
     * Get totalDp
     *
     * @return string 
     */
    public function getTotalDp()
    {
        return $this->totalDp;
    }

    /**
     * Set balanceDp
     *
     * @param string $balanceDp
     * @return ShopDiscountPosition
     */
    public function setBalanceDp($balanceDp)
    {
        $this->balanceDp = $balanceDp;
    
        return $this;
    }

    /**
     * Get balanceDp
     *
     * @return string 
     */
    public function getBalanceDp()
    {
        return $this->balanceDp;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ShopDiscountPosition
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