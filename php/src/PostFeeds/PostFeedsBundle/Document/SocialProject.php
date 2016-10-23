<?php

namespace PostFeeds\PostFeedsBundle\Document;



/**
 * PostFeeds\PostFeedsBundle\Document\SocialProject
 */
class SocialProject
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $title
     */
    protected $title;

    /**
     * @var int $owner_id
     */
    protected $owner_id;

    /**
     * @var string $website
     */
    protected $website;
    /**
     * @var string $email
     */
    protected $email;
    /**
     * @var string $description
     */
    protected $description;

    /**
     * @var int $we_want
     */
    protected $we_want;

    /**
     * @var int $is_delete
     */
    protected $is_delete;

    /**
     * @var int $status
     */
    protected $status;

    /**
     * @var date $created_at
     */
    protected $created_at;

    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var PostFeeds\PostFeedsBundle\Document\SocialProjectCoverImg
     */
    protected $cover_img = array();

    /**
     * @var PostFeeds\PostFeedsBundle\Document\SocialProjectAddress
     */
    protected $address = array();

    /**
     * @var PostFeeds\PostFeedsBundle\Document\MediaFeeds
     */
    protected $medias = array();

    public function __construct()
    {
        $this->cover_img = new \Doctrine\Common\Collections\ArrayCollection();
        $this->address = new \Doctrine\Common\Collections\ArrayCollection();
        $this->medias = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set title
     *
     * @param string $title
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set ownerId
     *
     * @param int $ownerId
     * @return self
     */
    public function setOwnerId($ownerId)
    {
        $this->owner_id = $ownerId;
        return $this;
    }

    /**
     * Get ownerId
     *
     * @return int $ownerId
     */
    public function getOwnerId()
    {
        return $this->owner_id;
    }

    /**
     * Set website
     *
     * @param string $website
     * @return self
     */
    public function setWebsite($website)
    {
        $this->website = $website;
        return $this;
    }

    /**
     * Get website
     *
     * @return string $website
     */
    public function getWebsite()
    {
        return $this->website;
    }
    
    /**
     * Set email
     *
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
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
     * Set weWant
     *
     * @param int $weWant
     * @return self
     */
    public function setWeWant($weWant)
    {
        $this->we_want = $weWant;
        return $this;
    }

    /**
     * Get weWant
     *
     * @return int $weWant
     */
    public function getWeWant()
    {
        return $this->we_want;
    }

    /**
     * Set isDelete
     *
     * @param int $isDelete
     * @return self
     */
    public function setIsDelete($isDelete)
    {
        $this->is_delete = $isDelete;
        return $this;
    }

    /**
     * Get isDelete
     *
     * @return int $isDelete
     */
    public function getIsDelete()
    {
        return $this->is_delete;
    }

    /**
     * Set status
     *
     * @param int $status
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get status
     *
     * @return int $status
     */
    public function getStatus()
    {
        return $this->status;
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

    /**
     * Add coverImg
     *
     * @param PostFeeds\PostFeedsBundle\Document\SocialProjectCoverImg $coverImg
     */
    public function addCoverImg(\PostFeeds\PostFeedsBundle\Document\SocialProjectCoverImg $coverImg)
    {
        $this->cover_img[] = $coverImg;
    }

    /**
     * Remove coverImg
     *
     * @param PostFeeds\PostFeedsBundle\Document\SocialProjectCoverImg $coverImg
     */
    public function removeCoverImg(\PostFeeds\PostFeedsBundle\Document\SocialProjectCoverImg $coverImg)
    {
        $this->cover_img->removeElement($coverImg);
    }

    /**
     * Get coverImg
     *
     * @return Doctrine\Common\Collections\Collection $coverImg
     */
    public function getCoverImg()
    {
        return $this->cover_img;
    }

    /**
     * Add address
     *
     * @param PostFeeds\PostFeedsBundle\Document\SocialProjectAddress $address
     */
    public function addAddress(\PostFeeds\PostFeedsBundle\Document\SocialProjectAddress $address)
    {
        $this->address[] = $address;
    }

    /**
     * Remove address
     *
     * @param PostFeeds\PostFeedsBundle\Document\SocialProjectAddress $address
     */
    public function removeAddress(\PostFeeds\PostFeedsBundle\Document\SocialProjectAddress $address)
    {
        $this->address->removeElement($address);
    }

    /**
     * Get address
     *
     * @return Doctrine\Common\Collections\Collection $address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Add media
     *
     * @param PostFeeds\PostFeedsBundle\Document\MediaFeeds $media
     */
    public function addMedia(\PostFeeds\PostFeedsBundle\Document\MediaFeeds $media)
    {
        $this->medias[] = $media;
    }

    /**
     * Remove media
     *
     * @param PostFeeds\PostFeedsBundle\Document\MediaFeeds $media
     */
    public function removeMedia(\PostFeeds\PostFeedsBundle\Document\MediaFeeds $media)
    {
        $this->medias->removeElement($media);
    }

    /**
     * Get medias
     *
     * @return Doctrine\Common\Collections\Collection $medias
     */
    public function getMedias()
    {
        return $this->medias;
    }
}
