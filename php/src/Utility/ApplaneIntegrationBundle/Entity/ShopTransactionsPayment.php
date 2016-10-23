<?php

namespace Utility\ApplaneIntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShopTransactionsPayment
 */
class ShopTransactionsPayment
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
    private $pendingIds;

    /**
     * @var float
     */
    private $pendingAmount;

    /**
     * @var float
     */
    private $totalAmount;

    /**
     * @var string
     */
    private $contractTxnId;

    /**
     * @var string
     */
    private $payType;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var string
     */
    private $mode;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var integer
     */
    private $contractId;

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
     * @return ShopTransactionsPayment
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
     * Set pendingIds
     *
     * @param string $pendingIds
     * @return ShopTransactionsPayment
     */
    public function setPendingIds($pendingIds)
    {
        $this->pendingIds = $pendingIds;
    
        return $this;
    }

    /**
     * Get pendingIds
     *
     * @return string 
     */
    public function getPendingIds()
    {
        return $this->pendingIds;
    }

    /**
     * Set pendingAmount
     *
     * @param float $pendingAmount
     * @return ShopTransactionsPayment
     */
    public function setPendingAmount($pendingAmount)
    {
        $this->pendingAmount = $pendingAmount;
    
        return $this;
    }

    /**
     * Get pendingAmount
     *
     * @return float 
     */
    public function getPendingAmount()
    {
        return $this->pendingAmount;
    }

    /**
     * Set totalAmount
     *
     * @param float $totalAmount
     * @return ShopTransactionsPayment
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    
        return $this;
    }

    /**
     * Get totalAmount
     *
     * @return float 
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Set contractTxnId
     *
     * @param string $contractTxnId
     * @return ShopTransactionsPayment
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
     * Set payType
     *
     * @param string $payType
     * @return ShopTransactionsPayment
     */
    public function setPayType($payType)
    {
        $this->payType = $payType;
    
        return $this;
    }

    /**
     * Get payType
     *
     * @return string 
     */
    public function getPayType()
    {
        return $this->payType;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return ShopTransactionsPayment
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return ShopTransactionsPayment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    
        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set mode
     *
     * @param string $mode
     * @return ShopTransactionsPayment
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    
        return $this;
    }

    /**
     * Get mode
     *
     * @return string 
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ShopTransactionsPayment
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
     * @return ShopTransactionsPayment
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
     * Set paymentDate
     *
     * @param \DateTime $paymentDate
     * @return ShopTransactionsPayment
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
     * @return ShopTransactionsPayment
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
     * @return ShopTransactionsPayment
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
     * @return ShopTransactionsPayment
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
     * @return ShopTransactionsPayment
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
     * @return ShopTransactionsPayment
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
     * @return ShopTransactionsPayment
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
     * @return ShopTransactionsPayment
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
     * @return ShopTransactionsPayment
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
     * @return ShopTransactionsPayment
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
     * @return ShopTransactionsPayment
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
}
