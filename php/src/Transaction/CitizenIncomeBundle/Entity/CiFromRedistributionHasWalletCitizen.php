<?php

namespace Transaction\CitizenIncomeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CiFromRedistributionHasWalletCitizen
 */
class CiFromRedistributionHasWalletCitizen
{
    /**
     * @var integer
     */
    private $CiFromRedistributionId;

    /**
     * @var integer
     */
    private $walletCitizenId;

    /**
     * @var integer
     */
    private $ciRemoved;

    /**
     * @var integer
     */
    private $amountRemovedWalletCurrency;

    /**
     * @var integer
     */
    private $amountReceivedWalletCurrency;


    /**
     * Get CiFromRedistributionId
     *
     * @return integer 
     */
    public function getCiFromRedistributionId()
    {
        return $this->CiFromRedistributionId;
    }

    /**
     * Set walletCitizenId
     *
     * @param integer $walletCitizenId
     * @return CiFromRedistributionHasWalletCitizen
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
     * Set ciRemoved
     *
     * @param integer $ciRemoved
     * @return CiFromRedistributionHasWalletCitizen
     */
    public function setCiRemoved($ciRemoved)
    {
        $this->ciRemoved = $ciRemoved;
    
        return $this;
    }

    /**
     * Get ciRemoved
     *
     * @return integer 
     */
    public function getCiRemoved()
    {
        return $this->ciRemoved;
    }

    /**
     * Set amountRemovedWalletCurrency
     *
     * @param integer $amountRemovedWalletCurrency
     * @return CiFromRedistributionHasWalletCitizen
     */
    public function setAmountRemovedWalletCurrency($amountRemovedWalletCurrency)
    {
        $this->amountRemovedWalletCurrency = $amountRemovedWalletCurrency;
    
        return $this;
    }

    /**
     * Get amountRemovedWalletCurrency
     *
     * @return integer 
     */
    public function getAmountRemovedWalletCurrency()
    {
        return $this->amountRemovedWalletCurrency;
    }

    /**
     * Set amountReceivedWalletCurrency
     *
     * @param integer $amountReceivedWalletCurrency
     * @return CiFromRedistributionHasWalletCitizen
     */
    public function setAmountReceivedWalletCurrency($amountReceivedWalletCurrency)
    {
        $this->amountReceivedWalletCurrency = $amountReceivedWalletCurrency;
    
        return $this;
    }

    /**
     * Get amountReceivedWalletCurrency
     *
     * @return integer 
     */
    public function getAmountReceivedWalletCurrency()
    {
        return $this->amountReceivedWalletCurrency;
    }
}
