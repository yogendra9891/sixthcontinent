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

class CitizenExportController extends Controller {

    protected $citizen_profile      = "/uploads/users/exportprofile/citizen";
    protected $citizen_profile_type = 'citizen';

    public function indexAction($name) {
        return $this->render('ExportManagementBundle:Default:index.html.twig', array('name' => $name));
    }

    /**
     * getting the citizen filename
     * @return string
     */
    public function getCitizenFileName()
    {
        $file_name = $this->citizen_profile_type."_" . date("Y-m-d") . ".csv";
        return $file_name;
    }
    /**
     * Exporting the citizen user profile
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function exportcitizenprofileAction(Request $request)
    {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit','512M');
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $citizen_profile_data = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')
                ->getCitizenUserProfile();
        if (count($citizen_profile_data)) {
            //exporting the data.
            $result = $this->exportcitizencsv($citizen_profile_data);
        }
        if (!empty($result)) {
         $data = array('code'=>101, 'message'=>'SUCCESS', 'data'=>array('link'=>$result));            
        } else {
         $data = array('code'=>100, 'message'=>'NO_PROFILE_FOR_EXPORT', 'data'=>array());            
        }
        echo json_encode($data);
        exit;
    }

    /**
     * Writing the file.
     * @param type $citizen_data
     */
    public function exportcitizencsv($citizen_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->citizen_profile;

        //creating the file name.
        $file_name = $this->getCitizenFileName();

        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $citizen_file_name = $file_path . "/" . $file_name;
        
        //check if file exist
        if (!file_exists($citizen_file_name)) {
            $fp = fopen($citizen_file_name, 'a');
            $head_data = array("id", "username", "email", "firstname", "lastname", "gender", "phone", "country", "dob", "region",
                "city", "address", "zip", "latitude", "longitude", "mapplace", "createdat");
            //Preparing the head for csv file.
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }            
        }

        if (file_exists($citizen_file_name)) {
            $fp = fopen($citizen_file_name, 'w'); //get the file object
            $head_data = array("id", "username", "email", "firstname", "lastname", "gender", "phone", "country", "dob", "region",
                "city", "address", "zip", "latitude", "longitude", "mapplace", "createdat");
            fputcsv($fp, $head_data);
            //taking the mongodb doctrine object
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            $citizen_profile_type = $this->citizen_profile_type;
            foreach ($citizen_data as $array_data) {
                $user_id    = $array_data['userId'];
                $user_name  = $array_data['username'];
                $email      = $array_data['email'];
                $first_name = $array_data['firstname'];
                $last_name  = $array_data['lastname'];
                $gender     = $array_data['gender'];
                $phone      = $array_data['phone'];
                $country    = $array_data['country'];
                $dob        = $array_data['dateOfBirth']->format('Y-m-d');
                $region     = $array_data['region'];
                $city       = $array_data['city'];
                $address    = $array_data['address'];
                $zip        = $array_data['zip'];
                $latitude   = $array_data['latitude'];
                $longitude  = $array_data['longitude'];
                $mapplace   = $array_data['mapPlace'];
                $created_at = $array_data['createdAt']->format('Y-m-d');
                
                $data = array('id'=>$user_id, 'username'=>$user_name, 'email'=>$email, 'firstname'=>$first_name,
                    'lastname'=>$last_name, 'gender'=>$gender, 'phone'=>$phone, 'country'=>$country,
                    'dob'=>$dob, 'region'=>$region, 'city'=>$city, 'address'=>$address, 'zip'=>$zip,
                    'latitude'=>$latitude, 'longitude'=>$longitude, 'mapplace'=>$mapplace, 'createdat'=>$created_at);
                fputcsv($fp, $data); //write the file
                //saving the data for logs
                $profile_export = new ProfileExport();
                $profile_export->setUserId($user_id);
                $profile_export->setType($citizen_profile_type);
                $dm->persist($profile_export);
                $dm->flush();
            }
        }
        fclose($fp); //close the file
        
