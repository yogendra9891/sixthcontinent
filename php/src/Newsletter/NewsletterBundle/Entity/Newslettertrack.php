<?php

namespace Newsletter\NewsletterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Newslettertrack
 */
class Newslettertrack
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $senderId;

    /**
     * @var integer
     */
    private $recevierId;

    /**
     * @var integer
     */
    private $sentStatus;

    /**
     * @var integer
     */
    private $openStatus;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var integer
     */
    private $templateId;

    /**
     * @var string
     */
    private $token;


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
     * Set senderId
     *
     * @param integer $senderId
     * @return Newslettertrack
     */
    public function setSenderId($senderId)
    {
        $this->senderId = $senderId;
    
        return $this;
    }

    /**
     * Get senderId
     *
     * @return integer 
     */
    public function getSenderId()
    {
        return $this->senderId;
    }

    /**
     * Set recevierId
     *
     * @param integer $recevierId
     * @return Newslettertrack
     */
    public function setRecevierId($recevierId)
    {
        $this->recevierId = $recevierId;
    
        return $this;
    }

    /**
     * Get recevierId
     *
     * @return integer 
     */
    public function getRecevierId()
    {
        return $this->recevierId;
    }

    /**
     * Set sentStatus
     *
     * @param integer $sentStatus
     * @return Newslettertrack
     */
    public function setSentStatus($sentStatus)
    {
        $this->sentStatus = $sentStatus;
    
        return $this;
    }

    /**
     * Get sentStatus
     *
     * @return integer 
     */
    public function getSentStatus()
    {
        return $this->sentStatus;
    }

    /**
     * Set openStatus
     *
     * @param integer $openStatus
     * @return Newslettertrack
     */
    public function setOpenStatus($openStatus)
    {
        $this->openStatus = $openStatus;
    
        return $this;
    }

    /**
     * Get openStatus
     *
     * @return integer 
     */
    public function getOpenStatus()
    {
        return $this->openStatus;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Newslettertrack
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set templateId
     *
     * @param integer $templateId
     * @return Newslettertrack
     */
    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;
    
        return $this;
    }

    /**
     * Get templateId
     *
     * @return integer 
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return Newslettertrack
     */
    public function setToken($token)
    {
        $this->token = $token;
    
        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }
}
