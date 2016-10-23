<?php

namespace Media\MediaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
/**
*commit..
*/
    public function indexAction($name)
    {
        return $this->render('MediaMediaBundle:Default:index.html.twig', array('name' => $name));
    }
}
