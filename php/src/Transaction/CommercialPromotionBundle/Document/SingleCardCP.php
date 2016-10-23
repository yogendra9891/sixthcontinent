<?php

namespace Transaction\CommercialPromotionBundle\Document;



/**
 * Transaction\CommercialPromotionBundle\Document\SingleCardCP
 */
class SingleCardCP
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $card_id
     */
    protected $card_id;

    /**
     * @var string $amount
     */
    protected $amount;

    /**
     * @var string $product_code
     */
    protected $product_code;

    /**
     * @var string $landscape_image
     */
    protected $landscape_image;

    /**
     * @var string $portrait_image
     */
    protected $portrait_image;


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
     * Set cardId
     *
     * @param string $cardId
     * @return self
     */
    public function setCardId($cardId)
    {
        $this->card_id = $cardId;
        return $this;
    }

    /**
     * Get cardId
     *
     * @return string $cardId
     */
    public function getCardId()
    {
        return $this->card_id;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return self
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Get amount
     *
     * @return string $amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set productCode
     *
     * @param string $productCode
     * @return self
     */
    public function setProductCode($productCode)
    {
        $this->product_code = $productCode;
        return $this;
    }

    /**
     * Get productCode
     *
     * @return string $productCode
     */
    public function getProductCode()
    {
        return $this->product_code;
    }

    /**
     * Set landscapeImage
     *
     * @param string $landscapeImage
     * @return self
     */
    public function setLandscapeImage($landscapeImage)
    {
        $this->landscape_image = $landscapeImage;
        return $this;
    }

    /**
     * Get landscapeImage
     *
     * @return string $landscapeImage
     */
    public function getLandscapeImage()
    {
        return $this->landscape_image;
    }

    /**
     * Set portraitImage
     *
     * @param string $portraitImage
     * @return self
     */
    public function setPortraitImage($portraitImage)
    {
        $this->portrait_image = $portraitImage;
        return $this;
    }

    /**
     * Get portraitImage
     *
     * @return string $portraitImage
     */
    public function getPortraitImage()
    {
        return $this->portrait_image;
    }
}
