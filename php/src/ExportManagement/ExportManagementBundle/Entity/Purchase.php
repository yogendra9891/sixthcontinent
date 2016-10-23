<?php

namespace ExportManagement\ExportManagementBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Purchase
 */
class Purchase
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $numeroQuietanza;

    /**
     * @var string
     */
    private $tipoQuietanza;

    /**
     * @var string
     */
    private $causale;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $description;

    /**
     * @var integer
     */
    private $amount;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var integer
     */
    private $citizenId;

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
     * Set date
     *
     * @param \DateTime $date
     * @return Purchase
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
     * Set numeroQuietanza
     *
     * @param string $numeroQuietanza
     * @return Purchase
     */
    public function setNumeroQuietanza($numeroQuietanza)
    {
        $this->numeroQuietanza = $numeroQuietanza;
    
        return $this;
    }

    /**
     * Get numeroQuietanza
     *
     * @return string 
     */
    public function getNumeroQuietanza()
    {
        return $this->numeroQuietanza;
    }

    /**
     * Set tipoQuietanza
     *
     * @param string $tipoQuietanza
     * @return Purchase
     */
    public function setTipoQuietanza($tipoQuietanza)
    {
        $this->tipoQuietanza = $tipoQuietanza;
    
        return $this;
    }

    /**
     * Get tipoQuietanza
     *
     * @return string 
     */
    public function getTipoQuietanza()
    {
        return $this->tipoQuietanza;
    }

    /**
     * Set causale
     *
     * @param string $causale
     * @return Purchase
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
     * Set code
     *
     * @param string $code
     * @return Purchase
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
     * @return Purchase
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
     * Set amount
     *
     * @param integer $amount
     * @return Purchase
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
     * Set shopId
     *
     * @param integer $shopId
     * @return Purchase
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
     * Set citizenId
     *
     * @param integer $citizenId
     * @return Purchase
     */
    public function setCitizenId($citizenId)
    {
        $this->citizenId = $citizenId;
    
        return $this;
    }

    /**
     * Get citizenId
     *
     * @return integer 
     */
    public function getCitizenId()
    {
        return $this->citizenId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Purchase
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