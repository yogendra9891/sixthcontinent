<?php

namespace Transaction\TransactionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TotalEconomyShifted
 */
class TotalEconomyShifted
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $economyShifted;

    /**
     * @var \DateTime
     */
    private $updatedAt;


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
     * Set economyShifted
     *
     * @param integer $economyShifted
     * @return TotalEconomyShifted
     */
    public function setEconomyShifted($economyShifted)
    {
        $this->economyShifted = $economyShifted;
    
        return $this;
    }

    /**
     * Get economyShifted
     *
     * @return integer 
     */
    public function getEconomyShifted()
    {
        return $this->economyShifted;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return TotalEconomyShifted
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}