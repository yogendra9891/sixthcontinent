<?php

namespace PostFeeds\PostFeedsBundle\Document;



/**
 * PostFeeds\PostFeedsBundle\Document\CommentFeeds
 */
class CommentFeeds
{
    /**
     * @var MongoId $id
     */
    /** @Id */
    protected $id;

    /**
     * @var int $user_id
     */
    protected $user_id;

    /**
     * @var collection $user_info
     */
    protected $user_info;

    /**
     * @var string $text
     */
    protected $text;

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
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
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
     * Set userInfo
     *
     * @param collection $userInfo
     * @return self
     */
    public function setUserInfo($userInfo)
    {
        $this->user_info = $userInfo;
        return $this;
    }

    /**
     * Get userInfo
     *
     * @return collection $userInfo
     */
    public function getUserInfo()
    {
        return $this->user_info;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return self
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Get text
     *
     * @return string $text
     */
    public function getText()
    {
        return $this->text;
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
     * @var PostFeeds\PostFeedsBundle\Document\RatingFeeds
     */
    protected $rate = array();

    /**
     * @var PostFeeds\PostFeedsBundle\Document\TaggingFeeds
     */
    protected $tag = array();

    /**
     * @var PostFeeds\PostFeedsBundle\Document\MediaFeeds
     */
    protected $media = array();

    public function __construct()
    {
        $this->rate = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tag = new \Doctrine\Common\Collections\ArrayCollection();
        $this->media = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add rate
     *
     * @param PostFeeds\PostFeedsBundle\Document\RatingFeeds $rate
     */
    public function addRate(\PostFeeds\PostFeedsBundle\Document\RatingFeeds $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param PostFeeds\PostFeedsBundle\Document\RatingFeeds $rate
     */
    public function removeRate(\PostFeeds\PostFeedsBundle\Document\RatingFeeds $rate)
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
     * Add tag
     *
     * @param PostFeeds\PostFeedsBundle\Document\TaggingFeeds $tag
     */
    public function addTag(\PostFeeds\PostFeedsBundle\Document\TaggingFeeds $tag)
    {
        $this->tag[] = $tag;
    }

    /**
     * Remove tag
     *
     * @param PostFeeds\PostFeedsBundle\Document\TaggingFeeds $tag
     */
    public function removeTag(\PostFeeds\PostFeedsBundle\Document\TaggingFeeds $tag)
    {
        $this->tag->removeElement($tag);
    }

    /**
     * Get tag
     *
     * @return Doctrine\Common\Collections\Collection $tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Add media
     *
     * @param PostFeeds\PostFeedsBundle\Document\MediaFeeds $media
     */
    public function addMedia(\PostFeeds\PostFeedsBundle\Document\MediaFeeds $media)
    {
        $this->media[] = $media;
    }

    /**
     * Remove media
     *
     * @param PostFeeds\PostFeedsBundle\Document\MediaFeeds $media
     */
    public function removeMedia(\PostFeeds\PostFeedsBundle\Document\MediaFeeds $media)
    {
        $this->media->removeElement($media);
    }

    /**
     * Get media
     *
     * @return Doctrine\Common\Collections\Collection $media
     */
    public function getMedia()
    {
        return $this->media;
    }
    /**
     * @var hash $tag_user
     */
    protected $tag_user;

    /**
     * @var hash $tag_shop
     */
    protected $tag_shop;

    /**
     * @var hash $tag_club
     */
    protected $tag_club;


    /**
     * Set tagUser
     *
     * @param hash $tagUser
     * @return self
     */
    public function setTagUser($tagUser)
    {
        $this->tag_user = $tagUser;
        return $this;
    }

    /**
     * Get tagUser
     *
     * @return hash $tagUser
     */
    public function getTagUser()
    {
        return $this->tag_user;
    }

    /**
     * Set tagShop
     *
     * @param hash $tagShop
     * @return self
     */
    public function setTagShop($tagShop)
    {
        $this->tag_shop = $tagShop;
        return $this;
    }

    /**
     * Get tagShop
     *
     * @return hash $tagShop
     */
    public function getTagShop()
    {
        return $this->tag_shop;
    }

    /**
     * Set tagClub
     *
     * @param hash $tagClub
     * @return self
     */
    public function setTagClub($tagClub)
    {
        $this->tag_club = $tagClub;
        return $this;
    }

    /**
     * Get tagClub
     *
     * @return hash $tagClub
     */
    public function getTagClub()
    {
        return $this->tag_club;
    }
    /**
     * @var int $is_comment
     */
    protected $is_comment;

    /**
     * @var int $is_rate
     */
    protected $is_rate;

    /**
     * @var int $is_tag
     */
    protected $is_tag;

    /**
     * @var int $is_media
     */
    protected $is_media;


    /**
     * Set isComment
     *
     * @param int $isComment
     * @return self
     */
    public function setIsComment($isComment)
    {
        $this->is_comment = $isComment;
        return $this;
    }

    /**
     * Get isComment
     *
     * @return int $isComment
     */
    public function getIsComment()
    {
        return $this->is_comment;
    }

    /**
     * Set isRate
     *
     * @param int $isRate
     * @return self
     */
    public function setIsRate($isRate)
    {
        $this->is_rate = $isRate;
        return $this;
    }

    /**
     * Get isRate
     *
     * @return int $isRate
     */
    public function getIsRate()
    {
        return $this->is_rate;
    }

    /**
     * Set isTag
     *
     * @param int $isTag
     * @return self
     */
    public function setIsTag($isTag)
    {
        $this->is_tag = $isTag;
        return $this;
    }

    /**
     * Get isTag
     *
     * @return int $isTag
     */
    public function getIsTag()
    {
        return $this->is_tag;
    }

    /**
     * Set isMedia
     *
     * @param int $isMedia
     * @return self
     */
    public function setIsMedia($isMedia)
    {
        $this->is_media = $isMedia;
        return $this;
    }

    /**
     * Get isMedia
     *
     * @return int $isMedia
     */
    public function getIsMedia()
    {
        return $this->is_media;
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
