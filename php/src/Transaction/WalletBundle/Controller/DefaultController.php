<?php

namespace Transaction\WalletBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
Use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use FOS\RestBundle\Controller\FOSRestController;

class DefaultController extends Controller
{
    public function indexAction()
    {	
        $em = $this->getDoctrine()->getManager();

        /* Get wallet Data */
        $walletData = $em
                        ->getRepository('WalletBundle:WalletCitizen')
                        ->getCreditAvailableInShop(9732,50398);
        print_r($walletData);

        exit();
    }
}
