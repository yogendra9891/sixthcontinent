<?php

namespace Utility\SecurityBundle\Controller;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Request;

interface TokenAuthenticatedController
{
    public function initialize(Request $request, SecurityContextInterface $security_context);
    
    // ...
}