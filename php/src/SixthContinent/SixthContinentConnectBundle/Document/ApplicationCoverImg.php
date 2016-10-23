<?php

namespace SixthContinent\SixthContinentConnectBundle\Document;



/**
 * SixthContinent\SixthContinentConnectBundle\Document\ApplicationCoverImg
 */
class ApplicationCoverImg
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $app_id
     */
    protected $app_id;

    /**
     * @var string $x
     */
    protected $x;

    /**
     * @var string $y
     */
    protected $y;

    /**
     * @var PostFeeds\PostFeedsBundle\Document\MediaFeeds
     */
    protected $image_name = array();

    public function __construct()
    {
        $this->image_name = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
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
     * Set appId
     *
     * @param string $appId
     * @return self
     */
    public function setAppId($appId)
    {
        $this->app_id = $appId;
        return $this;
    }

    /**
     * Get appId
     *
     * @return string $appId
     */
    public function getAppId()
    {
        return $this->app_id;
    }

    /**
     * Set x
     *
     * @param string $x
     * @return self
     */
    public function setX($x)
    {
        $this->x = $x;
        return $this;
    }

    /**
     * Get x
     *
     * @return string $x
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Set y
     *
     * @param string $y
     * @return self
     */
    public function setY($y)
    {
        $this->y = $y;
        return $this;
    }

    /**
     * Get y
     *
     * @return string $y
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Add imageName
     *
     * @param PostFeeds\PostFeedsBundle\Document\MediaFeeds $imageName
     */
    public function addImageName(\PostFeeds\PostFeedsBundle\Document\MediaFeeds $imageName)
    {
        $this->image_name[] = $imageName;
    }

    /**
     * Remove imageName
     *
     * @param PostFeeds\PostFeedsBundle\Document\MediaFeeds $imageName
     */
    public function removeImageName(\PostFeeds\PostFeedsBundle\Document\MediaFeeds $imageName)
    {
        $this->image_name->removeElement($imageName);
    }

    /**
     * Get imageName
     *
     * @return Doctrine\Common\Collections\Collection $imageName
     */
    public function getImageName()
    {
        return $this->image_name;
    }
}