<?php
/**
 * Define the interface
 * @author admin
 *
 */
namespace Notification\NotificationBundle\Model;

interface INotification
{
    public function sendAction($object);
}