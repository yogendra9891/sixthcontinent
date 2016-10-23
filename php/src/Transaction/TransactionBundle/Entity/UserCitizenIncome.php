<?php

namespace Transaction\TransactionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserCitizenIncome
 */
class UserCitizenIncome
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $citizenIncomeId;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $citizenIncomeAmount;

    /**
     * @var integer
     */
    private $date;

    /**
     * @var integer
     */
    private $dataJob;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Set citizenIncomeId
     *
     * @param integer $citizenIncomeId
     * @return UserCitizenIncome
     */
    public function setCitizenIncomeId($citizenIncomeId)
    {
        $this->citizenIncomeId = $citizenIncomeId;
    
        return $this;
    }

    /**
     * Get citizenIncomeId
     *
     * @return integer 
     */
    public function getCitizenIncomeId()
    {
        return $this->citizenIncomeId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return UserCitizenIncome
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
     * Set citizenIncomeAmount
     *
     * @param integer $citizenIncomeAmount
     * @return UserCitizenIncome
     */
    public function setCitizenIncomeAmount($citizenIncomeAmount)
    {
        $this->citizenIncomeAmount = $citizenIncomeAmount;
    
        return $this;
    }

    /**
     * Get citizenIncomeAmount
     *
     * @return integer 
     */
    public function getCitizenIncomeAmount()
    {
        return $this->citizenIncomeAmount;
    }

    /**
     * Set date
     *
     * @param integer $date
     * @return UserCitizenIncome
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return integer 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set dataJob
     *
     * @param integer $dataJob
     * @return UserCitizenIncome
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
}
