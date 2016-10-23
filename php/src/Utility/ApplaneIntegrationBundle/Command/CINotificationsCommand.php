<?php

// src/AppBundle/Command/GreetCommand.php

namespace Utility\ApplaneIntegrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;

class CINotificationsCommand extends ContainerAwareCommand implements ApplaneConstentInterface {

    protected function configure() {
        $this
                ->setName('ci:notifications')
                ->addArgument(
                    'date_start',
                    InputArgument::REQUIRED,
                    'Starting date dd-mm-yyyy ?'
                )
                    ->addArgument(
                    'date_end',
                    InputArgument::OPTIONAL,
                    'Ending date dd-mm-yyyy ?'
                )
                ->setDescription('notification for CI to users')
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
        $limit = 500;
        $skip = 0;  
        $is_sixc_data = 1;
        
        $start_date=$input->getArgument('date_start');
        $end_date=($input->getArgument('date_end')) ?$input->getArgument('date_end'):$start_date;


        
        $em = $this->getContainer()->get('doctrine')->getManager();
//        $applane_service = $this->getContainer()->get('appalne_integration.callapplaneservice');
        //get service for getting the admin id(loads in Utility/ApplaneIntegrationBundle/Resources/config/services.yml)
        $admin_service = $this->getContainer()->get('user.admin');
        //get admin id 
        $admin_id = $admin_service->getAdminId();
        $user_service = $this->getContainer()->get('user_object.service');
        $sender_data = $user_service->UserObjectService($admin_id);
        $postService = $this->getContainer()->get('post_detail.service');
        $users_array = array();
        $citizen_incomes = array();
        $info = array();
        $wallet_citizen = $this->getContainer()->get("wallet_manager");
        do {
            $users_array = array();
            $citizen_incomes = array();
            $info = array();
            $wallet_data = $wallet_citizen->getUserWithFullWallet($limit, $skip , $start_date , $end_date);
            $this->_log("[Query that hits the applane is : $start_date - $end_date  ]");
            $this->_log('sending the notification to users from:'.$skip.',to:'.($skip+ $limit));
            $skip = $skip + $limit;
            $this->_log("[Data from applane is : ".(string)json_encode($wallet_data , true)."]");
            if (count($wallet_data) > 0) {
                foreach ($wallet_data as $data) {
                    //$citizen_id = $data->_id;
                    //$citizen_id = $data->_id;
                    if($data["amount"] > 0 ){
                    $id = $data["buyer_id"];
                    $citizen_income = $data["citizen_income"] ;
                    $users_array[] = $id;
                    $citizen_incomes[$id] = $citizen_income;
                    $info[$id] = array('user_id' => $id, 'citizen_income' => $citizen_income);
                    }
                }
                $users_data = $user_service->MultipleUserObjectService($users_array);
                if(count($users_data) > 0) {
                try {
                    $postService->saveCitizenIncomeNotification($users_data, $citizen_incomes, $sender_data, $admin_id, 'TXN', 'TXN_CUST_CI_GAIN', 'I', 5, $info);                  
                    
                } catch (\Exception $ex) {
                    $this->_log("Expection occure:" . json_encode($ex->getMessage()));
                }
                }
            } else {
                $is_sixc_data = 0;
                $this->_log("[No responce from appalne : Job is completed]");
            }

        } while ($is_sixc_data);
    }

    /**
     *  function for writting the logs
     * @param type $sMessage
     */
    public function _log($sMessage) {
        $monoLog = $this->getContainer()->get('monolog.logger.cinotification_logs');
        $monoLog->info($sMessage);
    }

    function errorHandler($errno, $errstr, $errfile, $errline) {
        throw new \Exception($errstr, $errno);
    }

}
