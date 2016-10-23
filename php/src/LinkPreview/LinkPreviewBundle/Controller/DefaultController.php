<?php

namespace LinkPreview\LinkPreviewBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('LinkPreviewLinkPreviewBundle:Default:index.html.twig', array('name' => $name));
    }
}
