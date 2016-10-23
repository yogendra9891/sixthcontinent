<?php

namespace CardManagement\CardManagementBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contract
 */
class Contract
{
     /**
     * @var integer
     */
    private $defaultflag = 0;
     /**
     * @var integer
     */
    private $status;
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $contractNumber;

    /**
     * @var integer
     */
    private $profileId;

    /**
     * @var \DateTime
     */
    private $registrationTime;

    /**
     * @var string
     */
    private $mail;

    /**
     * @var string
     */
    private $pan;

    /**
     * @var string
     */
    private $brand;

    /**
     * @var string
     */
    private $expirationPan;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $nationality;

    /**
     * @var integer
     */
    private $sessionId;

    /**
     * @var string
     */
    private $productType;

    /**
     * @var string
     */
    private $languageCode;

    /**
     * @var string
     */
    private $region;

    /**
     * @var \DateTime
     */
    private $createTime;

    /**
     * @var integer
     */
    private $deleted;


    /**
     * @var string
     */
    private $transactionType = 'C';
    
    /**
     * @var string
     */
    private $message = '';
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
     * Set contractNumber
     *
     * @param string $contractNumber
     * @return Contract
     */
    public function setContractNumber($contractNumber)
    {
        $this->contractNumber = $contractNumber;
    
        return $this;
    }

    /**
     * Get contractNumber
     *
     * @return string 
     */
    public function getContractNumber()
    {
        return $this->contractNumber;
    }

    /**
     * Set profileId
     *
     * @param integer $profileId
     * @return Contract
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;
    
        return $this;
    }

    /**
     * Get profileId
     *
     * @return integer 
     */
    public function getProfileId()
    {
        return $this->profileId;
    }

    /**
     * Set registrationTime
     *
     * @param \DateTime $registrationTime
     * @return Contract
     */
    public function setRegistrationTime($registrationTime)
    {
        $this->registrationTime = $registrationTime;
    
        return $this;
    }

    /**
     * Get registrationTime
     *
     * @return \DateTime 
     */
    public function getRegistrationTime()
    {
        return $this->registrationTime;
    }

    /**
     * Set mail
     *
     * @param string $mail
     * @return Contract
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
    
        return $this;
    }

    /**
     * Get mail
     *
     * @return string 
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Set pan
     *
     * @param string $pan
     * @return Contract
     */
    public function setPan($pan)
    {
        $this->pan = $pan;
    
        return $this;
    }

    /**
     * Get pan
     *
     * @return string 
     */
    public function getPan()
    {
        return $this->pan;
    }

    /**
     * Set brand
     *
     * @param string $brand
     * @return Contract
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    
        return $this;
    }

    /**
     * Get brand
     *
     * @return string 
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * Set expirationPan
     *
     * @param string $expirationPan
     * @return Contract
     */
    public function setExpirationPan($expirationPan)
    {
        $this->expirationPan = $expirationPan;
    
        return $this;
    }

    /**
     * Get expirationPan
     *
     * @return string 
     */
    public function getExpirationPan()
    {
        return $this->expirationPan;
    }

    /**
     * Set alias
     *
     * @param string $alias
     * @return Contract
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    
        return $this;
    }

    /**
     * Get alias
     *
     * @return string 
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Contract
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return Contract
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
     * Set nationality
     *
     * @param string $nationality
     * @return Contract
     */
    public function setNationality($nationality)
    {
        $this->nationality = $nationality;
    
        return $this;
    }

    /**
     * Get nationality
     *
     * @return string 
     */
    public function getNationality()
    {
        return $this->nationality;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return Contract
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    
        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set productType
     *
     * @param string $productType
     * @return Contract
     */
    public function setProductType($productType)
    {
        $this->productType = $productType;
    
        return $this;
    }

    /**
     * Get productType
     *
     * @return string 
     */
    public function getProductType()
    {
        return $this->productType;
    }

    /**
     * Set languageCode
     *
     * @param string $languageCode
     * @return Contract
     */
    public function setLanguageCode($languageCode)
    {
        $this->languageCode = $languageCode;
    
        return $this;
    }

    /**
     * Get languageCode
     *
     * @return string 
     */
    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * Set region
     *
     * @param string $region
     * @return Contract
     */
    public function setRegion($region)
    {
        $this->region = $region;
    
        return $this;
    }

    /**
     * Get region
     *
     * @return string 
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     * @return Contract
     */
    public function setCreateTime($createTime)
    {
        $this->createTime = $createTime;
    
        return $this;
    }

    /**
     * Get createTime
     *
     * @return \DateTime 
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * Set deleted
     *
     * @param integer $deleted
     * @return Contract
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    
        return $this;
    }

    /**
     * Get deleted
     *
     * @return integer 
     */
    public function getDeleted()
    {
        return $this->deleted;
    }
    /**
     * Set status
     *
     * @param integer $status
     * @return Payment
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
     * Set defaultflag
     *
     * @param integer $defaultflag
     * @return defaultflag
     */
    public function setDefaultflag($defaultflag)
    {
        $this->defaultflag = $defaultflag;
    
        return $this;
    }

    /**
     * Get defaultflag
     *
     * @return integer 
     */
    public function getDefaultflag()
    {
        return $this->defaultflag;
    }
    
    /**
     * Set transactionType
     *
     * @param string $transactionType
     * @return transactionType
     */
    public function setTransactionType($transactionType)
    {
        $this->transactionType = $transactionType;
    
        return $this;
    }

    /**
     * Get transactionType
     *
     * @return string 
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }
    
     /**
     * Set message
     *
     * @param string $message
     * @return Message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    
        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }
}