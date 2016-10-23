<?php

namespace Message\MessageBundle\Document;



/**
 * Message\MessageBundle\Document\Thread
 */
class Thread
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $subject
     */
    protected $subject;

    /**
     * @var date $createdAt
     */
    protected $createdAt;

    /**
     * @var int $isSpam
     */
    protected $isSpam;

    /**
     * @var int $createdBy_id
     */
    protected $createdBy_id;


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
     * Set subject
     *
     * @param string $subject
     * @return self
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Get subject
     *
     * @return string $subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set createdAt
     *
     * @param date $createdAt
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return date $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set isSpam
     *
     * @param int $isSpam
     * @return self
     */
    public function setIsSpam($isSpam)
    {
        $this->isSpam = $isSpam;
        return $this;
    }

    /**
     * Get isSpam
     *
     * @return int $isSpam
     */
    public function getIsSpam()
    {
        return $this->isSpam;
    }

    /**
     * Set createdById
     *
     * @param int $createdById
     * @return self
     */
    public function setCreatedById($createdById)
    {
        $this->createdBy_id = $createdById;
        return $this;
    }

    /**
     * Get createdById
     *
     * @return int $createdById
     */
    public function getCreatedById()
    {
        return $this->createdBy_id;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
}
