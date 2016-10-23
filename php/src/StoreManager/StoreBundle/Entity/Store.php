<?php

namespace StoreManager\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Store
 */
class Store
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $parentStoreId;

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
    private $phone;

    /**
     * @var string
     */
    private $businessName;

    /**
     * @var string
     */
    private $legalStatus;

    /**
     * @var string
     */
    private $businessType;

    /**
     * @var integer
     */
    private $paymentStatus;

    /**
     * @var integer
     */
    private $creditCardStatus;

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
    private $fiscalCode;

    /**
     * @var string
     */
    private $iban;

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
    private $name;

    /**
     * @var string
     */
    private $storeImage;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @var boolean
     */
    private $isActive;

    /**
     * @var boolean
     */
    private $isAllowed;

    /**
     * @var boolean
     */
    private $shopStatus;

    /**
     * @var integer
     */
    private $totalDp;

    /**
     * @var integer
     */
    private $balanceDp;

    /**
     * @var string
     */
    private $saleCountry;

    /**
     * @var string
     */
    private $saleRegion;

    /**
     * @var string
     */
    private $saleCity;

    /**
     * @var string
     */
    private $saleProvince;

    /**
     * @var string
     */
    private $saleZip;

    /**
     * @var string
     */
    private $saleAddress;

    /**
     * @var string
     */
    private $saleEmail;

    /**
     * @var string
     */
    private $salePhoneNumber;

    /**
     * @var integer
     */
    private $saleCatid;

    /**
     * @var integer
     */
    private $saleSubcatid;

    /**
     * @var string
     */
    private $saleDescription;

    /**
     * @var string
     */
    private $saleMap;

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
     * @var string
     */
    private $shopKeyword;

    /**
     * @var float
     */
    private $avgRating;

    /**
     * @var integer
     */
    private $voteCount;

    /**
     * @var integer
     */
    private $newContractStatus;

    /**
     * @var integer
     */
    private $isSubscribed;

    /**
     * @var integer
     */
    private $isPaypalAdded;

    /**
     * @var float
     */
    private $txnPercentage;

    /**
     * @var float
     */
    private $cardPercentage;

    /**
     * @var integer
     */
    private $affiliationStatus;

    /**
     * @var float
     */
    private $citizenAffCharge;

    /**
     * @var float
     */
    private $shopAffCharge;

    /**
     * @var float
     */
    private $friendsFollowerCharge;

    /**
     * @var float
     */
    private $buyerCharge;

    /**
     * @var float
     */
    private $sixcCharge;

    /**
     * @var float
     */
    private $allCountryCharge;


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
     * Set parentStoreId
     *
     * @param integer $parentStoreId
     * @return Store
     */
    public function setParentStoreId($parentStoreId)
    {
        $this->parentStoreId = $parentStoreId;
    
        return $this;
    }

    /**
     * Get parentStoreId
     *
     * @return integer 
     */
    public function getParentStoreId()
    {
        return $this->parentStoreId;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Store
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
     * @return Store
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
     * Set phone
     *
     * @param string $phone
     * @return Store
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
     * Set businessName
     *
     * @param string $businessName
     * @return Store
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
     * Set legalStatus
     *
     * @param string $legalStatus
     * @return Store
     */
    public function setLegalStatus($legalStatus)
    {
        $this->legalStatus = $legalStatus;
    
        return $this;
    }

    /**
     * Get legalStatus
     *
     * @return string 
     */
    public function getLegalStatus()
    {
        return $this->legalStatus;
    }

    /**
     * Set businessType
     *
     * @param string $businessType
     * @return Store
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
     * Set paymentStatus
     *
     * @param integer $paymentStatus
     * @return Store
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;
    
        return $this;
    }

    /**
     * Get paymentStatus
     *
     * @return integer 
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * Set creditCardStatus
     *
     * @param integer $creditCardStatus
     * @return Store
     */
    public function setCreditCardStatus($creditCardStatus)
    {
        $this->creditCardStatus = $creditCardStatus;
    
        return $this;
    }

    /**
     * Get creditCardStatus
     *
     * @return integer 
     */
    public function getCreditCardStatus()
    {
        return $this->creditCardStatus;
    }

    /**
     * Set businessCountry
     *
     * @param string $businessCountry
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * Set zip
     *
     * @param string $zip
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * Set mapPlace
     *
     * @param string $mapPlace
     * @return Store
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
     * Set latitude
     *
     * @param string $latitude
     * @return Store
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
     * @return Store
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
     * Set name
     *
     * @param string $name
     * @return Store
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
     * Set storeImage
     *
     * @param string $storeImage
     * @return Store
     */
    public function setStoreImage($storeImage)
    {
        $this->storeImage = $storeImage;
    
        return $this;
    }

    /**
     * Get storeImage
     *
     * @return string 
     */
    public function getStoreImage()
    {
        return $this->storeImage;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Store
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
     * @return Store
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
     * @param boolean $isActive
     * @return Store
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    
        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isAllowed
     *
     * @param boolean $isAllowed
     * @return Store
     */
    public function setIsAllowed($isAllowed)
    {
        $this->isAllowed = $isAllowed;
    
        return $this;
    }

    /**
     * Get isAllowed
     *
     * @return boolean 
     */
    public function getIsAllowed()
    {
        return $this->isAllowed;
    }

    /**
     * Set shopStatus
     *
     * @param boolean $shopStatus
     * @return Store
     */
    public function setShopStatus($shopStatus)
    {
        $this->shopStatus = $shopStatus;
    
        return $this;
    }

    /**
     * Get shopStatus
     *
     * @return boolean 
     */
    public function getShopStatus()
    {
        return $this->shopStatus;
    }

    /**
     * Set totalDp
     *
     * @param integer $totalDp
     * @return Store
     */
    public function setTotalDp($totalDp)
    {
        $this->totalDp = $totalDp;
    
        return $this;
    }

    /**
     * Get totalDp
     *
     * @return integer 
     */
    public function getTotalDp()
    {
        return $this->totalDp;
    }

    /**
     * Set balanceDp
     *
     * @param integer $balanceDp
     * @return Store
     */
    public function setBalanceDp($balanceDp)
    {
        $this->balanceDp = $balanceDp;
    
        return $this;
    }

    /**
     * Get balanceDp
     *
     * @return integer 
     */
    public function getBalanceDp()
    {
        return $this->balanceDp;
    }

    /**
     * Set saleCountry
     *
     * @param string $saleCountry
     * @return Store
     */
    public function setSaleCountry($saleCountry)
    {
        $this->saleCountry = $saleCountry;
    
        return $this;
    }

    /**
     * Get saleCountry
     *
     * @return string 
     */
    public function getSaleCountry()
    {
        return $this->saleCountry;
    }

    /**
     * Set saleRegion
     *
     * @param string $saleRegion
     * @return Store
     */
    public function setSaleRegion($saleRegion)
    {
        $this->saleRegion = $saleRegion;
    
        return $this;
    }

    /**
     * Get saleRegion
     *
     * @return string 
     */
    public function getSaleRegion()
    {
        return $this->saleRegion;
    }

    /**
     * Set saleCity
     *
     * @param string $saleCity
     * @return Store
     */
    public function setSaleCity($saleCity)
    {
        $this->saleCity = $saleCity;
    
        return $this;
    }

    /**
     * Get saleCity
     *
     * @return string 
     */
    public function getSaleCity()
    {
        return $this->saleCity;
    }

    /**
     * Set saleProvince
     *
     * @param string $saleProvince
     * @return Store
     */
    public function setSaleProvince($saleProvince)
    {
        $this->saleProvince = $saleProvince;
    
        return $this;
    }

    /**
     * Get saleProvince
     *
     * @return string 
     */
    public function getSaleProvince()
    {
        return $this->saleProvince;
    }

    /**
     * Set saleZip
     *
     * @param string $saleZip
     * @return Store
     */
    public function setSaleZip($saleZip)
    {
        $this->saleZip = $saleZip;
    
        return $this;
    }

    /**
     * Get saleZip
     *
     * @return string 
     */
    public function getSaleZip()
    {
        return $this->saleZip;
    }

    /**
     * Set saleAddress
     *
     * @param string $saleAddress
     * @return Store
     */
    public function setSaleAddress($saleAddress)
    {
        $this->saleAddress = $saleAddress;
    
        return $this;
    }

    /**
     * Get saleAddress
     *
     * @return string 
     */
    public function getSaleAddress()
    {
        return $this->saleAddress;
    }

    /**
     * Set saleEmail
     *
     * @param string $saleEmail
     * @return Store
     */
    public function setSaleEmail($saleEmail)
    {
        $this->saleEmail = $saleEmail;
    
        return $this;
    }

    /**
     * Get saleEmail
     *
     * @return string 
     */
    public function getSaleEmail()
    {
        return $this->saleEmail;
    }

    /**
     * Set salePhoneNumber
     *
     * @param string $salePhoneNumber
     * @return Store
     */
    public function setSalePhoneNumber($salePhoneNumber)
    {
        $this->salePhoneNumber = $salePhoneNumber;
    
        return $this;
    }

    /**
     * Get salePhoneNumber
     *
     * @return string 
     */
    public function getSalePhoneNumber()
    {
        return $this->salePhoneNumber;
    }

    /**
     * Set saleCatid
     *
     * @param integer $saleCatid
     * @return Store
     */
    public function setSaleCatid($saleCatid)
    {
        $this->saleCatid = $saleCatid;
    
        return $this;
    }

    /**
     * Get saleCatid
     *
     * @return integer 
     */
    public function getSaleCatid()
    {
        return $this->saleCatid;
    }

    /**
     * Set saleSubcatid
     *
     * @param integer $saleSubcatid
     * @return Store
     */
    public function setSaleSubcatid($saleSubcatid)
    {
        $this->saleSubcatid = $saleSubcatid;
    
        return $this;
    }

    /**
     * Get saleSubcatid
     *
     * @return integer 
     */
    public function getSaleSubcatid()
    {
        return $this->saleSubcatid;
    }

    /**
     * Set saleDescription
     *
     * @param string $saleDescription
     * @return Store
     */
    public function setSaleDescription($saleDescription)
    {
        $this->saleDescription = $saleDescription;
    
        return $this;
    }

    /**
     * Get saleDescription
     *
     * @return string 
     */
    public function getSaleDescription()
    {
        return $this->saleDescription;
    }

    /**
     * Set saleMap
     *
     * @param string $saleMap
     * @return Store
     */
    public function setSaleMap($saleMap)
    {
        $this->saleMap = $saleMap;
    
        return $this;
    }

    /**
     * Get saleMap
     *
     * @return string 
     */
    public function getSaleMap()
    {
        return $this->saleMap;
    }

    /**
     * Set represFiscalCode
     *
     * @param string $represFiscalCode
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * @return Store
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
     * Set shopKeyword
     *
     * @param string $shopKeyword
     * @return Store
     */
    public function setShopKeyword($shopKeyword)
    {
        $this->shopKeyword = $shopKeyword;
    
        return $this;
    }

    /**
     * Get shopKeyword
     *
     * @return string 
     */
    public function getShopKeyword()
    {
        return $this->shopKeyword;
    }

    /**
     * Set avgRating
     *
     * @param float $avgRating
     * @return Store
     */
    public function setAvgRating($avgRating)
    {
        $this->avgRating = $avgRating;
    
        return $this;
    }

    /**
     * Get avgRating
     *
     * @return float 
     */
    public function getAvgRating()
    {
        return $this->avgRating;
    }

    /**
     * Set voteCount
     *
     * @param integer $voteCount
     * @return Store
     */
    public function setVoteCount($voteCount)
    {
        $this->voteCount = $voteCount;
    
        return $this;
    }

    /**
     * Get voteCount
     *
     * @return integer 
     */
    public function getVoteCount()
    {
        return $this->voteCount;
    }

    /**
     * Set newContractStatus
     *
     * @param integer $newContractStatus
     * @return Store
     */
    public function setNewContractStatus($newContractStatus)
    {
        $this->newContractStatus = $newContractStatus;
    
        return $this;
    }

    /**
     * Get newContractStatus
     *
     * @return integer 
     */
    public function getNewContractStatus()
    {
        return $this->newContractStatus;
    }

    /**
     * Set isSubscribed
     *
     * @param integer $isSubscribed
     * @return Store
     */
    public function setIsSubscribed($isSubscribed)
    {
        $this->isSubscribed = $isSubscribed;
    
        return $this;
    }

    /**
     * Get isSubscribed
     *
     * @return integer 
     */
    public function getIsSubscribed()
    {
        return $this->isSubscribed;
    }

    /**
     * Set isPaypalAdded
     *
     * @param integer $isPaypalAdded
     * @return Store
     */
    public function setIsPaypalAdded($isPaypalAdded)
    {
        $this->isPaypalAdded = $isPaypalAdded;
    
        return $this;
    }

    /**
     * Get isPaypalAdded
     *
     * @return integer 
     */
    public function getIsPaypalAdded()
    {
        return $this->isPaypalAdded;
    }

    /**
     * Set txnPercentage
     *
     * @param float $txnPercentage
     * @return Store
     */
    public function setTxnPercentage($txnPercentage)
    {
        $this->txnPercentage = $txnPercentage;
    
        return $this;
    }

    /**
     * Get txnPercentage
     *
     * @return float 
     */
    public function getTxnPercentage()
    {
        return $this->txnPercentage;
    }

    /**
     * Set cardPercentage
     *
     * @param float $cardPercentage
     * @return Store
     */
    public function setCardPercentage($cardPercentage)
    {
        $this->cardPercentage = $cardPercentage;
    
        return $this;
    }

    /**
     * Get cardPercentage
     *
     * @return float 
     */
    public function getCardPercentage()
    {
        return $this->cardPercentage;
    }

    /**
     * Set affiliationStatus
     *
     * @param integer $affiliationStatus
     * @return Store
     */
    public function setAffiliationStatus($affiliationStatus)
    {
        $this->affiliationStatus = $affiliationStatus;
    
        return $this;
    }

    /**
     * Get affiliationStatus
     *
     * @return integer 
     */
    public function getAffiliationStatus()
    {
        return $this->affiliationStatus;
    }

    /**
     * Set citizenAffCharge
     *
     * @param float $citizenAffCharge
     * @return Store
     */
    public function setCitizenAffCharge($citizenAffCharge)
    {
        $this->citizenAffCharge = $citizenAffCharge;
    
        return $this;
    }

    /**
     * Get citizenAffCharge
     *
     * @return float 
     */
    public function getCitizenAffCharge()
    {
        return $this->citizenAffCharge;
    }

    /**
     * Set shopAffCharge
     *
     * @param float $shopAffCharge
     * @return Store
     */
    public function setShopAffCharge($shopAffCharge)
    {
        $this->shopAffCharge = $shopAffCharge;
    
        return $this;
    }

    /**
     * Get shopAffCharge
     *
     * @return float 
     */
    public function getShopAffCharge()
    {
        return $this->shopAffCharge;
    }

    /**
     * Set friendsFollowerCharge
     *
     * @param float $friendsFollowerCharge
     * @return Store
     */
    public function setFriendsFollowerCharge($friendsFollowerCharge)
    {
        $this->friendsFollowerCharge = $friendsFollowerCharge;
    
        return $this;
    }

    /**
     * Get friendsFollowerCharge
     *
     * @return float 
     */
    public function getFriendsFollowerCharge()
    {
        return $this->friendsFollowerCharge;
    }

    /**
     * Set buyerCharge
     *
     * @param float $buyerCharge
     * @return Store
     */
    public function setBuyerCharge($buyerCharge)
    {
        $this->buyerCharge = $buyerCharge;
    
        return $this;
    }

    /**
     * Get buyerCharge
     *
     * @return float 
     */
    public function getBuyerCharge()
    {
        return $this->buyerCharge;
    }

    /**
     * Set sixcCharge
     *
     * @param float $sixcCharge
     * @return Store
     */
    public function setSixcCharge($sixcCharge)
    {
        $this->sixcCharge = $sixcCharge;
    
        return $this;
    }

    /**
     * Get sixcCharge
     *
     * @return float 
     */
    public function getSixcCharge()
    {
        return $this->sixcCharge;
    }

    /**
     * Set allCountryCharge
     *
     * @param float $allCountryCharge
     * @return Store
     */
    public function setAllCountryCharge($allCountryCharge)
    {
        $this->allCountryCharge = $allCountryCharge;
    
        return $this;
    }

    /**
     * Get allCountryCharge
     *
     * @return float 
     */
    public function getAllCountryCharge()
    {
        return $this->allCountryCharge;
    }
}
