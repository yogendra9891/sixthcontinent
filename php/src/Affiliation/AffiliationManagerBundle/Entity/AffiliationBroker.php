<?php

namespace Affiliation\AffiliationManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AffiliationBroker
 */
class AffiliationBroker
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $fromId;

    /**
     * @var integer
     */
    private $toId;

    /**
     * @var \DateTime
     */
    private $createdAt;


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
     * Set fromId
     *
     * @param integer $fromId
     * @return AffiliationBroker
     */
    public function setFromId($fromId)
    {
        $this->fromId = $fromId;
    
        return $this;
    }

    /**
     * Get fromId
     *
     * @return integer 
     */
    public function getFromId()
    {
        return $this->fromId;
    }

    /**
     * Set toId
     *
     * @param integer $toId
     * @return AffiliationBroker
     */
    public function setToId($toId)
    {
        $this->toId = $toId;
    
        return $this;
    }

    /**
     * Get toId
     *
     * @return integer 
     */
    public function getToId()
    {
        return $this->toId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return AffiliationBroker
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
}
