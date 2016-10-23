<?php
namespace ExportManagement\ExportManagementBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;
use ExportManagement\ExportManagementBundle\Entity\PaymentExport;

// save th payment logs
class PaymentLogsService
{
    protected $em;
    
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em        = $em;
    }
    
    /**
     * saving the file info of uploaded file into database
     * @param string $file_name
     * @param int $type
     * @param string $date
     * @return true
     */
    public function saveFileInfo($file_name, $type, $date) {
        $em = $this->em; //getting doctrine object.
        //check if record is exists or not
        $result = count($em->getRepository('ExportManagementBundle:PaymentExport')
                     ->findOneBy(array('type'=>$type, 'filename'=>$file_name)));
        if (!$result) {
            try {
                $payment_export = new PaymentExport(); //making entity object
                $payment_export->setDate($date);
                $payment_export->setFilename($file_name);
                $payment_export->setType($type);
                $em->persist($payment_export);
                $em->flush();
            } catch (\Exception $ex) {}
            }
        return true;
    }
    
    /**
     * Convert the csv to xls
     * @param string $citizen_file_name
     */
    public function convertCsvToExcel($file_path, $citizen_file_name, $file_name) {
        //code for xls
        $objReader = \PHPExcel_IOFactory::createReader('CSV');
        $objReader->setDelimiter(",");
        // If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
        $objPHPExcel = $objReader->load($citizen_file_name);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $xls_file_name = rtrim($file_name, '.csv').'.xls';
        $objWriter->save($file_path.'/'.$xls_file_name);
        return true;
    }
 }
