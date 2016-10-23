<?php

namespace PostFeeds\PostFeedsBundle\Document;



/**
 * PostFeeds\PostFeedsBundle\Document\ClubTagFeeds
 */
class ClubTagFeeds
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var collection $club_info
     */
    protected $club_info;

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
     * Set clubInfo
     *
     * @param collection $clubInfo
     * @return self
     */
    public function setClubInfo($clubInfo)
    {
        $this->club_info = $clubInfo;
        return $this;
    }

    /**
     * Get clubInfo
     *
     * @return collection $clubInfo
     */
    public function getClubInfo()
    {
        return $this->club_info;
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
