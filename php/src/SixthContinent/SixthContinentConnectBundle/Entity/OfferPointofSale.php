<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OfferPointofSale
 */
class OfferPointofSale
{
    
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $offerId;

    /**
     * @var integer
     */
    private $pvExternalId;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $region;

    /**
     * @var string
     */
    private $place;

    /**
     * @var string
     */
    private $province;

    /**
     * @var string
     */
    private $zip;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $latitude;

    /**
     * @var string
     */
    private $longitude;

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
     * Set offerId
     *
     * @param integer $offerId
     * @return OfferPointofSale
     */
    public function setOfferId($offerId)
    {
        $this->offerId = $offerId;
    
        return $this;
    }

    /**
     * Get offerId
     *
     * @return integer 
     */
    public function getOfferId()
    {
        return $this->offerId;
    }

    /**
     * Set pvExternalId
     *
     * @param integer $pvExternalId
     * @return OfferPointofSale
     */
    public function setPvExternalId($pvExternalId)
    {
        $this->pvExternalId = $pvExternalId;
    
        return $this;
    }

    /**
     * Get pvExternalId
     *
     * @return integer 
     */
    public function getPvExternalId()
    {
        return $this->pvExternalId;
    }

    /**
     * Set country
     *
     * @param string $country
     * @return OfferPointofSale
     */
    public function setCountry($country)
    {
        $this->country = $country;
    
        return $this;
    }

    /**
     * Get country
     *
     * @return string 
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set region
     *
     * @param string $region
     * @return OfferPointofSale
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
     * Set place
     *
     * @param string $place
     * @return OfferPointofSale
     */
    public function setPlace($place)
    {
        $this->place = $place;
    
        return $this;
    }

    /**
     * Get place
     *
     * @return string 
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Set province
     *
     * @param string $province
     * @return OfferPointofSale
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
     * Set zip
     *
     * @param string $zip
     * @return OfferPointofSale
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
     * Set address
     *
     * @param string $address
     * @return OfferPointofSale
     */
    public function setAddress($address)
    {
        $this->address = $address;
    
        return $this;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set latitude
     *
     * @param string $latitude
     * @return OfferPointofSale
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
     * @return OfferPointofSale
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
     * Set isActive
     *
     * @param integer $isActive
     * @return OfferPointofSale
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
     * @return OfferPointofSale
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
}