<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Relatives
 */
class Relatives
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var integer
     */
    private $relativeId;

    /**
     * @var integer
     */
    private $relationId;


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
     * Set userId
     *
     * @param integer $userId
     * @return Relatives
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set relativeId
     *
     * @param integer $relativeId
     * @return Relatives
     */
    public function setRelativeId($relativeId)
    {
        $this->relativeId = $relativeId;
    
        return $this;
    }

    /**
     * Get relativeId
     *
     * @return integer 
     */
    public function getRelativeId()
    {
        return $this->relativeId;
    }

    /**
     * Set relationId
     *
     * @param string $relationId
     * @return Relatives
     */
    public function setRelationId($relationId)
    {
        $this->relationId = $relationId;
    
        return $this;
    }

    /**
     * Get relationId
     *
     * @return integer 
     */
    public function getRelationId()
    {
        return $this->relationId;
    }
}
