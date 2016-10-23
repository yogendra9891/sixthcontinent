<?php

namespace PostFeeds\PostFeedsBundle\Document;



/**
 * PostFeeds\PostFeedsBundle\Document\PostFeeds
 */
class PostFeeds
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var int $user_id
     */
    protected $user_id;

    /**
     * @var int $to_id
     */
    protected $to_id;

    /**
     * @var string $title
     */
    protected $title;

    /**
     * @var string $description
     */
    protected $description;

    /**
     * @var int $link_type
     */
    protected $link_type;

    /**
     * @var int $is_active
     */
    protected $is_active;

    /**
     * @var int $privacy_setting
     */
    protected $privacy_setting;

    /**
     * @var date $created_at
     */
    protected $created_at;

    /**
     * @var date $updated_at
     */
    protected $updated_at;

    /**
     * @var string $post_type
     */
    protected $post_type;

    /**
     * @var hash $type_info
     */
    protected $type_info;


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
     * @var PostFeeds\PostFeedsBundle\Document\CommentFeeds
     */
    protected $comments = array();

    /**
     * @var PostFeeds\PostFeedsBundle\Document\RatingFeeds
     */
    protected $rate = array();

    /**
     * @var PostFeeds\PostFeedsBundle\Document\MediaFeeds
     */
    protected $media = array();

    public function __construct()
    {
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->rate = new \Doctrine\Common\Collections\ArrayCollection();
        $this->media = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set toId
     *
     * @param int $toId
     * @return self
     */
    public function setToId($toId)
    {
        $this->to_id = $toId;
        return $this;
    }

    /**
     * Get toId
     *
     * @return int $toId
     */
    public function getToId()
    {
        return $this->to_id;
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
     * Set linkType
     *
     * @param int $linkType
     * @return self
     */
    public function setLinkType($linkType)
    {
        $this->link_type = $linkType;
        return $this;
    }

    /**
     * Get linkType
     *
     * @return int $linkType
     */
    public function getLinkType()
    {
        return $this->link_type;
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
     * Set privacySetting
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
     * Get privacySetting
     *
     * @return int $privacySetting
     */
    public function getPrivacySetting()
    {
        return $this->privacy_setting;
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
     * Set postType
     *
     * @param string $postType
     * @return self
     */
    public function setPostType($postType)
    {
        $this->post_type = $postType;
        return $this;
    }

    /**
     * Get postType
     *
     * @return string $postType
     */
    public function getPostType()
    {
        return $this->post_type;
    }

    /**
     * Set typeInfo
     *
     * @param hash $typeInfo
     * @return self
     */
    public function setTypeInfo($typeInfo)
    {
        $this->type_info = $typeInfo;
        return $this;
    }

    /**
     * Get typeInfo
     *
     * @return hash $typeInfo
     */
    public function getTypeInfo()
    {
        return $this->type_info;
    }

    

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
     * Add comment
     *
     * @param PostFeeds\PostFeedsBundle\Document\CommentFeeds $comment
     */
    public function addComment(\PostFeeds\PostFeedsBundle\Document\CommentFeeds $comment)
    {
        $this->comments[] = $comment;
    }

    /**
     * Remove comment
     *
     * @param PostFeeds\PostFeedsBundle\Document\CommentFeeds $comment
     */
    public function removeComment(\PostFeeds\PostFeedsBundle\Document\CommentFeeds $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return Doctrine\Common\Collections\Collection $comments
     */
    public function getComments()
    {
        return $this->comments;
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
     * @var string $share_type
     */
    protected $share_type;

    /**
     * @var hash $content_share
     */
    protected $content_share = null;

    /**
     * @var string $share_object_id
     */
    protected $share_object_id;

    /**
     * @var string $share_object_type
     */
    protected $share_object_type;


    /**
     * Set shareType
     *
     * @param string $shareType
     * @return self
     */
    public function setShareType($shareType)
    {
        $this->share_type = $shareType;
        return $this;
    }

    /**
     * Get shareType
     *
     * @return string $shareType
     */
    public function getShareType()
    {
        return $this->share_type;
    }

    /**
     * Set contentShare
     *
     * @param hash $contentShare
     * @return self
     */
    public function setContentShare($contentShare)
    {
        $this->content_share = $contentShare;
        return $this;
    }

    /**
     * Get contentShare
     *
     * @return hash $contentShare
     */
    public function getContentShare()
    {
        return $this->content_share;
    }

    /**
     * Set shareObjectId
     *
     * @param string $shareObjectId
     * @return self
     */
    public function setShareObjectId($shareObjectId)
    {
        $this->share_object_id = $shareObjectId;
        return $this;
    }

    /**
     * Get shareObjectId
     *
     * @return string $shareObjectId
     */
    public function getShareObjectId()
    {
        return $this->share_object_id;
    }

    /**
     * Set shareObjectType
     *
     * @param string $shareObjectType
     * @return self
     */
    public function setShareObjectType($shareObjectType)
    {
        $this->share_object_type = $shareObjectType;
        return $this;
    }

    /**
     * Get shareObjectType
     *
     * @return string $shareObjectType
     */
    public function getShareObjectType()
    {
        return $this->share_object_type;
    }
}
