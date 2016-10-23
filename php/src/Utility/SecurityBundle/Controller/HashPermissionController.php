<?php

namespace Utility\SecurityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Utility\SecurityBundle\Controller\TokenAuthenticatedController;
use Symfony\Component\Security\Core\SecurityContextInterface;



class HashPermissionController implements TokenAuthenticatedController 
{
    /*
     * function implementation
     */
    public function initialize(Request $request, SecurityContextInterface $security_context){
        
    }
  

}
