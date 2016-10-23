<?php
namespace StoreManager\StoreBundle\Services;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
// service method  class
class CurlService
{ 
   /**
    * Curl service to call remote server
    * @param array $de_serialize
    * @param string $remoteUrl
    * @throws \StoreManager\StoreBundle\Services\NotFoundException
    * @throws NotFoundException
    */
    public function socialBeesRemoteServer($de_serialize, $remoteUrl) {
        //check for valid request
        try {
            // create a new cURL resource
            $ch = curl_init();
            $timeout = 5;
            $data = $de_serialize;
            // set URL and other appropriate options
            curl_setopt($ch, CURLOPT_URL, $remoteUrl);
            //TRUE to do a regular HTTP POST.
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            //TRUE to return the transfer as a string of the return value
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            // grab URL and pass it to the browser
            $data_response = curl_exec($ch);
            // close cURL resource, and free up system resources
            curl_close($ch);
           
            return true;
       } catch (NotFoundException $e) {
            if (Configure::read('debug')) {
                throw $e;
            }
            throw new NotFoundException($e);
        }
        exit;
    }
    
    public function shoppingplusClientRemoteServer($fields, $remoteUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$remoteUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
         $postvars = '';
         foreach($fields as $key=>$value) {
           $postvars .= $key . "=" . $value . "&";
         } 
         // echo $postvars; 
         rtrim($postvars, '&');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
        $output = curl_exec($ch);
        // $info = curl_getinfo($ch);
       // echo'<pre>'; print_r($output); echo'</pre>';
        curl_close($ch);
        return $output;
    }
    public function shoppingplusCitizenRemoteServer($fields, $remoteUrl) {
        $ch = curl_init();
        //$remoteUrl = "http://localhost/test/shoppingplus.php";
        curl_setopt($ch, CURLOPT_URL,$remoteUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        $postvars = '';
        foreach($fields as $key=>$value) {
           $postvars .= $key . "=" . $value . "&";
         } 
         // echo $postvars; 
        rtrim($postvars, '&');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
        $output = curl_exec($ch);
       
        curl_close($ch);
        return $output;
    }
    
    /**
     * Convert white space to html
     * @param string $string
     */
    public function convertSpaceToHtml($string){
        $string_trim = trim($string);
        $string_html = str_replace ( ' ', '%20', $string_trim);
        return $string_html;
    }
   
    
}