<?php

namespace CardManagement\CardManagementBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SubscriptionCartasiLog
 */
class SubscriptionCartasiLog
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $contractTxnId;

    /**
     * @var \DateTime
     */
    private $paymentDate;

    /**
     * @var string
     */
    private $tipoCarta;

    /**
     * @var string
     */
    private $paese;

    /**
     * @var string
     */
    private $codTrans;

    /**
     * @var string
     */
    private $tipoProdotto;

    /**
     * @var string
     */
    private $tipoTransazione;

    /**
     * @var string
     */
    private $codiceAutorizzazione;

    /**
     * @var string
     */
    private $dataOra;

    /**
     * @var integer
     */
    private $codiceEstio;

    /**
     * @var string
     */
    private $descrizioneEstio;

    /**
     * @var string
     */
    private $mac;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var integer
     */
    private $contractId;

    /**
     * @var integer
     */
    private $subscriptionId;


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
     * Set contractTxnId
     *
     * @param string $contractTxnId
     * @return SubscriptionCartasiLog
     */
    public function setContractTxnId($contractTxnId)
    {
        $this->contractTxnId = $contractTxnId;
    
        return $this;
    }

    /**
     * Get contractTxnId
     *
     * @return string 
     */
    public function getContractTxnId()
    {
        return $this->contractTxnId;
    }

    /**
     * Set paymentDate
     *
     * @param \DateTime $paymentDate
     * @return SubscriptionCartasiLog
     */
    public function setPaymentDate($paymentDate)
    {
        $this->paymentDate = $paymentDate;
    
        return $this;
    }

    /**
     * Get paymentDate
     *
     * @return \DateTime 
     */
    public function getPaymentDate()
    {
        return $this->paymentDate;
    }

    /**
     * Set tipoCarta
     *
     * @param string $tipoCarta
     * @return SubscriptionCartasiLog
     */
    public function setTipoCarta($tipoCarta)
    {
        $this->tipoCarta = $tipoCarta;
    
        return $this;
    }

    /**
     * Get tipoCarta
     *
     * @return string 
     */
    public function getTipoCarta()
    {
        return $this->tipoCarta;
    }

    /**
     * Set paese
     *
     * @param string $paese
     * @return SubscriptionCartasiLog
     */
    public function setPaese($paese)
    {
        $this->paese = $paese;
    
        return $this;
    }

    /**
     * Get paese
     *
     * @return string 
     */
    public function getPaese()
    {
        return $this->paese;
    }

    /**
     * Set codTrans
     *
     * @param string $codTrans
     * @return SubscriptionCartasiLog
     */
    public function setCodTrans($codTrans)
    {
        $this->codTrans = $codTrans;
    
        return $this;
    }

    /**
     * Get codTrans
     *
     * @return string 
     */
    public function getCodTrans()
    {
        return $this->codTrans;
    }

    /**
     * Set tipoProdotto
     *
     * @param string $tipoProdotto
     * @return SubscriptionCartasiLog
     */
    public function setTipoProdotto($tipoProdotto)
    {
        $this->tipoProdotto = $tipoProdotto;
    
        return $this;
    }

    /**
     * Get tipoProdotto
     *
     * @return string 
     */
    public function getTipoProdotto()
    {
        return $this->tipoProdotto;
    }

    /**
     * Set tipoTransazione
     *
     * @param string $tipoTransazione
     * @return SubscriptionCartasiLog
     */
    public function setTipoTransazione($tipoTransazione)
    {
        $this->tipoTransazione = $tipoTransazione;
    
        return $this;
    }

    /**
     * Get tipoTransazione
     *
     * @return string 
     */
    public function getTipoTransazione()
    {
        return $this->tipoTransazione;
    }

    /**
     * Set codiceAutorizzazione
     *
     * @param string $codiceAutorizzazione
     * @return SubscriptionCartasiLog
     */
    public function setCodiceAutorizzazione($codiceAutorizzazione)
    {
        $this->codiceAutorizzazione = $codiceAutorizzazione;
    
        return $this;
    }

    /**
     * Get codiceAutorizzazione
     *
     * @return string 
     */
    public function getCodiceAutorizzazione()
    {
        return $this->codiceAutorizzazione;
    }

    /**
     * Set dataOra
     *
     * @param string $dataOra
     * @return SubscriptionCartasiLog
     */
    public function setDataOra($dataOra)
    {
        $this->dataOra = $dataOra;
    
        return $this;
    }

    /**
     * Get dataOra
     *
     * @return string 
     */
    public function getDataOra()
    {
        return $this->dataOra;
    }

    /**
     * Set codiceEstio
     *
     * @param integer $codiceEstio
     * @return SubscriptionCartasiLog
     */
    public function setCodiceEstio($codiceEstio)
    {
        $this->codiceEstio = $codiceEstio;
    
        return $this;
    }

    /**
     * Get codiceEstio
     *
     * @return integer 
     */
    public function getCodiceEstio()
    {
        return $this->codiceEstio;
    }

    /**
     * Set descrizioneEstio
     *
     * @param string $descrizioneEstio
     * @return SubscriptionCartasiLog
     */
    public function setDescrizioneEstio($descrizioneEstio)
    {
        $this->descrizioneEstio = $descrizioneEstio;
    
        return $this;
    }

    /**
     * Get descrizioneEstio
     *
     * @return string 
     */
    public function getDescrizioneEstio()
    {
        return $this->descrizioneEstio;
    }

    /**
     * Set mac
     *
     * @param string $mac
     * @return SubscriptionCartasiLog
     */
    public function setMac($mac)
    {
        $this->mac = $mac;
    
        return $this;
    }

    /**
     * Get mac
     *
     * @return string 
     */
    public function getMac()
    {
        return $this->mac;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return SubscriptionCartasiLog
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
     * Set contractId
     *
     * @param integer $contractId
     * @return SubscriptionCartasiLog
     */
    public function setContractId($contractId)
    {
        $this->contractId = $contractId;
    
        return $this;
    }

    /**
     * Get contractId
     *
     * @return integer 
     */
    public function getContractId()
    {
        return $this->contractId;
    }

    /**
     * Set subscriptionId
     *
     * @param integer $subscriptionId
     * @return SubscriptionCartasiLog
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    
        return $this;
    }

    /**
     * Get subscriptionId
     *
     * @return integer 
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }
}
