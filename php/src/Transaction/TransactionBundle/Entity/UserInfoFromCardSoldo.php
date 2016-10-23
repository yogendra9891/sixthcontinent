<?php

namespace Transaction\TransactionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserInfoFromCardSoldo
 */
class UserInfoFromCardSoldo
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $userId;

    /**
     * @var string
     */
    private $descrizione;

    /**
     * @var integer
     */
    private $saldoc;

    /**
     * @var integer
     */
    private $saldorc;

    /**
     * @var integer
     */
    private $saldorm;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $updatedAt;


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
     * Set userId
     *
     * @param integer $userId
     * @return UserInfoFromCardSoldo
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set descrizione
     *
     * @param string $descrizione
     * @return UserInfoFromCardSoldo
     */
    public function setDescrizione($descrizione)
    {
        $this->descrizione = $descrizione;
    
        return $this;
    }

    /**
     * Get descrizione
     *
     * @return string 
     */
    public function getDescrizione()
    {
        return $this->descrizione;
    }

    /**
     * Set saldoc
     *
     * @param integer $saldoc
     * @return UserInfoFromCardSoldo
     */
    public function setSaldoc($saldoc)
    {
        $this->saldoc = $saldoc;
    
        return $this;
    }

    /**
     * Get saldoc
     *
     * @return integer 
     */
    public function getSaldoc()
    {
        return $this->saldoc;
    }

    /**
     * Set saldorc
     *
     * @param integer $saldorc
     * @return UserInfoFromCardSoldo
     */
    public function setSaldorc($saldorc)
    {
        $this->saldorc = $saldorc;
    
        return $this;
    }

    /**
     * Get saldorc
     *
     * @return integer 
     */
    public function getSaldorc()
    {
        return $this->saldorc;
    }

    /**
     * Set saldorm
     *
     * @param integer $saldorm
     * @return UserInfoFromCardSoldo
     */
    public function setSaldorm($saldorm)
    {
        $this->saldorm = $saldorm;
    
        return $this;
    }

    /**
     * Get saldorm
     *
     * @return integer 
     */
    public function getSaldorm()
    {
        return $this->saldorm;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserInfoFromCardSoldo
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return UserInfoFromCardSoldo
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
