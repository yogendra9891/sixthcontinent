<?php

namespace UserManager\Sonata\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BlacklistIP
 */
class BlacklistIP
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $ipAddress;

    /**
     * @var integer
     */
    private $id;


    /**
     * Set identifier
     *
     * @param string $identifier
     * @return BlacklistIP
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    
        return $this;
    }

    /**
     * Get identifier
     *
     * @return string 
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return BlacklistIP
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    
        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string 
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
}
