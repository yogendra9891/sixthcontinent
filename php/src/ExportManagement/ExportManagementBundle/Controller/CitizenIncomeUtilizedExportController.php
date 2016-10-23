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

/**
 * Export citizen income export that is utilized.
 */
class CitizenIncomeUtilizedExportController extends Controller {

    protected $citizen_income_utilized = "/uploads/transaction/citizenincomeutilized";
    protected $citizen_income = "citizenincome";
    protected $citizen_income_utilized_type = 'citizenincome';
    protected $citizen_income_utilized_database_log_type = 2;

    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * getting the citizen income file name
     * @return string
     */
    public function getCitizenIncomeUtilizedFileName() {
        $file_name = $this->citizen_income . "_" . date("Y-m-d") . ".csv";
        return $file_name;
    }

    /**
     * Exporting the citizen income utilized 
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function exportcitizenincomeutilizedAction(Request $request) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $citizen_income_data = $em->getRepository('AcmeGiftBundle:Movimen')
                ->getCitizenIncomeUtilized();
        if (count($citizen_income_data)) {
            //exporting the data.
            $result = $this->exportcitizenincomecsv($citizen_income_data);
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
     * @param type $citizen_income_data
     */
    public function exportcitizenincomecsv($citizen_income_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->citizen_income_utilized;

        //creating the file name.
        $file_name = $this->getCitizenIncomeUtilizedFileName();
        $type = $this->citizen_income_utilized_database_log_type;
        $date = new \DateTime('now');
        $item_type = 'user';
        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        //$citizen_file_name = $file_path . "/" . $file_name;
        $data = array();
        $head_data_array = array("userid", "shopid", "incomeutilized");
        $columns = array('C');
        //check if file exist
        //taking the mongodb doctrine object
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $citizen_income_type = $this->citizen_income_utilized_type;
        foreach ($citizen_income_data as $array_data) {
            $user_id = $array_data['user_id'];
            $shop_id = $array_data['shop_id'];
            $income_utilized = $this->convertCurrency($array_data['income']);

            $data[] = array('userid' => $user_id, 'shopid' => $shop_id, 'incomeutilized' => $income_utilized);
        }

        $payment_logs_Object = $this->get('export_management.payment_logs'); //calling the service for making the payment/transaction logs and change the file format
        $s3_file_path = "uploads/transaction/citizenincomeutilized";

        //call the service for exporting the file.
        $convert_files = $this->container->get('export_management.convert_exported_files');
        $result = $convert_files->ExportTransactionFiles($file_path, $file_name, $s3_file_path, $citizen_income_type, $head_data_array, $data, $item_type, $columns);

        if ($result != '') { //means file uploaded on s3 server.
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
    public function convertCurrency($amount)
    {
        $final_amount = (float)$amount/1000000;
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
