<?php
namespace Acme\DemoBundle\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongodb;
/**
 * @mongodb\Document(collection="users")
 */
class Check
{
    /**
     * @mongodb\Id
     */
    protected $id;

    /**
     * @mongodb\Field(type="string")
     */
    protected $name;

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }
}
