<?php

namespace SixthContinent\SixthContinentConnectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('SixthContinentConnectBundle:Default:index.html.twig', array('name' => $name));
    }
}
