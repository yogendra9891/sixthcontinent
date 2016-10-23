<?php
namespace Utility\UniversalNotificationsBundle\EventListener;

use Utility\UniversalNotificationsBundle\Controller\TokenAuthenticatedController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class TokenListener
{
    private $tokens;

//    public function __construct($tokens)
//    {
//        $this->tokens = array(
//            'tcqWu8FQ6BqdqDCv8fC3Vbz2aYkZghBeyFeujJ9voRNVMtQfqEmwn29ujtRW63or'
//        );
//    }
    
    public function __construct()
    {
        $this->tokens = array(
            'tcqWu8FQ6BqdqDCv8fC3Vbz2aYkZghBeyFeujJ9voRNVMtQfqEmwn29ujtRW63or'
        );
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }
        $request =$event->getRequest();
        $checkForRoutes = array('utility_send_universal_notifications');
        $route = $request->attributes->get('_route');
        if (in_array($route, $checkForRoutes) and $controller[0] instanceof TokenAuthenticatedController) {
            $token = $request->query->get('token');
            if (!in_array($token, $this->tokens)) {
                throw new AccessDeniedHttpException('This action needs a valid token!');
            }
        }
    }
}