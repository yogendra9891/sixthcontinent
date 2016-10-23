<?php

namespace Transaction\TransactionBundle\Document;



/**
 * Transaction\TransactionBundle\Document\RecurringPaymentLog
 */
class RecurringPaymentLog
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var string $shop_obj
     */
    protected $shop_obj;

    /**
     * @var string $transaction_obj
     */
    protected $transaction_obj;

    /**
     * @var date $create_at
     */
    protected $create_at;


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
     * Set type
     *
     * @param string $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set shopObj
     *
     * @param string $shopObj
     * @return self
     */
    public function setShopObj($shopObj)
    {
        $this->shop_obj = $shopObj;
        return $this;
    }

    /**
     * Get shopObj
     *
     * @return string $shopObj
     */
    public function getShopObj()
    {
        return $this->shop_obj;
    }

    /**
     * Set transactionObj
     *
     * @param string $transactionObj
     * @return self
     */
    public function setTransactionObj($transactionObj)
    {
        $this->transaction_obj = $transactionObj;
        return $this;
    }

    /**
     * Get transactionObj
     *
     * @return string $transactionObj
     */
    public function getTransactionObj()
    {
        return $this->transaction_obj;
    }

    /**
     * Set createAt
     *
     * @param date $createAt
     * @return self
     */
    public function setCreateAt($createAt)
    {
        $this->create_at = $createAt;
        return $this;
    }

    /**
     * Get createAt
     *
     * @return date $createAt
     */
    public function getCreateAt()
    {
        return $this->create_at;
    }
    /**
     * @var string $description
     */
    protected $description;


    /**
     * Set description
     *
     * @param string $description
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * @var int $shop_id
     */
    protected $shop_id;


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
     * @var int $status
     */
    protected $status;


    /**
     * Set status
     *
     * @param int $status
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get status
     *
     * @return int $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @var int $sent
     */
    protected $sent;


    /**
     * Set sent
     *
     * @param int $sent
     * @return self
     */
    public function setSent($sent)
    {
        $this->sent = $sent;
        return $this;
    }

    /**
     * Get sent
     *
     * @return int $sent
     */
    public function getSent()
    {
        return $this->sent;
    }
    /**
     * @var date $updated_at
     */
    protected $updated_at;


    /**
     * Set updatedAt
     *
     * @param date $updatedAt
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return date $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
}
