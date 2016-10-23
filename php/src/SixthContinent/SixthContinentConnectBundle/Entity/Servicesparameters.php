<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Servicesparameters
 */
class Servicesparameters
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $parameters;

    /**
     * @var integer
     */
    private $applicationService;


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
     * Set parameters
     *
     * @param string $parameters
     * @return Servicesparameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    
        return $this;
    }

    /**
     * Get parameters
     *
     * @return string 
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set applicationService
     *
     * @param integer $applicationService
     * @return Servicesparameters
     */
    public function setApplicationService($applicationService)
    {
        $this->applicationService = $applicationService;
    
        return $this;
    }

    /**
     * Get applicationService
     *
     * @return integer 
     */
    public function getApplicationService()
    {
        return $this->applicationService;
    }
}