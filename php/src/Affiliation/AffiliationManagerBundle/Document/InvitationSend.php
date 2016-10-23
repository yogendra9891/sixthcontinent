<?php

namespace Affiliation\AffiliationManagerBundle\Document;



/**
 * Affiliation\AffiliationManagerBundle\Document\InvitationSend
 */
class InvitationSend
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var int $from_id
     */
    protected $from_id;

    /**
     * @var string $email
     */
    protected $email;

    /**
     * @var int $status
     */
    protected $status;

    /**
     * @var int $count
     */
    protected $count;

    /**
     * @var date $created_at
     */
    protected $created_at;

    /**
     * @var date $updated_at
     */
    protected $updated_at;


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
     * Set fromId
     *
     * @param int $fromId
     * @return self
     */
    public function setFromId($fromId)
    {
        $this->from_id = $fromId;
        return $this;
    }

    /**
     * Get fromId
     *
     * @return int $fromId
     */
    public function getFromId()
    {
        return $this->from_id;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

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
     * Set count
     *
     * @param int $count
     * @return self
     */
    public function setCount($count)
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Get count
     *
     * @return int $count
     */
    public function getCount()
    {
        return $this->count;
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
    /**
     * @var int $affiliation_type
     */
    protected $affiliation_type;


    /**
     * Set affiliationType
     *
     * @param int $affiliationType
     * @return self
     */
    public function setAffiliationType($affiliationType)
    {
        $this->affiliation_type = $affiliationType;
        return $this;
    }

    /**
     * Get affiliationType
     *
     * @return int $affiliationType
     */
    public function getAffiliationType()
    {
        return $this->affiliation_type;
    }
}
