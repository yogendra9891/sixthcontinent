<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BusinessCategory
 */
class BusinessCategory
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var integer
     */
    private $parent = 0;

    /**
     * @var integer
     */
    private $status = 0;
    
    /**
     * @var text
     */
    private $image = 0;
    
    /**
     * @var text
     */
    private $image_thumb = 0;

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
     * Set name
     *
     * @param string $name
     * @return BusinessCategory
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set parent
     *
     * @param integer $parent
     * @return BusinessCategory
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    
        return $this;
    }

    /**
     * Get parent
     *
     * @return integer 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return BusinessCategory
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * Set image
     *
     * @param text $image
     * @return BusinessCategory
     */
    public function setImage($image)
    {
        $this->image = $image;
    
        return $this;
    }

    /**
     * Get image
     *
     * @return text 
     */
    public function getImage()
    {
        return $this->image;
    }
    
    /**
     * Set image_thumb
     *
     * @param text $image_thumb
     * @return BusinessCategory
     */
    public function setImageThumb($image_thumb)
    {
        $this->image_thumb = $image_thumb;
    
        return $this;
    }

    /**
     * Get image_thumb
     *
     * @return text 
     */
    public function getImageThumb()
    {
        return $this->image_thumb;
    }
    
     /**
     * @var float
     */
    private $txn_percentage = 0.0;

    /**
     * @var float
     */
    private $card_percentage = 0.0;


    /**
     * Set txnPercentage
     *
     * @param float $txnPercentage
     * @return Store
     */
    public function setTxnPercentage($txn_percentage)
    {
        $this->txn_percentage = $txn_percentage;
    
        return $this;
    }

    /**
     * Get txnPercentage
     *
     * @return float 
     */
    public function getTxnPercentage()
    {
        return $this->txn_percentage;
    }

    /**
     * Set cardPercentage
     *
     * @param float $cardPercentage
     * @return Store
     */
    public function setCardPercentage($card_percentage)
    {
        $this->card_percentage = $card_percentage;
    
        return $this;
    }

    /**
     * Get cardPercentage
     *
     * @return float 
     */
    public function getCardPercentage()
    {
        return $this->card_percentage;
    }
    
}