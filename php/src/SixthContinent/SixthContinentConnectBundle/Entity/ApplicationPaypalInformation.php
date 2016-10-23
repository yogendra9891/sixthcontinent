<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ApplicationPaypalInformation
 */
class ApplicationPaypalInformation
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $appId;

    /**
     * @var string
     */
    private $accountIdToDisplay;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $status;

    /**
     * @var integer
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
    private $errorDescription;

    /**
     * @var integer
     */
    private $isDeleted;

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
     * @var integer
     */
    private $isDefault;


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
     * Set appId
     *
     * @param string $appId
     * @return ApplicationPaypalInformation
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    
        return $this;
    }

    /**
     * Get appId
     *
     * @return string 
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Set accountIdToDisplay
     *
     * @param string $accountIdToDisplay
     * @return ApplicationPaypalInformation
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
     * Set email
     *
     * @param string $email
     * @return ApplicationPaypalInformation
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return ApplicationPaypalInformation
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
     * @param integer $correlationId
     * @return ApplicationPaypalInformation
     */
    public function setCorrelationId($correlationId)
    {
        $this->correlationId = $correlationId;
    
        return $this;
    }

    /**
     * Get correlationId
     *
     * @return integer 
     */
    public function getCorrelationId()
    {
        return $this->correlationId;
    }

    /**
     * Set accountType
     *
     * @param string $accountType
     * @return ApplicationPaypalInformation
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
     * @return ApplicationPaypalInformation
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
     * @return ApplicationPaypalInformation
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
     * @return ApplicationPaypalInformation
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
     * Set errorDescription
     *
     * @param string $errorDescription
     * @return ApplicationPaypalInformation
     */
    public function setErrorDescription($errorDescription)
    {
        $this->errorDescription = $errorDescription;
    
        return $this;
    }

    /**
     * Get errorDescription
     *
     * @return string 
     */
    public function getErrorDescription()
    {
        return $this->errorDescription;
    }

    /**
     * Set isDeleted
     *
     * @param integer $isDeleted
     * @return ApplicationPaypalInformation
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
    
        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return integer 
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ApplicationPaypalInformation
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
     * @return ApplicationPaypalInformation
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
     * @return ApplicationPaypalInformation
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
     * @return ApplicationPaypalInformation
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
     * Set isDefault
     *
     * @param integer $isDefault
     * @return ApplicationPaypalInformation
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
    
        return $this;
    }

    /**
     * Get isDefault
     *
     * @return integer 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }
}