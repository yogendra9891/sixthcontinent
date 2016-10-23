<?php

namespace AdminUserManager\AdminUserManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('AdminUserManagerAdminUserManagerBundle:Default:index.html.twig', array('name' => $name));
    }
}
