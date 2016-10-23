<?php

namespace Dashboard\DashboardManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('DashboardManagerBundle:Default:index.html.twig', array('name' => $name));
    }
}
