<?php

namespace Dashboard\DashboardManagerBundle\Document;
use Dashboard\DashboardManagerBundle\Document\DashboardPostRating as DashboardPostRating;


/**
 * Dashboard\DashboardManagerBundle\Document\DashboardPost
 */
class DashboardPost
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $user_id
     */
    protected $user_id;

    /**
     * @var string $to_id
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
     * @var string $is_active
     */
    protected $is_active;
    
    /**
     * @var int $privacy_setting
     */
    protected $privacy_setting = 1;

    /**
     * @var date $created_date
     */
    protected $created_date;

    /**
     * @var collection $tagged_friends
     */
    protected $tagged_friends;

    /**
     * @varDocument\DashboardPostRating
     */
    protected $rate = array();
    
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
     * @param string $userId
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
     * @return string $userId
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set toId
     *
     * @param string $toId
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
     * @return string $toId
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
     * Set isActive
     *
     * @param string $isActive
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
     * @return string $isActive
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * Set privacySetting
     *
     * @param string $privacySetting
     * @return self
     */
    public function setprivacySetting($privacySetting)
    {
        $this->privacy_setting = $privacySetting;
        return $this;
    }

    /**
     * Get privacySetting
     *
     * @return string $privacySetting
     */
    public function getprivacySetting()
    {
        return $this->privacy_setting;
    }    
    /**
     * Set createdDate
     *
     * @param date $createdDate
     * @return self
     */
    public function setCreatedDate($createdDate)
    {
        $this->created_date = $createdDate;
        return $this;
    }

    /**
     * Get createdDate
     *
     * @return date $createdDate
     */
    public function getCreatedDate()
    {
        return $this->created_date;
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
     * Get rate
     */
    public function getRate()
    {
        return $this->rate;
    }


    public function __construct()
    {
        $this->rate = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add rate
     *
     * @param Dashboard\DashboardManagerBundle\Document\DashboardPostRating $rate
     */
    public function addRate(DashboardPostRating $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param Dashboard\DashboardManagerBundle\Document\DashboardPostRating $rate
     */
    public function removeRate(DashboardPostRating $rate)
    {
        $this->rate->removeElement($rate);
    }


    /**
     * @var string $share_type
     */
    protected $share_type = '';

    /**
     * @var float $store_voting_avg
     */
    protected $store_voting_avg = 0;

    /**
     * @var int $store_voting_count
     */
    protected $store_voting_count = 0;

    /**
     * @var float $customer_voting
     */
    protected $customer_voting = 0;

    /**
     * @var string $transaction_id
     */
    protected $transaction_id = '';

    /**
     * @var string $invoice_id
     */
    protected $invoice_id = '';

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
     * @var hash $info
     */
    protected $info = array();

    /**
     * Set info
     *
     * @param hash $info
     * @return self
     */
    public function setInfo($info)
    {
        $this->info = $info;
        return $this;
    }

    /**
     * Get info
     *
     * @return hash $info
     */
    public function getInfo()
    {
        return $this->info;
    }
    /**
     * @var collection $content_share
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
     * @param collection $contentShare
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
     * @return collection $contentShare
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
