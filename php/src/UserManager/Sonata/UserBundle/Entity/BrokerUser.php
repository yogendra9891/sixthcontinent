<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BrokerUser
 */
class BrokerUser
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
    private $roleId;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var string
     */
    private $vatNumber;

    /**
     * @var string
     */
    private $fiscalCode;

    /**
     * @var string
     */
    private $iban;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var string
     */
    private $profileImg;
    
    /**
     * @var string
     */
    private $mapPlace;
    
    /**
     * @var string
     */
    private $latitude;

    /**
     * @var string
     */
    private $longitude;
    
    /**
     * @var string
     */
    private $ssn = '';

    /**
     * @var string
     */
    private $idCard = '';
    
    /**
     * 
     */
    private $isActive = 0;
    
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
     * @return BrokerUser
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
     * Set userId
     *
     * @param integer $userId
     * @return BrokerUser
     */
    public function setRoleId($roleId)
    {
        $this->roleId = $roleId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getRoleId()
    {
        return $this->roleId;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return BrokerUser
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    
        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set vatNumber
     *
     * @param string $vatNumber
     * @return BrokerUser
     */
    public function setVatNumber($vatNumber)
    {
        $this->vatNumber = $vatNumber;
    
        return $this;
    }

    /**
     * Get vatNumber
     *
     * @return string 
     */
    public function getVatNumber()
    {
        return $this->vatNumber;
    }

    /**
     * Set fiscalCode
     *
     * @param string $fiscalCode
     * @return BrokerUser
     */
    public function setFiscalCode($fiscalCode)
    {
        $this->fiscalCode = $fiscalCode;
    
        return $this;
    }

    /**
     * Get fiscalCode
     *
     * @return string 
     */
    public function getFiscalCode()
    {
        return $this->fiscalCode;
    }

    /**
     * Set iban
     *
     * @param string $iban
     * @return BrokerUser
     */
    public function setIban($iban)
    {
        $this->iban = $iban;
    
        return $this;
    }

    /**
     * Get iban
     *
     * @return string 
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return BrokerUser
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
     * Set profileImg
     *
     * @param string $profileImg
     * @return CitizenUser
     */
    public function setProfileImg($profileImg)
    {
        $this->profileImg = $profileImg;
    
        return $this;
    }

    /**
     * Get profileImg
     *
     * @return profileImg 
     */
    public function getProfileImg()
    {
        return $this->profileImg;
    }
    
     /**
     * Set mapPlace
     *
     * @param string $mapPlace
     * @return CitizenUser
     */
    public function setMapPlace($mapPlace)
    {
        $this->mapPlace = $mapPlace;
    
        return $this;
    }

    /**
     * Get mapPlace
     *
     * @return mapPlace 
     */
    public function getMapPlace()
    {
        return $this->mapPlace;
    }
    
     /**
     * Set latitude
     *
     * @param string $latitude
     * @return CitizenUser
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    
        return $this;
    }

    /**
     * Get latitude
     *
     * @return string 
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param string $longitude
     * @return CitizenUser
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    
        return $this;
    }

    /**
     * Get longitude
     *
     * @return string 
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Get ssn
     *
     * @return string 
     */
    public function getSsn()
    {
        return $this->ssn;
    }

    /**
     * Set ssn
     *
     * @param string $ssn
     * @return BrokerUser
     */
    public function setSsn($ssn)
    {
        $this->ssn = $ssn;
    
        return $this;
    }
    
    /**
     * Get idCard
     *
     * @return string 
     */
    public function getIdCard()
    {
        return $this->idCard;
    }

    /**
     * Set idCard
     *
     * @param string $idCard
     * @return BrokerUser
     */
    public function setIdCard($idCard)
    {
        $this->idCard = $idCard;
    
        return $this;
    }
    
    /**
     * Get isActive
     *
     * @return int 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isActive
     *
     * @param string $isActive
     * @return BrokerUser
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    
        return $this;
    }
}