        $s3_file_path = "uploads/users/exportprofile/citizen" ;
        $file_local_path = $file_path.'/'.$file_name;
        $exported_file = $this->s3imageUpload($s3_file_path, $file_local_path, $file_name);
        return (($exported_file!='')?$exported_file:'');
    }
    
    /**
     * Upload documents on s3 server
     * @param string $s3filepath
     * @param string $file_local_path
     * @param string $filename
     * @return string $file_url
     */
    public function s3imageUpload($s3filepath, $file_local_path, $filename)
    {
        $amazan_service = $this->get('amazan_upload_object.service');
        $file_url = $amazan_service->ImageS3UploadService($s3filepath, $file_local_path, $filename);
        return $file_url;
    }
    
    /**
     * Exporting the citizen user profile in second time(or further)
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function exportcitizenprofilebacklogsAction(Request $request)
    {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit','512M');
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $citizen_profile_logs_data = $dm->getRepository('ExportManagementBundle:ProfileExport')
                                        ->findBy(array('type'=>$this->citizen_profile_type), array('id'=> 'DESC'), 1, 0);
        $last_user_id = 0;
        $result       = '';
        
        //creating the file name.
        $file_name = $this->getCitizenFileName();
        //getting the last citizen profile exported id..
        if (count($citizen_profile_logs_data)) {
            $last_user_id = $citizen_profile_logs_data[0]->getUserId();
        }
        //getting the entity manager object.
        $em = $this->container->get('doctrine')->getManager();
        $citizen_profile_data = $em->getRepository('UserManagerSonataUserBundle:CitizenUser')
                                   ->getCitizenUserProfileBackLogs($last_user_id);
        
        //if any profileleft fro exporting..
        if (count($citizen_profile_data)) {
            //exporting the data.
            $Exported_result = $this->exportcitizencsvbacklogs($citizen_profile_data);
            $result = $this->getS3BaseUri().$this->citizen_profile . "/" . $file_name;
        }
       
        if ($result != '') {
            $data = array('code'=>101, 'message'=>'SUCCESS', 'data'=>array('link'=>$result)); 
        } else {
                $data = array('code'=>100, 'message'=>'NO_PROFILE_EXPORTED', 'data'=>array()); 
        }
                   
        echo json_encode($data);
        exit;
    }

    /**
     * Writing the file.
     * @param type $citizen_data
     */
    public function exportcitizencsvlogs($citizen_data) {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->citizen_profile;

        //creating the file name.
        $file_name = $this->getCitizenFileName();

        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $citizen_file_name = $file_path . "/" . $file_name;
        
        //check if file exist
        if (!file_exists($citizen_file_name)) {
            $fp = fopen($citizen_file_name, 'a');
            $head_data = array("id", "username", "email", "firstname", "lastname", "gender", "phone", "country", "dob", "region",
                "city", "address", "zip", "latitude", "longitude", "mapplace", "createdat");
            //Preparing the head for csv file.
            try {
                fputcsv($fp, $head_data);
            } catch (\Exception $ex) {
                
            }            
        }

        if (file_exists($citizen_file_name)) {
            $fp = fopen($citizen_file_name, 'a'); //get the file object
            //taking the mongodb doctrine object
            $dm = $this->get('doctrine.odm.mongodb.document_manager');
            $citizen_profile_type = $this->citizen_profile_type;
            foreach ($citizen_data as $array_data) {
                $user_id    = $array_data['userId'];
                $user_name  = $array_data['username'];
                $email      = $array_data['email'];
                $first_name = $array_data['firstname'];
                $last_name  = $array_data['lastname'];
                $gender     = $array_data['gender'];
                $phone      = $array_data['phone'];
                $country    = $array_data['country'];
                $dob        = $array_data['dateOfBirth']->format('Y-m-d');
                $region     = $array_data['region'];
                $city       = $array_data['city'];
                $address    = $array_data['address'];
                $zip        = $array_data['zip'];
                $latitude   = $array_data['latitude'];
                $longitude  = $array_data['longitude'];
                $mapplace   = $array_data['mapPlace'];
                $created_at = $array_data['createdAt']->format('Y-m-d');
                
                $data = array('id'=>$user_id, 'username'=>$user_name, 'email'=>$email, 'firstname'=>$first_name,
                    'lastname'=>$last_name, 'gender'=>$gender, 'phone'=>$phone, 'country'=>$country,
                    'dob'=>$dob, 'region'=>$region, 'city'=>$city, 'address'=>$address, 'zip'=>$zip,
                    'latitude'=>$latitude, 'longitude'=>$longitude, 'mapplace'=>$mapplace, 'createdat'=>$created_at);
                fputcsv($fp, $data); //write the file
                //saving the data for logs
                $profile_export = new ProfileExport();
                $profile_export->setUserId($user_id);
                $profile_export->setType($citizen_profile_type);
                $dm->persist($profile_export);
                $dm->flush();
            }
        }
        fclose($fp); //close the file
        
        $s3_file_path = "uploads/users/exportprofile/citizen" ;
        $file_local_path = $file_path.'/'.$file_name;
        $exported_file = $this->s3imageUpload($s3_file_path, $file_local_path, $file_name);
        return (($exported_file!='')?$exported_file:'');
    }
    
    /**
     * Export the users those are left from first attempt
     */
    public function exportcitizencsvbacklogs($citizen_profile_data) 
    {
        //create a file path
        $file_path = __DIR__ . "/../../../../web" . $this->citizen_profile;

        //creating the file name.
        $file_name = $this->getCitizenFileName();

        //check if a directory exists.
        if (!is_dir($file_path)) {
            \mkdir($file_path, 0777, true);
        }
        $citizen_file_name = $this->getS3BaseUri().$this->citizen_profile . "/" . $file_name;
        $destination_file = $file_path."/".$file_name;
        //copy the csv from s3 to local...
        @copy($citizen_file_name, $destination_file);
        $result_link = $this->exportcitizencsvlogs($citizen_profile_data);
        return $result_link;
    }
    
    /**
     * Function to retrieve s3 server base
     */
    public function getS3BaseUri() {
        //finding the base path of aws and bucket name
        $aws_base_path = $this->container->getParameter('aws_base_path');
        $aws_bucket    = $this->container->getParameter('aws_bucket');
        $full_path     = $aws_base_path.'/'.$aws_bucket;
        return $full_path;
    }
}
