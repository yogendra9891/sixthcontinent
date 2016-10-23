<?php

namespace StoreManager\StoreBundle\Document;



/**
 * StoreManager\StoreBundle\Document\StoreOwnerNotification
 */
class StoreOwnerNotification
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var int $store_id
     */
    protected $store_id;


    /**
     * @var int $store_owner_id
     */
    protected $store_owner_id;

    /**
     * @var string $mail_for
     */
    protected $mail_for;
    
    /**
     * @var int $is_mail_send
     */
    protected $is_mail_send;
    
    /**
     * @var \DateTime
     */
    protected $mail_send_on;




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
     * @param int $storeId
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
     * @return int $storeId
     */
    public function getStoreId()
    {
        return $this->store_id;
    }

    /**
     * Set storeOwnerId
     *
     * @param int $storeOwnerId
     * @return self
     */
    public function setStoreOwnerId($storeOwnerId)
    {
        $this->store_owner_id = $storeOwnerId;
        return $this;
    }

    /**
     * Get getStoreOwnerId
     *
     * @return int $StoreOwnerId
     */
    public function getStoreOwnerId()
    {
        return $this->store_owner_id;
    }

    /**
     * Set isMailSend
     *
     * @param int $isMailSend
     * @return self
     */
    public function setIsMailSend($isMailSend)
    {
        $this->is_mail_send = $isMailSend;
        return $this;
    }

    /**
     * Get getIsMailSend
     *
     * @return int $IsMailSend
     */
    public function getIsMailSend()
    {
        return $this->is_mail_send;
    }
    
    /**
     * Set mailFor
     *
     * @param string $mailFor
     * @return self
     */
    public function setMailFor($mailFor)
    {
        $this->mail_for = $mailFor;
        return $this;
    }

    /**
     * Get mailFor
     *
     * @return string $mailFor
     */
    public function getMailFor()
    {
        return $this->mail_for;
    }


    /**
     * Set mailSendOn
     *
     * @param \DateTime  $mailSendOn
     * @return self
     */
    public function setMailSendOn($mailSendOn)
    {
        $this->mail_send_on = $mailSendOn;
        return $this;
    }

    /**
     * Get mailSendOn
     *
     * @return \DateTime $mailSendOn
     */
    public function getMailSendOn()
    {
        return $this->mail_send_on;
    }

}
