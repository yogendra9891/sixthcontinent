<?php

namespace UserManager\Sonata\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse; 
use FOS\RestBundle\Controller\FOSRestController;


class FacebookloginController extends FOSRestController {
    
    /**
     * Facebook login
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function facebookloginAction(Request $request)
    {
        die('sd');
    }
    
}