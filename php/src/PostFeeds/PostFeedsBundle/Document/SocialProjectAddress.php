<?php

namespace PostFeeds\PostFeedsBundle\Document;



/**
 * PostFeeds\PostFeedsBundle\Document\SocialProjectAddress
 */
class SocialProjectAddress
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $project_id
     */
    protected $project_id;

    /**
     * @var string $location
     */
    protected $location;

    /**
     * @var string $city
     */
    protected $city;

    /**
     * @var string $country
     */
    protected $country;
    /**
     * @var string $latitude
     */
    protected $latitude;
    /**
     * @var string $longitude
     */
    protected $longitude;


    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set projectId
     *
     * @param string $projectId
     * @return self
     */
    public function setProjectId($projectId)
    {
        $this->project_id = $projectId;
        return $this;
    }

    /**
     * Get projectId
     *
     * @return string $projectId
     */
    public function getProjectId()
    {
        return $this->project_id;
    }

    /**
     * Set location
     *
     * @param string $location
     * @return self
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * Get location
     *
     * @return string $location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return self
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Get city
     *
     * @return string $city
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set country
     *
     * @param string $country
     * @return self
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * Get country
     *
     * @return string $country
     */
    public function getCountry()
    {
        return $this->country;
    }
    /**
     * Set latitude
     *
     * @param string $latitude
     * @return self
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * Get latitude
     *
     * @return string $latitude
     */
    public function getLatitude()
    {
        return $this->latitude;
    }
    /**
     * Set longitude
     *
     * @param string $longitude
     * @return self
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * Get longitude
     *
     * @return string $longitude
     */
    public function getLongitude()
    {
        return $this->longitude;
    }
}
