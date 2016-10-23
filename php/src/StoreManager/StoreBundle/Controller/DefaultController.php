<?php

namespace StoreManager\StoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('StoreManagerStoreBundle:Default:index.html.twig', array('name' => $name));
    }
}
