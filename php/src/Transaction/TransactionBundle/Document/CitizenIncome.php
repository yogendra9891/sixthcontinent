<?php

namespace Transaction\TransactionBundle\Document;



/**
 * Transaction\TransactionBundle\Document\CitizenIncome
 */
class CitizenIncome
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $user_obj
     */
    protected $user_obj;

    /**
     * @var int $percentage
     */
    protected $percentage;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var string $shop_obj
     */
    protected $shop_obj;

    /**
     * @var float $amount
     */
    protected $amount;

    /**
     * @var string $transaction_obj
     */
    protected $transaction_obj;

    /**
     * @var string $payer_obj
     */
    protected $payer_obj;

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
     * Set userObj
     *
     * @param string $userObj
     * @return self
     */
    public function setUserObj($userObj)
    {
        $this->user_obj = $userObj;
        return $this;
    }

    /**
     * Get userObj
     *
     * @return string $userObj
     */
    public function getUserObj()
    {
        return $this->user_obj;
    }

    /**
     * Set percentage
     *
     * @param int $percentage
     * @return self
     */
    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;
        return $this;
    }

    /**
     * Get percentage
     *
     * @return int $percentage
     */
    public function getPercentage()
    {
        return $this->percentage;
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
     * Set amount
     *
     * @param float $amount
     * @return self
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Get amount
     *
     * @return float $amount
     */
    public function getAmount()
    {
        return $this->amount;
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
     * Set payerObj
     *
     * @param string $payerObj
     * @return self
     */
    public function setPayerObj($payerObj)
    {
        $this->payer_obj = $payerObj;
        return $this;
    }

    /**
     * Get payerObj
     *
     * @return string $payerObj
     */
    public function getPayerObj()
    {
        return $this->payer_obj;
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
}
