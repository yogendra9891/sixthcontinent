<?php

namespace StoreManager\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Transactionshop
 */
class Transactionshop
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $dataMovimento;

    /**
     * @var integer
     */
    private $dataJob;

    /**
     * @var integer
     */
    private $totDare;

    /**
     * @var integer
     */
    private $totQuota;


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
     * Set userId
     *
     * @param integer $userId
     * @return Transactionshop
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set dataMovimento
     *
     * @param integer $dataMovimento
     * @return Transactionshop
     */
    public function setDataMovimento($dataMovimento)
    {
        $this->dataMovimento = $dataMovimento;
    
        return $this;
    }

    /**
     * Get dataMovimento
     *
     * @return integer 
     */
    public function getDataMovimento()
    {
        return $this->dataMovimento;
    }

    /**
     * Set dataJob
     *
     * @param integer $dataJob
     * @return Transactionshop
     */
    public function setDataJob($dataJob)
    {
        $this->dataJob = $dataJob;
    
        return $this;
    }

    /**
     * Get dataJob
     *
     * @return integer 
     */
    public function getDataJob()
    {
        return $this->dataJob;
    }

    /**
     * Set totDare
     *
     * @param integer $totDare
     * @return Transactionshop
     */
    public function setTotDare($totDare)
    {
        $this->totDare = $totDare;
    
        return $this;
    }

    /**
     * Get totDare
     *
     * @return integer 
     */
    public function getTotDare()
    {
        return $this->totDare;
    }

    /**
     * Set totQuota
     *
     * @param integer $totQuota
     * @return Transactionshop
     */
    public function setTotQuota($totQuota)
    {
        $this->totQuota = $totQuota;
    
        return $this;
    }

    /**
     * Get totQuota
     *
     * @return integer 
     */
    public function getTotQuota()
    {
        return $this->totQuota;
    }
    /**
     * @var integer
     */
    private $paymentStatus = 0;


    /**
     * Set paymentStatus
     *
     * @param integer $paymentStatus
     * @return Transactionshop
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;
    
        return $this;
    }

    /**
     * Get paymentStatus
     *
     * @return integer 
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }
}