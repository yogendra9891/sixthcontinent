<?php

// src/AppBundle/Command/GreetCommand.php

namespace Utility\ApplaneIntegrationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;

class CINotificationCommand extends ContainerAwareCommand implements ApplaneConstentInterface {

    protected function configure() {
        $this
                ->setName('ci:notification')
                ->setDescription('notification for CI to users')
        ;
    }

    /**
     *  function that will execute on console command fophp app/console ci:notification
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $final_data = $this->prepareApplanDateForCitizenIncomeGain();
        $this->_log('Applane data created successfully');
        $url_query = self::QUERY_CODE;
        $query_query = self::URL_QUERY;
        $em = $this->getContainer()->get('doctrine')->getManager();
        $applane_service = $this->getContainer()->get('appalne_integration.callapplaneservice');
        $this->_log('Calling the applane service');
        $applane_resp = $applane_service->callApplaneService($final_data, $url_query, $query_query);
        $this->_log('applane service calls succesfully');
        //getting the applane responce
        $data_appalne = json_decode($applane_resp);    
        //check if status id ok
        if ($data_appalne->code == 200) {
            $postService = $this->getContainer()->get('post_detail.service');
            $data_appalne = $data_appalne->response;
            $data_appalnes = $data_appalne->result;
            $this->_log('start sending the notifications to the users');
            foreach ($data_appalnes as $data) {
                $msg_type = 'CITIZEN_INCOME_GAIN';
                $msg = 'Today you gain';
                $itemId = 1;
                $citizen_id = $data->_id;
                $citizen_id = $citizen_id->citizen_id;
                $citizen_credit = $data->credit;
                $citizen_credit = floor($citizen_credit*100)/100;
                $postService->sendCitizenIncomeNotification($citizen_id,$citizen_credit,true,true);
            }
        } else {
            echo "some error occured";
        }
    }

    public function prepareApplanDateForCitizenIncomeGain() {
       $gt_date  = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y')) ));
       $lt_date  = date(DATE_RFC3339, (mktime(0, 0, 0, date('n'), date('j'), date('Y')) + 60 * 60 * 24));
        
        $filter = array();
        $filter['__history.__createdOn'] = (object) array('$gte' => $gt_date, '$lt' => $lt_date);
        $group = array();
        $group['_id'] = (object) array('citizen_id' => '$citizen_id._id');
        $group['credit'] = (object) array('$sum' => '$credit');
        $group['$filter'] = (object) array("credit" => array('$gte' => self::CI_NOTIFICATION_AMOUNT));
        $data = array();
        $data['$collection'] = self::SIX_CONTINENT_CITIZEN_BUCKS_COLLECTION;
        $data['$filter'] = (object) $filter;
        $data['$group'] = (object) $group;
        $data = (object) $data;
        $data = json_encode($data);
        return $data;
    }
    
    /**
     *  function for writting the logs
     * @param type $sMessage
     */
     public function _log($sMessage){
        $monoLog = $this->getContainer()->get('monolog.logger.cinotification_logs');
        $monoLog->info($sMessage);
    }

}
