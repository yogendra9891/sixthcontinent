<?php

namespace Utility\BarcodeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Utility\BarcodeBundle\Utils\BarcodeGenerator;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        $couponService = $this->container->get('tamoil_offer.coupon');
        $coupon = $couponService->createCoupon($name, array(
            'offer_value'=>25,
            'offer_id'=>1, 
            'transaction_id'=>2, 
            'discount'=>0, 
            'used_ci'=>12.5, 
            'cash_amount'=>12.5
            ));
        return new Response($coupon ? 'Coupon sent on mail.' : 'Error occured while sending coupon on mail.');
//        $barcode = new BarcodeGenerator();
//        $barcode->setText($name);
//        $barcode->setType(BarcodeGenerator::Code128);
//        $barcode->setScale(2);
//        $barcode->setThickness(25);
//        $code = $barcode->generate();
//        return new Response('<img src="data:image/png;base64,'.$code.'" />');
    }
}
