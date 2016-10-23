<?php

namespace Newsletter\NewsletterBundle\Document;



/**
 * Newsletter\NewsletterBundle\Document\Template
 */
class Template
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $title
     */
    protected $title;

    /**
     * @var string $body
     */
    protected $body;

    /**
     * @var int $author_id
     */
    protected $author_id;

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
     * Set body
     *
     * @param string $body
     * @return self
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Get body
     *
     * @return string $body
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set authorId
     *
     * @param int $authorId
     * @return self
     */
    public function setAuthorId($authorId)
    {
        $this->author_id = $authorId;
        return $this;
    }

    /**
     * Get authorId
     *
     * @return int $authorId
     */
    public function getAuthorId()
    {
        return $this->author_id;
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