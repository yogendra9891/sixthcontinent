<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserDeletedAssign
 */
class UserDeletedAssign
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
    private $assignId;
    
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
     * @return UserDeletedAssign
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
     * Set assignId
     *
     * @param integer $assignId
     * @return UserDeletedAssign
     */
    public function setAssignId($assignId)
    {
        $this->assignId = $assignId;
    
        return $this;
    }

    /**
     * Get assignId
     *
     * @return integer 
     */
    public function getAssignId()
    {
        return $this->assignId;
    }    
}
