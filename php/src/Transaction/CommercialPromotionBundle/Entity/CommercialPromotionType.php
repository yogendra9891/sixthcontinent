<?php

namespace Transaction\CommercialPromotionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CommercialPromotionType
 */
class CommercialPromotionType
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $promotionLabel;

    /**
     * @var integer
     */
    private $toBuy;

    /**
     * @var string
     */
    private $defaultImg;

    /**
     * @var string
     */
    private $promotionType;


    /**
     * Set id
     *
     * @param integer $id
     * @return CommercialPromotionType
     */
    public function setId($id)
    {
        $this->id = $id;
    
        return $this;
    }

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
     * Set description
     *
     * @param string $description
     * @return CommercialPromotionType
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set promotionLabel
     *
     * @param string $promotionLabel
     * @return CommercialPromotionType
     */
    public function setPromotionLabel($promotionLabel)
    {
        $this->promotionLabel = $promotionLabel;
    
        return $this;
    }

    /**
     * Get promotionLabel
     *
     * @return string 
     */
    public function getPromotionLabel()
    {
        return $this->promotionLabel;
    }

    /**
     * Set toBuy
     *
     * @param integer $toBuy
     * @return CommercialPromotionType
     */
    public function setToBuy($toBuy)
    {
        $this->toBuy = $toBuy;
    
        return $this;
    }

    /**
     * Get toBuy
     *
     * @return integer 
     */
    public function getToBuy()
    {
        return $this->toBuy;
    }

    /**
     * Set defaultImg
     *
     * @param string $defaultImg
     * @return CommercialPromotionType
     */
    public function setDefaultImg($defaultImg)
    {
        $this->defaultImg = $defaultImg;
    
        return $this;
    }

    /**
     * Get defaultImg
     *
     * @return string 
     */
    public function getDefaultImg()
    {
        return $this->defaultImg;
    }

    /**
     * Set promotionType
     *
     * @param string $promotionType
     * @return CommercialPromotionType
     */
    public function setPromotionType($promotionType)
    {
        $this->promotionType = $promotionType;
    
        return $this;
    }

    /**
     * Get promotionType
     *
     * @return string 
     */
    public function getPromotionType()
    {
        return $this->promotionType;
    }
}