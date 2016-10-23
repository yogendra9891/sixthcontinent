<?php

namespace Transaction\CommercialPromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CommercialPromotion
 */
class CommercialPromotion
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $initQuantity;

    /**
     * @var integer
     */
    private $availableQuantity;

    /**
     * @var \DateTime
     */
    private $timeStartH;

    /**
     * @var \DateTime
     */
    private $timeEndH;

    /**
     * @var integer
     */
    private $timeStart;

    /**
     * @var integer
     */
    private $timeEnd;

    /**
     * @var integer
     */
    private $discountAmount;

    /**
     * @var integer
     */
    private $commercialPromotionTypeId;

    /**
     * @var integer
     */
    private $sellerId;

    /**
     * @var integer
     */
    private $sexFemale;

    /**
     * @var integer
     */
    private $extraInfo;

    /**
     * @var integer
     */
    private $price;

    /**
     * @var integer
     */
    private $timeCreate;

    /**
     * @var \DateTime
     */
    private $timeCreateH;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var integer
     */
    private $maxUsageInitPrice;


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
     * Set initQuantity
     *
     * @param integer $initQuantity
     * @return CommercialPromotion
     */
    public function setInitQuantity($initQuantity)
    {
        $this->initQuantity = $initQuantity;
    
        return $this;
    }

    /**
     * Get initQuantity
     *
     * @return integer 
     */
    public function getInitQuantity()
    {
        return $this->initQuantity;
    }

    /**
     * Set availableQuantity
     *
     * @param integer $availableQuantity
     * @return CommercialPromotion
     */
    public function setAvailableQuantity($availableQuantity)
    {
        $this->availableQuantity = $availableQuantity;
    
        return $this;
    }

    /**
     * Get availableQuantity
     *
     * @return integer 
     */
    public function getAvailableQuantity()
    {
        return $this->availableQuantity;
    }

    /**
     * Set timeStartH
     *
     * @param \DateTime $timeStartH
     * @return CommercialPromotion
     */
    public function setTimeStartH($timeStartH)
    {
        $this->timeStartH = $timeStartH;
    
        return $this;
    }

    /**
     * Get timeStartH
     *
     * @return \DateTime 
     */
    public function getTimeStartH()
    {
        return $this->timeStartH;
    }

    /**
     * Set timeEndH
     *
     * @param \DateTime $timeEndH
     * @return CommercialPromotion
     */
    public function setTimeEndH($timeEndH)
    {
        $this->timeEndH = $timeEndH;
    
        return $this;
    }

    /**
     * Get timeEndH
     *
     * @return \DateTime 
     */
    public function getTimeEndH()
    {
        return $this->timeEndH;
    }

    /**
     * Set timeStart
     *
     * @param integer $timeStart
     * @return CommercialPromotion
     */
    public function setTimeStart($timeStart)
    {
        $this->timeStart = $timeStart;
    
        return $this;
    }

    /**
     * Get timeStart
     *
     * @return integer 
     */
    public function getTimeStart()
    {
        return $this->timeStart;
    }

    /**
     * Set timeEnd
     *
     * @param integer $timeEnd
     * @return CommercialPromotion
     */
    public function setTimeEnd($timeEnd)
    {
        $this->timeEnd = $timeEnd;
    
        return $this;
    }

    /**
     * Get timeEnd
     *
     * @return integer 
     */
    public function getTimeEnd()
    {
        return $this->timeEnd;
    }

    /**
     * Set discountAmount
     *
     * @param integer $discountAmount
     * @return CommercialPromotion
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->discountAmount = $discountAmount;
    
        return $this;
    }

    /**
     * Get discountAmount
     *
     * @return integer 
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * Set commercialPromotionTypeId
     *
     * @param integer $commercialPromotionTypeId
     * @return CommercialPromotion
     */
    public function setCommercialPromotionTypeId($commercialPromotionTypeId)
    {
        $this->commercialPromotionTypeId = $commercialPromotionTypeId;
    
        return $this;
    }

    /**
     * Get commercialPromotionTypeId
     *
     * @return integer 
     */
    public function getCommercialPromotionTypeId()
    {
        return $this->commercialPromotionTypeId;
    }

    /**
     * Set sellerId
     *
     * @param integer $sellerId
     * @return CommercialPromotion
     */
    public function setSellerId($sellerId)
    {
        $this->sellerId = $sellerId;
    
        return $this;
    }

    /**
     * Get sellerId
     *
     * @return integer 
     */
    public function getSellerId()
    {
        return $this->sellerId;
    }

    /**
     * Set sexFemale
     *
     * @param integer $sexFemale
     * @return CommercialPromotion
     */
    public function setSexFemale($sexFemale)
    {
        $this->sexFemale = $sexFemale;
    
        return $this;
    }

    /**
     * Get sexFemale
     *
     * @return integer 
     */
    public function getSexFemale()
    {
        return $this->sexFemale;
    }

    /**
     * Set extraInfo
     *
     * @param integer $extraInfo
     * @return CommercialPromotion
     */
    public function setExtraInfo($extraInfo)
    {
        $this->extraInfo = $extraInfo;
    
        return $this;
    }

    /**
     * Get extraInfo
     *
     * @return integer 
     */
    public function getExtraInfo()
    {
        return $this->extraInfo;
    }

    /**
     * Set price
     *
     * @param integer $price
     * @return CommercialPromotion
     */
    public function setPrice($price)
    {
        $this->price = $price;
    
        return $this;
    }

    /**
     * Get price
     *
     * @return integer 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set timeCreate
     *
     * @param integer $timeCreate
     * @return CommercialPromotion
     */
    public function setTimeCreate($timeCreate)
    {
        $this->timeCreate = $timeCreate;
    
        return $this;
    }

    /**
     * Get timeCreate
     *
     * @return integer 
     */
    public function getTimeCreate()
    {
        return $this->timeCreate;
    }

    /**
     * Set timeCreateH
     *
     * @param \DateTime $timeCreateH
     * @return CommercialPromotion
     */
    public function setTimeCreateH($timeCreateH)
    {
        $this->timeCreateH = $timeCreateH;
    
        return $this;
    }

    /**
     * Get timeCreateH
     *
     * @return \DateTime 
     */
    public function getTimeCreateH()
    {
        return $this->timeCreateH;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return CommercialPromotion
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set maxUsageInitPrice
     *
     * @param integer $maxUsageInitPrice
     * @return CommercialPromotion
     */
    public function setMaxUsageInitPrice($maxUsageInitPrice)
    {
        $this->maxUsageInitPrice = $maxUsageInitPrice;
    
        return $this;
    }

    /**
     * Get maxUsageInitPrice
     *
     * @return integer 
     */
    public function getMaxUsageInitPrice()
    {
        return $this->maxUsageInitPrice;
    }
}