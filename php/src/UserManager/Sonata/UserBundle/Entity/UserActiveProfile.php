<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserActiveProfile
 */
class UserActiveProfile
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
     * @var string
     */
    private $profileType;

    /**
     * @var integer
     */
    private $type;


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
     * @return UserActiveProfile
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
     * Set profileType
     *
     * @param string $profileType
     * @return UserActiveProfile
     */
    public function setProfileType($profileType)
    {
        $this->profileType = $profileType;
    
        return $this;
    }

    /**
     * Get profileType
     *
     * @return string 
     */
    public function getProfileType()
    {
        return $this->profileType;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return UserActiveProfile
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }
}
