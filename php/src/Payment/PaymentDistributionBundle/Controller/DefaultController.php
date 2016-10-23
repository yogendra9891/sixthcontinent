<?php

namespace Payment\PaymentDistributionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('PaymentPaymentDistributionBundle:Default:index.html.twig', array('name' => $name));
    }
}
