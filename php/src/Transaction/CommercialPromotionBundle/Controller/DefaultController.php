<?php

namespace Transaction\CommercialPromotionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('CommercialPromotionBundle:Default:index.html.twig', array('name' => $name));
    }
}
