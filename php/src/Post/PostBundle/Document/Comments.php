<?php

namespace Post\PostBundle\Document;



/**
 * Post\PostBundle\Document\Comments
 */
class Comments
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $post_id
     */
    protected $post_id;

    /**
     * @var string $comment_text
     */
    protected $comment_text;

    /**
     * @var int $comment_author
     */
    protected $comment_author;

    /**
     * @var date $comment_created_at
     */
    protected $comment_created_at;

    /**
     * @var date $comment_updated_at
     */
    protected $comment_updated_at;

    /**
     * @var int $status
     */
    protected $status;
    
    /**
     * @var hash $tagging
     */
    protected $tagging;


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
     * Set postId
     *
     * @param string $postId
     * @return self
     */
    public function setPostId($postId)
    {
        $this->post_id = $postId;
        return $this;
    }

    /**
     * Get postId
     *
     * @return string $postId
     */
    public function getPostId()
    {
        return $this->post_id;
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
     * Set commentAuthor
     *
     * @param int $commentAuthor
     * @return self
     */
    public function setCommentAuthor($commentAuthor)
    {
        $this->comment_author = $commentAuthor;
        return $this;
    }

    /**
     * Get commentAuthor
     *
     * @return int $commentAuthor
     */
    public function getCommentAuthor()
    {
        return $this->comment_author;
    }

    /**
     * Set commentCreatedAt
     *
     * @param date $commentCreatedAt
     * @return self
     */
    public function setCommentCreatedAt($commentCreatedAt)
    {
        $this->comment_created_at = $commentCreatedAt;
        return $this;
    }

    /**
     * Get commentCreatedAt
     *
     * @return date $commentCreatedAt
     */
    public function getCommentCreatedAt()
    {
        return $this->comment_created_at;
    }

    /**
     * Set commentUpdatedAt
     *
     * @param date $commentUpdatedAt
     * @return self
     */
    public function setCommentUpdatedAt($commentUpdatedAt)
    {
        $this->comment_updated_at = $commentUpdatedAt;
        return $this;
    }

    /**
     * Get commentUpdatedAt
     *
     * @return date $commentUpdatedAt
     */
    public function getCommentUpdatedAt()
    {
        return $this->comment_updated_at;
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
     * @var string $profileType
     */
    protected $profile_type;
    /**
     * @var string $type
     */
    protected $type;
    
    /**
     * Set profileType
     *
     * @param string $profileType
     * @return self
     */
    public function setProfileType($profileType)
    {
        $this->profile_type = $profileType;
        return $this;
    }

    /**
     * Get profileType
     *
     * @return string $profileType
     */
    public function getProfileType()
    {
        return $this->profile_type;
    }
    
    
    /**
     * Set type
     *
     * @param string $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }
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
     * @var Post\PostBundle\Document\CommentRating
     */
    protected $rate = array();

    public function __construct()
    {
        $this->rate = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param Post\PostBundle\Document\CommentRating $rate
     */
    public function addRate(\Post\PostBundle\Document\CommentRating $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param Post\PostBundle\Document\CommentRating $rate
     */
    public function removeRate(\Post\PostBundle\Document\CommentRating $rate)
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
     * Set tagging
     *
     * @param array $tagging
     * @return self
     */
    public function setTagging($tagging)
    {
        $this->tagging = $tagging;
        return $this;
    }

    /**
     * Get tagging
     *
     * @return array $tagging
     */
    public function getTagging()
    {
        return $this->tagging;
    }
}
