<?php

namespace ExportManagement\ExportManagementBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentExport
 */
class PaymentExport
{
    /* type field value saving in db*/
    const TYPE_CODE_1 = 1;
    const TYPE_CODE_2 = 2;
    const TYPE_CODE_3 = 3;
    const TYPE_CODE_4 = 4;
    const TYPE_CODE_5 = 5;
    const TYPE_CODE_6 = 6;
    
    /*value of type shown on admin panel*/
    const TYPE_STRING_1 = 'Shop Weekly Transaction';
    const TYPE_STRING_2 = 'Citizen Income Utilized';
    const TYPE_STRING_3 = 'Shop Registration fee';
    const TYPE_STRING_4 = 'Shop Pending Amount';
    const TYPE_STRING_5 = 'Shop Pending Amount and Registration fee';
    const TYPE_STRING_6 = 'Gift Card export';
    
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $filename;


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
     * @return PaymentExport
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
     * Set type
     *
     * @param string $type
     * @return PaymentExport
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set filename
     *
     * @param string $filename
     * @return PaymentExport
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    
        return $this;
    }

    /**
     * Get filename
     *
     * @return string 
     */
    public function getFilename()
    {
        return $this->filename;
    }
    
    /**
     * 
     * @return string
     */
    public function getTypeAsString()
    {
        switch($this->type){
            case(self::TYPE_CODE_1): return self::TYPE_STRING_1;
            case(self::TYPE_CODE_2): return self::TYPE_STRING_2;
            case(self::TYPE_CODE_3): return self::TYPE_STRING_3;
            case(self::TYPE_CODE_4): return self::TYPE_STRING_4;
            case(self::TYPE_CODE_5): return self::TYPE_STRING_5;
            case(self::TYPE_CODE_6): return self::TYPE_STRING_6;
        }
    }
    
    /**
     * get list of type with string to shown on admin panel
     * @return type
     */
    public static function getTypeList() {
        return array(
            self::TYPE_CODE_1=>self::TYPE_STRING_1,
            self::TYPE_CODE_2=>self::TYPE_STRING_2,
            self::TYPE_CODE_3=>self::TYPE_STRING_3,
            self::TYPE_CODE_4=>self::TYPE_STRING_4,
            self::TYPE_CODE_5=>self::TYPE_STRING_5,
            self::TYPE_CODE_6=>self::TYPE_STRING_6
        );
    }
}