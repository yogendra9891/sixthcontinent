<?php

namespace Transaction\CommercialPromotionBundle\Document;



/**
 * Transaction\CommercialPromotionBundle\Document\ShoppingCardCP
 */
class ShoppingCardCP
{
    /**
     * @var $id
     */
    protected $id;

    /**
     * @var string $post_title
     */
    protected $post_title;

    /**
     * @var string $description
     */
    protected $description;

    /**
     * @var Transaction\CommercialPromotionBundle\Document\TagsCP
     */
    protected $tags_cp = array();

    /**
     * @var Transaction\CommercialPromotionBundle\Document\ImagesCP
     */
    protected $images_cp = array();

    public function __construct()
    {
        $this->tags_cp = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set postTitle
     *
     * @param string $postTitle
     * @return self
     */
    public function setPostTitle($postTitle)
    {
        $this->post_title = $postTitle;
        return $this;
    }

    /**
     * Get postTitle
     *
     * @return string $postTitle
     */
    public function getPostTitle()
    {
        return $this->post_title;
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
}
