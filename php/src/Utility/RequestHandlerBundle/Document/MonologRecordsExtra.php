<?php

namespace Utility\RequestHandlerBundle\Document;



/**
 *Utility\RequestHandlerBundle\Document\MonologRecordsExtra
 */
class MonologRecordsExtra
{
  
    /**
     * @var date $created_at
     */
    protected $created_at;


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
