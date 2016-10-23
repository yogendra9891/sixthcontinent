<?php

namespace Notification\NotificationBundle\Controller;

use Notification\NotificationBundle\Model\INotification;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Notification\NotificationBundle\Model;

/**
 * Define the sms class for sending the notification
 * @author admin
 *
 */
class SmsController extends Controller implements INotification
{
   
    public function sendAction($object)
    {
    	die('sms');
    }
}
