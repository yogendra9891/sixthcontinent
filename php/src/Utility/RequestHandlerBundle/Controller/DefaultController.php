<?php

namespace Utility\RequestHandlerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('UtilityRequestHandlerBundle:Default:index.html.twig', array('name' => $name));
    }
}
