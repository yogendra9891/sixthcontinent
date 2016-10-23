<?php

namespace CardManagement\CardManagementBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WaiverOptions
 */
class WaiverOptions
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var \DateTime
     */
    private $endDate;

    /**
     * @var string
     */
    private $waiverType;

    /**
     * @var string
     */
    private $reason;

    /**
     * @var string
     */
    private $description;

    /**
     * @var integer
     */
    private $status;

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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return WaiverOptions
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    
        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return WaiverOptions
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    
        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set waiverType
     *
     * @param string $waiverType
     * @return WaiverOptions
     */
    public function setWaiverType($waiverType)
    {
        $this->waiverType = $waiverType;
    
        return $this;
    }

    /**
     * Get waiverType
     *
     * @return string 
     */
    public function getWaiverType()
    {
        return $this->waiverType;
    }

    /**
     * Set reason
     *
     * @param string $reason
     * @return WaiverOptions
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    
        return $this;
    }

    /**
     * Get reason
     *
     * @return string 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return WaiverOptions
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
     * Set status
     *
     * @param integer $status
     * @return WaiverOptions
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return WaiverOptions
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
     * @var string
     */
    private $itemIds = '';

    /**
     * @var string
     */
    private $Options = '';

    /**
     * @var string
     */
    private $itemType = '';


    /**
     * Set itemIds
     *
     * @param string $itemIds
     * @return WaiverOptions
     */
    public function setItemIds($itemIds)
    {
        $this->itemIds = $itemIds;
    
        return $this;
    }

    /**
     * Get itemIds
     *
     * @return string 
     */
    public function getItemIds()
    {
        return $this->itemIds;
    }

    /**
     * Set Options
     *
     * @param string $options
     * @return WaiverOptions
     */
    public function setOptions($options)
    {
        $this->Options = $options;
    
        return $this;
    }

    /**
     * Get Options
     *
     * @return string 
     */
    public function getOptions()
    {
        return $this->Options;
    }

    /**
     * Set itemType
     *
     * @param string $itemType
     * @return WaiverOptions
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
}