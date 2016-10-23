<?php
namespace Notification\NotificationBundle\Document;



/**
 * Notification\NotificationBundle\Document\UserNotifications
 */
class UserNotifications
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $from
     */
    protected $from;

    /**
     * @var string $to
     */
    protected $to;

    /**
     * @var string $item_id
     */
    protected $item_id;

    /**
     * @var string $message_type
     */
    protected $message_type;

    /**
     * @var string $message
     */
    protected $message;

    /**
     * @var date $date
     */
    protected $date;

    /**
     * @var string $is_read
     */
    protected $is_read;
    
    /**
     * @var hash $info
     */
    protected $info = array();


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
     * Set from
     *
     * @param string $from
     * @return self
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Get from
     *
     * @return string $from
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set to
     *
     * @param string $to
     * @return self
     */
    public function setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Get to
     *
     * @return string $to
     */
    public function getTo()
    {
        return $this->to;
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
     * Set messageType
     *
     * @param string $messageType
     * @return self
     */
    public function setMessageType($messageType)
    {
        $this->message_type = $messageType;
        return $this;
    }

    /**
     * Get messageType
     *
     * @return string $messageType
     */
    public function getMessageType()
    {
        return $this->message_type;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return self
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get message
     *
     * @return string $message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set date
     *
     * @param date $date
     * @return self
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Get date
     *
     * @return date $date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set isRead
     *
     * @param string $isRead
     * @return self
     */
    public function setIsRead($isRead)
    {
        $this->is_read = $isRead;
        return $this;
    }

    /**
     * Get isRead
     *
     * @return string $isRead
     */
    public function getIsRead()
    {
        return $this->is_read;
    }
    /**
     * @var string $message_status
     */
    protected $message_status;


    /**
     * Set messageStatus
     *
     * @param string $messageStatus
     * @return self
     */
    public function setMessageStatus($messageStatus)
    {
        $this->message_status = $messageStatus;
        return $this;
    }

    /**
     * Get messageStatus
     *
     * @return string $messageStatus
     */
    public function getMessageStatus()
    {
        return $this->message_status;
    }
    /**
     * @var int $is_view
     */
    protected $is_view = 0;


    /**
     * Set isView
     *
     * @param int $isView
     * @return self
     */
    public function setIsView($isView)
    {
        $this->is_view = $isView;
        return $this;
    }

    /**
     * Get isView
     *
     * @return int $isView
     */
    public function getIsView()
    {
        return $this->is_view;
    }
    
    /**
     * @var int $notification_role
     */
    protected $notification_role = 3;


    /**
     * Set notificationRole
     *
     * @param int $notificationRole
     * @return self
     */
    public function setNotificationRole($notificationRole)
    {
        $this->notification_role = $notificationRole;
        return $this;
    }

    /**
     * Get notificationRole
     *
     * @return int $notificationRole
     */
    public function getNotificationRole()
    {
        return $this->notification_role;
    }
    
    /**
     * Set info
     *
     * @param array $info
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
     * @return array $info
     */
    public function getInfo()
    {
        return $this->info;
    }
}
