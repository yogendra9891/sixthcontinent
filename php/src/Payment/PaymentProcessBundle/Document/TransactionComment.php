<?php

namespace Payment\PaymentProcessBundle\Document;



/**
 * Payment\PaymentProcessBundle\Document\TransactionComment
 */
class TransactionComment
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var int $user_id
     */
    protected $user_id;

    /**
     * @var int $shop_id
     */
    protected $shop_id;

    /**
     * @var string $transaction_id
     */
    protected $transaction_id;

    /**
     * @var int $rating
     */
    protected $rating;

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
     * Set userId
     *
     * @param int $userId
     * @return self
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;
        return $this;
    }

    /**
     * Get userId
     *
     * @return int $userId
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set shopId
     *
     * @param int $shopId
     * @return self
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;
        return $this;
    }

    /**
     * Get shopId
     *
     * @return int $shopId
     */
    public function getShopId()
    {
        return $this->shop_id;
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
     * Set rating
     *
     * @param int $rating
     * @return self
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
        return $this;
    }

    /**
     * Get rating
     *
     * @return int $rating
     */
    public function getRating()
    {
        return $this->rating;
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
    /**
     * @var string $comment
     */
    protected $comment;


    /**
     * Set comment
     *
     * @param string $comment
     * @return self
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Get comment
     *
     * @return string $comment
     */
    public function getComment()
    {
        return $this->comment;
    }
    /**
     * @var string $invoice_id
     */
    protected $invoice_id='';


    /**
     * Set invoiceId
     *
     * @param string $invoiceId
     * @return self
     */
    public function setInvoiceId($invoiceId)
    {
        $this->invoice_id = $invoiceId;
        return $this;
    }

    /**
     * Get invoiceId
     *
     * @return string $invoiceId
     */
    public function getInvoiceId()
    {
        return $this->invoice_id;
    }
}
