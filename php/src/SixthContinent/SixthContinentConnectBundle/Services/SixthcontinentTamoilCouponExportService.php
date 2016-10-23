<?php

namespace SixthContinent\SixthContinentConnectBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\UtilityBundle\Utils\Utility;
use SixthContinent\SixthContinentConnectBundle\Entity\Sixthcontinentconnecttransaction;
use SixthContinent\SixthContinentConnectBundle\Entity\TamoilExportCounter;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Symfony\Component\Locale\Locale;

//export the tamoil coupon 
class SixthcontinentTamoilCouponExportService {

    protected $em;
    protected $dm;
    protected $container;
    protected $week_days = array(1, 2, 3, 4, 5);
    protected $week_ends = array(0, 6);
    protected $tamoil_file_path = '/uploads/transaction/tamoiloffer/';
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }

    /**
     * Create connect app log
     * @param string $monolog_req
     * @param string $monolog_response
     */
    public function __createLog($monolog_req, $monolog_response = array()) {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.offer_purchasing_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);
        return true;
    }

    protected function _getSixcontinentAppService() {
        return $this->container->get('sixth_continent_connect.connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentConnectService
    }

    protected function _getSixthcontinentPaypalService() {
        return $this->container->get('sixth_continent_connect.paypal_connect_app'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentPaypalConnectService
    }

    protected function _getSixthcontinentOfferTransactionService() {
        return $this->container->get('sixth_continent_connect.purchasing_offer_transaction'); //SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentOfferTransactionService
    }

    /**
     * export the tamoil coupon
     */
    public function exportTamoilCoupon() {
        $em = $this->em;
        $week_days_index = $this->week_days;
        $week_ends_index = $this->week_ends;
        $offer_transaction_service = $this->_getSixthcontinentOfferTransactionService();
        $this->__createLog('Entering into class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentTamoilCouponExportService] and function [exportTamoilCoupon]');
        $current_date = new \DateTime('now');
        $current = $current_date->format('Y-m-d H:i:s'); //current formated date
        //$current = $current_date->format('2015-08-06 12:08:08'); //current formated date
        $current_time = $this->convertToTime($current); //time in seconds
        $current_day_index = $this->findDayIndex($current_time); //day index 0-6
        $date_format = $this->getFormattedDate($current_time); //return yyyymmdd
        $time_format = $this->getFormattedTime($current_time); //return hhiiss
        $time_creation = $date_format.$time_format;
        if (in_array($current_day_index, $week_days_index)) {
            $export_counter = $export_counter_id = 0;
            //get the counter
            $export_counter_result = $em->getRepository('SixthContinentConnectBundle:TamoilExportCounter')
                                        ->getExportCounter();
            if (sizeof($export_counter_result)) {
               $export_counter    = $export_counter_result['counter'];
               $export_counter_id = $export_counter_result['id']; 
            }
            $formated_counter = $export_counter + 1;
            $final_export_counter = str_pad("$formated_counter", ApplaneConstentInterface::TAMOIL_EXPORT_COUNTER_LENGTH, "0", STR_PAD_LEFT);
            //get file name
            $file_name = $this->getFileName($date_format, $time_format, $final_export_counter);
            
            //get dates array
            $dates_array = $this->getCouponConsumedDates($current);
            $final_file_path = $this->getFilePath($file_name);
            $header = $this->prepareHeader($date_format, $time_format, $final_export_counter);
            
            $myfile = fopen($final_file_path, "w");
            $this->WriteTamoil($myfile, $header);
            $code_consumptions = $em->getRepository('SixthContinentConnectBundle:CodesConsumption')
                               ->getCodeCosumptionsData($dates_array);
            foreach ($code_consumptions as $records) { 
                $transaction_id = $records['transaction_id'];
                $coupon_id = $records['coupon_id'];
                $coupon_consumption_date = $records['date'];
                $consumption_date = $this->convertToTime($coupon_consumption_date);
                $order_sent_to_client = $this->getFormattedDate($consumption_date);
                $this->__createLog('CodeConsumption id is coming: '.$transaction_id.' and date is coming: '.$coupon_consumption_date);
                $code = $em->getRepository('SixthContinentConnectBundle:Codes')
                           ->getCodesByCouponId($coupon_id);
                $coupon_record = $em->getRepository('SixthContinentConnectBundle:CouponToActive')
                                    ->findOneBy(array('id'=>$coupon_id));
                $order_number = $coupon_record->getOrderNumberFromImport();
                $offer_expiration_date = $coupon_record->getExpiredDate()->format('Ymd');
                $date_order  = $coupon_record->getImportedAt()->format('Ymd');
                $row_content = $this->prepareRowContent($code, $time_creation, $order_number, $date_order, $offer_expiration_date, $order_sent_to_client);
                $this->WriteTamoil($myfile, $row_content);
            }
            $counter = count($code_consumptions); //total records
            $footer = $this->prepareFooter($counter);
            $this->WriteTamoil($myfile, $footer);
            fclose($myfile);
            //update teh export counter
            $this->updateExportCounter($export_counter_id, $formated_counter);
        } else {
            $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentTamoilCouponExportService] and function [exportTamoilCoupon] and its a weekend: ' . $current);
        }
        $this->__createLog('Exiting from class [SixthContinent\SixthContinentConnectBundle\Services\SixthcontinentTamoilCouponExportService] and function [exportTamoilCoupon]');
        return true;
    }

    public function prepareRowContent($code, $time_creation, $order_number, $date_order, $offer_expiration_date, $order_sent_to_client) {
        $activation_status = ApplaneConstentInterface::TAMOIL_COUPON_EXPORT_ACTIVATION_STATUS;
        $ds_stato = ApplaneConstentInterface::TAMOIL_COUPON_EXPORT_DS_STATUS;
        $initial_constant = ApplaneConstentInterface::TAMOIL_COUPON_EXPORT_INITIAL_CONSTS;
        $client_code = $this->container->getParameter('tamoil_coupon_client_code');
        $space_36 = $this->getSpace(36);
        $txt = $initial_constant.$code.$time_creation.$activation_status.$ds_stato.$client_code.$order_number.$date_order.$offer_expiration_date.$order_sent_to_client.$space_36."\n";
        return $txt;
    }

    /**
     * write the content into file
     * @param object $file
     * @param string $content
     */
    public function WriteTamoil($file, $content) {
        fwrite($file, $content);
        return true;
    }
    
    /**
     * update the counter for the tamoil export
     * @param type $export_counter_id
     * @param type $formated_counter
     * @return boolean
     */
    private function updateExportCounter($export_counter_id, $formated_counter) {
      $em = $this->em;
      if ($export_counter_id > 0) { //update the record
        $tamoil_export_counter = $em->getRepository('SixthContinentConnectBundle:TamoilExportCounter')
                                    ->find($export_counter_id);
        $tamoil_export_counter->setCounter($formated_counter);
      } else { //insert a new record
        $tamoil_export_counter = new TamoilExportCounter();
        $tamoil_export_counter->setCounter($formated_counter);
      }
      try {
        $em->persist($tamoil_export_counter);
        $em->flush();
      } catch (\Exception $ex) {

      }
      return true;
    }
    /**
     * prepare the last row of the 
     * @param int $counter
     * @return string $final_string
     */
    public function prepareFooter($counter) {
        $footer_constant = ApplaneConstentInterface::TAMOIL_FOOTER_START_CONSTANT;
        $footer_constant_length = ApplaneConstentInterface::TAMOIL_COUPON_LAST_ROW_CONSTANT_LENGTH;
        $counter_string = str_pad("$counter", $footer_constant_length, "0", STR_PAD_LEFT);
        $footer_spaces = $this->getSpace(120);
        $final_string = $footer_constant.$counter_string.$footer_spaces;
        return $final_string;
    }
    
    /**
     * Prepare the first row of the file.
     * @param type $date_format
     * @param type $time_format
     * @param type $counter
     * @return type
     */
    public function prepareHeader($date_format, $time_format, $counter) {
        $header_constant = ApplaneConstentInterface::TAMOIL_COUPON_FIRST_ROW_CONSTANT;
        $first_space = $this->getSpace(3);
        $last_space = $this->getSpace(100);
        $first_row = $header_constant.$first_space.$counter.$date_format.$time_format.$last_space."\n";
        return $first_row;
    }
    
    /**
     * get the spaces
     * @param int $counter
     * @return string
     */
    public function getSpace($counter) {
        $space ="";
        for ($index = 0; $index < $counter ; $index++) {
            $space.=" ";    
        }
        return $space;
    }
    
    /**
     * get the file location to be saved
     * @param string $file_name
     */
    public function getFilePath($file_name) {
        $file_path = $this->tamoil_file_path;
        $file_location_path = __DIR__.'../../../../../web'.$file_path;
        $final_location = $file_location_path.$file_name;
        @\mkdir($file_location_path, 0777, true);
        return $final_location;
    }
    /**
     * find the coupon consumned dates
     * @param string $date
     * @return array
     */
    public function getCouponConsumedDates($date) {
        $dates = array();
        $days_difference = ApplaneConstentInterface::TAMOIL_EXPORT_DAYS_COUNTER;
        $friday_index    = ApplaneConstentInterface::FRIDAY_INDEX;
        $time = $this->convertToTime($date . ' -' . $days_difference . 'Weekday');
        $first_day_index = $this->findDayIndex($time); //week day index.
        $first_date = date('Y-m-d', $time);
        if ($first_day_index == $friday_index) {
            $second_date = $this->addDaysinTime($time, 1); //add 1 day
            $third_date = $this->addDaysinTime($time, 2); //add 2 day
            $dates = array($first_date, $second_date, $third_date);
        } else {
            $dates = array($first_date);
        }
        return $dates;
    }

    /**
     * add no of days in a date
     * @param int $time
     * @param int $day_counter
     * @return string
     */
    public function addDaysinTime($time, $day_counter) {
        $day_time_constant = 3600 * 24;
        $new_time = ($day_time_constant * $day_counter);
        return date('Y-m-d', ($time + $new_time));
    }

    /**
     * get formatted date
     * @param int $time (in seconds)
     * @return int 
     */
    public function getFormattedDate($time) {
        return date('Ymd', $time);
    }

    /**
     * get formatted time
     * @param int $time (in seconds)
     * @return int 
     */
    public function getFormattedTime($time) {
        return date('His', $time);
    }

    /**
     * get the file name
     * @param int $date_format
     * @param int $time_format
     * @param string $final_export_counter
     * @return string $file_name
     */
    public function getFileName($date_format, $time_format, $counter) {
        $file_name_prefix = ApplaneConstentInterface::TAMOIL_EXPORT_FILE_NAME;
        $file_extension = ApplaneConstentInterface::TAMOIL_EXPORT_FILE_EXTENSION;
        $file_name = $file_name_prefix . $date_format . '_' . $time_format . '_' . $counter . '.' . $file_extension;
        return $file_name;
    }

    /**
     * get the day index 0-6, (Monday-Friday => 1-5), (saturday=>6, sunday=>0)
     * @param int $time
     * @return int 
     */
    public function findDayIndex($time) {
        return date("w", $time);
    }

    /**
     * convert a date to specific format
     * @param date object $date
     */
    public function convertToDateFormat($date) {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * convert to strtotime function
     * @param object $time date object (yyyy-mm-dd H:i:s)
     * @return int seconds
     */
    public function convertToTime($time) {
        return strtotime($time);
    }

}
