<?php

namespace Utility\RequestHandlerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * handling the curl request for test cases.
 */
class CurlTestCaseController extends Controller {

    /**
     * call service by curl.
     * @throws Exception
     */
    public function curlTestAction($req_data, $url) {
        $endpoint = $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req_data);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output);
        return $output;
    }

    /**
     * Function to retrieve current applications base URI(hostname/project/web)
     */
    public function getBaseUri() {
        // get the router context to retrieve URI information
        $context = $this->get('router')->getContext();
        // return scheme, host and base URL
        return $context->getScheme() . '://' . $context->getHost() . $context->getBaseUrl() . '/';
    }

}
