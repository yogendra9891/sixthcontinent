<?php

namespace Acme\GiftBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Movimen
 */
class Movimen
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $iDMOVIMENTO;

    /**
     * @var string
     */
    private $iDCARD;

    /**
     * @var string
     */
    private $iDPDV;

    /**
     * @var string
     */
    private $iMPORTODIGITATO;

    /**
     * @var string
     */
    private $cREDITOSTORNATO;

    /**
     * @var string
     */
    private $dATA;

    /**
     * @var string
     */
    private $rCUTI;

    /**
     * @var string
     */
    private $sHUTI;

    /**
     * @var string
     */
    private $pSUTI;

    /**
     * @var string
     */
    private $gCUTI;

    /**
     * @var string
     */
    private $gCRIM;
	
	
    /**
     * @var string
     */
    private $mOUTI;


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
     * Set iDMOVIMENTO
     *
     * @param string $iDMOVIMENTO
     * @return Movimen
     */
    public function setIDMOVIMENTO($iDMOVIMENTO)
    {
        $this->iDMOVIMENTO = $iDMOVIMENTO;
    
        return $this;
    }

    /**
     * Get iDMOVIMENTO
     *
     * @return string 
     */
    public function getIDMOVIMENTO()
    {
        return $this->iDMOVIMENTO;
    }

    /**
     * Set iDCARD
     *
     * @param string $iDCARD
     * @return Movimen
     */
    public function setIDCARD($iDCARD)
    {
        $this->iDCARD = $iDCARD;
    
        return $this;
    }

    /**
     * Get iDCARD
     *
     * @return string 
     */
    public function getIDCARD()
    {
        return $this->iDCARD;
    }

    /**
     * Set iDPDV
     *
     * @param string $iDPDV
     * @return Movimen
     */
    public function setIDPDV($iDPDV)
    {
        $this->iDPDV = $iDPDV;
    
        return $this;
    }

    /**
     * Get iDPDV
     *
     * @return string 
     */
    public function getIDPDV()
    {
        return $this->iDPDV;
    }

    /**
     * Set iMPORTODIGITATO
     *
     * @param string $iMPORTODIGITATO
     * @return Movimen
     */
    public function setIMPORTODIGITATO($iMPORTODIGITATO)
    {
        $this->iMPORTODIGITATO = $iMPORTODIGITATO;
    
        return $this;
    }

    /**
     * Get iMPORTODIGITATO
     *
     * @return string 
     */
    public function getIMPORTODIGITATO()
    {
        return $this->iMPORTODIGITATO;
    }

    /**
     * Set cREDITOSTORNATO
     *
     * @param string $cREDITOSTORNATO
     * @return Movimen
     */
    public function setCREDITOSTORNATO($cREDITOSTORNATO)
    {
        $this->cREDITOSTORNATO = $cREDITOSTORNATO;
    
        return $this;
    }

    /**
     * Get cREDITOSTORNATO
     *
     * @return string 
     */
    public function getCREDITOSTORNATO()
    {
        return $this->cREDITOSTORNATO;
    }

    /**
     * Set dATA
     *
     * @param string $dATA
     * @return Movimen
     */
    public function setDATA($dATA)
    {
        $this->dATA = $dATA;
    
        return $this;
    }

    /**
     * Get dATA
     *
     * @return string
     */
    public function getDATA()
    {
        return $this->dATA;
    }

    /**
     * Set rCUTI
     *
     * @param string $rCUTI
     * @return Movimen
     */
    public function setRCUTI($rCUTI)
    {
        $this->rCUTI = $rCUTI;
    
        return $this;
    }

    /**
     * Get rCUTI
     *
     * @return string 
     */
    public function getRCUTI()
    {
        return $this->rCUTI;
    }

    /**
     * Set sHUTI
     *
     * @param string $sHUTI
     * @return Movimen
     */
    public function setSHUTI($sHUTI)
    {
        $this->sHUTI = $sHUTI;
    
        return $this;
    }

    /**
     * Get sHUTI
     *
     * @return string 
     */
    public function getSHUTI()
    {
        return $this->sHUTI;
    }

    /**
     * Set pSUTI
     *
     * @param string $pSUTI
     * @return Movimen
     */
    public function setPSUTI($pSUTI)
    {
        $this->pSUTI = $pSUTI;
    
        return $this;
    }

    /**
     * Get pSUTI
     *
     * @return string 
     */
    public function getPSUTI()
    {
        return $this->pSUTI;
    }

    /**
     * Set gCUTI
     *
     * @param string $gCUTI
     * @return Movimen
     */
    public function setGCUTI($gCUTI)
    {
        $this->gCUTI = $gCUTI;
    
        return $this;
    }

    /**
     * Get gCUTI
     *
     * @return string 
     */
    public function getGCUTI()
    {
        return $this->gCUTI;
    }

    /**
     * Set gCRIM
     *
     * @param string $gCRIM
     * @return Movimen
     */
    public function setGCRIM($gCRIM)
    {
        $this->gCRIM = $gCRIM;
    
        return $this;
    }

    /**
     * Get gCRIM
     *
     * @return string 
     */
    public function getGCRIM()
    {
        return $this->gCRIM;
    }
	
	/**
     * Set mOUTI
     *
     * @param string $mOUTI
     * @return Movimen
     */
    public function setMOUTI($mOUTI)
    {
        $this->mOUTI = $mOUTI;
    
        return $this;
    }

    /**
     * Get mOUTI
     *
     * @return string 
     */
    public function getMOUTI()
    {
        return $this->mOUTI;
    }
}