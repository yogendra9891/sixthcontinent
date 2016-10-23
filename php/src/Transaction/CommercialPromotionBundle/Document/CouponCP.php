<?php

namespace Transaction\CommercialPromotionBundle\Document;



/**
 * Transaction\CommercialPromotionBundle\Document\CouponCP
 */
class CouponCP
{
    /**
     * @var $id
     */
    protected $id;

    /**
     * @var Transaction\CommercialPromotionBundle\Document\TagsCP
     */
    protected $tags_cp = array();

    public function __construct()
    {
        $this->tags_cp = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add tagsCp
     *
     * @param Transaction\CommercialPromotionBundle\Document\TagsCP $tagsCp
     */
    public function addTagsCp(\Transaction\CommercialPromotionBundle\Document\TagsCP $tagsCp)
    {
        $this->tags_cp[] = $tagsCp;
    }

    /**
     * Remove tagsCp
     *
     * @param Transaction\CommercialPromotionBundle\Document\TagsCP $tagsCp
     */
    public function removeTagsCp(\Transaction\CommercialPromotionBundle\Document\TagsCP $tagsCp)
    {
        $this->tags_cp->removeElement($tagsCp);
    }

    /**
     * Get tagsCp
     *
     * @return Doctrine\Common\Collections\Collection $tagsCp
     */
    public function getTagsCp()
    {
        return $this->tags_cp;
    }
}
