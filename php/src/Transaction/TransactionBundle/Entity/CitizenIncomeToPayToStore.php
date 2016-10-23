<?php

namespace Transaction\TransactionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CitizenIncomeToPayToStore
 */
class CitizenIncomeToPayToStore
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
    private $totAvere;


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
     * @return CitizenIncomeToPayToStore
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
     * @return CitizenIncomeToPayToStore
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
     * @return CitizenIncomeToPayToStore
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
     * Set totAvere
     *
     * @param integer $totAvere
     * @return CitizenIncomeToPayToStore
     */
    public function setTotAvere($totAvere)
    {
        $this->totAvere = $totAvere;
    
        return $this;
    }

    /**
     * Get totAvere
     *
     * @return integer 
     */
    public function getTotAvere()
    {
        return $this->totAvere;
    }
}