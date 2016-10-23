<?php

namespace Transaction\TransactionBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use StoreManager\StoreBundle\Entity\Store;
use StoreManager\StoreBundle\Entity\UserToStore;
use StoreManager\StoreBundle\Entity\StoreMedia;
use StoreManager\StoreBundle\Entity\StoreJoinNotification;
use StoreManager\StoreBundle\Entity\Transactionshop;
use Ijanki\Bundle\FtpBundle\Exception\FtpException;
use Transaction\TransactionBundle\Document\TransictionImportLogs;
use Transaction\TransactionBundle\Entity\CitizenIncomeToPayToStore;
use Transaction\TransactionBundle\Entity\UserGiftCardPurchased;
use Transaction\TransactionBundle\Entity\UserCitizenIncome;

class TransactionController extends Controller {

    protected $transicion = "/uploads/transaction/";
    protected $min_tot_amount = 0;

    /**
     * function which is called via the webservice for import data in Table
     * @param string $filetype Type od file need to import(EX. TA,TD,SM) 
     */
    public function importcsvdataAction($filetype) {    
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $filetype = strtoupper($filetype);
        $file_download_status = $this->getFileFromFTP($filetype);
        if ($file_download_status) {
            $date = date('Y-m-d');
            $log_count = 0;
            $mediaOriginalPath = __DIR__ . "/../../../../web/" . $this->transicion;
            $local_file = $mediaOriginalPath . $filetype . ".csv";
            //count total number of lines in csv file
            $fp = file($local_file);
            $total_line_in_csv = count($fp);
            $dm = $this->container->get('doctrine.odm.mongodb.document_manager');
            $transition_log_object = $dm->getRepository('TransactionTransactionBundle:TransictionImportLogs')
                    ->findOneBy(array('type' => $filetype, 'date' => new \MongoDate(strtotime($date))));

            //check if we have a object of filetype for current date(is cron job is running firsttime or backup)
            if (count($transition_log_object) == 1) {
                $log_count = $transition_log_object->getCount();
            }
            //check the filetype and choose in which table we have to insert data
            if ($filetype == 'TD') {
                $row = $this->insertDataInTDTable($local_file, $log_count, $filetype);
                exit();
            }
            if ($filetype == 'SM') {
                $row = $this->insertDataInSMTable($local_file, $log_count, $filetype);
                exit();
            }
            if ($filetype == 'TA') {
                $row = $this->insertDataInTATable($local_file, $log_count, $filetype);
                exit();
            }
            if ($filetype == 'TG') {
                $row = $this->insertDataInTGTable($local_file, $log_count, $filetype);
                exit();
            }
            
            //code for citizen income in
            if ($filetype == 'TI') {
                $row = $this->insertDataInTITable($local_file, $log_count, $filetype);
                exit();
            }            
        }
        exit();
    }

    /**
     * Connect to the shopping plus and write the file on the local server
     * @param String $filetype Type of file need to import(EX. TA,TD,SM) 
     */
    private function getFileFromFTP($filetype) {
        $host = $this->container->getParameter('shoppingplus_hostname');
        $username = $this->container->getParameter('shoppingplus_ftp_username');
        $password = $this->container->getParameter('shoppingplus_ftp_password');
        $port = $this->container->getParameter('shoppingplus_port');
        try {
            $ftp = $this->container->get('ijanki_ftp');
            $ftp->connect($host, $port);
            $ftp->login($username, $password);

            $mediaOriginalPath = __DIR__ . "/../../../../web/" . $this->transicion;
            if (!is_dir($mediaOriginalPath)) {
                \mkdir($mediaOriginalPath, 0777, true);
            }
            $local_file = $mediaOriginalPath . $filetype . ".csv";

            $ftp->pasv('true');
            //getting the list of all the file of a pattern from the FTP
            $filename = $filetype . self::getTodayDate();
            $file_list = $ftp->nlist('topos/' . $filename . '*.csv');
            if (count($file_list) > 0) {
                $source_file = end($file_list);
                $ftp->get($local_file, $source_file, FTP_ASCII);
                return true;
            } else {
                return false;
            }
        } catch (FtpException $e) {
            echo 'Error: ', $e->getMessage();
        }
    }

