<?php

namespace Transaction\TransactionSystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TransactionType
 */
class TransactionType
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $transactionLabel;

    /**
     * @var string
     */
    private $description;


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
     * Set transactionLabel
     *
     * @param string $transactionLabel
     * @return TransactionType
     */
    public function setTransactionLabel($transactionLabel)
    {
        $this->transactionLabel = $transactionLabel;
    
        return $this;
    }

    /**
     * Get transactionLabel
     *
     * @return string 
     */
    public function getTransactionLabel()
    {
        return $this->transactionLabel;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return TransactionType
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }
}