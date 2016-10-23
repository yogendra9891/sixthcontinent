<?php

namespace Transaction\CitizenIncomeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CiFromRedistribution
 */
class CiFromRedistribution
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $timeInitTransaction;

    /**
     * @var integer
     */
    private $timeEndTransaction;

    /**
     * @var \DateTime
     */
    private $timeInitTransactionH;

    /**
     * @var \DateTime
     */
    private $timeEndTransactionH;

    /**
     * @var integer
     */
    private $timeRedistribution;

    /**
     * @var \DateTime
     */
    private $timeRedistributionH;

    /**
     * @var integer
     */
    private $totalUser;

    /**
     * @var integer
     */
    private $totalAmountBaseCurrency;

    /**
     * @var integer
     */
    private $amountNotRedistributedBaseCurrency;

    /**
     * @var float
     */
    private $percentageToRedistribute;

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
     * Set timeInitTransaction
     *
     * @param integer $timeInitTransaction
     * @return CiFromRedistribution
     */
    public function setTimeInitTransaction($timeInitTransaction)
    {
        $this->timeInitTransaction = $timeInitTransaction;
    
        return $this;
    }

    /**
     * Get timeInitTransaction
     *
     * @return integer 
     */
    public function getTimeInitTransaction()
    {
        return $this->timeInitTransaction;
    }

    /**
     * Set timeEndTransaction
     *
     * @param integer $timeEndTransaction
     * @return CiFromRedistribution
     */
    public function setTimeEndTransaction($timeEndTransaction)
    {
        $this->timeEndTransaction = $timeEndTransaction;
    
        return $this;
    }

    /**
     * Get timeEndTransaction
     *
     * @return integer 
     */
    public function getTimeEndTransaction()
    {
        return $this->timeEndTransaction;
    }

    /**
     * Set timeInitTransactionH
     *
     * @param \DateTime $timeInitTransactionH
     * @return CiFromRedistribution
     */
    public function setTimeInitTransactionH($timeInitTransactionH)
    {
        $this->timeInitTransactionH = $timeInitTransactionH;
    
        return $this;
    }

    /**
     * Get timeInitTransactionH
     *
     * @return \DateTime 
     */
    public function getTimeInitTransactionH()
    {
        return $this->timeInitTransactionH;
    }

    /**
     * Set timeEndTransactionH
     *
     * @param \DateTime $timeEndTransactionH
     * @return CiFromRedistribution
     */
    public function setTimeEndTransactionH($timeEndTransactionH)
    {
        $this->timeEndTransactionH = $timeEndTransactionH;
    
        return $this;
    }

    /**
     * Get timeEndTransactionH
     *
     * @return \DateTime 
     */
    public function getTimeEndTransactionH()
    {
        return $this->timeEndTransactionH;
    }

    /**
     * Set timeRedistribution
     *
     * @param integer $timeRedistribution
     * @return CiFromRedistribution
     */
    public function setTimeRedistribution($timeRedistribution)
    {
        $this->timeRedistribution = $timeRedistribution;
    
        return $this;
    }

    /**
     * Get timeRedistribution
     *
     * @return integer 
     */
    public function getTimeRedistribution()
    {
        return $this->timeRedistribution;
    }

    /**
     * Set timeRedistributionH
     *
     * @param \DateTime $timeRedistributionH
     * @return CiFromRedistribution
     */
    public function setTimeRedistributionH($timeRedistributionH)
    {
        $this->timeRedistributionH = $timeRedistributionH;
    
        return $this;
    }

    /**
     * Get timeRedistributionH
     *
     * @return \DateTime 
     */
    public function getTimeRedistributionH()
    {
        return $this->timeRedistributionH;
    }

    /**
     * Set totalUser
     *
     * @param integer $totalUser
     * @return CiFromRedistribution
     */
    public function setTotalUser($totalUser)
    {
        $this->totalUser = $totalUser;
    
        return $this;
    }

    /**
     * Get totalUser
     *
     * @return integer 
     */
    public function getTotalUser()
    {
        return $this->totalUser;
    }

    /**
     * Set totalAmountBaseCurrency
     *
     * @param integer $totalAmountBaseCurrency
     * @return CiFromRedistribution
     */
    public function setTotalAmountBaseCurrency($totalAmountBaseCurrency)
    {
        $this->totalAmountBaseCurrency = $totalAmountBaseCurrency;
    
        return $this;
    }

    /**
     * Get totalAmountBaseCurrency
     *
     * @return integer 
     */
    public function getTotalAmountBaseCurrency()
    {
        return $this->totalAmountBaseCurrency;
    }

    /**
     * Set amountNotRedistributedBaseCurrency
     *
     * @param integer $amountNotRedistributedBaseCurrency
     * @return CiFromRedistribution
     */
    public function setAmountNotRedistributedBaseCurrency($amountNotRedistributedBaseCurrency)
    {
        $this->amountNotRedistributedBaseCurrency = $amountNotRedistributedBaseCurrency;
    
        return $this;
    }

    /**
     * Get amountNotRedistributedBaseCurrency
     *
     * @return integer 
     */
    public function getAmountNotRedistributedBaseCurrency()
    {
        return $this->amountNotRedistributedBaseCurrency;
    }

    /**
     * Set percentageToRedistribute
     *
     * @param float $percentageToRedistribute
     * @return CiFromRedistribution
     */
    public function setPercentageToRedistribute($percentageToRedistribute)
    {
        $this->percentageToRedistribute = $percentageToRedistribute;
    
        return $this;
    }

    /**
     * Get percentageToRedistribute
     *
     * @return float 
     */
    public function getPercentageToRedistribute()
    {
        return $this->percentageToRedistribute;
    }

    /**
     * Set singleShareBaseCurrency
     *
     * @param integer $singleShareBaseCurrency
     * @return CiFromRedistribution
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
     * @return CiFromRedistribution
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
     * @return CiFromRedistribution
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
     * @return CiFromRedistribution
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
     * @return CiFromRedistribution
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
     * @return CiFromRedistribution
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
     * @return CiFromRedistribution
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
     * @return CiFromRedistribution
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
     * @return CiFromRedistribution
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
