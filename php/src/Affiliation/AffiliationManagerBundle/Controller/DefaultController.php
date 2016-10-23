<?php

namespace Affiliation\AffiliationManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('AffiliationAffiliationManagerBundle:Default:index.html.twig', array('name' => $name));
    }
}
