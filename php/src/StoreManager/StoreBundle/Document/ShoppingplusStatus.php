<?php

namespace StoreManager\StoreBundle\Document;



/**
 * StoreManager\StoreBundle\Document\ShoppingplusStatus
 */
class ShoppingplusStatus
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var int $register_id
     */
    protected $register_id;

    /**
     * @var string $status
     */
    protected $status;

    /**
     * @var int $entity_type
     */
    protected $entity_type;

    /**
     * @var date $created
     */
    protected $created;

    /**
     * @var string $error_code
     */
    protected $error_code;

    /**
     * @var string $error_desc
     */
    protected $error_desc;

    /**
     * @var string $step
     */
    protected $step;


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
     * Set registerId
     *
     * @param int $registerId
     * @return self
     */
    public function setRegisterId($registerId)
    {
        $this->register_id = $registerId;
        return $this;
    }

    /**
     * Get registerId
     *
     * @return int $registerId
     */
    public function getRegisterId()
    {
        return $this->register_id;
    }

    /**
     * Set status
     *
     * @param string $status
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
     * @return string $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set entityType
     *
     * @param int $entityType
     * @return self
     */
    public function setEntityType($entityType)
    {
        $this->entity_type = $entityType;
        return $this;
    }

    /**
     * Get entityType
     *
     * @return int $entityType
     */
    public function getEntityType()
    {
        return $this->entity_type;
    }

    /**
     * Set created
     *
     * @param date $created
     * @return self
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * Get created
     *
     * @return date $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set errorCode
     *
     * @param string $errorCode
     * @return self
     */
    public function setErrorCode($errorCode)
    {
        $this->error_code = $errorCode;
        return $this;
    }

    /**
     * Get errorCode
     *
     * @return string $errorCode
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * Set errorDesc
     *
     * @param string $errorDesc
     * @return self
     */
    public function setErrorDesc($errorDesc)
    {
        $this->error_desc = $errorDesc;
        return $this;
    }

    /**
     * Get errorDesc
     *
     * @return string $errorDesc
     */
    public function getErrorDesc()
    {
        return $this->error_desc;
    }

    /**
     * Set step
     *
     * @param string $step
     * @return self
     */
    public function setStep($step)
    {
        $this->step = $step;
        return $this;
    }

    /**
     * Get step
     *
     * @return string $step
     */
    public function getStep()
    {
        return $this->step;
    }
}
