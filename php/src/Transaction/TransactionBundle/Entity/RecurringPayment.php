<?php

namespace Transaction\TransactionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RecurringPayment
 */
class RecurringPayment
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $shopId;

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
    private $codiceEsito;

    /**
     * @var string
     */
    private $descrizioneEsito;

    /**
     * @var string
     */
    private $mac;


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
     * Set shopId
     *
     * @param integer $shopId
     * @return RecurringPayment
     */
    public function setShopId($shopId)
    {
        $this->shopId = $shopId;
    
        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer 
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * Set tipoCarta
     *
     * @param string $tipoCarta
     * @return RecurringPayment
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
     * @return RecurringPayment
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
     * Set tipoProdotto
     *
     * @param string $tipoProdotto
     * @return RecurringPayment
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
     * @return RecurringPayment
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
     * @return RecurringPayment
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
     * @return RecurringPayment
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
     * Set codiceEsito
     *
     * @param integer $codiceEsito
     * @return RecurringPayment
     */
    public function setCodiceEsito($codiceEsito)
    {
        $this->codiceEsito = $codiceEsito;
    
        return $this;
    }

    /**
     * Get codiceEsito
     *
     * @return integer 
     */
    public function getCodiceEsito()
    {
        return $this->codiceEsito;
    }

    /**
     * Set descrizioneEsito
     *
     * @param string $descrizioneEsito
     * @return RecurringPayment
     */
    public function setDescrizioneEsito($descrizioneEsito)
    {
        $this->descrizioneEsito = $descrizioneEsito;
    
        return $this;
    }

    /**
     * Get descrizioneEsito
     *
     * @return string 
     */
    public function getDescrizioneEsito()
    {
        return $this->descrizioneEsito;
    }

    /**
     * Set mac
     *
     * @param string $mac
     * @return RecurringPayment
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
     * @var \DateTime
     */
    private $created_at;


    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return RecurringPayment
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
    
        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }
    /**
     * @var integer
     */
    private $transactionId;


    /**
     * Set transactionId
     *
     * @param integer $transactionId
     * @return RecurringPayment
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    
        return $this;
    }

    /**
     * Get transactionId
     *
     * @return integer 
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }
    /**
     * @var float
     */
    private $amount;


    /**
     * Set amount
     *
     * @param float $amount
     * @return RecurringPayment
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    
        return $this;
    }

    /**
     * Get amount
     *
     * @return float 
     */
    public function getAmount()
    {
        return $this->amount;
    }
    /**
     * @var string
     */
    private $codTrans;


    /**
     * Set codTrans
     *
     * @param string $codTrans
     * @return RecurringPayment
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
}