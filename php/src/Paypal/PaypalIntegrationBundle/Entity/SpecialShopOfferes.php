<?php

namespace Paypal\PaypalIntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SpecialShopOfferes
 */
class SpecialShopOfferes
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var string
     */
    private $paypalPayment;


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
     * Set shopId
     *
     * @param integer $shopId
     * @return SpecialShopOfferes
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
     * Set status
     *
     * @param integer $status
     * @return SpecialShopOfferes
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
     * Set paypalPayment
     *
     * @param string $paypalPayment
     * @return SpecialShopOfferes
     */
    public function setPaypalPayment($paypalPayment)
    {
        $this->paypalPayment = $paypalPayment;
    
        return $this;
    }

    /**
     * Get paypalPayment
     *
     * @return string 
     */
    public function getPaypalPayment()
    {
        return $this->paypalPayment;
    }
}