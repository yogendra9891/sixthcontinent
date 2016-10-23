<?php

namespace StoreManager\PostBundle\Document;



/**
 * StoreManager\PostBundle\Document\StorePosts
 */
class StorePosts
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $store_id
     */
    protected $store_id;

    /**
     * @var string $store_post_title
     */
    protected $store_post_title;

    /**
     * @var string $store_post_desc
     */
    protected $store_post_desc;

    /**
     * @var int $link_type
     */
    protected $link_type;

    /**
     * @var string $store_post_author
     */
    protected $store_post_author;

    /**
     * @var date $store_post_created
     */
    protected $store_post_created;

    /**
     * @var date $store_post_updated
     */
    protected $store_post_updated;

    /**
     * @var int $store_post_status
     */
    protected $store_post_status;

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
     * @var StoreManager\PostBundle\Document\StorePostsRating
     */
    protected $rate = array();
    
    /**
     * @var collection $tagged_friends
     */
    protected $tagged_friends;

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
     * Set storeId
     *
     * @param string $storeId
     * @return self
     */
    public function setStoreId($storeId)
    {
        $this->store_id = $storeId;
        return $this;
    }

    /**
     * Get storeId
     *
     * @return string $storeId
     */
    public function getStoreId()
    {
        return $this->store_id;
    }

    /**
     * Set storePostTitle
     *
     * @param string $storePostTitle
     * @return self
     */
    public function setStorePostTitle($storePostTitle)
    {
        $this->store_post_title = $storePostTitle;
        return $this;
    }

    /**
     * Get storePostTitle
     *
     * @return string $storePostTitle
     */
    public function getStorePostTitle()
    {
        return $this->store_post_title;
    }

    /**
     * Set storePostDesc
     *
     * @param string $storePostDesc
     * @return self
     */
    public function setStorePostDesc($storePostDesc)
    {
        $this->store_post_desc = $storePostDesc;
        return $this;
    }

    /**
     * Get storePostDesc
     *
     * @return string $storePostDesc
     */
    public function getStorePostDesc()
    {
        return $this->store_post_desc;
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
     * Set storePostAuthor
     *
     * @param string $storePostAuthor
     * @return self
     */
    public function setStorePostAuthor($storePostAuthor)
    {
        $this->store_post_author = $storePostAuthor;
        return $this;
    }

    /**
     * Get storePostAuthor
     *
     * @return string $storePostAuthor
     */
    public function getStorePostAuthor()
    {
        return $this->store_post_author;
    }

    /**
     * Set storePostCreated
     *
     * @param date $storePostCreated
     * @return self
     */
    public function setStorePostCreated($storePostCreated)
    {
        $this->store_post_created = $storePostCreated;
        return $this;
    }

    /**
     * Get storePostCreated
     *
     * @return date $storePostCreated
     */
    public function getStorePostCreated()
    {
        return $this->store_post_created;
    }

    /**
     * Set storePostUpdated
     *
     * @param date $storePostUpdated
     * @return self
     */
    public function setStorePostUpdated($storePostUpdated)
    {
        $this->store_post_updated = $storePostUpdated;
        return $this;
    }

    /**
     * Get storePostUpdated
     *
     * @return date $storePostUpdated
     */
    public function getStorePostUpdated()
    {
        return $this->store_post_updated;
    }

    /**
     * Set storePostStatus
     *
     * @param int $storePostStatus
     * @return self
     */
    public function setStorePostStatus($storePostStatus)
    {
        $this->store_post_status = $storePostStatus;
        return $this;
    }

    /**
     * Get storePostStatus
     *
     * @return int $storePostStatus
     */
    public function getStorePostStatus()
    {
        return $this->store_post_status;
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
     * @param StoreManager\PostBundle\Document\StorePostsRating $rate
     */
    public function addRate(\StoreManager\PostBundle\Document\StorePostsRating $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param StoreManager\PostBundle\Document\StorePostsRating $rate
     */
    public function removeRate(\StoreManager\PostBundle\Document\StorePostsRating $rate)
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
     * @var string $share_type
     */
    protected $share_type = '';


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
     * @var float $store_voting_avg
     */
    protected $store_voting_avg = 0;

    /**
     * @var int $store_voting_count
     */
    protected $store_voting_count = 0;


    /**
     * Set storeVotingAvg
     *
     * @param float $storeVotingAvg
     * @return self
     */
    public function setStoreVotingAvg($storeVotingAvg)
    {
        $this->store_voting_avg = $storeVotingAvg;
        return $this;
    }

    /**
     * Get storeVotingAvg
     *
     * @return float $storeVotingAvg
     */
    public function getStoreVotingAvg()
    {
        return $this->store_voting_avg;
    }

    /**
     * Set storeVotingCount
     *
     * @param int $storeVotingCount
     * @return self
     */
    public function setStoreVotingCount($storeVotingCount)
    {
        $this->store_voting_count = $storeVotingCount;
        return $this;
    }

    /**
     * Get storeVotingCount
     *
     * @return int $storeVotingCount
     */
    public function getStoreVotingCount()
    {
        return $this->store_voting_count;
    }
    /**
     * @var float $customer_voting
     */
    protected $customer_voting = 0;


    /**
     * Set customerVoting
     *
     * @param float $customerVoting
     * @return self
     */
    public function setCustomerVoting($customerVoting)
    {
        $this->customer_voting = $customerVoting;
        return $this;
    }

    /**
     * Get customerVoting
     *
     * @return float $customerVoting
     */
    public function getCustomerVoting()
    {
        return $this->customer_voting;
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
     * @var string $transaction_id
     */
    protected $transaction_id = '';


    /**
     * Set transactionId
     *
     * @param string $transactionId
     * @return self
     */
    public function setTransactionId($transactionId)
    {
        $this->transaction_id = $transactionId;
        return $this;
    }

    /**
     * Get transactionId
     *
     * @return string $transactionId
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }
    /**
     * @var string $invoice_id
     */
    protected $invoice_id = '';


    /**
     * Set invoiceId
     *
     * @param string $invoiceId
     * @return self
     */
    public function setInvoiceId($invoiceId)
    {
        $this->invoice_id = $invoiceId;
        return $this;
    }

    /**
     * Get invoiceId
     *
     * @return string $invoiceId
     */
    public function getInvoiceId()
    {
        return $this->invoice_id;
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
}
