<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserConnection
 */
class UserConnection
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $connectFrom;

    /**
     * @var integer
     */
    private $connectTo;

    /**
     * @var integer
     */
    private $status;
    
    /**
     * @var integer
     */
    private $professionalStatus;

    /**
     * @var integer
     */
    private $personalStatus;

    /**
     * @var integer
     */
    private $professionalRequest;

    /**
     * @var integer
     */
    private $personalRequest;

    /**
     * @var string
     */
    private $msg;

    /**
     * @var \DateTime
     */
    private $created;


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
     * Set connectFrom
     *
     * @param integer $connectFrom
     * @return UserConnection
     */
    public function setConnectFrom($connectFrom)
    {
        $this->connectFrom = $connectFrom;
    
        return $this;
    }

    /**
     * Get connectFrom
     *
     * @return integer 
     */
    public function getConnectFrom()
    {
        return $this->connectFrom;
    }

    /**
     * Set connectTo
     *
     * @param integer $connectTo
     * @return UserConnection
     */
    public function setConnectTo($connectTo)
    {
        $this->connectTo = $connectTo;
    
        return $this;
    }

    /**
     * Get connectTo
     *
     * @return integer 
     */
    public function getConnectTo()
    {
        return $this->connectTo;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return UserConnection
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set msg
     *
     * @param string $msg
     * @return UserConnection
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;
    
        return $this;
    }

    /**
     * Get msg
     *
     * @return string 
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return UserConnection
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }
    
    /**
     * Set professionalStatus
     *
     * @param integer $professionalStatus
     * @return UserConnection
     */
    public function setProfessionalStatus($professionalStatus)
    {
        $this->professionalStatus = $professionalStatus;
    
        return $this;
    }

    /**
     * Get professionalStatus
     *
     * @return integer 
     */
    public function getProfessionalStatus()
    {
        return $this->professionalStatus;
    }

    /**
     * Set personalStatus
     *
     * @param integer $personalStatus
     * @return UserConnection
     */
    public function setPersonalStatus($personalStatus)
    {
        $this->personalStatus = $personalStatus;
    
        return $this;
    }

    /**
     * Get personalStatus
     *
     * @return integer 
     */
    public function getPersonalStatus()
    {
        return $this->personalStatus;
    }

    /**
     * Set professionalRequest
     *
     * @param integer $professionalRequest
     * @return UserConnection
     */
    public function setProfessionalRequest($professionalRequest)
    {
        $this->professionalRequest = $professionalRequest;
    
        return $this;
    }

    /**
     * Get professionalRequest
     *
     * @return integer 
     */
    public function getProfessionalRequest()
    {
        return $this->professionalRequest;
    }

    /**
     * Set personalRequest
     *
     * @param integer $personalRequest
     * @return UserConnection
     */
    public function setPersonalRequest($personalRequest)
    {
        $this->personalRequest = $personalRequest;
    
        return $this;
    }

    /**
     * Get personalRequest
     *
     * @return integer 
     */
    public function getPersonalRequest()
    {
        return $this->personalRequest;
    }
}