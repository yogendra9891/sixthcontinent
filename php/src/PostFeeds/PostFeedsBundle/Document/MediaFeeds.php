<?php

namespace PostFeeds\PostFeedsBundle\Document;



/**
 * PostFeeds\PostFeedsBundle\Document\MediaFeeds
 */
class MediaFeeds
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $item_id
     */
    protected $item_id;

    /**
     * @var string $media_name
     */
    protected $media_name;

    /**
     * @var string $media_type
     */
    protected $media_type;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var int $status
     */
    protected $status;

    /**
     * @var int $is_featured
     */
    protected $is_featured;

    /**
     * @var date $created_at
     */
    protected $created_at;

    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var PostFeeds\PostFeedsBundle\Document\RatingFeeds
     */
    protected $rate = array();

    /**
     * @var PostFeeds\PostFeedsBundle\Document\CommentFeeds
     */
    protected $comment = array();

    public function __construct()
    {
        $this->rate = new \Doctrine\Common\Collections\ArrayCollection();
        $this->comment = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set itemId
     *
     * @param string $itemId
     * @return self
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;
        return $this;
    }

    /**
     * Get itemId
     *
     * @return string $itemId
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set mediaName
     *
     * @param string $mediaName
     * @return self
     */
    public function setMediaName($mediaName)
    {
        $this->media_name = $mediaName;
        return $this;
    }

    /**
     * Get mediaName
     *
     * @return string $mediaName
     */
    public function getMediaName()
    {
        return $this->media_name;
    }

    /**
     * Set mediaType
     *
     * @param string $mediaType
     * @return self
     */
    public function setMediaType($mediaType)
    {
        $this->media_type = $mediaType;
        return $this;
    }

    /**
     * Get mediaType
     *
     * @return string $mediaType
     */
    public function getMediaType()
    {
        return $this->media_type;
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
     * Set isFeatured
     *
     * @param int $isFeatured
     * @return self
     */
    public function setIsFeatured($isFeatured)
    {
        $this->is_featured = $isFeatured;
        return $this;
    }

    /**
     * Get isFeatured
     *
     * @return int $isFeatured
     */
    public function getIsFeatured()
    {
        return $this->is_featured;
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
     * Add comment
     *
     * @param PostFeeds\PostFeedsBundle\Document\CommentFeeds $comment
     */
    public function addComment(\PostFeeds\PostFeedsBundle\Document\CommentFeeds $comment)
    {
        $this->comment[] = $comment;
    }

    /**
     * Remove comment
     *
     * @param PostFeeds\PostFeedsBundle\Document\CommentFeeds $comment
     */
    public function removeComment(\PostFeeds\PostFeedsBundle\Document\CommentFeeds $comment)
    {
        $this->comment->removeElement($comment);
    }

    /**
     * Get comment
     *
     * @return Doctrine\Common\Collections\Collection $comment
     */
    public function getComment()
    {
        return $this->comment;
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
     * @var PostFeeds\PostFeedsBundle\Document\PostFeeds
     */
    protected $post = array();


    /**
     * Add post
     *
     * @param PostFeeds\PostFeedsBundle\Document\PostFeeds $post
     */
    public function addPost(\PostFeeds\PostFeedsBundle\Document\PostFeeds $post)
    {
        $this->post[] = $post;
    }

    /**
     * Remove post
     *
     * @param PostFeeds\PostFeedsBundle\Document\PostFeeds $post
     */
    public function removePost(\PostFeeds\PostFeedsBundle\Document\PostFeeds $post)
    {
        $this->post->removeElement($post);
    }

    /**
     * Get post
     *
     * @return Doctrine\Common\Collections\Collection $post
     */
    public function getPost()
    {
        return $this->post;
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
    /**
     * @var int $user_id
     */
    protected $user_id;


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
}
