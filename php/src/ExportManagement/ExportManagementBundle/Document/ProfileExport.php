<?php

namespace ExportManagement\ExportManagementBundle\Document;



/**
 * ExportManagement\ExportManagementBundle\Document\ProfileExport
 */
class ProfileExport
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
     * @var string $type
     */
    protected $type;


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
}
