<?php

namespace Utility\RatingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('UtilityRatingBundle:Default:index.html.twig', array('name' => $name));
    }
}
