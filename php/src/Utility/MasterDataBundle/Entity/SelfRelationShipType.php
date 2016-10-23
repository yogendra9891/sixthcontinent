<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SelfRelationShipType
 */
class SelfRelationShipType
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
    private $parent;

    /**
     * @var boolean
     */
    private $status;

    /**
     * @var string
     */
    private $image;

    /**
     * @var string
     */
    private $imageThumb;


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
     * @return SelfRelationShipType
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
     * @return SelfRelationShipType
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
     * @param boolean $status
     * @return SelfRelationShipType
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return boolean 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return SelfRelationShipType
     */
    public function setImage($image)
    {
        $this->image = $image;
    
        return $this;
    }

    /**
     * Get image
     *
     * @return string 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set imageThumb
     *
     * @param string $imageThumb
     * @return SelfRelationShipType
     */
    public function setImageThumb($imageThumb)
    {
        $this->imageThumb = $imageThumb;
    
        return $this;
    }

    /**
     * Get imageThumb
     *
     * @return string 
     */
    public function getImageThumb()
    {
        return $this->imageThumb;
    }
}