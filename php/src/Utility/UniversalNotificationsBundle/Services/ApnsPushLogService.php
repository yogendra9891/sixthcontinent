<?php
namespace Utility\UniversalNotificationsBundle\Services;
require_once dirname(dirname(__FILE__)).'/Resources/lib/ApnsPHP/PushAutoload.php';
use \ApnsPHP_Log_Interface ;
class ApnsPushLogService implements ApnsPHP_Log_Interface{
    private $container;
    public function log($sMessage){
        
        $_log = sprintf("%s ApnsPHP[%d]: %s\n", date('r'), getmypid(), trim($sMessage));
        
        $monoLog = $this->getContainer()->get('monolog.logger.push_logs');
        $monoLog->info($_log);
        
    }
    
    protected function getContainer(){
        global $kernel;
        if(!$this->container){
            $this->container = $kernel->getContainer();
        }
        return $this->container;
    }
}
