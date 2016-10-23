<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Applicationservices
 */
class Applicationservices
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $serviceId;

    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var string
     */
    private $serviceSecret;

    /**
     * @var integer
     */
    private $service;


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
     * Set serviceId
     *
     * @param string $serviceId
     * @return Applicationservices
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;
    
        return $this;
    }

    /**
     * Get serviceId
     *
     * @return string 
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * Set serviceName
     *
     * @param string $serviceName
     * @return Applicationservices
     */
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;
    
        return $this;
    }

    /**
     * Get serviceName
     *
     * @return string 
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * Set serviceSecret
     *
     * @param string $serviceSecret
     * @return Applicationservices
     */
    public function setServiceSecret($serviceSecret)
    {
        $this->serviceSecret = $serviceSecret;
    
        return $this;
    }

    /**
     * Get serviceSecret
     *
     * @return string 
     */
    public function getServiceSecret()
    {
        return $this->serviceSecret;
    }

    /**
     * Set service
     *
     * @param integer $service
     * @return Applicationservices
     */
    public function setService($service)
    {
        $this->service = $service;
    
        return $this;
    }

    /**
     * Get service
     *
     * @return integer 
     */
    public function getService()
    {
        return $this->service;
    }
}