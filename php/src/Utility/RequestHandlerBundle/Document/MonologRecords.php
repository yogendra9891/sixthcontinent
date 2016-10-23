<?php

namespace Utility\RequestHandlerBundle\Document;
/**
 * Utility\RequestHandlerBundle\Document\MonologRecords
 */
class MonologRecords
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $message
     */
    protected $message;

    /**
     * @var int $level
     */
    protected $level;

    /**
     * @var string $level_name
     */
    protected $level_name;

    /**
     * @var string $channel
     */
    protected $channel;

    /**
     * @var date $datetime
     */
    protected $datetime;
    
    /**
     * @var Utility\RequestHandlerBundle\Document\MonologRecordsContext
     */
    protected $context = array();


    /**
     * @var Utility\RequestHandlerBundle\Document\MonologRecordsExtra
     */
    protected $extra = array();


    public function __construct()
    {
        $this->context = new \Doctrine\Common\Collections\ArrayCollection();
        $this->extra = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add context
     *
     * @param Utility\RequestHandlerBundle\Document\MonologRecordsContext $context
     */
    public function addContext(\Utility\RequestHandlerBundle\Document\MonologRecordsContext $context)
    {
        $this->context[] = $context;
    }

    /**
     * Remove context
     *
     * @param Utility\RequestHandlerBundle\Document\MonologRecordsContext $context
     */
    public function removeContext(\Utility\RequestHandlerBundle\Document\MonologRecordsContext $context)
    {
        $this->context->removeElement($context);
    }  

    /**
     * Get context
     * @return string $context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set level
     *
     * @param int $level
     * @return self
     */
    public function setLevel($level)
    {
        $this->level = $level;
        return $this;
    }

    /**
     * Get level
     *
     * @return int $level
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set levelName
     *
     * @param string $levelName
     * @return self
     */
    public function setLevelName($levelName)
    {
        $this->level_name = $levelName;
        return $this;
    }

    /**
     * Get levelName
     *
     * @return string $levelName
     */
    public function getLevelName()
    {
        return $this->level_name;
    }

    /**
     * Set channel
     *
     * @param string $channel
     * @return self
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * Get channel
     *
     * @return string $channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set datetime
     *
     * @param date $datetime
     * @return self
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;
        return $this;
    }

    /**
     * Get datetime
     *
     * @return date $datetime
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * Add extra
     *
     * @param Utility\RequestHandlerBundle\Document\MonologRecordsExtra $extra
     */
    public function addExtra(\Utility\RequestHandlerBundle\Document\MonologRecordsExtra $extra)
    {
        $this->extra[] = $extra;
    }

    /**
     * Remove extra
     *
     * @param Utility\RequestHandlerBundle\Document\MonologRecordsExtra $extra
     */
    public function removeExtra(\Utility\RequestHandlerBundle\Document\MonologRecordsExtra $extra)
    {
        $this->extra->removeElement($extra);
    }

    /**
     * Get extra
     *
     * @return Doctrine\Common\Collections\Collection $extra
     */
    public function getExtra()
    {
        return $this->extra;
    }
}
