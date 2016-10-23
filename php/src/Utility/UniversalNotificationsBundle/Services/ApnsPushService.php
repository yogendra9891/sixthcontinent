<?php
namespace Utility\UniversalNotificationsBundle\Services;
use Utility\UniversalNotificationsBundle\Services\ApnsPushLogService;
error_reporting(-1);
require_once dirname(dirname(__FILE__)).'/Resources/lib/ApnsPHP/PushAutoload.php';

class ApnsPushService {
    private $isDev = true;
    private $certificate;
    
    public function __construct($certificate, $isDev=true) {
        $this->certificate = $certificate;
        $this->isDev = $isDev;
    }
    
    public function initPush(){
        $push = new \ApnsPHP_Push(
                        $this->isDev==true ? \ApnsPHP_Abstract::ENVIRONMENT_SANDBOX : \ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
                        $this->certificate
                );
        $push->setLogger(new ApnsPushLogService);
        return $push;
    }

    public function createPush($devicetoken){
        $message = new \ApnsPHP_Message($devicetoken);
        return $message;
    }
    
    public function sendSample($certificate, $token){
        // Instanciate a new ApnsPHP_Push object
        $push = new \ApnsPHP_Push(
                \ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
                $certificate
        );

        // Set the Root Certificate Autority to verify the Apple remote peer
        //$push->setRootCertificationAuthority('entrust_root_certification_authority.pem');

        // Increase write interval to 100ms (default value is 10ms).
        // This is an example value, the 10ms default value is OK in most cases.
        // To speed up the sending operations, use Zero as parameter but
        // some messages may be lost.
        // $push->setWriteInterval(100 * 1000);

        // Connect to the Apple Push Notification Service
        $push->connect();

        for ($i = 1; $i <= 2; $i++) {
                // Instantiate a new Message with a single recipient
                $message = new \ApnsPHP_Message($token);

                // Set a custom identifier. To get back this identifier use the getCustomIdentifier() method
                // over a ApnsPHP_Message object retrieved with the getErrors() message.
                $message->setCustomIdentifier(sprintf("Message-%03d", $i));

                // Set badge icon to "3"
                $message->setBadge($i);
                $message->setText('Sixthcontinent Test - '. $i);

                // Add the message to the message queue
                $push->add($message);
        }

        // Send all messages in the message queue
        $push->send();

        // Disconnect from the Apple Push Notification Service
        $push->disconnect();

        // Examine the error message container
        $aErrorQueue = $push->getErrors();
        if (!empty($aErrorQueue)) {
                var_dump($aErrorQueue);
        }
    }
}
