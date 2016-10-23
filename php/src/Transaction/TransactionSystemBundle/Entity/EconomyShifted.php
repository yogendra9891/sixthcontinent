<?php

namespace Transaction\TransactionSystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EconomyShifted
 */
class EconomyShifted
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $totalAmount;

    /**
     * @var \DateTime
     */
    private $timeCreatedH;

    /**
     * @var \DateTime
     */
    private $timeUpdatedH;

    /**
     * @var integer
     */
    private $timeCreated;

    /**
     * @var integer
     */
    private $timeUpdated;

    /**
     * @var integer
     */
    private $totalEconomyAmount;


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
     * Set totalAmount
     *
     * @param integer $totalAmount
     * @return EconomyShifted
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    
        return $this;
    }

    /**
     * Get totalAmount
     *
     * @return integer 
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Set timeCreatedH
     *
     * @param \DateTime $timeCreatedH
     * @return EconomyShifted
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
     * Set timeUpdatedH
     *
     * @param \DateTime $timeUpdatedH
     * @return EconomyShifted
     */
    public function setTimeUpdatedH($timeUpdatedH)
    {
        $this->timeUpdatedH = $timeUpdatedH;
    
        return $this;
    }

    /**
     * Get timeUpdatedH
     *
     * @return \DateTime 
     */
    public function getTimeUpdatedH()
    {
        return $this->timeUpdatedH;
    }

    /**
     * Set timeCreated
     *
     * @param integer $timeCreated
     * @return EconomyShifted
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
     * Set timeUpdated
     *
     * @param integer $timeUpdated
     * @return EconomyShifted
     */
    public function setTimeUpdated($timeUpdated)
    {
        $this->timeUpdated = $timeUpdated;
    
        return $this;
    }

    /**
     * Get timeUpdated
     *
     * @return integer 
     */
    public function getTimeUpdated()
    {
        return $this->timeUpdated;
    }

    /**
     * Set totalEconomyAmount
     *
     * @param integer $totalEconomyAmount
     * @return EconomyShifted
     */
    public function setTotalEconomyAmount($totalEconomyAmount)
    {
        $this->totalEconomyAmount = $totalEconomyAmount;
    
        return $this;
    }

    /**
     * Get totalEconomyAmount
     *
     * @return integer 
     */
    public function getTotalEconomyAmount()
    {
        return $this->totalEconomyAmount;
    }
}