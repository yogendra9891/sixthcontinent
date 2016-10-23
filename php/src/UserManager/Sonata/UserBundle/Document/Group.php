<?php

namespace UserManager\Sonata\UserBundle\Document;



/**
 * UserManager\Sonata\UserBundle\Document\Group
 */
class Group
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
     * @var string $description
     */
    protected $description;

    /**
     * @var int $group_status
     */
    protected $group_status;

    /**
     * @var int $owner_id
     */
    protected $owner_id;

    /**
     * @var date $created_at
     */
    protected $created_at;

    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var int $is_delete
     */
    protected $is_delete;

    /**
     * @var UserManager\Sonata\UserBundle\Document\ClubRating
     */
    protected $rate = array();

    public function __construct()
    {
        $this->rate = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set groupStatus
     *
     * @param int $groupStatus
     * @return self
     */
    public function setGroupStatus($groupStatus)
    {
        $this->group_status = $groupStatus;
        return $this;
    }

    /**
     * Get groupStatus
     *
     * @return int $groupStatus
     */
    public function getGroupStatus()
    {
        return $this->group_status;
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
     * Add rate
     *
     * @param UserManager\Sonata\UserBundle\Document\ClubRating $rate
     */
    public function addRate(\UserManager\Sonata\UserBundle\Document\ClubRating $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param UserManager\Sonata\UserBundle\Document\ClubRating $rate
     */
    public function removeRate(\UserManager\Sonata\UserBundle\Document\ClubRating $rate)
    {
        $this->rate->removeElement($rate);
    }

    /**
     * Get rate
     *
     * @return Doctrine\Common\Collections\Collection $rate
     */
    public function getRate()
    {
        return $this->rate;
    }
    /**
     * @var int $vote_count
     */
    protected $vote_count = 0;

    /**
     * @var int $vote_sum
     */
    protected $vote_sum = 0;

    /**
     * @var float $avg_rating
     */
    protected $avg_rating = 0;


    /**
     * Set voteCount
     *
     * @param int $voteCount
     * @return self
     */
    public function setVoteCount($voteCount)
    {
        $this->vote_count = $voteCount;
        return $this;
    }

    /**
     * Get voteCount
     *
     * @return int $voteCount
     */
    public function getVoteCount()
    {
        return $this->vote_count;
    }

    /**
     * Set voteSum
     *
     * @param int $voteSum
     * @return self
     */
    public function setVoteSum($voteSum)
    {
        $this->vote_sum = $voteSum;
        return $this;
    }

    /**
     * Get voteSum
     *
     * @return int $voteSum
     */
    public function getVoteSum()
    {
        return $this->vote_sum;
    }

    /**
     * Set avgRating
     *
     * @param float $avgRating
     * @return self
     */
    public function setAvgRating($avgRating)
    {
        $this->avg_rating = $avgRating;
        return $this;
    }

    /**
     * Get avgRating
     *
     * @return float $avgRating
     */
    public function getAvgRating()
    {
        return $this->avg_rating;
    }
}