    /**
     * funttion for inserting the data in DB table from the CSV file
     * @param type $local_file path of the .CSV file need to import
     * @param type $log_count Count of the record that is already inserted(Backup)
     * @return int $row count of the row that is inserted in DB
     */
    private function insertDataInTDTable($local_file, $log_count, $filetype) {
        $row = 1;
        if (($handle = fopen($local_file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if ($row > $log_count) {
                    if((self::castToInt($data[2]) > 0 || self::castToInt($data[3]) > 0)) {
                    $transaction_shop = new Transactionshop();
                    $transaction_shop->setDataMovimento(self::castToDate($data[0]));
                    $transaction_shop->setUserId(self::castToInt($data[1]));
                    $transaction_shop->setTotDare(self::castToInt($data[2]));
                    $transaction_shop->setTotQuota(self::castToInt($data[3]));
                    $transaction_shop->setDataJob(time());
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($transaction_shop);
                    $em->flush();
                }
                $this->updateMongo($row, $filetype);
                }
                $row++;
            }
            fclose($handle);
        }

        return $row;
    }

    /**
     * funttion for inserting the data in DB table from the CSV file
     * @param type $local_file path of the .CSV file need to import
     * @param type $log_count Count of the record that is already inserted(Backup)
     * @return int $row count of the row that is inserted in DB
     */
    private function insertDataInSMTable($local_file, $log_count, $filetype) {
        $row = 1;
        if (($handle = fopen($local_file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if ($row > $log_count) {
                    $sixth_market = new SixthMarket();
                    $sixth_market->setDataMovimento(self::castToDate($data[0]));
                    $sixth_market->setAccumulo(self::castToInt($data[1]));
                    $sixth_market->setDataJob(time());
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($sixth_market);
                    $em->flush();
                    $this->updateMongo($row, $filetype);
                }
                $row++;
            }
            fclose($handle);
        }

        return $row;
    }

    /**
     * funttion for inserting the data in DB table(CitizenIncomeToPayToStore) from the CSV file
     * @param type $local_file path of the .CSV file need to import
     * @param type $log_count Count of the record that is already inserted(Backup)
     * @return int $row count of the row that is inserted in DB
     */
    private function insertDataInTATable($local_file, $log_count, $filetype) {
        $row = 1;
        if (($handle = fopen($local_file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if ($row > $log_count) {
                    if(self::castToInt($data[2]) > 0) {
                    $citizen_income_pay_to_store = new CitizenIncomeToPayToStore();
                    $citizen_income_pay_to_store->setDataMovimento(self::castToDate($data[0]));
                    $citizen_income_pay_to_store->setUserId(self::castToInt($data[1]));
                    $citizen_income_pay_to_store->setTotAvere(self::castToInt($data[2]));
                    $citizen_income_pay_to_store->setDataJob(time());
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($citizen_income_pay_to_store);
                    $em->flush();
                    }
                    $this->updateMongo($row, $filetype);
                }
                $row++;
            }
            fclose($handle);
        }

        return $row;
    }

    /**
     * function for updating the Mongo log table
     * @param Object $transition_log_object object of mongo for update
     * @param Date $date date of the CSV record need to insert
     * @param Int $row count of the record inserted
     * @param String $filetype Type of file need to import(EX. TA,TD,SM)
     */
    private function updateMongo($row, $filetype) {
        $date = date('Y-m-d');
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $transition_log_object = $dm->getRepository('TransactionTransactionBundle:TransictionImportLogs')
                ->findOneBy(array('type' => $filetype, 'date' => new \MongoDate(strtotime($date))));

        //check if we have a object of filetype for current date(is cron job is running firsttime or backup)
        if (count($transition_log_object) == 1) { 
            $log_count = $transition_log_object->getCount();
        } else {
            $transition_log_object = new TransictionImportLogs();
        }
        $transition_log_object->setType($filetype);
        $transition_log_object->setDate($date);
        $transition_log_object->setCount($row);
        $dm->persist($transition_log_object);
        $dm->flush();
    }

    /**
     * 
     * @param Date $field
     * @return date
     */
    private static function castToDate($field) {
        return strtotime(substr($field, 0, 4) . "-" . substr($field, 4, 2) . "-" . substr($field, 6, 2));
    }

    /**
     * 
     * @param string $field
     * @return int
     */
    private static function castToInt($field) {
        return (int) $field;
    }

    /**
     * function for getting the yesterday date
     * @return Date return yesterday date
     */
    private static function getYesterdayDate() {
        return date("Ymd", strtotime("yesterday"));
    }
    
    /**
     * function for getting the yesterday date
     * @return Date return yesterday date
     */
    private static function getTodayDate() {
        return date("Ymd");
    }
    
    /**
     * funttion for inserting the data in DB table(UserGiftCardPurchased) from the CSV file
     * @param type $local_file path of the .CSV file need to import
     * @param type $log_count Count of the record that is already inserted(Backup)
     * @return int $row count of the row that is inserted in DB
     */
    private function insertDataInTGTable($local_file, $log_count, $filetype) {
        $row = 1;
        if (($handle = fopen($local_file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if ($row > $log_count) {
                    $em = $this->getDoctrine()->getManager();
                    $gift_card_result = $em
                            ->getRepository('TransactionTransactionBundle:UserGiftCardPurchased')
                            ->findBy(array('giftCardId' => $data[4])); 
                    $gift_card_count = count($gift_card_result);
                    if($gift_card_count == 0 ) {
                    if (self::castToInt($data[2]) > 0) {
                        $user_gift_card_purchased = new UserGiftCardPurchased();
                        $user_gift_card_purchased->setGiftCardId($data[4]);
                        $user_gift_card_purchased->setUserId(self::castToInt($data[1]));
                        $user_gift_card_purchased->setShopId(self::castToInt($data[3]));
                        $user_gift_card_purchased->setGiftCardAmount(self::castToInt($data[2]));
                        $user_gift_card_purchased->setDate(self::castToDate($data[0]));
                        $user_gift_card_purchased->setDataJob(time());
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($user_gift_card_purchased);
                        $em->flush();
                    }
                    }
                    $this->updateMongo($row, $filetype);
                }
                $row++;
            }
            fclose($handle);
        }

        return $row;
    }

    /**
     * funttion for inserting the data in DB table(UserCitizenIncome) from the CSV file
     * @param type $local_file path of the .CSV file need to import
     * @param type $log_count Count of the record that is already inserted(Backup)
     * @return int $row count of the row that is inserted in DB
     */
    private function insertDataInTITable($local_file, $log_count, $filetype) {
        $row = 1;
        $time = time();
        if (($handle = fopen($local_file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if ($row > $log_count) {
                    $em = $this->getDoctrine()->getManager();
                    $citizen_income_result = $em
                            ->getRepository('TransactionTransactionBundle:UserCitizenIncome')
                            ->findBy(array('citizenIncomeId' => $data[3])); 
                    $citizen_income_count = count($citizen_income_result);
                    if ($citizen_income_count == 0 ) {
                        if (self::castToInt($data[2]) > 0) {
                            $user_citizen_income = new UserCitizenIncome();
                            $user_citizen_income->setCitizenIncomeId(self::castToInt($data[3]));
                            $user_citizen_income->setUserId(self::castToInt($data[1]));
                            $user_citizen_income->setCitizenIncomeAmount(self::castToInt($data[2]));
                            $user_citizen_income->setDate(self::castToDate($data[0]));
                            $user_citizen_income->setDataJob($time);
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($user_citizen_income);
                            $em->flush();
                        }
                    }
                    $this->updateMongo($row, $filetype);
                }
                $row++;
            }
            fclose($handle);
        }

        return $row;
    }
}
