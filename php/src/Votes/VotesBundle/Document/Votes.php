<?php
namespace Votes\VotesBundle\Document;

/**
 * Votes\VotesBundle\Document\Votes
 */
class Votes {
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $item_type
     */
    protected $item_type;
    
    /**
     * @var string $item_id
     */
    protected $item_id;
    
    /**
     * @var string $voter_id
     */
    protected $voter_id;
    
    /**
     * @var string $voter_profile_id
     */
    protected $voter_profile_id;
    
    /**
     * @var string $voter_type
     */
    protected $voter_type;
    
    /**
     * @var int $vote
     */
    protected $vote;
    
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
     * Set voterId
     *
     * @param string $voterId
     * @return self
     */
    public function setVoterId($voterId)
    {
        $this->voter_id = $voterId;
        return $this;
    }

    /**
     * Get voterId
     *
     * @return string $voterId
     */
    public function getVoterId()
    {
        return $this->voter_id;
    }

    /**
     * Set voterProfileId
     *
     * @param string $voterProfileId
     * @return self
     */
    public function setVoterProfileId($voterProfileId)
    {
        $this->voter_profile_id = $voterProfileId;
        return $this;
    }

    /**
     * Get voterProfileId
     *
     * @return string $voterProfileId
     */
    public function getVoterProfileId()
    {
        return $this->voter_profile_id;
    }

    /**
     * Set voterType
     *
     * @param string $voterType
     * @return self
     */
    public function setVoterType($voterType)
    {
        $this->voter_type = $voterType;
        return $this;
    }

    /**
     * Get voterType
     *
     * @return string $voterType
     */
    public function getVoterType()
    {
        return $this->voter_type;
    }

    /**
     * Set vote
     *
     * @param int $vote
     * @return self
     */
    public function setVote($vote)
    {
        $this->vote = $vote;
        return $this;
    }

    /**
     * Get vote
     *
     * @return int $vote
     */
    public function getVote()
    {
        return $this->vote;
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
}
