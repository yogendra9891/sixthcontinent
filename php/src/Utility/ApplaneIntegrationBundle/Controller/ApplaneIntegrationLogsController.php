<?php

namespace Utility\ApplaneIntegrationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ApplaneIntegrationLogsController extends Controller
{
    /**
     * download the file for logs
     * @param string $filename
     */
    public function applanetransactionlogsAction($filename = 'dev')
    {
        $file_names = array('dev', 'transaction', 'prod'); //define the file name.
        if (!in_array($filename, $file_names)) {
            $data = array('code'=>1051, 'message'=>'FILE_NAME_NOT_SUPPORTED', 'data'=>array());
            echo json_encode($data);
            exit;
        }
        try {
            $filePath = __DIR__."../../../../../app/logs/$filename.log";
            $file = $filename.'.log';
            header('Content-Type: application/txt');
            header("Content-Disposition: attachment; filename=$file");
            header("Content-Length: " . filesize($filePath));
            readfile($filePath);            
        } catch (\Exception $ex) {

        }
        exit;
    }
    
}
