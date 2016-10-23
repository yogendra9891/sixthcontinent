<?php

namespace Transaction\WalletBundle\Controller;

use FOS\UserBundle\CouchDocument\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;
use Transaction\WalletBundle\Entity\ShoppingCard;

class ShoppingCardController extends Controller {

    public function indexAction($name) {
        exit("Yeah");
    }

    /**
     * utility service
     * @return type
     */
    protected function getUtilityService() {
        return $this->container->get('store_manager_store.storeUtility');
    }

    private function _getEntityManager() {
        return $this->getDoctrine()->getManager();
    }

    /**
     * Add ShoppingCard to wallet of the citizen
     * 
     * @param Request $request
     */
    public function addToCitizenWalletAction(Request $request) {
        $result_data = array();
        
        $utilityService = $this->getUtilityService();
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $requiredParams = array('buyer_id', 'offer_id' , 'sixc_transaction_id');
        if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
            $resp_data = new Resp($result['code'], $result['message'], array());
            Utility::createResponse($resp_data);
        }
        $coupo_result = $this->_getEntityManager()
                ->getRepository("WalletBundle:ShoppingCard")
                ->addToCitizenWallet($data);
        $resp_data = new Resp($coupo_result['code'], $coupo_result['message'], array());
        Utility::createResponse($resp_data); 
    }
}
