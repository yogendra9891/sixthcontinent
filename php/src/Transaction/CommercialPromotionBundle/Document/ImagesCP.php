<?php

namespace Transaction\CommercialPromotionBundle\Document;



/**
 * Transaction\CommercialPromotionBundle\Document\ImagesCP
 */
class ImagesCP
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $image_type
     */
    protected $image_type;

    /**
     * @var string $real
     */
    protected $real;

    /**
     * @var string $thumb
     */
    protected $thumb;


    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set imageType
     *
     * @param string $imageType
     * @return self
     */
    public function setImageType($imageType)
    {
        $this->image_type = $imageType;
        return $this;
    }

    /**
     * Get imageType
     *
     * @return string $imageType
     */
    public function getImageType()
    {
        return $this->image_type;
    }

    /**
     * Set real
     *
     * @param string $real
     * @return self
     */
    public function setReal($real)
    {
        $this->real = $real;
        return $this;
    }

    /**
     * Get real
     *
     * @return string $real
     */
    public function getReal()
    {
        return $this->real;
    }

    /**
     * Set thumb
     *
     * @param string $thumb
     * @return self
     */
    public function setThumb($thumb)
    {
        $this->thumb = $thumb;
        return $this;
    }

    /**
     * Get thumb
     *
     * @return string $thumb
     */
    public function getThumb()
    {
        return $this->thumb;
    }
}
