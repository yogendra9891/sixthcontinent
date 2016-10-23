<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CitizenUser
 */
class CitizenUser
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
    private $region;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $zip;

    /**
     * @var string
     */
    private $latitude;

    /**
     * @var string
     */
    private $longitude;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $updatedAt;

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
    private $cityBorn;

    /**
     * @var string
     */
    private $ssn = '';

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
     * @return CitizenUser
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
     * Set roleId
     *
     * @param integer $roleId
     * @return CitizenUser
     */
    public function setRoleId($roleId)
    {
        $this->roleId = $roleId;

        return $this;
    }

    /**
     * Get roleId
     *
     * @return integer
     */
    public function getRoleId()
    {
        return $this->roleId;
    }

    /**
     * Set region
     *
     * @param string $region
     * @return CitizenUser
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
     * Set city
     *
     * @param string $city
     * @return CitizenUser
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set cityBorn
     *
     * @param string $cityBorn
     * @return CitizenUser
     */
    public function setCityBorn($cityBorn)
    {
        $this->cityBorn = $cityBorn;

        return $this;
    }

    /**
     * Get cityBorn
     *
     * @return string
     */
    public function getCityBorn()
    {
        return $this->cityBorn;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return CitizenUser
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
     * Set zip
     *
     * @param string $zip
     * @return CitizenUser
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return CitizenUser
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
     * @return CitizenUser
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
     * Set ssn
     *
     * @param string $ssn
     * @return CitizenUser
     */
    public function setSsn($ssn)
    {
        $this->ssn = $ssn;

        return $this;
    }

    /**
     * Get ssn
     *
     * @return ssn
     */
    public function getSsn()
    {
        return $this->ssn;
    }
}
