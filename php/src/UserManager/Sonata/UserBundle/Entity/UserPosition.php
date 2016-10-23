<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserPosition
 */
class UserPosition
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
    private $count;

    /**
     * @var integer
     */
    private $district;

    /**
     * @var integer
     */
    private $circle;

    /**
     * @var integer
     */
    private $position;


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
     * @return UserPosition
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
     * Set count
     *
     * @param integer $count
     * @return UserPosition
     */
    public function setCount($count)
    {
        $this->count = $count;
    
        return $this;
    }

    /**
     * Get count
     *
     * @return integer 
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set district
     *
     * @param integer $district
     * @return UserPosition
     */
    public function setDistrict($district)
    {
        $this->district = $district;
    
        return $this;
    }

    /**
     * Get district
     *
     * @return integer 
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * Set circle
     *
     * @param integer $circle
     * @return UserPosition
     */
    public function setCircle($circle)
    {
        $this->circle = $circle;
    
        return $this;
    }

    /**
     * Get circle
     *
     * @return integer 
     */
    public function getCircle()
    {
        return $this->circle;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return UserPosition
     */
    public function setPosition($position)
    {
        $this->position = $position;
    
        return $this;
    }

    /**
     * Get position
     *
     * @return integer 
     */
    public function getPosition()
    {
        return $this->position;
    }
}
