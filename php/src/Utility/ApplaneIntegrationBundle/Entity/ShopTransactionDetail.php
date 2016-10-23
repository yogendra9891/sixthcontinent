<?php

namespace Utility\ApplaneIntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShopTransactionDetail
 */
class ShopTransactionDetail
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var integer
     */
    private $shopId;

    /**
     * @var integer
     */
    private $amount;

    /**
     * @var integer
     */
    private $status = 0;

    /**
     * @var string
     */
    private $contractTxnId = '';

    /**
     * @var string
     */
    private $comment = '';

    /**
     * @var \DateTime
     */

    private $paymentDate=null;

    /**
     * @var string
     */
    private $pendingIds = '';

    /**
     * @var integer
     */
    private $pendingAmount = 0;

    /**
     * @var integer
     */
    private $regFee = 0;

    /**
     * @var integer
     */
    private $regVat = 0;

    /**
     * @var integer
     */
    private $recurringVat = 0;

    /**
     * @var integer
     */
    private $totalAmount = 0;


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
     * Set date
     *
     * @param \DateTime $date
     * @return ShopTransactionDetail
     */
    public function setDate($date)
    {
        $this->date = $date;
    
        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     * @return ShopTransactionDetail
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
     * Set amount
     *
     * @param integer $amount
     * @return ShopTransactionDetail
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    
        return $this;
    }

    /**
     * Get amount
     *
     * @return integer 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return ShopTransactionDetail
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
     * Set contractTxnId
     *
     * @param string $contractTxnId
     * @return ShopTransactionDetail
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
     * Set comment
     *
     * @param string $comment
     * @return ShopTransactionDetail
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
     * Set paymentDate
     *
     * @param \DateTime $paymentDate
     * @return ShopTransactionDetail
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
     * Set pendingIds
     *
     * @param string $pendingIds
     * @return ShopTransactionDetail
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
     * @param integer $pendingAmount
     * @return ShopTransactionDetail
     */
    public function setPendingAmount($pendingAmount)
    {
        $this->pendingAmount = $pendingAmount;
    
        return $this;
    }

    /**
     * Get pendingAmount
     *
     * @return integer 
     */
    public function getPendingAmount()
    {
        return $this->pendingAmount;
    }

    /**
     * Set regFee
     *
     * @param integer $regFee
     * @return ShopTransactionDetail
     */
    public function setRegFee($regFee)
    {
        $this->regFee = $regFee;
    
        return $this;
    }

    /**
     * Get regFee
     *
     * @return integer 
     */
    public function getRegFee()
    {
        return $this->regFee;
    }

    /**
     * Set regVat
     *
     * @param integer $regVat
     * @return ShopTransactionDetail
     */
    public function setRegVat($regVat)
    {
        $this->regVat = $regVat;
    
        return $this;
    }

    /**
     * Get regVat
     *
     * @return integer 
     */
    public function getRegVat()
    {
        return $this->regVat;
    }

    /**
     * Set recurringVat
     *
     * @param integer $recurringVat
     * @return ShopTransactionDetail
     */
    public function setRecurringVat($recurringVat)
    {
        $this->recurringVat = $recurringVat;
    
        return $this;
    }

    /**
     * Get recurringVat
     *
     * @return integer 
     */
    public function getRecurringVat()
    {
        return $this->recurringVat;
    }

    /**
     * Set totalAmount
     *
     * @param integer $totalAmount
     * @return ShopTransactionDetail
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    
        return $this;
    }

    /**
     * Get totalAmount
     *
     * @return integer 
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }
    
    /**
     * @var \DateTime
     */
    private $created_at;


    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return ShopTransactionDetail
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
    private $userId;

    /**
     * @var float
     */
    private $payable_amount;


    /**
     * Set userId
     *
     * @param integer $userId
     * @return ShopTransactionDetail
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
     * Set payable_amount
     *
     * @param float $payableAmount
     * @return ShopTransactionDetail
     */
    public function setPayableAmount($payableAmount)
    {
        $this->payable_amount = $payableAmount;
    
        return $this;
    }

    /**
     * Get payable_amount
     *
     * @return float 
     */
    public function getPayableAmount()
    {
        return $this->payable_amount;
    }
    /**
     * @var string
     */
    private $payType = '';


    /**
     * Set payType
     *
     * @param string $payType
     * @return ShopTransactionDetail
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
     * @var string
     */
    private $tipoCarta = '';

    /**
     * @var string
     */
    private $paese  = '';

    /**
     * @var string
     */
    private $codTrans  = '';

    /**
     * @var string
     */
    private $tipoProdotto  = '';

    /**
     * @var string
     */
    private $tipoTransazione  = '';

    /**
     * @var string
     */
    private $codiceAutorizzazione  = '';

    /**
     * @var string
     */
    private $dataOra  = '';

    /**
     * @var integer
     */
    private $codiceEsito  = 0;

    /**
     * @var string
     */
    private $descrizioneEsito  = '';

    /**
     * @var string
     */
    private $mac  = '';


    /**
     * Set tipoCarta
     *
     * @param string $tipoCarta
     * @return ShopTransactionDetail
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
     * @return ShopTransactionDetail
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
     * @return ShopTransactionDetail
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
     * @return ShopTransactionDetail
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
     * @return ShopTransactionDetail
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
     * @return ShopTransactionDetail
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
     * @return ShopTransactionDetail
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
     * @return ShopTransactionDetail
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
     * @return ShopTransactionDetail
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
     * @return ShopTransactionDetail
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
     * @var string
     */
    private $invoiceId;


    /**
     * Set invoiceId
     *
     * @param string $invoiceId
     * @return ShopTransactionDetail
     */
    public function setInvoiceId($invoiceId)
    {
        $this->invoiceId = $invoiceId;
    
        return $this;
    }

    /**
     * Get invoiceId
     *
     * @return string 
     */
    public function getInvoiceId()
    {
        return $this->invoiceId;
    }
    /**
     * @var integer
     */
    private $contractId = 0;


    /**
     * Set contractId
     *
     * @param integer $contractId
     * @return ShopTransactionDetail
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
}