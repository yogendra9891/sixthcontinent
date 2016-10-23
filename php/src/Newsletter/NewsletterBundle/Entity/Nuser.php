<?php

namespace Newsletter\NewsletterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Nuser
 */
class Nuser
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $username;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return Nuser
     */
    public function setUsername($username)
    {
        $this->username = $username;
    
        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }
    
     /**
     * @var string
     */
    protected $email;
     public function getEmail()
    {
        return $this->email;
    }
    
     public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }
}
