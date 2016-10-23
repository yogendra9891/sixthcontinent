<?php

namespace StoreManager\StoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TotoCitizenIncome
 */
class TotoCitizenIncome
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $identityId;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $avatar;

    /**
     * @var integer
     */
    private $numeroAffiliati;

    /**
     * @var string
     */
    private $totaleEconomia;

    /**
     * @var string
     */
    private $guadagnoAffiliazioni;

    /**
     * @var string
     */
    private $dataUpdate;

    /**
     * @var float
     */
    private $totalone;


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
     * Set identityId
     *
     * @param integer $identityId
     * @return TotoCitizenIncome
     */
    public function setIdentityId($identityId)
    {
        $this->identityId = $identityId;
    
        return $this;
    }

    /**
     * Get identityId
     *
     * @return integer 
     */
    public function getIdentityId()
    {
        return $this->identityId;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return TotoCitizenIncome
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    
        return $this;
    }

    /**
     * Get firstName
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return TotoCitizenIncome
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    
        return $this;
    }

    /**
     * Get lastName
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set avatar
     *
     * @param string $avatar
     * @return TotoCitizenIncome
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    
        return $this;
    }

    /**
     * Get avatar
     *
     * @return string 
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Set numeroAffiliati
     *
     * @param integer $numeroAffiliati
     * @return TotoCitizenIncome
     */
    public function setNumeroAffiliati($numeroAffiliati)
    {
        $this->numeroAffiliati = $numeroAffiliati;
    
        return $this;
    }

    /**
     * Get numeroAffiliati
     *
     * @return integer 
     */
    public function getNumeroAffiliati()
    {
        return $this->numeroAffiliati;
    }

    /**
     * Set totaleEconomia
     *
     * @param string $totaleEconomia
     * @return TotoCitizenIncome
     */
    public function setTotaleEconomia($totaleEconomia)
    {
        $this->totaleEconomia = $totaleEconomia;
    
        return $this;
    }

    /**
     * Get totaleEconomia
     *
     * @return string 
     */
    public function getTotaleEconomia()
    {
        return $this->totaleEconomia;
    }

    /**
     * Set guadangoAffiliazioni
     *
     * @param string $guadangoAffiliazioni
     * @return TotoCitizenIncome
     */
    public function setGuadagnoAffiliazioni($guadangoAffiliazioni)
    {
        $this->guadagnoAffiliazioni = $guadagnoAffiliazioni;
    
        return $this;
    }

    /**
     * Get guadangoAffiliazioni
     *
     * @return string 
     */
    public function getGuadagnoAffiliazioni()
    {
        return $this->guadagnoAffiliazioni;
    }

    /**
     * Set dateUpdate
     *
     * @param string $dataUpdate
     * @return TotoCitizenIncome
     */
    public function setDataUpdate($dataUpdate)
    {
        $this->dataUpdate = $dataUpdate;
    
        return $this;
    }

    /**
     * Get dateUpdate
     *
     * @return string 
     */
    public function getDataUpdate()
    {
        return $this->dataUpdate;
    }

    /**
     * Set totalone
     *
     * @param float $totalone
     * @return TotoCitizenIncome
     */
    public function setTotalone($totalone)
    {
        $this->totalone = $totalone;
    
        return $this;
    }

    /**
     * Get totalone
     *
     * @return float 
     */
    public function getTotalone()
    {
        return $this->totalone;
    }
}