<?php

namespace UserManager\Sonata\UserBundle\Document;



/**
 * UserManager\Sonata\UserBundle\Document\BusinessKeyword
 */
class BusinessKeyword
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $category_id
     */
    protected $category_id;

    /**
     * @var string $keyword
     */
    protected $keyword;


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
     * Set categoryId
     *
     * @param int $categoryId
     * @return self
     */
    public function setCategoryId($categoryId)
    {
        $this->category_id = $categoryId;
        return $this;
    }

    /**
     * Get categoryId
     *
     * @return int $categoryId
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set keyword
     *
     * @param string $keyword
     * @return self
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;
        return $this;
    }

    /**
     * Get keyword
     *
     * @return string $keyword
     */
    public function getKeyword()
    {
        return $this->keyword;
    }
}
