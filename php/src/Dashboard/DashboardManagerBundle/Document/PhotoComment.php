<?php

namespace Dashboard\DashboardManagerBundle\Document;



/**
 * Dashboard\DashboardManagerBundle\Document\PhotoComment
 */
class PhotoComment
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $comment_text
     */
    protected $comment_text;

    /**
     * @var int $user_id
     */
    protected $user_id;

    /**
     * @var int $is_active
     */
    protected $is_active;

    /**
     * @var date $created_at
     */
    protected $created_at;

    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var int $vote_count
     */
    protected $vote_count;

    /**
     * @var int $vote_sum
     */
    protected $vote_sum;

    /**
     * @var float $avg_rating
     */
    protected $avg_rating;

    /**
     * @var Dashboard\DashboardManagerBundle\Document\PhotoCommentRating
     */
    protected $rate = array();

    /**
     * @var Dashboard\DashboardManagerBundle\Document\PhotoMedia
     */
    protected $photomedia = array();

    public function __construct()
    {
        $this->rate = new \Doctrine\Common\Collections\ArrayCollection();
        $this->photomedia = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set commentText
     *
     * @param string $commentText
     * @return self
     */
    public function setCommentText($commentText)
    {
        $this->comment_text = $commentText;
        return $this;
    }

    /**
     * Get commentText
     *
     * @return string $commentText
     */
    public function getCommentText()
    {
        return $this->comment_text;
    }

    /**
     * Set userId
     *
     * @param int $userId
     * @return self
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;
        return $this;
    }

    /**
     * Get userId
     *
     * @return int $userId
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set isActive
     *
     * @param int $isActive
     * @return self
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;
        return $this;
    }

    /**
     * Get isActive
     *
     * @return int $isActive
     */
    public function getIsActive()
    {
        return $this->is_active;
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

    /**
     * Add rate
     *
     * @param Dashboard\DashboardManagerBundle\Document\PhotoCommentRating $rate
     */
    public function addRate(\Dashboard\DashboardManagerBundle\Document\PhotoCommentRating $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param Dashboard\DashboardManagerBundle\Document\PhotoCommentRating $rate
     */
    public function removeRate(\Dashboard\DashboardManagerBundle\Document\PhotoCommentRating $rate)
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
     * Add photomedia
     *
     * @param Dashboard\DashboardManagerBundle\Document\PhotoMedia $photomedia
     */
    public function addPhotomedia(\Dashboard\DashboardManagerBundle\Document\PhotoMedia $photomedia)
    {
        $this->photomedia[] = $photomedia;
    }

    /**
     * Remove photomedia
     *
     * @param Dashboard\DashboardManagerBundle\Document\PhotoMedia $photomedia
     */
    public function removePhotomedia(\Dashboard\DashboardManagerBundle\Document\PhotoMedia $photomedia)
    {
        $this->photomedia->removeElement($photomedia);
    }

    /**
     * Get photomedia
     *
     * @return Doctrine\Common\Collections\Collection $photomedia
     */
    public function getPhotomedia()
    {
        return $this->photomedia;
    }
}
