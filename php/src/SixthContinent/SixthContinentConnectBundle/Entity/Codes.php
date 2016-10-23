<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Codes
 */
class Codes
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var integer
     */
    private $typeId;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $status;

    /**
     * @var integer
     */
    private $isDeleted;


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
     * Set type
     *
     * @param string $type
     * @return Codes
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set typeId
     *
     * @param integer $typeId
     * @return Codes
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;
    
        return $this;
    }

    /**
     * Get typeId
     *
     * @return integer 
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Codes
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
     * Set status
     *
     * @param string $status
     * @return Codes
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set isDeleted
     *
     * @param integer $isDeleted
     * @return Codes
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
    
        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return integer 
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }
    /**
     * @var int
     */
    private $couponToActiveId;


    /**
     * Set couponToActiveId
     *
     * @param \int $couponToActiveId
     * @return Codes
     */
    public function setCouponToActiveId(\int $couponToActiveId)
    {
        $this->couponToActiveId = $couponToActiveId;
    
        return $this;
    }

    /**
     * Get couponToActiveId
     *
     * @return \int 
     */
    public function getCouponToActiveId()
    {
        return $this->couponToActiveId;
    }
}