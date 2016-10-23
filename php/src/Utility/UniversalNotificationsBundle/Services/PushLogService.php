<?php
namespace Utility\UniversalNotificationsBundle\Services;

class PushLogService{
    private $container;
    public function log($sMessage){
        
        $_log = sprintf("%s PushNotification[%d]: %s\n", date('r'), getmypid(), trim($sMessage));
        
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
