<?php

namespace Transaction\CommercialPromotionBundle\Document;



/**
 * Transaction\CommercialPromotionBundle\Document\VoucherCP
 */
class VoucherCP
{
    /**
     * @var $id
     */
    protected $id;

    /**
     * @var string $description
     */
    protected $description;

    /**
     * @var string $url_confirmation
     */
    protected $url_confirmation;

    /**
     * @var string $html_page
     */
    protected $html_page;

    /**
     * @var Transaction\CommercialPromotionBundle\Document\ImagesCP
     */
    protected $images_cp = array();

    public function __construct()
    {
        $this->images_cp = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set id
     *
     * @param custom_id $id
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get id
     *
     * @return custom_id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add imagesCp
     *
     * @param Transaction\CommercialPromotionBundle\Document\ImagesCP $imagesCp
     */
    public function addImagesCp(\Transaction\CommercialPromotionBundle\Document\ImagesCP $imagesCp)
    {
        $this->images_cp[] = $imagesCp;
    }

    /**
     * Remove imagesCp
     *
     * @param Transaction\CommercialPromotionBundle\Document\ImagesCP $imagesCp
     */
    public function removeImagesCp(\Transaction\CommercialPromotionBundle\Document\ImagesCP $imagesCp)
    {
        $this->images_cp->removeElement($imagesCp);
    }

    /**
     * Get imagesCp
     *
     * @return Doctrine\Common\Collections\Collection $imagesCp
     */
    public function getImagesCp()
    {
        return $this->images_cp;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set urlConfirmation
     *
     * @param string $urlConfirmation
     * @return self
     */
    public function setUrlConfirmation($urlConfirmation)
    {
        $this->url_confirmation = $urlConfirmation;
        return $this;
    }

    /**
     * Get urlConfirmation
     *
     * @return string $urlConfirmation
     */
    public function getUrlConfirmation()
    {
        return $this->url_confirmation;
    }

    /**
     * Set htmlPage
     *
     * @param string $htmlPage
     * @return self
     */
    public function setHtmlPage($htmlPage)
    {
        $this->html_page = $htmlPage;
        return $this;
    }

    /**
     * Get htmlPage
     *
     * @return string $htmlPage
     */
    public function getHtmlPage()
    {
        return $this->html_page;
    }
}
