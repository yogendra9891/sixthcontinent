<?php

namespace ExportManagement\ExportManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use ExportManagement\ExportManagementBundle\Document\ProfileExport;

class ShopPayToSixthContinentExportController extends Controller {

    protected $shop_pay_sixthxontinent_path = "/uploads/transaction/shoppaytosixth";
    protected $shop = 'shopdailypay'; //shoppaytosixcontinent
    protected $shop_daily_pay_type = 'shopdailypay';
    protected $shop_pay_sixthxontinent_database_log_type = 4;

    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * getting the shop filename
     * @return string
     */
    public function getShopPayToSixthFileName() {
        $file_name = $this->shop . "_" . date("Y-m-d") . ".csv";
        return $file_name;
    }

    /**
     * Shop daily pay to sixthcontent x% after recuring payment.
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function exportshoppaytosixcontinentAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $shop_daily_pay_to_sixth_data = $em->getRepository('CardManagementBundle:ShopRegPayment')
                ->getShopDailyPayToSixthContinent();
        if (count($shop_daily_pay_to_sixth_data)) {
            //exporting the data.
            $result = $this->exportshopdailypaytosixthcsv($shop_daily_pay_to_sixth_data);
        }
        if (!empty($result)) {
            $data = array('code' => 101, 'message' => 'SUCCESS', 'data' => array('link' => $result));
        } else {
            $data = array('code' => 100, 'message' => 'NO_DATA_FOR_EXPORT', 'data' => array());
        }
        echo json_encode($data);
        exit;
    }

    /**
     * Writing the file.
     * @param type $shop_registration_payment_data
     */
    public function exportshopdailypaytosixthcsv($shop_pay_sixth_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->shop_pay_sixthxontinent_path;

        //creating the file name.
        $file_name = $this->getShopPayToSixthFileName();
        $type = $this->shop_pay_sixthxontinent_database_log_type;
        $date = new \DateTime('now');
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        //$shop_file_name = $file_path . "/" . $file_name;
        $item_type = 'shop';
        $data = array();
        $head_data_array = array("DATA", "CAUSALE", "CODICE", "DESCRIZIONE", "DESCRIZIONE 2", "IMPORTO", "ID-SHOP", "IMPORTO_PIU_IVA");
        $columns = array('F', 'H');
        //$x% in parameter file
        $x = $this->container->getParameter('ci_percentage');

        //taking the mongodb doctrine object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $shop_daily_registration_type = $this->shop_daily_pay_type;
        $causale = 'RC' . $x.'PC'; //registration fee motivation 
        $codice = $x.'PC'; //registration fee code
        $description = "CORRISPETTIVO PUBBLICITA'";
        $description2 = 'PER VENDITE EFFETTUATE';
        foreach ($shop_pay_sixth_data as $array_data) {
            $shop_id = $array_data['shop_id'];
            //here amount is pending amount+vat, so we have to calculate the amount without vat
            $amount = $this->convertCurrency($array_data['amount'] - $array_data['pending_vat']);
            $pending_amount_vat = $this->convertCurrency($array_data['amount']);
            $transaction_time = $array_data['created_at']->format('d/m/Y');
            $data[] = array('DATA' => $transaction_time, 'CAUSALE' => $causale, 'CODICE' => $codice,
                'DESCRIZIONE' => $description, 'DESCRIZIONE 2' => $description2, 'IMPORTO' => $amount, 'shopid' => $shop_id, 'IMPORTO_PIU_IVA' => $pending_amount_vat);
        }

        $s3_file_path = "uploads/transaction/shopdailypay";
        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportTransactionFiles($file_path, $file_name, $s3_file_path, $shop_daily_registration_type, $head_data_array, $data, $item_type, $columns);

        //if file uploded then we will make database log for showing list in backend(admin panel).
        if ($result != '') { //means file uploaded on s3 server.
            $payment_logs_Object = $this->get('export_management.payment_logs'); //calling the service for making the payment/transaction logs
            //check the file type to be exported.
            $file_type = $this->container->getParameter('exported_file_type');
            if ($file_type == 'xls') {
                $file_name = $this->convertCsvFileName($file_name). '.xls';
            } else {
                $file_name = $file_name;
            }
            $payment_logs_Object->saveFileInfo($file_name, $type, $date);
        }
        return (($result != '') ? $result : '');
    }

    /**
     * Upload documents on s3 server
     * @param string $s3filepath
     * @param string $file_local_path
     * @param string $filename
     * @return string $file_url
     */
    public function s3imageUpload($s3filepath, $file_local_path, $filename) {
        $amazan_service = $this->get('amazan_upload_object.service');
        $file_url = $amazan_service->ImageS3UploadService($s3filepath, $file_local_path, $filename);
        return $file_url;
    }

    /**
     * Convert currency
     * @param int amount
     * @return float
     */
    public function convertCurrency($amount) {
        $final_amount = (float) $amount / 1000000;
        //return $final_amount;
        return number_format($final_amount, 2, '.', ''); //set precision 2 places.
    }

    /**
     * finding the csv file name
     * @param string $file_name
     * @return type
     */
    public function convertCsvFileName($file_name) {
        return rtrim($file_name, '.csv');
    }

}
