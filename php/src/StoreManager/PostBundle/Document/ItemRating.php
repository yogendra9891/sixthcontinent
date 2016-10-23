<?php

namespace StoreManager\PostBundle\Document;



/**
 * StoreManager\PostBundle\Document\ItemRating
 */
class ItemRating
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
     * @var string $item_type
     */
    protected $item_type;
    
    /**
     * @var collection $tagged_friends
     */
    protected $tagged_friends;

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
     * @var StoreManager\PostBundle\Document\ItemRatingRate
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
     * Set itemType
     *
     * @param string $itemType
     * @return self
     */
    public function setItemType($itemType)
    {
        $this->item_type = $itemType;
        return $this;
    }

    /**
     * Get itemType
     *
     * @return string $itemType
     */
    public function getItemType()
    {
        return $this->item_type;
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
     * @param StoreManager\PostBundle\Document\ItemRatingRate $rate
     */
    public function addRate(\StoreManager\PostBundle\Document\ItemRatingRate $rate)
    {
        $this->rate[] = $rate;
    }

    /**
     * Remove rate
     *
     * @param StoreManager\PostBundle\Document\ItemRatingRate $rate
     */
    public function removeRate(\StoreManager\PostBundle\Document\ItemRatingRate $rate)
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
}
