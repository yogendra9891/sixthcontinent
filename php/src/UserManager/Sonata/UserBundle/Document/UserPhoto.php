<?php

namespace UserManager\Sonata\UserBundle\Document;



/**
 * UserManager\Sonata\UserBundle\Document\UserPhoto
 */
class UserPhoto
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
     * @var int $profile_type
     */
    protected $profile_type;

    /**
     * @var string $photo_id
     */
    protected $photo_id;

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
     * Set profileType
     *
     * @param int $profileType
     * @return self
     */
    public function setProfileType($profileType)
    {
        $this->profile_type = $profileType;
        return $this;
    }

    /**
     * Get profileType
     *
     * @return int $profileType
     */
    public function getProfileType()
    {
        return $this->profile_type;
    }

    /**
     * Set photoId
     *
     * @param string $photoId
     * @return self
     */
    public function setPhotoId($photoId)
    {
        $this->photo_id = $photoId;
        return $this;
    }

    /**
     * Get photoId
     *
     * @return string $photoId
     */
    public function getPhotoId()
    {
        return $this->photo_id;
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
}
