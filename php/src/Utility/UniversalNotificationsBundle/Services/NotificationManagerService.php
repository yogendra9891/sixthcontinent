<?php

namespace Utility\UniversalNotificationsBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use WalletManagement\WalletBundle\Entity\UserDiscountPosition;
use StoreManager\StoreBundle\Controller\ShoppingplusController;
use Utility\UtilityBundle\Utils\Utility;

// service method  class
class NotificationManagerService {

    protected $em;
    protected $dm;
    protected $container;
    
    const NOTIFICATION_TRUE  = TRUE;
    const NOTIFICATION_FALSE  = FALSE;
    const DEFAULT_NOTIFICATION_SETTING  = FALSE;

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
        //$this->request   = $request;
    }
    
    /**
     *  function for getting the user notification
     * @param type $user_id
     * @param type $notification_setting
     * @param type $notification_type
     * @param type $user_type
     * @return boolean
     */
    public function getUserNotificationSetting($user_id, $notification_setting, $notification_type, $user_type) {
        try {

            $notification_setting = Utility::getUpperCaseString(Utility::getTrimmedString($notification_setting));
            $notification_type = Utility::getUpperCaseString(Utility::getTrimmedString($notification_type));
            $user_type = Utility::getUpperCaseString(Utility::getTrimmedString($user_type));
            $em = $this->em;
            //get the notification setting form the DB for the user
            $notification_settings = $em
                    ->getRepository('UtilityUniversalNotificationsBundle:NotifictionManager')
                    ->findBy(array('userId' => $user_id, 'notificationType' => $notification_type, 'userType' => $user_type));

            if (!$notification_settings) {
                return self::NOTIFICATION_FALSE;
            } else {
                if (isset($notification_settings[0])) {
                    $setting_record = $notification_settings[0];
                    $setting_binary_value = $setting_record->getNotificationSetting();
                    $settings = Utility::convertNotificationSettingToBinary($setting_binary_value);
                    if (isset($settings[$notification_setting])) {
                        if ($settings[$notification_setting] == 1) {
                            return self::NOTIFICATION_TRUE;
                        } else {
                            return self::NOTIFICATION_FALSE;
                        }
                    } else {
                        return self::DEFAULT_NOTIFICATION_SETTING;
                    }
                } else {
                    return self::DEFAULT_NOTIFICATION_SETTING;
                }
            }
        } catch (\Exception $ex) {
            return self::NOTIFICATION_FALSE;
        }
    }

}