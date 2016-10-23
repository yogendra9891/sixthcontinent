<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ApplicationBusinessAccount
 */
class ApplicationBusinessAccount
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $applicationId;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $businessName;

    /**
     * @var string
     */
    private $businessType;

    /**
     * @var string
     */
    private $businessCountry;

    /**
     * @var string
     */
    private $businessRegion;

    /**
     * @var string
     */
    private $businessCity;

    /**
     * @var string
     */
    private $businessAddress;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var string
     */
    private $zip;

    /**
     * @var string
     */
    private $province;

    /**
     * @var string
     */
    private $vatNumber;

    /**
     * @var string
     */
    private $iban;

    /**
     * @var string
     */
    private $fiscalCode;

    /**
     * @var string
     */
    private $represFiscalCode;

    /**
     * @var string
     */
    private $represFirstName;

    /**
     * @var string
     */
    private $represLastName;

    /**
     * @var string
     */
    private $represPlaceOfBirth;

    /**
     * @var \DateTime
     */
    private $represDob;

    /**
     * @var string
     */
    private $represEmail;

    /**
     * @var string
     */
    private $represPhoneNumber;

    /**
     * @var string
     */
    private $represAddress;

    /**
     * @var string
     */
    private $represProvince;

    /**
     * @var string
     */
    private $represCity;

    /**
     * @var string
     */
    private $represZip;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @var integer
     */
    private $isActive;

    /**
     * @var integer
     */
    private $isDeleted;


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
     * Set applicationId
     *
     * @param string $applicationId
     * @return ApplicationBusinessAccount
     */
    public function setApplicationId($applicationId)
    {
        $this->applicationId = $applicationId;
    
        return $this;
    }

    /**
     * Get applicationId
     *
     * @return string 
     */
    public function getApplicationId()
    {
        return $this->applicationId;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return ApplicationBusinessAccount
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
     * Set description
     *
     * @param string $description
     * @return ApplicationBusinessAccount
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return ApplicationBusinessAccount
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
     * Set businessName
     *
     * @param string $businessName
     * @return ApplicationBusinessAccount
     */
    public function setBusinessName($businessName)
    {
        $this->businessName = $businessName;
    
        return $this;
    }

    /**
     * Get businessName
     *
     * @return string 
     */
    public function getBusinessName()
    {
        return $this->businessName;
    }

    /**
     * Set businessType
     *
     * @param string $businessType
     * @return ApplicationBusinessAccount
     */
    public function setBusinessType($businessType)
    {
        $this->businessType = $businessType;
    
        return $this;
    }

    /**
     * Get businessType
     *
     * @return string 
     */
    public function getBusinessType()
    {
        return $this->businessType;
    }

    /**
     * Set businessCountry
     *
     * @param string $businessCountry
     * @return ApplicationBusinessAccount
     */
    public function setBusinessCountry($businessCountry)
    {
        $this->businessCountry = $businessCountry;
    
        return $this;
    }

    /**
     * Get businessCountry
     *
     * @return string 
     */
    public function getBusinessCountry()
    {
        return $this->businessCountry;
    }

    /**
     * Set businessRegion
     *
     * @param string $businessRegion
     * @return ApplicationBusinessAccount
     */
    public function setBusinessRegion($businessRegion)
    {
        $this->businessRegion = $businessRegion;
    
        return $this;
    }

    /**
     * Get businessRegion
     *
     * @return string 
     */
    public function getBusinessRegion()
    {
        return $this->businessRegion;
    }

    /**
     * Set businessCity
     *
     * @param string $businessCity
     * @return ApplicationBusinessAccount
     */
    public function setBusinessCity($businessCity)
    {
        $this->businessCity = $businessCity;
    
        return $this;
    }

    /**
     * Get businessCity
     *
     * @return string 
     */
    public function getBusinessCity()
    {
        return $this->businessCity;
    }

    /**
     * Set businessAddress
     *
     * @param string $businessAddress
     * @return ApplicationBusinessAccount
     */
    public function setBusinessAddress($businessAddress)
    {
        $this->businessAddress = $businessAddress;
    
        return $this;
    }

    /**
     * Get businessAddress
     *
     * @return string 
     */
    public function getBusinessAddress()
    {
        return $this->businessAddress;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return ApplicationBusinessAccount
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
     * Set zip
     *
     * @param string $zip
     * @return ApplicationBusinessAccount
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    
        return $this;
    }

    /**
     * Get zip
     *
     * @return string 
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set province
     *
     * @param string $province
     * @return ApplicationBusinessAccount
     */
    public function setProvince($province)
    {
        $this->province = $province;
    
        return $this;
    }

    /**
     * Get province
     *
     * @return string 
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * Set vatNumber
     *
     * @param string $vatNumber
     * @return ApplicationBusinessAccount
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
     * Set iban
     *
     * @param string $iban
     * @return ApplicationBusinessAccount
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
     * Set fiscalCode
     *
     * @param string $fiscalCode
     * @return ApplicationBusinessAccount
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
     * Set represFiscalCode
     *
     * @param string $represFiscalCode
     * @return ApplicationBusinessAccount
     */
    public function setRepresFiscalCode($represFiscalCode)
    {
        $this->represFiscalCode = $represFiscalCode;
    
        return $this;
    }

    /**
     * Get represFiscalCode
     *
     * @return string 
     */
    public function getRepresFiscalCode()
    {
        return $this->represFiscalCode;
    }

    /**
     * Set represFirstName
     *
     * @param string $represFirstName
     * @return ApplicationBusinessAccount
     */
    public function setRepresFirstName($represFirstName)
    {
        $this->represFirstName = $represFirstName;
    
        return $this;
    }

    /**
     * Get represFirstName
     *
     * @return string 
     */
    public function getRepresFirstName()
    {
        return $this->represFirstName;
    }

    /**
     * Set represLastName
     *
     * @param string $represLastName
     * @return ApplicationBusinessAccount
     */
    public function setRepresLastName($represLastName)
    {
        $this->represLastName = $represLastName;
    
        return $this;
    }

    /**
     * Get represLastName
     *
     * @return string 
     */
    public function getRepresLastName()
    {
        return $this->represLastName;
    }

    /**
     * Set represPlaceOfBirth
     *
     * @param string $represPlaceOfBirth
     * @return ApplicationBusinessAccount
     */
    public function setRepresPlaceOfBirth($represPlaceOfBirth)
    {
        $this->represPlaceOfBirth = $represPlaceOfBirth;
    
        return $this;
    }

    /**
     * Get represPlaceOfBirth
     *
     * @return string 
     */
    public function getRepresPlaceOfBirth()
    {
        return $this->represPlaceOfBirth;
    }

    /**
     * Set represDob
     *
     * @param \DateTime $represDob
     * @return ApplicationBusinessAccount
     */
    public function setRepresDob($represDob)
    {
        $this->represDob = $represDob;
    
        return $this;
    }

    /**
     * Get represDob
     *
     * @return \DateTime 
     */
    public function getRepresDob()
    {
        return $this->represDob;
    }

    /**
     * Set represEmail
     *
     * @param string $represEmail
     * @return ApplicationBusinessAccount
     */
    public function setRepresEmail($represEmail)
    {
        $this->represEmail = $represEmail;
    
        return $this;
    }

    /**
     * Get represEmail
     *
     * @return string 
     */
    public function getRepresEmail()
    {
        return $this->represEmail;
    }

    /**
     * Set represPhoneNumber
     *
     * @param string $represPhoneNumber
     * @return ApplicationBusinessAccount
     */
    public function setRepresPhoneNumber($represPhoneNumber)
    {
        $this->represPhoneNumber = $represPhoneNumber;
    
        return $this;
    }

    /**
     * Get represPhoneNumber
     *
     * @return string 
     */
    public function getRepresPhoneNumber()
    {
        return $this->represPhoneNumber;
    }

    /**
     * Set represAddress
     *
     * @param string $represAddress
     * @return ApplicationBusinessAccount
     */
    public function setRepresAddress($represAddress)
    {
        $this->represAddress = $represAddress;
    
        return $this;
    }

    /**
     * Get represAddress
     *
     * @return string 
     */
    public function getRepresAddress()
    {
        return $this->represAddress;
    }

    /**
     * Set represProvince
     *
     * @param string $represProvince
     * @return ApplicationBusinessAccount
     */
    public function setRepresProvince($represProvince)
    {
        $this->represProvince = $represProvince;
    
        return $this;
    }

    /**
     * Get represProvince
     *
     * @return string 
     */
    public function getRepresProvince()
    {
        return $this->represProvince;
    }

    /**
     * Set represCity
     *
     * @param string $represCity
     * @return ApplicationBusinessAccount
     */
    public function setRepresCity($represCity)
    {
        $this->represCity = $represCity;
    
        return $this;
    }

    /**
     * Get represCity
     *
     * @return string 
     */
    public function getRepresCity()
    {
        return $this->represCity;
    }

    /**
     * Set represZip
     *
     * @param string $represZip
     * @return ApplicationBusinessAccount
     */
    public function setRepresZip($represZip)
    {
        $this->represZip = $represZip;
    
        return $this;
    }

    /**
     * Get represZip
     *
     * @return string 
     */
    public function getRepresZip()
    {
        return $this->represZip;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ApplicationBusinessAccount
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return ApplicationBusinessAccount
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
     * Set isActive
     *
     * @param integer $isActive
     * @return ApplicationBusinessAccount
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    
        return $this;
    }

    /**
     * Get isActive
     *
     * @return integer 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isDeleted
     *
     * @param integer $isDeleted
     * @return ApplicationBusinessAccount
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
     * @var string
     */
    private $latitude;

    /**
     * @var string
     */
    private $longitude;


    /**
     * Set latitude
     *
     * @param string $latitude
     * @return ApplicationBusinessAccount
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
     * @return ApplicationBusinessAccount
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
     * @var string
     */
    private $mapPlace;


    /**
     * Set mapPlace
     *
     * @param string $mapPlace
     * @return ApplicationBusinessAccount
     */
    public function setMapPlace($mapPlace)
    {
        $this->mapPlace = $mapPlace;
    
        return $this;
    }

    /**
     * Get mapPlace
     *
     * @return string 
     */
    public function getMapPlace()
    {
        return $this->mapPlace;
    }
    /**
     * @var string
     */
    private $connectFeesPayer;

    /**
     * @var string
     */
    private $connectCiFeesPayer;


    /**
     * Set connectFeesPayer
     *
     * @param string $connectFeesPayer
     * @return ApplicationBusinessAccount
     */
    public function setConnectFeesPayer($connectFeesPayer)
    {
        $this->connectFeesPayer = $connectFeesPayer;
    
        return $this;
    }

    /**
     * Get connectFeesPayer
     *
     * @return string 
     */
    public function getConnectFeesPayer()
    {
        return $this->connectFeesPayer;
    }

    /**
     * Set connectCiFeesPayer
     *
     * @param string $connectCiFeesPayer
     * @return ApplicationBusinessAccount
     */
    public function setConnectCiFeesPayer($connectCiFeesPayer)
    {
        $this->connectCiFeesPayer = $connectCiFeesPayer;
    
        return $this;
    }

    /**
     * Get connectCiFeesPayer
     *
     * @return string 
     */
    public function getConnectCiFeesPayer()
    {
        return $this->connectCiFeesPayer;
    }
    /**
     * @var string
     */
    private $profileImage = '';

    /**
     * @var integer
     */
    private $catId = 0;


    /**
     * Set profileImage
     *
     * @param string $profileImage
     * @return ApplicationBusinessAccount
     */
    public function setProfileImage($profileImage)
    {
        $this->profileImage = $profileImage;
    
        return $this;
    }

    /**
     * Get profileImage
     *
     * @return string 
     */
    public function getProfileImage()
    {
        return $this->profileImage;
    }

    /**
     * Set catId
     *
     * @param integer $catId
     * @return ApplicationBusinessAccount
     */
    public function setCatId($catId)
    {
        $this->catId = $catId;
    
        return $this;
    }

    /**
     * Get catId
     *
     * @return integer 
     */
    public function getCatId()
    {
        return $this->catId;
    }
}