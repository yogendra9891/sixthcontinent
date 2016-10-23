<?php

namespace Media\MediaBundle\Document;


/**
 * Media\MediaBundle\Document\StoreAlbumMediaComment
 */
class StoreAlbumMediaComment
{
 
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $album_id
     */
    protected $album_id;

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
     * @var Media\MediaBundle\Document\AlbumMediaCommentRating
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
     * Set albumId
     *
     * @param string $albumId
     * @return self
     */
    public function setAlbumId($albumId)
    {
        $this->album_id = $albumId;
        return $this;
    }

    /**
     * Get albumId
     *
     * @return string $albumId
     */
    public function getAlbumId()
    {
        return $this->album_id;
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
     * @param Media\MediaBundle\Document\AlbumMediaCommentRating $rate
     */
    public function addRate(\Media\MediaBundle\Document\AlbumMediaCommentRating $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param Media\MediaBundle\Document\AlbumMediaCommentRating $rate
     */
    public function removeRate(\Media\MediaBundle\Document\AlbumMediaCommentRating $rate)
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
     * @var string $media_id
     */
    protected $media_id;

    /**
     * @var collection $medias
     */
    protected $medias;


    /**
     * Set mediaId
     *
     * @param string $mediaId
     * @return self
     */
    public function setMediaId($mediaId)
    {
        $this->media_id = $mediaId;
        return $this;
    }

    /**
     * Get mediaId
     *
     * @return string $mediaId
     */
    public function getMediaId()
    {
        return $this->media_id;
    }

    /**
     * Set medias
     *
     * @param collection $medias
     * @return self
     */
    public function setMedias($medias)
    {
        $this->medias = $medias;
        return $this;
    }

    /**
     * Get medias
     *
     * @return collection $medias
     */
    public function getMedias()
    {
        return $this->medias;
    }
    
    /**
     * @var hash $tagging
     */
    protected $tagging;
    
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
     * @return array tagging
     */
    public function getTagging()
    {
        return $this->tagging;
    }
}
