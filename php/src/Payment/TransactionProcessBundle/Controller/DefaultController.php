<?php

namespace Payment\TransactionProcessBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('PaymentTransactionProcessBundle:Default:index.html.twig', array('name' => $name));
    }
}
