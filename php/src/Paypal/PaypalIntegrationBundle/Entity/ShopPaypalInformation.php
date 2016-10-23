<?php

namespace Paypal\PaypalIntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShopPaypalInformation
 */
class ShopPaypalInformation
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
    private $emailIdToDisplay;

    /**
     * @var string
     */
    private $emailId;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $correlationId;

    /**
     * @var string
     */
    private $accountType;

    /**
     * @var string
     */
    private $accountId;

    /**
     * @var string
     */
    private $buildId;

    /**
     * @var string
     */
    private $errorCode;

    /**
     * @var string
     */
    private $errorDiscription;

    /**
     * @var boolean
     */
    private $isDeleted = 0;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var string
     */
    private $mobileNumber;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;


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
     * @return ShopPaypalInformation
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
     * Set emailIdToDisplay
     *
     * @param string $emailIdToDisplay
     * @return ShopPaypalInformation
     */
    public function setEmailIdToDisplay($emailIdToDisplay)
    {
        $this->emailIdToDisplay = $emailIdToDisplay;
    
        return $this;
    }

    /**
     * Get emailIdToDisplay
     *
     * @return string 
     */
    public function getEmailIdToDisplay()
    {
        return $this->emailIdToDisplay;
    }

    /**
     * Set emailId
     *
     * @param string $emailId
     * @return ShopPaypalInformation
     */
    public function setEmailId($emailId)
    {
        $this->emailId = $emailId;
    
        return $this;
    }

    /**
     * Get emailId
     *
     * @return string 
     */
    public function getEmailId()
    {
        return $this->emailId;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return ShopPaypalInformation
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set correlationId
     *
     * @param string $correlationId
     * @return ShopPaypalInformation
     */
    public function setCorrelationId($correlationId)
    {
        $this->correlationId = $correlationId;
    
        return $this;
    }

    /**
     * Get correlationId
     *
     * @return string 
     */
    public function getCorrelationId()
    {
        return $this->correlationId;
    }

    /**
     * Set accountType
     *
     * @param string $accountType
     * @return ShopPaypalInformation
     */
    public function setAccountType($accountType)
    {
        $this->accountType = $accountType;
    
        return $this;
    }

    /**
     * Get accountType
     *
     * @return string 
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * Set accountId
     *
     * @param string $accountId
     * @return ShopPaypalInformation
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;
    
        return $this;
    }

    /**
     * Get accountId
     *
     * @return string 
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * Set buildId
     *
     * @param string $buildId
     * @return ShopPaypalInformation
     */
    public function setBuildId($buildId)
    {
        $this->buildId = $buildId;
    
        return $this;
    }

    /**
     * Get buildId
     *
     * @return string 
     */
    public function getBuildId()
    {
        return $this->buildId;
    }

    /**
     * Set errorCode
     *
     * @param string $errorCode
     * @return ShopPaypalInformation
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
    
        return $this;
    }

    /**
     * Get errorCode
     *
     * @return string 
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Set errorDiscription
     *
     * @param string $errorDiscription
     * @return ShopPaypalInformation
     */
    public function setErrorDiscription($errorDiscription)
    {
        $this->errorDiscription = $errorDiscription;
    
        return $this;
    }

    /**
     * Get errorDiscription
     *
     * @return string 
     */
    public function getErrorDiscription()
    {
        return $this->errorDiscription;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return ShopPaypalInformation
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
    
        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return boolean 
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ShopPaypalInformation
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
     * Set mobileNumber
     *
     * @param string $mobileNumber
     * @return ShopPaypalInformation
     */
    public function setMobileNumber($mobileNumber)
    {
        $this->mobileNumber = $mobileNumber;
    
        return $this;
    }

    /**
     * Get mobileNumber
     *
     * @return string 
     */
    public function getMobileNumber()
    {
        return $this->mobileNumber;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return ShopPaypalInformation
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    
        return $this;
    }

    /**
     * Get firstName
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return ShopPaypalInformation
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    
        return $this;
    }

    /**
     * Get lastName
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->lastName;
    }
    /**
     * @var string
     */
    private $accountIdToDisplay;

    /**
     * @var string
     */
    private $isDefault = 0;


    /**
     * Set accountIdToDisplay
     *
     * @param string $accountIdToDisplay
     * @return ShopPaypalInformation
     */
    public function setAccountIdToDisplay($accountIdToDisplay)
    {
        $this->accountIdToDisplay = $accountIdToDisplay;
    
        return $this;
    }

    /**
     * Get accountIdToDisplay
     *
     * @return string 
     */
    public function getAccountIdToDisplay()
    {
        return $this->accountIdToDisplay;
    }

    /**
     * Set isDefault
     *
     * @param string $isDefault
     * @return ShopPaypalInformation
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
    
        return $this;
    }

    /**
     * Get isDefault
     *
     * @return string 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }
}