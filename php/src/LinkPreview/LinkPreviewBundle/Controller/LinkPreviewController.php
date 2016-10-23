<?php

namespace LinkPreview\LinkPreviewBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use PostFeeds\PostFeedsBundle\Document\PostFeeds;
use Symfony\Component\HttpFoundation\Response;
use Utility\UtilityBundle\Utils\Utility;
use Utility\UtilityBundle\Utils\Response as Resp;


class LinkPreviewController extends Controller
{
    /**
     * Getting Link Preview
     * @param request object
     * @return json
     */
    public function getLinkPreviewAction(Request $request) {

        $link_preview_service = $this->container->get('linkpreview.share');
        $data = array();
        $utilityService = $this->getUtilityService();
        $requiredParams = array('text', 'imagequantity');
        if($request->get('reqObj')){
            //handling for json data
            if (($result = $utilityService->checkRequest($request, $requiredParams)) !== true) {
                $resp_data = new Resp($result['code'], $result['message'], array());
                $link_preview_service->__createLog('Exiting from class [ LinkPreview\LinkPreviewBundle\Controller\LinkPreviewController] and function [getLinkPreviewAction] with response: ' . (string) $resp_data);
                Utility::createResponse($resp_data);
            }
            $data = $utilityService->getDeSerializeDataFromRequest($request);
        }else{
            //handling for form submitted data
             $data['text'] = $request->get('text');
             if($data['text'] == ''){
                 $this->returnResponse('text');
             }
             $data['imagequantity'] = $request->get('imagequantity');
              if($data['imagequantity'] == ''){
                 $this->returnResponse('imagequantity');
             }
        }
        $response = $link_preview_service->getLinkPreview($data);
        $link_preview_service->__createLog('Exiting from class [ LinkPreview\LinkPreviewBundle\Controller\LinkPreviewController] and function [getLinkPreviewAction] with response: ' . (string) $response);
        echo $response;
        exit;
    }
    
    /**
     * Return response
     */
    private function returnResponse($msg) {
        $link_preview_service = $this->container->get('linkpreview.share');
        $data = array();
        $result = array('code' => 1001, 'message' => 'YOU_HAVE_MISSED_A_PARAMETER_' . strtoupper($msg), 'data' => $data);
        $resp_data = new Resp($result['code'], $result['message'], array());
        $link_preview_service->__createLog('Exiting from class [ LinkPreview\LinkPreviewBundle\Controller\LinkPreviewController] and function [getLinkPreviewAction] with response: ' . Utility::encodeData($resp_data));
        Utility::createResponse($resp_data);
    }

    /**
     * 
     * @return type
     */
    protected function getUtilityService() {
        return $this->container->get('store_manager_store.storeUtility'); //StoreManager\StoreBundle\Utils\UtilityService
    }
}
