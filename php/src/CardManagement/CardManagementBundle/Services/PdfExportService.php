<?php

namespace CardManagement\CardManagementBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;

require_once(__DIR__ . '/../Resources/lib/tcpdf/tcpdf_include.php');
require_once(__DIR__ . '/../Resources/lib/tcpdf/tcpdf.php');

use TCPDF;

// validate the data.like iban, vatnumber etc
class PdfExportService {

    protected $em;

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    /**
     * Varify Iban Number
     * @param string $iban
     * @return boolean
     */
    public function generatePdf($file_path_csv, $location, $attchment_name) {
        //$html = $this->getHtmlOfCsv($file_path_csv);
        $html = $file_path_csv;
        $template = htmlspecialchars_decode($html);
        
    //    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A2', true, 'UTF-8', false);
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'EN_MEDIUM', true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SixthContinent');
        $pdf->SetTitle('Contract');
        $pdf->SetSubject('Contract');
        $pdf->SetKeywords('TCPDF, PDF, sixthcontinent, test, guide');
        // set default header data

        $pdf->SetHeaderData('logo.png', '40', '', '');
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        //$pdf->SetDisplayMode('default','continuous');
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // ---------------------------------------------------------
        // set font
        $pdf->SetFont('helvetica', '', 9);
        // add a page
        $pdf->AddPage();

        // output the HTML content
        $pdf->writeHTML($template, true, 0, true, 0);

        // reset pointer to the last page
        $pdf->lastPage();
        // ---------------------------------------------------------
        //Close and output PDF document
        $attachment_path = $location;

        if (!file_exists($attachment_path)) {
            if (!mkdir($attachment_path, 0777, true)) {
                return false;
            }
        }
        $attachment_path_name = $location . "/" . $attchment_name;
        // die;
        ob_clean();
      //  ob_end_clean();
        $pdf->Output($attachment_path_name, 'F');
        return $attachment_path_name;
    }

    /**
     * 
     * @param type $file_path_csv
     * @return string
     */
    public function getHtmlOfCsv($file_path_csv) {
        $row = 1;
        if (($handle = fopen($file_path_csv, "r")) !== FALSE) {

            $html = '<table border="1">';

            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $num = count($data);
                if ($row == 1) {
                    $html .= '<thead><tr>';
                } else {
                    $html .= '<tr>';
                }

                for ($c = 0; $c < $num; $c++) {
                    //echo $data[$c] . "<br />\n";
                    if (empty($data[$c])) {
                        $value = "&nbsp;";
                    } else {
                        $value = $data[$c];
                    }
                    if ($row == 1) {
                        $html .= '<th>' . $value . '</th>';
                    } else {
                        $html .= '<td>' . $value . '</td>';
                    }
                }

                if ($row == 1) {
                    $html .= '</tr></thead><tbody>';
                } else {
                    $html .= '</tr>';
                }
                $row++;
            }

            $html .= '</tbody></table>';
            fclose($handle);
            return $html;
        }
    }

}
