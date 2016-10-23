<?php

namespace ExportManagement\ExportManagementBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\Session\Session;
use ExportManagement\ExportManagementBundle\Document\ProfileExport;
use Ijanki\Bundle\FtpBundle\Exception\FtpException;
use ExportManagement\ExportManagementBundle\Model\ExportConstantInterface;

// convert the exported files.
class ConvertExportedFilesService {

    protected $em;
    protected $dm;
    protected $container;

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
     * Exporting the data into file(shop/citizen).
     * @param string $file_path
     * @param string $file_name
     * @param string $file_name_date
     * @param string $sheet_name
     * @param string $file_log_type
     * @param array $head_data
     * @param array $data
     */
    public function ExportFiles($file_path, $file_name, $file_name_date, $sheet_name, $file_log_type, $head_data, $data, $column_format=array(), $column_left=array(), $column_cast=array()) {
        //making the local path for file
        $local_file_path = $file_path . "/" . $file_name_date;
        //check the file type to be exported.
        $file_type = $this->container->getParameter('exported_file_type');
        //check if file exist
        if (!file_exists($local_file_path)) {
            $fp = fopen($local_file_path, 'a');
            //Preparing the head for csv file.
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
        }

        if (file_exists($local_file_path)) {
            $fp = fopen($local_file_path, 'w'); //get the file object
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }

            foreach ($data as $array_data) {
                $current_row = $array_data;
                $user_id = $current_row['ID'];
                try {
                    fputcsv($fp, $current_row); //write the file
                } catch (\Exception $ex) {
                    
                }
                //saving the data for logs
                $profile_export = new ProfileExport();
                $profile_export->setUserId($user_id);
                $profile_export->setType($file_log_type);
                $this->dm->persist($profile_export);
            }
            $this->dm->flush();
        }
        fclose($fp); //close the file
        if ($file_type == 'xls') { //for excel
            $new_file_name              = $this->convertCsvFileName($file_name_date) . '.XLSX';
            $new_file_name_without_date = $this->convertCsvFileName($file_name) . '.XLSX';
            $file_local_path = $file_path . '/' . $new_file_name;
            $this->createExcelFile($file_path, $local_file_path, $file_name, $file_name_date, $sheet_name, $column_format, $column_left, $column_cast);
        }
        try {
//            $exported_file = $this->uploadonFtp($file_local_path, $new_file_name); //upload file without date in file name
//            $exported_file = $this->uploadonFtp($file_local_path, $new_file_name_without_date); //upload file with date in file  
//            return $exported_file;
            return 1;
        } catch (\Exception $ex) {
            return 0;
        }
    }
    
    /**
     * Exporting the data into file(transacrion).
     * @param string $file_path
     * @param string $file_name
     * @param string $s3_file_path
     * @param string $file_log_type
     * @param array $head_data
     * @param array $data
     * @type string $type
     */
    public function ExportTransactionFiles($file_path, $file_name, $file_name_date, $sheet_name, $file_log_type, $head_data, $data, $column_format=array(), $column_left=array(), $column_cast=array()) {
        //making the local path for file
        $local_file_path = $file_path . "/" . $file_name;
        //check the file type to be exported.
        $file_type = $this->container->getParameter('exported_file_type');
        //check if file exist
        if (!file_exists($local_file_path)) {
            $fp = fopen($local_file_path, 'a');
            //Preparing the head for csv file.
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
        }

        if (file_exists($local_file_path)) {
            $fp = fopen($local_file_path, 'w'); //get the file object
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }

            foreach ($data as $array_data) {
                $current_row = $array_data;
                try {
                    fputcsv($fp, $current_row); //write the file
                } catch (\Exception $ex) {
                    
                }
            }
        }
        fclose($fp); //close the file
        if ($file_type == 'xls') { //for excel
            $new_file_name              = $this->convertCsvFileName($file_name_date) . '.XLSX';
            $new_file_name_without_date = $this->convertCsvFileName($file_name) . '.XLSX';
            $file_local_path = $file_path . '/' . $new_file_name;
            $this->createExcelFile($file_path, $local_file_path, $file_name, $file_name_date, $sheet_name, $column_format, $column_left, $column_cast);
        }
        try {
//            $exported_file = $this->uploadonFtp($file_local_path, $new_file_name); //upload file without date in file name
//            $exported_file = $this->uploadonFtp($file_local_path, $new_file_name_without_date); //upload file with date in file  
//            return $exported_file;
            return 1;
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * Upload documents on s3 server
     * @param string $s3filepath
     * @param string $file_local_path
     * @param string $filename
     * @return string $file_url
     */
    public function s3imageUpload($s3filepath, $file_local_path, $filename) {
        $amazan_service = $this->container->get('amazan_upload_object.service');
        $file_url = '';
        try {
            $file_url = $amazan_service->ImageS3UploadService($s3filepath, $file_local_path, $filename);
        } catch (\Exception $ex) {

        }
        return $file_url;
    }

    /**
     * Creating the excel file.
     * @param string $file_local_path
     */
    public function createExcelFile($file_path, $file_local_path, $file_name, $file_name_date, $sheet_name, $cloumns_format=array(), $column_left=array(), $column_cast=array()) {

        //code for xls
        $objReader = \PHPExcel_IOFactory::createReader('CSV');
        $objReader->setDelimiter(",");
        // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
        $objPHPExcel = $objReader->load($file_local_path);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $xls_file_name = $this->convertCsvFileName($file_name_date) . '.XLSX';
        $file = $file_path . '/' . $xls_file_name; //get file path
        $objWriter->save($file); //convert csv to xls.

        
        $excel = \PHPExcel_IOFactory::load($file); //load excel class
        $worksheet = $excel->getActiveSheet()->setTitle($sheet_name); //set sheet title.
        
        if (count($cloumns_format) > 0) {
                $highestRow = $worksheet->getHighestRow();
                foreach ($cloumns_format as $cloumn) {
                    $cloumn1 = $cloumn."1";
                    $worksheet->getStyle("$cloumn1:$cloumn$highestRow")->getNumberFormat()->setFormatCode('0.00');
                }
        }
        //get column for left align.
        if (count($column_left) > 0) {
                //convert column for format
                $highestRow = $worksheet->getHighestRow();
                foreach ($column_left as $cloumn) {
                    $cloumn1 = $cloumn."1";
                    //get the cloumn data left align
                   $worksheet->getStyle("$cloumn1:$cloumn$highestRow")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                }
        }
        
         //get column for left align.
        if (count($column_cast) > 0) {
                $i = 1;
                //convert column for cast
                $highestRow = $worksheet->getHighestRow();
               // $column_cast1 = $column_cast[0];
                foreach ($column_cast as $column_cast1) {
                    $i = 1;
                    while($i <= $highestRow) {
                        $cloumn1 = $column_cast1.$i;
                        $column_value = $worksheet->getCell($cloumn1)->getvalue();
                        $column_value_string = ltrim($column_value, '&');
                        $worksheet->setCellValueExplicit($cloumn1, $column_value_string, \PHPExcel_Cell_DataType::TYPE_STRING);
                        $i++;
                    }
                }
        }
        //save file
        $objWriter = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $objWriter->save($file);
        chmod($file, 0777);
        return true;
    }

    /**
     * convert csv to xml
     * @param string $file_path
     * @param string $file_local_path
     * @param string $file_name
     */
    public function convertCsvToXml($file_path, $file_local_path, $file_name) {
        $xml_file_name = $this->convertCsvFileName($file_name) . '.xml';
        $file_name_xml = $file_path . "/" . $xml_file_name;

        $inputFilename = $file_local_path; //local file to be open
        $outputFilename = $file_name_xml;

        // Open csv to read
        $inputFile = fopen($inputFilename, 'rt');
        // Get the headers of the file
        $headers = fgetcsv($inputFile);

        // Create a new dom document with pretty formatting
        $doc = new \DomDocument();
        $doc->formatOutput = true;

        // Add a root node to the document
        $root = $doc->createElement('rows');
        $root = $doc->appendChild($root);

        // Loop through each row creating a <row> node with the correct data
        while (($row = fgetcsv($inputFile)) !== FALSE) {
            $container = $doc->createElement('row');
            foreach ($headers as $i => $header) {
                $child = $doc->createElement($header);
                $child = $container->appendChild($child);
                $value = $doc->createTextNode($row[$i]);
                $value = $child->appendChild($value);
            }
            $root->appendChild($container);
        }
        //$doc->saveXML();
        $strxml = $doc->saveXML();
        $handle = @fopen($outputFilename, "w");
        fwrite($handle, $strxml);
        fclose($handle);
    } 

    /**
     * finding the csv file name
     * @param string $file_name
     * @return type
     */
    public function convertCsvFileName($file_name) {
        return rtrim($file_name, '.csv');
    }

    
   /**
     * generate the pdf.
     * @param type $file_path
     * @param type $file_name
     * @param type $head_data
     * @param type $data
     */
    public function createPdf($file_path, $file_name, $head_data, $data) {
        $pdf_service = $this->container->get('card_management.pdf_export');
        $content = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
			"http://www.w3.org/TR/html4/loose.dtd">
			<html>
			<head>
			<title>Title</title></head>
			<body>';
        if (count($data) > 0) {
            $content.= '<page>';
            // Change the $content according to your requrement
            $content.= '<table border="0" cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #000;margin-top:0;margin-left:auto;margin-right:auto;font-family:arial;margin-bottom:2px;" >';
            $content.= '<tr>';
            $c = count($head_data);
            $d = ceil(100/$c);
            foreach ($head_data as $head_data_array) { 
                $content.= '<td style="width:60px;font-family:arial;font-weight:bold;font-size:14px;color:#666; border: 1px solid #000; text-align:center;">'.$head_data_array.'
                           </td>';
            }
            $content.= '</tr>';
            foreach ($data as $data_result) {
               $data_field_array = array_values($data_result);
               $content.= '<tr>';
               foreach ($data_field_array as $data_field) {
                   $content.= '<td style="width:60px;font-family:arial;font-weight:bold;font-size:14px;color:#666; border: 1px solid #000; text-align:center;">'.$data_field.'
                           </td>';
               }
               $content.= '</tr>';
            }
            $content.= '</table>';

            $content.= '</page>';
        }

        $content.='</body></html>';
        $result = $pdf_service->generatePdf($content, $file_path, $file_name);
    }
    
    /**
     * upload file on FTP.
     * @param string $file_local_path
     * @param string $new_file_name
     */
    public function uploadonFtp($file_local_path, $new_file_name) {
        $container = $this->container;
        $host      = $container->getParameter('ftp_host');
        $username  = $container->getParameter('ftp_username');
        $password  = $container->getParameter('ftp_password');
        $applane_service = $this->container->get('appalne_integration.callapplaneservice'); //transaction system service for logs
        try {
            $ftp = $container->get('ijanki_ftp');
            $ftp->connect($host);
            $ftp->login($username, $password);
            $e = $ftp->put($new_file_name, $file_local_path, FTP_BINARY); //upload the file on ftp server
             if ($e == 1) {
                $applane_service->writeTransactionLogs($new_file_name.' start uploading success. ', $new_file_name.' end uploading success.');  //write logs in success case.
                return 1;
            }
            $applane_service->writeTransactionLogs($new_file_name.' start uploading failed.', $new_file_name.' end uploading failed.');  //write logs in failed case.
            return 0;
        } catch (FtpException $e) {
           $applane_service->writeTransactionLogs($new_file_name.' start uploading exception case. ', $new_file_name.' '. $e->getMessage());  //write logs in failed case.
           return 0;
        }
        
    }
    
    /**
     * Exporting the data into file(transaction) sales
     * @param string $file_path
     * @param string $file_name
     * @param string $file_name_date
     * @param string $sheet_name
     * @param string $file_log_type
     * @param array $head_data
     * @param array $data
     * @param array $column_format
     * @param array $column_left
     */
    public function ExportTransactionSalesFiles($file_path, $file_name, $file_name_date, $sheet_name, $file_log_type, $head_data, $data, $column_format=array(), $column_left=array(), $column_cast=array()) {
        //making the local path for file
        $local_file_path = $file_path . "/" . $file_name;
        //check the file type to be exported.
        $file_type = $this->container->getParameter('exported_file_type');
        //check if file exist
        if (!file_exists($local_file_path)) {
            $fp = fopen($local_file_path, 'a');
            //Preparing the head for csv file.
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }
        }

        if (file_exists($local_file_path)) {
            $fp = fopen($local_file_path, 'w'); //get the file object
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }

            foreach ($data as $array_data) {
                $current_row = $array_data;
                try {
                    fputcsv($fp, $current_row); //write the file
                } catch (\Exception $ex) {
                    
                }
            }
        }
        fclose($fp); //close the file
        if ($file_type == 'xls') { //for excel
            $new_file_name              = $this->convertCsvFileName($file_name_date) . '.XLSX';
            $new_file_name_without_date = $this->convertCsvFileName($file_name) . '.XLSX';
            $file_local_path = $file_path . '/' . $new_file_name;
            $this->createSalesExcelFile($file_path, $local_file_path, $file_name, $file_name_date, $sheet_name, $column_format, $column_left, $column_cast);
        }
        try {
//            $exported_file = $this->uploadonFtp($file_local_path, $new_file_name); //upload file without date in file name
//            $exported_file = $this->uploadonFtp($file_local_path, $new_file_name_without_date); //upload file with date in file  
//            return $exported_file;
            return 1;
        } catch (\Exception $ex) {
            return 0;
        }
    }
    
        /**
     * Creating the excel file.
     * @param string $file_local_path
     */
    public function createSalesExcelFile($file_path, $file_local_path, $file_name, $file_name_date, $sheet_name, $cloumns_format=array(), $column_left=array(), $column_cast=array()) {

        //code for xls
        $objReader = \PHPExcel_IOFactory::createReader('CSV');
        $objReader->setDelimiter(",");
        // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
        $objPHPExcel = $objReader->load($file_local_path);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $xls_file_name = $this->convertCsvFileName($file_name_date) . '.XLSX';
        $file = $file_path . '/' . $xls_file_name; //get file path
        $objWriter->save($file); //convert csv to xls.

        
        $excel = \PHPExcel_IOFactory::load($file); //load excel class
        $worksheet = $excel->getActiveSheet()->setTitle($sheet_name); //set sheet title.
        
        if (count($cloumns_format) > 0) {
                $highestRow = $worksheet->getHighestRow();
                foreach ($cloumns_format as $cloumn) {
                    $cloumn1 = $cloumn."1";
                    $worksheet->getStyle("$cloumn1:$cloumn$highestRow")->getNumberFormat()->setFormatCode('0.00');
                }
        }
        //get column for left align.
        if (count($column_left) > 0) {
                //convert column for format
                $highestRow = $worksheet->getHighestRow();
                foreach ($column_left as $cloumn) {
                    $cloumn1 = $cloumn."1";
                    //get the cloumn data left align
                   $worksheet->getStyle("$cloumn1:$cloumn$highestRow")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                }
        }
        $row = 2;
        $description2_column = ExportConstantInterface::SALES_DESCRIPTION2_COLUMN;
        $codice_column       = ExportConstantInterface::SALES_CODICE_COLUMN;
        //handling for new line.
        while($row <= $highestRow) {
            $codice_value = $worksheet->getCell($codice_column.$row)->getValue();
            if (($codice_value == ExportConstantInterface::TEN_PERCENT_CODICE) || ($codice_value == ExportConstantInterface::SIX_PERCENT_CODICE)) {
                $cell_value   = $worksheet->getCell($description2_column.$row)->getValue();
                $explode_data = explode('\n', $cell_value);
                $imploded_value = implode("\n", $explode_data);
                $worksheet->setCellValue($description2_column.$row, $imploded_value);
                $worksheet->getStyle($description2_column.$row)->getAlignment()->setWrapText(true);
            }
            $row++;
        }
        
        //get column for casting to string
        if (count($column_cast) > 0) {
                $i = 1;
                //convert column for cast
                $highestRow = $worksheet->getHighestRow();
                $column_cast1 = $column_cast[0];
                while($i <= $highestRow) {
                    $cloumn1 = $column_cast1.$i;
                    $column_value = $worksheet->getCell($cloumn1)->getvalue();
                    $worksheet->setCellValueExplicit($cloumn1, $column_value, \PHPExcel_Cell_DataType::TYPE_STRING);
                    $i++;
                }
        }
        //save file
        $objWriter = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $objWriter->save($file);
        chmod($file, 0777);
        return true;
    }
}
