<?php

namespace Payment\PaymentProcessBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('PaymentPaymentProcessBundle:Default:index.html.twig', array('name' => $name));
    }
}
