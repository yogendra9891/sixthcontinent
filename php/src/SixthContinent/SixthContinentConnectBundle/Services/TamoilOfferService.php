<?php
namespace SixthContinent\SixthContinentConnectBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Utility\BarcodeBundle\Utils\BarcodeGenerator;
require_once(dirname(dirname(dirname(__DIR__))).'/CardManagement/CardManagementBundle/Resources/lib/tcpdf/tcpdf_include.php');
require_once(dirname(dirname(dirname(__DIR__))).'/CardManagement/CardManagementBundle/Resources/lib/tcpdf/tcpdf.php');
use TCPDF;
use SixthContinent\SixthContinentConnectBundle\Entity\BarCode;
use SixthContinent\SixthContinentConnectBundle\Entity\CodesConsumption;
class TamoilOfferService {
    protected $em;
    protected $dm;
    protected $container;
    
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }
    
    public function createCoupon($userId, $offer){
        $this->_log('[TamoilOfferService:createCoupon] Getting active coupon');
        $coupon = $this->em->getRepository('SixthContinentConnectBundle:CouponToActive')
                ->getSingleActiveCoupon();
        if(!$coupon){
            $this->_log('[TamoilOfferService:createCoupon] Coupon not found. [Line:'.__LINE__.']', true);
            return;
        }
        $this->_log('[TamoilOfferService:createCoupon] Getting codes for active coupon.  [Line:'.__LINE__.']');
        $codes = $this->em->getRepository('SixthContinentConnectBundle:Codes')
                ->findByCouponToActiveId($coupon->getId());
        if(empty($codes)){
            $this->_log('[TamoilOfferService:createCoupon] Codes not found.  [Line:'.__LINE__.']', true);
            return;
        }
        $barcodes = $_codes = array();
        $this->_log('[TamoilOfferService:createCoupon] Creating barcode A.  [Line:'.__LINE__.']');
        if(isset($codes[0])){
            $_codes['a'] = $codes[0]->getCode();
            $barcodes['a'] =  $this->_createBarcode($_codes['a'], BarcodeGenerator::Code128);
        }
        $this->_log('[TamoilOfferService:createCoupon] Creating barcode B.  [Line:'.__LINE__.']');
        if(isset($codes[1])){
            $_codes['b'] = $codes[1]->getCode();
            $barcodes['b'] =  $this->_createBarcode($_codes['b'], BarcodeGenerator::Code128);
        }
        try{
            $this->_log('[TamoilOfferService:createCoupon] Getting user ['.$userId.'] info.  [Line:'.__LINE__.']');
            $postService = $this->container->get('post_detail.service');
            $receiver = $postService->getUserData($userId);
//            $_date = new \DateTime('now');
//            $_date->add(new \DateInterval('P1Y'));
            $couponExpiry = $coupon->getExpiredDate()->format('d/m/Y');
            $this->_log('[TamoilOfferService:createCoupon] Updating coupon_to_active table.  [Line:'.__LINE__.']');
            $coupon->setUserId($userId);
            $coupon->setIsActive(2);
            $this->em->persist($coupon);
            $this->em->flush();
            $this->_log('[TamoilOfferService:createCoupon] Updating Barcode A in table.  [Line:'.__LINE__.']');
            if(isset($barcodes['a'])){
                $bcodeA = new BarCode();
                $bcodeA->setCodeId($_codes['a']);
                $bcodeA->setHashCode($barcodes['a']);
                $bcodeA->setImagePath('');
                $this->em->persist($bcodeA);
            }
            $this->_log('[TamoilOfferService:createCoupon] Updating Barcode B in table.  [Line:'.__LINE__.']');
            if(isset($barcodes['b'])){
                $bcodeB = new BarCode();
                $bcodeB->setCodeId($_codes['b']);
                $bcodeB->setHashCode($barcodes['b']);
                $bcodeB->setImagePath('');
                $this->em->persist($bcodeB);
            }
            $this->em->flush();
            
            $filename = $this->getRandomString(20, true, true, true) .'.pdf';
            $couponFile = basename($filename);
            $this->_log('[TamoilOfferService:createCoupon] Updating CodeConsumption table.  [Line:'.__LINE__.']');
            $this->saveCodeConsumption($userId, $offer['offer_id'], $offer['transaction_id'], $coupon->getId(), $couponFile);
            
            $this->_log('[TamoilOfferService:createCoupon] Trying to generate pdf.  [Line:'.__LINE__.']');
            $couponPdfPath = $this->_generatePdf($barcodes, $coupon->getOrderNumber(), $couponExpiry, $filename);
            $this->_log('[TamoilOfferService:createCoupon] Trying to send coupon on mail.  [Line:'.__LINE__.']');
            $this->sendCoupon($receiver, $coupon, $offer, $couponPdfPath);
        }catch(\Exception $e){
            $this->_log('[TamoilOfferService:createCoupon] '. $e->getMessage().' [Line:'.__LINE__.']', true);
            return false;
        }
        $this->_log('[TamoilOfferService:createCoupon] Coupon generated and sent on mail process complete.  [Line:'.__LINE__.']');
        return true;
    }
    
    private function _createBarcode($text, $type){
        $this->_log('[TamoilOfferService:_createBarcode] Generating barcodes for '.$text.' and type: '.$type.' [LINE:'.__LINE__.']');
        $barcode = new BarcodeGenerator();
        $barcode->setText($text);
        $barcode->setType($type);
        $barcode->setScale(2);
        $barcode->setThickness(25);
        $code = $barcode->generate();
        $this->_log('[TamoilOfferService:_createBarcode] Created barcode for '.$text.'.  [Line:'.__LINE__.']');
        return $code;
    }
    
    private function _generatePdf(array $barcodes, $orderNumber, $couponExpiryDate, $filename){
        $this->_log('[TamoilOfferService:_generatePdf] Generating pdf with barcodes');
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('SixthContinent');
        $pdf->SetTitle('Tamoil Offer - SixthContinent');
        $pdf->SetSubject('Tamoil Offer');
        $pdf->SetKeywords('SixthContinent');

        // set header and footer fonts
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // This method has several options, check the source code documentation for more information.
        $pdf->AddPage();

        // set text shadow effect
        //$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));
        $img_file = dirname(__DIR__). '/Resources/coupon/bg-tamiol-email.jpg';
        $pdf->Image($img_file, 5, 20, 200, 60, '', '', '', false, 300, '', false, false, 0);
        
        if(!empty($barcodes['a'])){ 
            $pdf->Image('@'.base64_decode($barcodes['a']), 150, 31, 48, 15, '', '', '', true, 100, '', false, false, 0);
        }
        if(!empty($barcodes['b'])){ 
            $pdf->Image('@'.base64_decode($barcodes['b']), 150, 53, 48, 15, '', '', '', true, 100, '', false, false, 0);
        }
        // Set some content to print
        $html = '<table cellpadding="8" cellspacing="5" border="0" style=" background-size: 800px 241px;margin: 0 auto; font-family: sans-serif; color: #111; height: 241px; width: 800px;">
                                        <tr>
                                                <td style="vertical-align: top;" width="168">
                                                </td>
                                                <td  width="80" style="vertical-align: top; font-weight: bold; font-size: 12px; text-align: right;">
                                                        <div><br/><br/><br/><span style="display: block;padding-bottom:25px;margin-bottom:25px;">'.$couponExpiryDate.'</span></div>
                                                </td>
                                                <td style="vertical-align: top; padding: 10px 5px; font-size: 12px; width: 228px;">
                                                </td>
                                                <td style="vertical-align: top; text-align: left;width: 327px;">
                                                        <div style="font-size: 15px; font-weight: bold;">
                                                        <br/>
                                                         &nbsp;&nbsp; Ordine N. '.$orderNumber.'
                                                        </div>
                                                        <div style="margin: 0 0 0px; text-align: left;">                                          </div>				
                                                </td>
                                        </tr>
                                </table>';

        // Print text using writeHTMLCell()
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        
        $attachment_path = dirname(dirname(dirname(dirname(__DIR__))))."/web/uploads/attachments/coupons/";
        if (!file_exists($attachment_path)) {
            try{
                $this->_log('[TamoilOfferService:_generatePdf] Creating folder - '.$attachment_path);
                if (!mkdir($attachment_path, 0777, true)) {
                    $this->_log('[TamoilOfferService:_generatePdf] Unable to create folder - '.$attachment_path, true);
                    return false;
                }
            }catch(\Exception $e){
                $this->_log('[TamoilOfferService:_generatePdf] Unable to create folder - '.$attachment_path.' with Exception : '. $e->getMessage(), true);
            }
        }
        $filePath = $attachment_path.$filename;
        $this->_log('[TamoilOfferService:_generatePdf] Saving coupon pdf file - '.$filePath);
        $pdf->Output($filePath, 'F');
        return $filePath;
    }
    
    public  function getRandomString($length=10, $uppercase=true, $lowercsae=false, $number=false){
        $u = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $l = 'abcdefghjkmnpqrstuvwxyz';
        $n = '123456789';
        $string = $u;
        $string .= $lowercsae ? $l : '';
        $string .= $number ? $n : '';
        $randomStr = str_shuffle(str_shuffle($string));
        $pickStr = substr($randomStr, 0, $length);
        return $pickStr;
    }
    
    private function sendCoupon($receiver, $coupon, $offer, $couponPdf){
        $receiver = is_array($receiver) ? $receiver : array();
        $this->_log('[TamoilOfferService:sendCoupon] Sending coupon file '.$couponPdf.' in a mail to user: '.  json_encode($receiver));
        $postService = $this->container->get('post_detail.service');
        if(!empty($receiver)){
            try{
                $email_template_service = $this->container->get('email_template.service');
                $templateId = $this->container->getParameter('sendgrid_tamoil_coupon_template');

                $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
                $accept_link = $angular_app_hostname.'specialoffer/'.$offer['offer_id'];
                //$shopUrl = $this->container->getParameter('shop_profile_url');
                //$href = $angular_app_hostname.$shopUrl.'/[shop_id]';
                $locale = isset($receiver['current_language']) ? $receiver['current_language'] : $this->container->getParameter('locale');
                $lang_array = $this->container->getParameter($locale);
                
                $current_locale = $lang_array['TIME_ZONE_LOCALE'];
                // get pre set locale
                $oldLocale = setlocale(LC_TIME, $current_locale);
                $current_date= utf8_encode( strftime("%d %B %Y", time()) );
                setlocale(LC_TIME, $oldLocale);
                $viewLink = ' <a href="'.$accept_link.'">'.$lang_array['CLICK_HERE'].'</a>';
                $tdate = new \DateTime('now');
                //$tdate->add(new \DateInterval('P5D'));

                $activatedDate = date('d/m/Y', strtotime($tdate->format('Y-m-d') . ' +4 Weekday'));
                //$activatedDate = $tdate->format('d/m/Y');
                $replaceTxt = array(
                    'link'=>$viewLink,
                    'price'=> '&euro;'.$offer['offer_value'],
                    'order_number'=>$coupon->getOrderNumber(),
                    'activatedDate'=>$activatedDate
                    );
                
                $templateParams = array();
                $toName = trim(ucfirst($receiver['first_name']).' '.ucfirst($receiver['last_name']));
                $templateParams['to'][] = $receiver['email'];
                $templateParams['sub']['[receiver_name]'][] = $toName;
                $templateParams['sub']['[buy_date]'][] = $current_date;
                $templateParams['sub']['[body_title]'][] = $postService->_updateByGivenText($lang_array['TAMOIL_OFFER_BODY'], $replaceTxt);
                $templateParams['sub']['[body_text_1]'][] = $lang_array['TAMOIL_OFFER_TEXT_1'];
                $templateParams['sub']['[body_text_2]'][] = $postService->_updateByGivenText($lang_array['TAMOIL_OFFER_TEXT_2'], $replaceTxt);
                $templateParams['sub']['[body_text_3]'][] = $postService->_updateByGivenText($lang_array['TAMOIL_OFFER_TEXT_3'], $replaceTxt);
                $templateParams['sub']['[desc_title]'][] = $lang_array['TAMOIL_OFFER_DESC'];
                $templateParams['sub']['[desc_offer_title]'][] = $lang_array['TAMOIL_OFFER_DESC_TITLE'];
                $templateParams['sub']['[desc_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_CI_TITLE'];
                $templateParams['sub']['[desc_shop_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_SHOP_TITLE'];
                $templateParams['sub']['[desc_offer_val]'][] = $offer['offer_value'];
                $templateParams['sub']['[desc_ci_val]'][] = $offer['used_ci'];
                $templateParams['sub']['[desc_shop_ci_val]'][] = $offer['cash_amount'];
                
                $viewLinkText = $postService->_updateByGivenText($lang_array['TAMOIL_OFFER_LINK_TEXT'], $replaceTxt);
                $templateParams['sub']['[view_detail_link]'][] = $viewLinkText;
                
                        
                //$this->_log('Email sent - '. json_encode($templateParams['to']), 'cardsold_notifications');
                $bodyData = "<br/>";
                $subject = $lang_array['TAMOIL_OFFER_SUBJECT'];
                $response = $email_template_service->sendMailWithCustomParams($templateParams, $subject, $bodyData, $templateId, "TAMOIL_COUPON", $couponPdf);
                $this->_log('[TamoilOfferService:sendCoupon] Coupon mail has been sent to user : '. json_encode($receiver).' [LINE:'.__LINE__.']');
            } catch (\Exception $ex) {
                $this->_log('[TamoilOfferService:sendCoupon] Failed to send coupon file '.$couponPdf.' in a mail to userID: '.$userId. ' with Exception: '. $ex->getMessage(), true);
            }
        }
    }
    
    
    private function _log($message, $isError=false){
        $monolog = $this->container->get('monolog.logger.tamoil_coupon');
        if($isError===true){
            $monolog->error($message);
        }else{
            $monolog->info($message);
        }
    }
    
    /**
     * save the consumed code and coupon information
     * @param int $user_id
     * @param int $offer_id
     * @param int $transaction_id
     * @param int $coupon_id
     * @param string $coupon_name
     * @return boolean
     */
    public function saveCodeConsumption($user_id, $offer_id, $transaction_id, $coupon_id, $coupon_name) {
        $em = $this->em;
        $time = new \DateTime('now');
        $code_consumption = new CodesConsumption();
        $code_consumption->setUserId($user_id);
        $code_consumption->setType('');
        $code_consumption->setTypeId($offer_id);
        $code_consumption->setOfferId($offer_id);
        $code_consumption->setTransactionId($transaction_id);
        $code_consumption->setCodeId(0);
        $code_consumption->setCouponId($coupon_id);
        $code_consumption->setCoupon($coupon_name);
        $code_consumption->setDate($time);
        try {
            $em->persist($code_consumption);
            $em->flush();
        } catch (\Exception $ex) {
            $this->_log('[TamoilOfferService:saveCodeConsumption] '. $ex->getMessage(), true);
        }
        return true;
    }
}
