<?php

namespace AdminUserManager\AdminUserManagerBundle\Extension;

use StoreManager\StoreBundle\Entity\Transactionshop;

class TransictionShopExtension extends \Twig_Extension {

    private $em;
    private $conn;

    public function __construct(\Doctrine\ORM\EntityManager $em) {
        $this->em = $em;
        $this->conn = $em->getConnection();
    }

    public function getFunctions() {
        return array(
            'Date' => new \Twig_Function_Method($this, 'getDate'),
            'moneyInEuroTotDare' => new \Twig_Function_Method($this, 'getValueInEuroTotDare'),
            'moneyInEuroTotQuota' => new \Twig_Function_Method($this, 'getValueInEuroTotQuota'),
            'getShopId' => new \Twig_Function_Method($this, 'getShopId'),
            'getShopName' => new \Twig_Function_Method($this, 'getShopName'),
            'getShopBusinessName' => new \Twig_Function_Method($this, 'getShopBusinessName'),
            'transictionDate' => new \Twig_Function_Method($this, 'transictionDate'),
            'moneyInEuroTotAvare' => new \Twig_Function_Method($this, 'moneyInEuroTotAvare'),
            'dumparray' => new \Twig_Function_Method($this, 'dumpArray')
        );
    }

    public function dumpArray($object) {
        echo '<pre>';
        print_r($object);
        exit;
    }
    
    public function getDate($date) {

        return date('d-m-Y', 1419202800);
    }

    /**
     * function for getting the tot_dare amount from TransictionShop object
     * @param type $money object of TransictionShop
     * @return int amount in Euro round to 2 digit 
     */
    public function getValueInEuroTotDare($money) {
        $transiction_shop = $this->em->getClassMetadata('StoreManagerStoreBundle:Transactionshop');
        $transiction_shop = $transiction_shop->getTableName();
        $object_class = (new \ReflectionClass($money));
        $object_class = $object_class->getShortName();
        if ($transiction_shop == $object_class) {
            $money_val = $money->getTotDare();
            $money_val = $this->getValueInEuro($money_val);
            return $money_val;
        } else {
            return "";
        }
    }

    /**
     * function for getting the tot_quota amount from TransictionShop object
     * @param type $money object of TransictionShop
     * @return int amount in Euro round to 2 digit 
     */
    public function getValueInEuroTotQuota($money) {
        $transiction_shop = $this->em->getClassMetadata('StoreManagerStoreBundle:Transactionshop');
        $transiction_shop = $transiction_shop->getTableName();
        $object_class = (new \ReflectionClass($money));
        $object_class = $object_class->getShortName();
        if ($transiction_shop == $object_class) {
            $money_val = $money->getTotQuota();
            $money_val = $this->getValueInEuro($money_val);
            return $money_val;
        } else {
            return 0;
        }
    }

    /**
     * function for calculating the given amount in Euro
     * @param type $money Money in money*1000000
     * @return type amount in Euro
     */
    public function getValueInEuro($money) {
        return round($money / 1000000, 2);
    }

    /**
     * function for getting the shop id from the object
     * @param type $object object of Transiction shop object
     * @return type shop id
     */
    public function getShopId($object) {
        return $object->getUserId();
    }

    /**
     * function for getting the shop name from the TransictionShop object
     * @param type $object object of Transiction shop object
     * @return type Shop name
     */
    public function getShopName($object) {
        $shop_id = $object->getUserId();
        $store_table = $this->em->getClassMetadata('StoreManagerStoreBundle:Store');
        $store_table = $store_table->getTableName();
        $sql = "SELECT name FROM " . $store_table . " where id =" . $shop_id;
        return $this->conn->fetchColumn($sql);
    }

    /**
     * function for getting the shop business name from the TransictionShop object
     * @param type $object object of Transiction shop object
     * @return type Shop business name
     */
    public function getShopBusinessName($object) {
        $shop_id = $object->getUserId();
        $store_table = $this->em->getClassMetadata('StoreManagerStoreBundle:Store');
        $store_table = $store_table->getTableName();
        $sql = "SELECT business_name FROM " . $store_table . " where id =" . $shop_id;
        return $this->conn->fetchColumn($sql);
    }

    /**
     * function for getting the transiction date from the TransictionShop object
     * @param type $object object of Transiction shop object
     * @return type Transiction date
     */
    public function transictionDate($object) {
        $transiction_date = $object->getDataMovimento();
        return date('d-M-Y', $transiction_date);
    }

    public function getName() {
        return 'transaction_shop_extension';
    }

    public function moneyInEuroTotAvare($object) {
        $transiction_shop = $this->em->getClassMetadata('TransactionTransactionBundle:CitizenIncomeToPayToStore');
        $transiction_shop = $transiction_shop->getTableName();
        $object_class = (new \ReflectionClass($object));
        $object_class = $object_class->getShortName();
        if ($transiction_shop == $object_class) {
            $money_val = $object->getTotAvere();
            $money_val = $this->getValueInEuro($money_val);
            return $money_val;
        } else {
            return 0;
        }
    }

}
