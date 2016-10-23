<?php

namespace Post\PostBundle\Document;



/**
 * Post\PostBundle\Document\Post
 */
class Post
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $post_title
     */
    protected $post_title;

    /**
     * @var string $post_desc
     */
    protected $post_desc;
    
    /**
     * @var int $link_type
     */
    protected $link_type;
    
    /**
     * @var string $post_author
     */
    protected $post_author;

    /**
     * @var date $post_created
     */
    protected $post_created;

    /**
     * @var date $post_updated
     */
    protected $post_updated;

    /**
     * @var int $post_status
     */
    protected $post_status;

    /**
     * @var string $post_gid
     */
    protected $post_gid;

    /**
     * @var int $post_group_owner_id
     */
    protected $post_group_owner_id;
    
    /**
     * @var collection $tagged_friends
     */
    protected $tagged_friends;


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
     * Set postTitle
     *
     * @param string $postTitle
     * @return self
     */
    public function setPostTitle($postTitle)
    {
        $this->post_title = $postTitle;
        return $this;
    }

    /**
     * Get postTitle
     *
     * @return string $postTitle
     */
    public function getPostTitle()
    {
        return $this->post_title;
    }

    /**
     * Set postDesc
     *
     * @param string $postDesc
     * @return self
     */
    public function setPostDesc($postDesc)
    {
        $this->post_desc = $postDesc;
        return $this;
    }

    /**
     * Get postDesc
     *
     * @return string $postDesc
     */
    public function getPostDesc()
    {
        return $this->post_desc;
    }

    /**
     * Set linkType
     *
     * @param string $linkType
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
     * @return string $link_type
     */
    public function getLinkType()
    {
        return $this->link_type;
    }
    
    /**
     * Set postAuthor
     *
     * @param string $postAuthor
     * @return self
     */
    public function setPostAuthor($postAuthor)
    {
        $this->post_author = $postAuthor;
        return $this;
    }

    /**
     * Get postAuthor
     *
     * @return string $postAuthor
     */
    public function getPostAuthor()
    {
        return $this->post_author;
    }

    /**
     * Set postCreated
     *
     * @param date $postCreated
     * @return self
     */
    public function setPostCreated($postCreated)
    {
        $this->post_created = $postCreated;
        return $this;
    }

    /**
     * Get postCreated
     *
     * @return date $postCreated
     */
    public function getPostCreated()
    {
        return $this->post_created;
    }

    /**
     * Set postUpdated
     *
     * @param date $postUpdated
     * @return self
     */
    public function setPostUpdated($postUpdated)
    {
        $this->post_updated = $postUpdated;
        return $this;
    }

    /**
     * Get postUpdated
     *
     * @return date $postUpdated
     */
    public function getPostUpdated()
    {
        return $this->post_updated;
    }

    /**
     * Set postStatus
     *
     * @param int $postStatus
     * @return self
     */
    public function setPostStatus($postStatus)
    {
        $this->post_status = $postStatus;
        return $this;
    }

    /**
     * Get postStatus
     *
     * @return int $postStatus
     */
    public function getPostStatus()
    {
        return $this->post_status;
    }

    /**
     * Set postGid
     *
     * @param string $postGid
     * @return self
     */
    public function setPostGid($postGid)
    {
        $this->post_gid = $postGid;
        return $this;
    }

    /**
     * Get postGid
     *
     * @return string $postGid
     */
    public function getPostGid()
    {
        return $this->post_gid;
    }

    /**
     * Set postGroupOwnerId
     *
     * @param int $postGroupOwnerId
     * @return self
     */
    public function setPostGroupOwnerId($postGroupOwnerId)
    {
        $this->post_group_owner_id = $postGroupOwnerId;
        return $this;
    }

    /**
     * Get postGroupOwnerId
     *
     * @return int $postGroupOwnerId
     */
    public function getPostGroupOwnerId()
    {
        return $this->post_group_owner_id;
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
     * @var Post\PostBundle\Document\PostRating
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
     * @param Post\PostBundle\Document\PostRating $rate
     */
    public function addRate(\Post\PostBundle\Document\PostRating $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param Post\PostBundle\Document\PostRating $rate
     */
    public function removeRate(\Post\PostBundle\Document\PostRating $rate)
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
     * Set taggedFriends
     *
     * @param collection $taggedFriends
     * @return self
     */
    public function setTaggedFriends($taggedFriends)
    {
        $this->tagged_friends = $taggedFriends;
        return $this;
    }

    /**
     * Get taggedFriends
     *
     * @return collection $taggedFriends
     */
    public function getTaggedFriends()
    {
        return $this->tagged_friends;
    }
    /**
     * @var hash $content_share
     */
    protected $content_share = null;

    /**
     * @var string $share_object_id
     */
    protected $share_object_id = 0;

    /**
     * @var string $share_object_type
     */
    protected $share_object_type = '';


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
    /**
     * @var string $share_type
     */
    protected $share_type;


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
}
