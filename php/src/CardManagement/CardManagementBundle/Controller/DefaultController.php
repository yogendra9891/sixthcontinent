<?php

namespace CardManagement\CardManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('CardManagementBundle:Default:index.html.twig', array('name' => $name));
    }
}
