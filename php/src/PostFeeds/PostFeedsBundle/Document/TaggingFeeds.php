<?php

namespace PostFeeds\PostFeedsBundle\Document;



/**
 * PostFeeds\PostFeedsBundle\Document\TaggingFeeds
 */
class TaggingFeeds
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var date $created_at
     */
    protected $created_at;

    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var PostFeeds\PostFeedsBundle\Document\ShopTagFeeds
     */
    protected $shop = array();

    /**
     * @var PostFeeds\PostFeedsBundle\Document\ClubTagFeeds
     */
    protected $club = array();

    /**
     * @var PostFeeds\PostFeedsBundle\Document\UserTagFeeds
     */
    protected $user = array();

    public function __construct()
    {
        $this->shop = new \Doctrine\Common\Collections\ArrayCollection();
        $this->club = new \Doctrine\Common\Collections\ArrayCollection();
        $this->user = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add shop
     *
     * @param PostFeeds\PostFeedsBundle\Document\ShopTagFeeds $shop
     */
    public function addShop(\PostFeeds\PostFeedsBundle\Document\ShopTagFeeds $shop)
    {
        $this->shop[] = $shop;
    }

    /**
     * Remove shop
     *
     * @param PostFeeds\PostFeedsBundle\Document\ShopTagFeeds $shop
     */
    public function removeShop(\PostFeeds\PostFeedsBundle\Document\ShopTagFeeds $shop)
    {
        $this->shop->removeElement($shop);
    }

    /**
     * Get shop
     *
     * @return Doctrine\Common\Collections\Collection $shop
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * Add club
     *
     * @param PostFeeds\PostFeedsBundle\Document\ClubTagFeeds $club
     */
    public function addClub(\PostFeeds\PostFeedsBundle\Document\ClubTagFeeds $club)
    {
        $this->club[] = $club;
    }

    /**
     * Remove club
     *
     * @param PostFeeds\PostFeedsBundle\Document\ClubTagFeeds $club
     */
    public function removeClub(\PostFeeds\PostFeedsBundle\Document\ClubTagFeeds $club)
    {
        $this->club->removeElement($club);
    }

    /**
     * Get club
     *
     * @return Doctrine\Common\Collections\Collection $club
     */
    public function getClub()
    {
        return $this->club;
    }

    /**
     * Add user
     *
     * @param PostFeeds\PostFeedsBundle\Document\UserTagFeeds $user
     */
    public function addUser(\PostFeeds\PostFeedsBundle\Document\UserTagFeeds $user)
    {
        $this->user[] = $user;
    }

    /**
     * Remove user
     *
     * @param PostFeeds\PostFeedsBundle\Document\UserTagFeeds $user
     */
    public function removeUser(\PostFeeds\PostFeedsBundle\Document\UserTagFeeds $user)
    {
        $this->user->removeElement($user);
    }

    /**
     * Get user
     *
     * @return Doctrine\Common\Collections\Collection $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set createdAt
     *
     * @param date $createdAt
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return date $createdAt
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt
     *
     * @param date $updatedAt
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return date $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
}
