<?php

namespace Transaction\CitizenIncomeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CiFromCashBack
 */
class CiFromCashBack
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $walletCitizenId;

    /**
     * @var integer
     */
    private $timeCreated;

    /**
     * @var \DateTime
     */
    private $timeCreatedH;

    /**
     * @var string
     */
    private $sixcTransactionId;

    /**
     * @var integer
     */
    private $singleShareBaseCurrency;

    /**
     * @var integer
     */
    private $singleShareUsd;

    /**
     * @var integer
     */
    private $singleShareEur;

    /**
     * @var integer
     */
    private $singleShareInr;

    /**
     * @var integer
     */
    private $singleShareChf;

    /**
     * @var integer
     */
    private $singleShareSek;

    /**
     * @var integer
     */
    private $singleShareDkk;

    /**
     * @var integer
     */
    private $singleShareGbp;

    /**
     * @var integer
     */
    private $singleShareYen;


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
     * Set walletCitizenId
     *
     * @param integer $walletCitizenId
     * @return CiFromCashBack
     */
    public function setWalletCitizenId($walletCitizenId)
    {
        $this->walletCitizenId = $walletCitizenId;
    
        return $this;
    }

    /**
     * Get walletCitizenId
     *
     * @return integer 
     */
    public function getWalletCitizenId()
    {
        return $this->walletCitizenId;
    }

    /**
     * Set timeCreated
     *
     * @param integer $timeCreated
     * @return CiFromCashBack
     */
    public function setTimeCreated($timeCreated)
    {
        $this->timeCreated = $timeCreated;
    
        return $this;
    }

    /**
     * Get timeCreated
     *
     * @return integer 
     */
    public function getTimeCreated()
    {
        return $this->timeCreated;
    }

    /**
     * Set timeCreatedH
     *
     * @param \DateTime $timeCreatedH
     * @return CiFromCashBack
     */
    public function setTimeCreatedH($timeCreatedH)
    {
        $this->timeCreatedH = $timeCreatedH;
    
        return $this;
    }

    /**
     * Get timeCreatedH
     *
     * @return \DateTime 
     */
    public function getTimeCreatedH()
    {
        return $this->timeCreatedH;
    }

    /**
     * Set sixcTransactionId
     *
     * @param string $sixcTransactionId
     * @return CiFromCashBack
     */
    public function setSixcTransactionId($sixcTransactionId)
    {
        $this->sixcTransactionId = $sixcTransactionId;
    
        return $this;
    }

    /**
     * Get sixcTransactionId
     *
     * @return string 
     */
    public function getSixcTransactionId()
    {
        return $this->sixcTransactionId;
    }

    /**
     * Set singleShareBaseCurrency
     *
     * @param integer $singleShareBaseCurrency
     * @return CiFromCashBack
     */
    public function setSingleShareBaseCurrency($singleShareBaseCurrency)
    {
        $this->singleShareBaseCurrency = $singleShareBaseCurrency;
    
        return $this;
    }

    /**
     * Get singleShareBaseCurrency
     *
     * @return integer 
     */
    public function getSingleShareBaseCurrency()
    {
        return $this->singleShareBaseCurrency;
    }

    /**
     * Set singleShareUsd
     *
     * @param integer $singleShareUsd
     * @return CiFromCashBack
     */
    public function setSingleShareUsd($singleShareUsd)
    {
        $this->singleShareUsd = $singleShareUsd;
    
        return $this;
    }

    /**
     * Get singleShareUsd
     *
     * @return integer 
     */
    public function getSingleShareUsd()
    {
        return $this->singleShareUsd;
    }

    /**
     * Set singleShareEur
     *
     * @param integer $singleShareEur
     * @return CiFromCashBack
     */
    public function setSingleShareEur($singleShareEur)
    {
        $this->singleShareEur = $singleShareEur;
    
        return $this;
    }

    /**
     * Get singleShareEur
     *
     * @return integer 
     */
    public function getSingleShareEur()
    {
        return $this->singleShareEur;
    }

    /**
     * Set singleShareInr
     *
     * @param integer $singleShareInr
     * @return CiFromCashBack
     */
    public function setSingleShareInr($singleShareInr)
    {
        $this->singleShareInr = $singleShareInr;
    
        return $this;
    }

    /**
     * Get singleShareInr
     *
     * @return integer 
     */
    public function getSingleShareInr()
    {
        return $this->singleShareInr;
    }

    /**
     * Set singleShareChf
     *
     * @param integer $singleShareChf
     * @return CiFromCashBack
     */
    public function setSingleShareChf($singleShareChf)
    {
        $this->singleShareChf = $singleShareChf;
    
        return $this;
    }

    /**
     * Get singleShareChf
     *
     * @return integer 
     */
    public function getSingleShareChf()
    {
        return $this->singleShareChf;
    }

    /**
     * Set singleShareSek
     *
     * @param integer $singleShareSek
     * @return CiFromCashBack
     */
    public function setSingleShareSek($singleShareSek)
    {
        $this->singleShareSek = $singleShareSek;
    
        return $this;
    }

    /**
     * Get singleShareSek
     *
     * @return integer 
     */
    public function getSingleShareSek()
    {
        return $this->singleShareSek;
    }

    /**
     * Set singleShareDkk
     *
     * @param integer $singleShareDkk
     * @return CiFromCashBack
     */
    public function setSingleShareDkk($singleShareDkk)
    {
        $this->singleShareDkk = $singleShareDkk;
    
        return $this;
    }

    /**
     * Get singleShareDkk
     *
     * @return integer 
     */
    public function getSingleShareDkk()
    {
        return $this->singleShareDkk;
    }

    /**
     * Set singleShareGbp
     *
     * @param integer $singleShareGbp
     * @return CiFromCashBack
     */
    public function setSingleShareGbp($singleShareGbp)
    {
        $this->singleShareGbp = $singleShareGbp;
    
        return $this;
    }

    /**
     * Get singleShareGbp
     *
     * @return integer 
     */
    public function getSingleShareGbp()
    {
        return $this->singleShareGbp;
    }

    /**
     * Set singleShareYen
     *
     * @param integer $singleShareYen
     * @return CiFromCashBack
     */
    public function setSingleShareYen($singleShareYen)
    {
        $this->singleShareYen = $singleShareYen;
    
        return $this;
    }

    /**
     * Get singleShareYen
     *
     * @return integer 
     */
    public function getSingleShareYen()
    {
        return $this->singleShareYen;
    }
}
