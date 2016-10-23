<?php

// src/AppBundle/Command/GreetCommand.php

namespace Utility\ApplaneIntegrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;

class CIRedistributionNotificationsCommand extends ContainerAwareCommand implements ApplaneConstentInterface {

    protected function configure() {
        $this
                ->setName('ci:redistribution_notifications')
                ->setDescription('notification for CI redstribution to users')
                ->addOption(
               'days',
                10,
                 InputOption::VALUE_REQUIRED,
                'Check for days remaining for CI distribution',
                10
            )
        ;
    }

    /**
     *  function that will execute on console command fophp app/console ci:notification
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        // FIX: Need to fix the implementation for reduce the memory consumption
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        set_error_handler(array($this, 'errorHandler'), E_ALL ^ E_NOTICE);
        $is_send = 1;
        try{
            $limit = $this->getContainer()->getParameter('appalne_record_fatch_limit');
        } catch (\Exception $ex) {
            $limit = 500;
        }
        try{
            $skip = $this->getContainer()->getParameter('appalne_record_fatch_offset');
        } catch (\Exception $ex) {
            $skip = 0;
        }
        
        $is_applane_data = 1;
        $days = $input->getOption('days');
        $url_query = self::CITIZEN_FOR_CI_REDISTRIBUTION;
        $query_query = self::URL_QUERY;
        $em = $this->getContainer()->get('doctrine')->getManager();
        $applane_service = $this->getContainer()->get('appalne_integration.callapplaneservice');
        //get service for getting the admin id(loads in Utility/ApplaneIntegrationBundle/Resources/config/services.yml)
        $admin_service = $this->getContainer()->get('user.admin');
        //get admin id 
        $admin_id = $admin_service->getAdminId();
        $user_service = $this->getContainer()->get('user_object.service');
        $sender_data = $user_service->UserObjectService($admin_id);
        $postService = $this->getContainer()->get('post_detail.service');

        do {
            $users_array = array();
            $days_left = array();
            $info = array();
            $final_data = $this->prepareApplaneDataForCIRedistribution($limit, $skip,$days);
            $this->_log("[Query that hits the applane is with params :".  json_encode($final_data)."]");
            $this->_log('sending the notification to users from:'.$skip.',to:'.($skip+ $limit));
            $skip = $skip + $limit;
            $applane_resp = $applane_service->callApplaneServiceWithParams($url_query, $final_data);
            $this->_log("[Data from applane is : $applane_resp]");
            $decode_applane_resp = json_decode($applane_resp);
            $data_appalne = isset($decode_applane_resp->response) ? $decode_applane_resp->response : (object) array();
            if (count($data_appalne) > 0) {
                foreach ($data_appalne as $data) {
                    $citizen_id = isset($data->citizen_id) ? $data->citizen_id : 0;
                    //check if the citizen id is numeric value 
                    if(is_numeric($citizen_id)) {
                    $users_array[] = $citizen_id;
                    $days = isset($data->day) ? $data->day : 0;
                    $days_left[$citizen_id] = $days;
                    $info[$citizen_id] = array('user_id' => $citizen_id, 'days_left' => $days);
                    } else {
                        $this->_log("[User Id that is inconstinent is User_id : $citizen_id]");
                    }
                }
                
                $users_data = $user_service->MultipleUserObjectService($users_array);
                if(count($users_data) > 0) {
                try {
                    $postService->saveCitizenIncomeReDistributionNotification($users_data, $days_left, $sender_data, $admin_id, 'TXN', 'TXN_CUST_CI_REDISTRIBUTION', 'I', 5, $info);                  
                    
                } catch (\Exception $ex) {
                    $this->_log("Expection occure:" . json_encode($ex->getMessage()));
                }
                }
                $is_applane_data = 1;
            } else {
                $is_applane_data = 0;
                $this->_log("[No responce from appalne : Job is completed]");
            }

        } while ($is_applane_data);
    }

    /**
     *  function for preparing the query params for the Applane call
     * @param type $limit
     * @param type $skip
     * @return type
     */
    public function prepareApplaneDataForCIRedistribution($limit, $skip,$days) {
        $query_params = array();
        $query_params['day'] = $days;
        $query_params['limit'] = $limit;
        $query_params['skip'] = $skip;
        
        return $query_params;
    }

    /**
     *  function for writting the logs
     * @param type $sMessage
     */
    public function _log($sMessage) {
        $monoLog = $this->getContainer()->get('monolog.logger.ci_redistribution');
        $monoLog->info($sMessage);
    }

    function errorHandler($errno, $errstr, $errfile, $errline) {
        throw new \Exception($errstr, $errno);
    }
    
}
