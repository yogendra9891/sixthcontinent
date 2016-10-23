<?php

namespace WalletManagement\WalletBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserDiscountPosition
 */
class UserDiscountPosition
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
    
     public function __construct()
    {
        $this->createdAt = new \DateTime(date('Y-m-d'));
        $this->updatedAt = new \DateTime(date('Y-m-d'));
        
    }


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
     * @return UserDiscountPosition
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
     * Set totalDp
     *
     * @param string $totalDp
     * @return UserDiscountPosition
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
     * @return UserDiscountPosition
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
     * @return UserDiscountPosition
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
    /**
     * @var integer
     */
    private $citizenIncome;


    /**
     * Set citizenIncome
     *
     * @param integer $citizenIncome
     * @return UserDiscountPosition
     */
    public function setCitizenIncome($citizenIncome)
    {
        $this->citizenIncome = $citizenIncome;
    
        return $this;
    }

    /**
     * Get citizenIncome
     *
     * @return integer 
     */
    public function getCitizenIncome()
    {
        return $this->citizenIncome;
    }
    /**
     * @var \DateTime
     */
    private $updatedAt;


    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return UserDiscountPosition
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
    /**
     * @var integer
     */
    private $totalCitizenIncome = 0;

    /**
     * @var integer
     */
    private $saldorm = 0;


    /**
     * Set totalCitizenIncome
     *
     * @param integer $totalCitizenIncome
     * @return UserDiscountPosition
     */
    public function setTotalCitizenIncome($totalCitizenIncome)
    {
        $this->totalCitizenIncome = $totalCitizenIncome;
    
        return $this;
    }

    /**
     * Get totalCitizenIncome
     *
     * @return integer 
     */
    public function getTotalCitizenIncome()
    {
        return $this->totalCitizenIncome;
    }

    /**
     * Set saldorm
     *
     * @param integer $saldorm
     * @return UserDiscountPosition
     */
    public function setSaldorm($saldorm)
    {
        $this->saldorm = $saldorm;
    
        return $this;
    }

    /**
     * Get saldorm
     *
     * @return integer 
     */
    public function getSaldorm()
    {
        return $this->saldorm;
    }
    /**
     * @var integer
     */
    private $blockCitizenIncome= 0;


    /**
     * Set blockCitizenIncome
     *
     * @param integer $blockCitizenIncome
     * @return UserDiscountPosition
     */
    public function setBlockCitizenIncome($blockCitizenIncome)
    {
        $this->blockCitizenIncome = $blockCitizenIncome;
    
        return $this;
    }

    /**
     * Get blockCitizenIncome
     *
     * @return integer 
     */
    public function getBlockCitizenIncome()
    {
        return $this->blockCitizenIncome;
    }
}