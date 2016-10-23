<?php

namespace Acme\GiftBundle\Document;



/**
 * Acme\GiftBundle\Document\MovimenLogs
 */
class MovimenLogs
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $transaction_id
     */
    protected $transaction_id;

    /**
     * @var string $reason
     */
    protected $reason;

    /**
     * @var date $created_at
     */
    protected $created_at;


    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set transactionId
     *
     * @param string $transactionId
     * @return self
     */
    public function setTransactionId($transactionId)
    {
        $this->transaction_id = $transactionId;
        return $this;
    }

    /**
     * Get transactionId
     *
     * @return string $transactionId
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * Set reason
     *
     * @param string $reason
     * @return self
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
        return $this;
    }

    /**
     * Get reason
     *
     * @return string $reason
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set createdAt
     *
     * @param date $createdAt
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return date $createdAt
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }
}