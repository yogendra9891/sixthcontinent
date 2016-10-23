<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BarCode
 */
class BarCode
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $hashCode;

    /**
     * @var string
     */
    private $imagePath;

    /**
     * @var integer
     */
    private $codeId;


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
     * Set hashCode
     *
     * @param string $hashCode
     * @return BarCode
     */
    public function setHashCode($hashCode)
    {
        $this->hashCode = $hashCode;
    
        return $this;
    }

    /**
     * Get hashCode
     *
     * @return string 
     */
    public function getHashCode()
    {
        return $this->hashCode;
    }

    /**
     * Set imagePath
     *
     * @param string $imagePath
     * @return BarCode
     */
    public function setImagePath($imagePath)
    {
        $this->imagePath = $imagePath;
    
        return $this;
    }

    /**
     * Get imagePath
     *
     * @return string 
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * Set codeId
     *
     * @param integer $codeId
     * @return BarCode
     */
    public function setCodeId($codeId)
    {
        $this->codeId = $codeId;
    
        return $this;
    }

    /**
     * Get codeId
     *
     * @return integer 
     */
    public function getCodeId()
    {
        return $this->codeId;
    }
}