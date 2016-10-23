<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Application
 */
class Application
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
    private $applicationName;

    /**
     * @var string
     */
    private $applicationVersion;

    /**
     * @var string
     */
    private $applicationSecret;

    /**
     * @var string
     */
    private $applicationServices;


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
     * @return Application
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
     * Set applicationName
     *
     * @param string $applicationName
     * @return Application
     */
    public function setApplicationName($applicationName)
    {
        $this->applicationName = $applicationName;
    
        return $this;
    }

    /**
     * Get applicationName
     *
     * @return string 
     */
    public function getApplicationName()
    {
        return $this->applicationName;
    }

    /**
     * Set applicationVersion
     *
     * @param string $applicationVersion
     * @return Application
     */
    public function setApplicationVersion($applicationVersion)
    {
        $this->applicationVersion = $applicationVersion;
    
        return $this;
    }

    /**
     * Get applicationVersion
     *
     * @return string 
     */
    public function getApplicationVersion()
    {
        return $this->applicationVersion;
    }

    /**
     * Set applicationSecret
     *
     * @param string $applicationSecret
     * @return Application
     */
    public function setApplicationSecret($applicationSecret)
    {
        $this->applicationSecret = $applicationSecret;
    
        return $this;
    }

    /**
     * Get applicationSecret
     *
     * @return string 
     */
    public function getApplicationSecret()
    {
        return $this->applicationSecret;
    }

    /**
     * Set applicationServices
     *
     * @param string $applicationServices
     * @return Application
     */
    public function setApplicationServices($applicationServices)
    {
        $this->applicationServices = $applicationServices;
    
        return $this;
    }

    /**
     * Get applicationServices
     *
     * @return string 
     */
    public function getApplicationServices()
    {
        return $this->applicationServices;
    }
    /**
     * @var integer
     */
    private $userId;


    /**
     * Set userId
     *
     * @param integer $userId
     * @return Application
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
     * @var string
     */
    private $applicationUrl;


    /**
     * Set applicationUrl
     *
     * @param string $applicationUrl
     * @return Application
     */
    public function setApplicationUrl($applicationUrl)
    {
        $this->applicationUrl = $applicationUrl;
    
        return $this;
    }

    /**
     * Get applicationUrl
     *
     * @return string 
     */
    public function getApplicationUrl()
    {
        return $this->applicationUrl;
    }
    /**
     * @var string
     */
    private $description;


    /**
     * Set description
     *
     * @param string $description
     * @return Application
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
}