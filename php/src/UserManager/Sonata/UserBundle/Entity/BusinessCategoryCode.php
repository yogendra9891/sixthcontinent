<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BusinessCategoryCode
 */
class BusinessCategoryCode
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $categoryCode;

    /**
     * @var string
     */
    private $langCode;

    /**
     * @var string
     */
    private $categoryName;


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
     * Set categoryCode
     *
     * @param string $categoryCode
     * @return BusinessCategoryCode
     */
    public function setCategoryCode($categoryCode)
    {
        $this->categoryCode = $categoryCode;
    
        return $this;
    }

    /**
     * Get categoryCode
     *
     * @return string 
     */
    public function getCategoryCode()
    {
        return $this->categoryCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return BusinessCategoryCode
     */
    public function setLangCode($langCode)
    {
        $this->langCode = $langCode;
    
        return $this;
    }

    /**
     * Get langCode
     *
     * @return string 
     */
    public function getLangCode()
    {
        return $this->langCode;
    }

    /**
     * Set categoryName
     *
     * @param string $categoryName
     * @return BusinessCategoryCode
     */
    public function setCategoryName($categoryName)
    {
        $this->categoryName = $categoryName;
    
        return $this;
    }

    /**
     * Get categoryName
     *
     * @return string 
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }
}
