<?php

namespace Utility\MasterDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LanguageCode
 */
class LanguageCode
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $languageName;

    /**
     * @var string
     */
    private $languageCode;

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
     * Set languageName
     *
     * @param string $languageName
     * @return LanguageCode
     */
    public function setLanguageName($languageName)
    {
        $this->languageName = $languageName;
    
        return $this;
    }

    /**
     * Get languageName
     *
     * @return string 
     */
    public function getLanguageName()
    {
        return $this->languageName;
    }

    /**
     * Set languageCode
     *
     * @param string $languageCode
     * @return LanguageCode
     */
    public function setLanguageCode($languageCode)
    {
        $this->languageCode = $languageCode;
    
        return $this;
    }

    /**
     * Get languageCode
     *
     * @return string 
     */
    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return LanguageCode
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
     * @return LanguageCode
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
     * @return LanguageCode
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