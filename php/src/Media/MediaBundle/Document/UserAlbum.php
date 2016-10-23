<?php

namespace Media\MediaBundle\Document;



/**
 * Media\MediaBundle\Document\UserAlbum
 */
class UserAlbum
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $album_name
     */
    protected $album_name;

    /**
     * @var int $user_id
     */
    protected $user_id;

    /**
     * @var string $album_desc
     */
    protected $album_desc;

    /**
     * @var int $privacy_setting
     */
    protected $privacy_setting;
    
    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var date $created_at
     */
    protected $created_at;


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
     * Set albumName
     *
     * @param string $albumName
     * @return self
     */
    public function setAlbumName($albumName)
    {
        $this->album_name = $albumName;
        return $this;
    }

    /**
     * Get albumName
     *
     * @return string $albumName
     */
    public function getAlbumName()
    {
        return $this->album_name;
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
     * Set albumDesc
     *
     * @param string $albumDesc
     * @return self
     */
    public function setAlbumDesc($albumDesc)
    {
        $this->album_desc = $albumDesc;
        return $this;
    }

    /**
     * Get albumDesc
     *
     * @return string $albumDesc
     */
    public function getAlbumDesc()
    {
        return $this->album_desc;
    }

    /**
     * Set PrivacySetting
     *
     * @param int $privacySetting
     * @return self
     */
    public function setPrivacySetting($privacySetting)
    {
        $this->privacy_setting = $privacySetting;
        return $this;
    }

    /**
     * Get userId
     *
     * @return int $privacy_setting
     */
    public function getPrivacySetting()
    {
        return $this->privacy_setting;
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
     * @var Media\MediaBundle\Document\UserAlbumRating
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
     * @param Media\MediaBundle\Document\UserAlbumRating $rate
     */
    public function addRate(\Media\MediaBundle\Document\UserAlbumRating $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param Media\MediaBundle\Document\UserAlbumRating $rate
     */
    public function removeRate(\Media\MediaBundle\Document\UserAlbumRating $rate)
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
}
