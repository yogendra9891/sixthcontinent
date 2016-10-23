<?php

namespace CardManagement\CardManagementBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Waivers
 */
class Waivers
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $itemId;

    /**
     * @var string
     */
    private $itemType;

    /**
     * @var integer
     */
    private $waverId;

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
     * Set itemId
     *
     * @param integer $itemId
     * @return Waivers
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    
        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer 
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set itemType
     *
     * @param string $itemType
     * @return Waivers
     */
    public function setItemType($itemType)
    {
        $this->itemType = $itemType;
    
        return $this;
    }

    /**
     * Get itemType
     *
     * @return string 
     */
    public function getItemType()
    {
        return $this->itemType;
    }

    /**
     * Set waverId
     *
     * @param integer $waverId
     * @return Waivers
     */
    public function setWaverId($waverId)
    {
        $this->waverId = $waverId;
    
        return $this;
    }

    /**
     * Get waverId
     *
     * @return integer 
     */
    public function getWaverId()
    {
        return $this->waverId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Waivers
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