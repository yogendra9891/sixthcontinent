<?php

namespace ExportManagement\ExportManagementBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sales
 */
class Sales
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $progress;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $causale;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $description2;

    /**
     * @var integer
     */
    private $amount;

    /**
     * @var integer
     */
    private $amountvat;

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
     * Set progress
     *
     * @param string $progress
     * @return Sales
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;
    
        return $this;
    }

    /**
     * Get progress
     *
     * @return string 
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Sales
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set causale
     *
     * @param string $causale
     * @return Sales
     */
    public function setCausale($causale)
    {
        $this->causale = $causale;
    
        return $this;
    }

    /**
     * Get causale
     *
     * @return string 
     */
    public function getCausale()
    {
        return $this->causale;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     * @return Sales
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
    
        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer 
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Sales
     */
    public function setCode($code)
    {
        $this->code = $code;
    
        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Sales
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
     * Set description2
     *
     * @param string $description2
     * @return Sales
     */
    public function setDescription2($description2)
    {
        $this->description2 = $description2;
    
        return $this;
    }

    /**
     * Get description2
     *
     * @return string 
     */
    public function getDescription2()
    {
        return $this->description2;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     * @return Sales
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    
        return $this;
    }

    /**
     * Get amount
     *
     * @return integer 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set amountvat
     *
     * @param integer $amountvat
     * @return Sales
     */
    public function setAmountvat($amountvat)
    {
        $this->amountvat = $amountvat;
    
        return $this;
    }

    /**
     * Get amountvat
     *
     * @return integer 
     */
    public function getAmountvat()
    {
        return $this->amountvat;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Sales
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