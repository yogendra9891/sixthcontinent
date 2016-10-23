<?php

namespace Payment\PaymentDistributionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PyamentDistributedAmount
 */
class PyamentDistributedAmount
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $citizenAffiliateAmount;

    /**
     * @var integer
     */
    private $shopAffiliateAmount;

    /**
     * @var integer
     */
    private $friendsFollowerAmount;

    /**
     * @var integer
     */
    private $purchaserUserAmount;

    /**
     * @var integer
     */
    private $countryCitizenAmount;

    /**
     * @var integer
     */
    private $sixthcontinentAmount;

    /**
     * @var string
     */
    private $transactionId;

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
     * Set citizenAffiliateAmount
     *
     * @param integer $citizenAffiliateAmount
     * @return PyamentDistributedAmount
     */
    public function setCitizenAffiliateAmount($citizenAffiliateAmount)
    {
        $this->citizenAffiliateAmount = $citizenAffiliateAmount;
    
        return $this;
    }

    /**
     * Get citizenAffiliateAmount
     *
     * @return integer 
     */
    public function getCitizenAffiliateAmount()
    {
        return $this->citizenAffiliateAmount;
    }

    /**
     * Set shopAffiliateAmount
     *
     * @param integer $shopAffiliateAmount
     * @return PyamentDistributedAmount
     */
    public function setShopAffiliateAmount($shopAffiliateAmount)
    {
        $this->shopAffiliateAmount = $shopAffiliateAmount;
    
        return $this;
    }

    /**
     * Get shopAffiliateAmount
     *
     * @return integer 
     */
    public function getShopAffiliateAmount()
    {
        return $this->shopAffiliateAmount;
    }

    /**
     * Set friendsFollowerAmount
     *
     * @param integer $friendsFollowerAmount
     * @return PyamentDistributedAmount
     */
    public function setFriendsFollowerAmount($friendsFollowerAmount)
    {
        $this->friendsFollowerAmount = $friendsFollowerAmount;
    
        return $this;
    }

    /**
     * Get friendsFollowerAmount
     *
     * @return integer 
     */
    public function getFriendsFollowerAmount()
    {
        return $this->friendsFollowerAmount;
    }

    /**
     * Set purchaserUserAmount
     *
     * @param integer $purchaserUserAmount
     * @return PyamentDistributedAmount
     */
    public function setPurchaserUserAmount($purchaserUserAmount)
    {
        $this->purchaserUserAmount = $purchaserUserAmount;
    
        return $this;
    }

    /**
     * Get purchaserUserAmount
     *
     * @return integer 
     */
    public function getPurchaserUserAmount()
    {
        return $this->purchaserUserAmount;
    }

    /**
     * Set countryCitizenAmount
     *
     * @param integer $countryCitizenAmount
     * @return PyamentDistributedAmount
     */
    public function setCountryCitizenAmount($countryCitizenAmount)
    {
        $this->countryCitizenAmount = $countryCitizenAmount;
    
        return $this;
    }

    /**
     * Get countryCitizenAmount
     *
     * @return integer 
     */
    public function getCountryCitizenAmount()
    {
        return $this->countryCitizenAmount;
    }

    /**
     * Set sixthcontinentAmount
     *
     * @param integer $sixthcontinentAmount
     * @return PyamentDistributedAmount
     */
    public function setSixthcontinentAmount($sixthcontinentAmount)
    {
        $this->sixthcontinentAmount = $sixthcontinentAmount;
    
        return $this;
    }

    /**
     * Get sixthcontinentAmount
     *
     * @return integer 
     */
    public function getSixthcontinentAmount()
    {
        return $this->sixthcontinentAmount;
    }

    /**
     * Set transactionId
     *
     * @param string $transactionId
     * @return PyamentDistributedAmount
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    
        return $this;
    }

    /**
     * Get transactionId
     *
     * @return string 
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PyamentDistributedAmount
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