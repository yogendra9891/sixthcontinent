<?php

namespace Transaction\CitizenIncomeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CiFromProfPersFriendsFollowerHasWalletCitizen
 */
class CiFromProfPersFriendsFollowerHasWalletCitizen
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $CiFromProfPersFriendsFollowerId;

    /**
     * @var integer
     */
    private $walletCitizenId;

    /**
     * @var integer
     */
    private $amountReceivedWalletCurrency;

    /**
     * @var integer
     */
    private $countConnectionProfPersFollowers;


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
     * Set CiFromProfPersFriendsFollowerId
     *
     * @param integer $ciFromProfPersFriendsFollowerId
     * @return CiFromProfPersFriendsFollowerHasWalletCitizen
     */
    public function setCiFromProfPersFriendsFollowerId($ciFromProfPersFriendsFollowerId)
    {
        $this->CiFromProfPersFriendsFollowerId = $ciFromProfPersFriendsFollowerId;
    
        return $this;
    }

    /**
     * Get CiFromProfPersFriendsFollowerId
     *
     * @return integer 
     */
    public function getCiFromProfPersFriendsFollowerId()
    {
        return $this->CiFromProfPersFriendsFollowerId;
    }

    /**
     * Set walletCitizenId
     *
     * @param integer $walletCitizenId
     * @return CiFromProfPersFriendsFollowerHasWalletCitizen
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
     * Set amountReceivedWalletCurrency
     *
     * @param integer $amountReceivedWalletCurrency
     * @return CiFromProfPersFriendsFollowerHasWalletCitizen
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

    /**
     * Set countConnectionProfPersFollowers
     *
     * @param integer $countConnectionProfPersFollowers
     * @return CiFromProfPersFriendsFollowerHasWalletCitizen
     */
    public function setCountConnectionProfPersFollowers($countConnectionProfPersFollowers)
    {
        $this->countConnectionProfPersFollowers = $countConnectionProfPersFollowers;
    
        return $this;
    }

    /**
     * Get countConnectionProfPersFollowers
     *
     * @return integer 
     */
    public function getCountConnectionProfPersFollowers()
    {
        return $this->countConnectionProfPersFollowers;
    }
}
