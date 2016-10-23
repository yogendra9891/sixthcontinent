<?php

namespace Paypal\PaypalIntegrationBundle\Controller;


use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Paypal\PaypalIntegrationBundle\Entity\ShopPaypalInformation;
use Utility\ApplaneIntegrationBundle\Model\ApplaneConstentInterface;
use Utility\ApplaneIntegrationBundle\Event\FilterDataEvent;
use Utility\UtilityBundle\Utils\Utility;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('PaypalIntegrationBundle:Default:index.html.twig', array('name' => $name));
    }
    
    /**
     * buy 100% shopping cards.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * 
     */
    public function postCheckfeepayersAction(Request $request) {
        $data = array();
        $required_parameter = array('user_id', 'shop_id','type','item_type');
        $store_utility = $this->container->get('store_manager_store.storeUtility');
        $response = $store_utility->checkRequest($request, $required_parameter); //check for request object
        if ($response !== true) {
            $resp_data = new Resp($response['code'], $response['message'], $response['data']);
            $this->__createLog('Exiting from class [UserManager\Sonata\UserBundle\Controller\SellerController] and function [Registersellers] with response: ' . (string)$resp_data);
            Utility::createResponse($resp_data);
        }
        $de_serialize = $store_utility->getDeSerializeDataFromRequest($request);
        $shop_id = $de_serialize['shop_id'];
        $type = $de_serialize['type'];
        $item_type = $de_serialize['item_type'];
        $paypal_service = $this->container->get('paypal_integration.payment_transaction');
        $fee_payer = $paypal_service->getPaypalFeePayer($type,$shop_id,$item_type);
        echo "<pre>";
        print_r($fee_payer);
        exit;
    }
}
