<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sixthcontinentconnecttransaction
 */
class Sixthcontinentconnecttransaction
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
    private $businessAccountUserId;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var string
     */
    private $transactionId;

    /**
     * @var string
     */
    private $applicationId;

    /**
     * @var string
     */
    private $cardPreference;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $transactionType;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $languageId;

    /**
     * @var string
     */
    private $status;

    /**
     * @var integer
     */
    private $transactionValue;

    /**
     * @var integer
     */
    private $discount;

    /**
     * @var integer
     */
    private $paybleValue;

    /**
     * @var integer
     */
    private $vat;

    /**
     * @var integer
     */
    private $checkoutValue;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $urlPost;

    /**
     * @var string
     */
    private $urlBack;

    /**
     * @var string
     */
    private $typeService;

    /**
     * @var string
     */
    private $mac;

    /**
     * @var string
     */
    private $paypalTransactionId;

    /**
     * @var string
     */
    private $paypalTransactionReference;

    /**
     * @var string
     */
    private $ciTransactionSystemId;

    /**
     * @var integer
     */
    private $totalAvailableCi;

    /**
     * @var integer
     */
    private $usedCi;

    /**
     * @var string
     */
    private $sessionId;


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
     * @return Sixthcontinentconnecttransaction
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
     * Set businessAccountUserId
     *
     * @param integer $businessAccountUserId
     * @return Sixthcontinentconnecttransaction
     */
    public function setBusinessAccountUserId($businessAccountUserId)
    {
        $this->businessAccountUserId = $businessAccountUserId;
    
        return $this;
    }

    /**
     * Get businessAccountUserId
     *
     * @return integer 
     */
    public function getBusinessAccountUserId()
    {
        return $this->businessAccountUserId;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     * @return Sixthcontinentconnecttransaction
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
     * Set transactionId
     *
     * @param string $transactionId
     * @return Sixthcontinentconnecttransaction
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
     * Set applicationId
     *
     * @param string $applicationId
     * @return Sixthcontinentconnecttransaction
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
     * Set cardPreference
     *
     * @param string $cardPreference
     * @return Sixthcontinentconnecttransaction
     */
    public function setCardPreference($cardPreference)
    {
        $this->cardPreference = $cardPreference;
    
        return $this;
    }

    /**
     * Get cardPreference
     *
     * @return string 
     */
    public function getCardPreference()
    {
        return $this->cardPreference;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Sixthcontinentconnecttransaction
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set transactionType
     *
     * @param string $transactionType
     * @return Sixthcontinentconnecttransaction
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
     * Set currency
     *
     * @param string $currency
     * @return Sixthcontinentconnecttransaction
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    
        return $this;
    }

    /**
     * Get currency
     *
     * @return string 
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set languageId
     *
     * @param string $languageId
     * @return Sixthcontinentconnecttransaction
     */
    public function setLanguageId($languageId)
    {
        $this->languageId = $languageId;
    
        return $this;
    }

    /**
     * Get languageId
     *
     * @return string 
     */
    public function getLanguageId()
    {
        return $this->languageId;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Sixthcontinentconnecttransaction
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
     * Set transactionValue
     *
     * @param integer $transactionValue
     * @return Sixthcontinentconnecttransaction
     */
    public function setTransactionValue($transactionValue)
    {
        $this->transactionValue = $transactionValue;
    
        return $this;
    }

    /**
     * Get transactionValue
     *
     * @return integer 
     */
    public function getTransactionValue()
    {
        return $this->transactionValue;
    }

    /**
     * Set discount
     *
     * @param integer $discount
     * @return Sixthcontinentconnecttransaction
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
    
        return $this;
    }

    /**
     * Get discount
     *
     * @return integer 
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set paybleValue
     *
     * @param integer $paybleValue
     * @return Sixthcontinentconnecttransaction
     */
    public function setPaybleValue($paybleValue)
    {
        $this->paybleValue = $paybleValue;
    
        return $this;
    }

    /**
     * Get paybleValue
     *
     * @return integer 
     */
    public function getPaybleValue()
    {
        return $this->paybleValue;
    }

    /**
     * Set vat
     *
     * @param integer $vat
     * @return Sixthcontinentconnecttransaction
     */
    public function setVat($vat)
    {
        $this->vat = $vat;
    
        return $this;
    }

    /**
     * Get vat
     *
     * @return integer 
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * Set checkoutValue
     *
     * @param integer $checkoutValue
     * @return Sixthcontinentconnecttransaction
     */
    public function setCheckoutValue($checkoutValue)
    {
        $this->checkoutValue = $checkoutValue;
    
        return $this;
    }

    /**
     * Get checkoutValue
     *
     * @return integer 
     */
    public function getCheckoutValue()
    {
        return $this->checkoutValue;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Sixthcontinentconnecttransaction
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
     * Set url
     *
     * @param string $url
     * @return Sixthcontinentconnecttransaction
     */
    public function setUrl($url)
    {
        $this->url = $url;
    
        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set urlPost
     *
     * @param string $urlPost
     * @return Sixthcontinentconnecttransaction
     */
    public function setUrlPost($urlPost)
    {
        $this->urlPost = $urlPost;
    
        return $this;
    }

    /**
     * Get urlPost
     *
     * @return string 
     */
    public function getUrlPost()
    {
        return $this->urlPost;
    }

    /**
     * Set urlBack
     *
     * @param string $urlBack
     * @return Sixthcontinentconnecttransaction
     */
    public function setUrlBack($urlBack)
    {
        $this->urlBack = $urlBack;
    
        return $this;
    }

    /**
     * Get urlBack
     *
     * @return string 
     */
    public function getUrlBack()
    {
        return $this->urlBack;
    }

    /**
     * Set typeService
     *
     * @param string $typeService
     * @return Sixthcontinentconnecttransaction
     */
    public function setTypeService($typeService)
    {
        $this->typeService = $typeService;
    
        return $this;
    }

    /**
     * Get typeService
     *
     * @return string 
     */
    public function getTypeService()
    {
        return $this->typeService;
    }

    /**
     * Set mac
     *
     * @param string $mac
     * @return Sixthcontinentconnecttransaction
     */
    public function setMac($mac)
    {
        $this->mac = $mac;
    
        return $this;
    }

    /**
     * Get mac
     *
     * @return string 
     */
    public function getMac()
    {
        return $this->mac;
    }

    /**
     * Set paypalTransactionId
     *
     * @param string $paypalTransactionId
     * @return Sixthcontinentconnecttransaction
     */
    public function setPaypalTransactionId($paypalTransactionId)
    {
        $this->paypalTransactionId = $paypalTransactionId;
    
        return $this;
    }

    /**
     * Get paypalTransactionId
     *
     * @return string 
     */
    public function getPaypalTransactionId()
    {
        return $this->paypalTransactionId;
    }

    /**
     * Set paypalTransactionReference
     *
     * @param string $paypalTransactionReference
     * @return Sixthcontinentconnecttransaction
     */
    public function setPaypalTransactionReference($paypalTransactionReference)
    {
        $this->paypalTransactionReference = $paypalTransactionReference;
    
        return $this;
    }

    /**
     * Get paypalTransactionReference
     *
     * @return string 
     */
    public function getPaypalTransactionReference()
    {
        return $this->paypalTransactionReference;
    }

    /**
     * Set ciTransactionSystemId
     *
     * @param string $ciTransactionSystemId
     * @return Sixthcontinentconnecttransaction
     */
    public function setCiTransactionSystemId($ciTransactionSystemId)
    {
        $this->ciTransactionSystemId = $ciTransactionSystemId;
    
        return $this;
    }

    /**
     * Get ciTransactionSystemId
     *
     * @return string 
     */
    public function getCiTransactionSystemId()
    {
        return $this->ciTransactionSystemId;
    }

    /**
     * Set totalAvailableCi
     *
     * @param integer $totalAvailableCi
     * @return Sixthcontinentconnecttransaction
     */
    public function setTotalAvailableCi($totalAvailableCi)
    {
        $this->totalAvailableCi = $totalAvailableCi;
    
        return $this;
    }

    /**
     * Get totalAvailableCi
     *
     * @return integer 
     */
    public function getTotalAvailableCi()
    {
        return $this->totalAvailableCi;
    }

    /**
     * Set usedCi
     *
     * @param integer $usedCi
     * @return Sixthcontinentconnecttransaction
     */
    public function setUsedCi($usedCi)
    {
        $this->usedCi = $usedCi;
    
        return $this;
    }

    /**
     * Get usedCi
     *
     * @return integer 
     */
    public function getUsedCi()
    {
        return $this->usedCi;
    }

    /**
     * Set sessionId
     *
     * @param string $sessionId
     * @return Sixthcontinentconnecttransaction
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    
        return $this;
    }

    /**
     * Get sessionId
     *
     * @return string 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
    /**
     * @var string
     */
    private $timeStamp;


    /**
     * Set timeStamp
     *
     * @param string $timeStamp
     * @return Sixthcontinentconnecttransaction
     */
    public function setTimeStamp($timeStamp)
    {
        $this->timeStamp = $timeStamp;
    
        return $this;
    }

    /**
     * Get timeStamp
     *
     * @return string 
     */
    public function getTimeStamp()
    {
        return $this->timeStamp;
    }
}