<?php

namespace Utility\MasterDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('MasterDataBundle:Default:index.html.twig', array('name' => $name));
    }
}
