<?php
namespace SixthContinent\SixthContinentConnectBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use SixthContinent\SixthContinentConnectBundle\Repository\SixthcontinentconnecttransactionRepository;
use SixthContinent\SixthContinentConnectBundle\Entity\Sixthcontinentconnecttransaction;
use Transaction\TransactionSystemBundle\Entity\Transaction;
use Transaction\WalletBundle\Entity\AmilonCard;
use Transaction\WalletBundle\Repository\AmilonCardRepository;
use Transaction\WalletBundle\Repository\CodePaidRepository;
use SoapClient;

class AmilonOfferService {
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
    
    private function sendCoupon($receiver , $offer , $offer_detail ){
        $receiver = is_array($receiver) ? $receiver : array();
        $this->_log('[AmilonOfferService:sendCoupon] Sending email  in a mail to user: '.  json_encode($receiver));
        $postService = $this->container->get('post_detail.service');
        if(!empty($receiver) && $offer_detail["result"]["commercialPromotionTypeId"] != "352306" ){ // this will b removed
            try{
                $email_template_service = $this->container->get('email_template.service');
                //$templateId = $this->container->getParameter('sendgrid_tamoil_coupon_template');
                $templateId ="25698369-0e79-4386-8ab5-4833fb514046";

                $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
                $accept_link = $angular_app_hostname.'voucher/'.$offer['offer_id']."/50916";
                $wallet_link = $angular_app_hostname.'wallets';
                $link_url = "<a href='$wallet_link'>qui</a>";
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
                    'activatedDate'=>$activatedDate
                    );
                
                $templateParams = array();
                $toName = trim(ucfirst($receiver['first_name']).' '.ucfirst($receiver['last_name']));
                $templateParams['to'][] = $receiver['email'];
                $templateParams['sub']['[receiver_name]'][] = $toName;
                $templateParams['sub']['[buy_date]'][] = $current_date;
                $templateParams['sub']['[body_title]'][] = "Acquisto Voucher";
                if($offer_detail["result"]["commercialPromotionTypeId"] >= 352850 &&  $offer_detail["result"]["commercialPromotionTypeId"]  <= 352901 ){ // Those are all ups type
                    $templateParams['sub']['[body_text_1]'][] = "Complimenti, il tuo Acquisto di Buoni QuiTicket si è concluso con successo. 
                    <br><br>
                    Entro 20 giorni ti arriverà quanto ordinato direttamente al tuo indirizzo.
                    <br><br>
                    Come indicato nei “Termini e Condizioni” della Pagina Offerta QuiTicket l’indirizzo 
                    di spedizione è quello che risulta nella tua Pagina Profilo al momento del tuo acquisto.
                    I Termini e le Condizioni di utilizzo le trovi sulla relativa Pagina Offerta
                    <br><br>
                    La compagnia di spedizione ti invierà un’e-mail che ti avvisa della partenza e dell’arrivo del pacco.
                    Qualora non fosse possibile consegnare, il pacco rimarrà in deposito presso il centro di smistamento del nostro vettore più vicino al tuo indirizzo per 10 giorni lavorativi (Sabato compreso) e verrai avvisato con apposita mail sulle modalità per il ritiro.
                    <br><br>
                    Ti ringraziamo per gli Acquisti Consapevoli eseguiti su SixthContinent.com,
                    utili a te e a tutta la comunità mondiale per costruire
                    un’Economia Equa e Sostenibile nel tempo.
                    <br><br>
                    Per qualunque esigenza puoi scrivici a supporto@sixthcontinent.com
                    ";
                }else{
                $templateParams['sub']['[body_text_1]'][] = "Acquisto avvenuto con successo.<br>Il tuo Voucher sara' attivo dal ".$activatedDate."<br>"
                        . "Vai nella Sezione Voucher del tuo Portafoglio, oppure clicca $link_url.";
                }
                $templateParams['sub']['[url_card_amilon]'][] = $offer_detail["result"]["promotion_type"]["defaultImg"];
                $templateParams['sub']['[body_text_3]'][] = $postService->_updateByGivenText($lang_array['TAMOIL_OFFER_TEXT_3'], $replaceTxt);
                $templateParams['sub']['[desc_title]'][] = "Acquisto Voucher ";
                $templateParams['sub']['[desc_offer_title]'][] = " Voucher costo";
                $templateParams['sub']['[desc_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_CI_TITLE'];
                $templateParams['sub']['[desc_shop_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_SHOP_TITLE'];
                $templateParams['sub']['[desc_offer_val]'][] = $offer['offer_value'];
                $templateParams['sub']['[desc_ci_val]'][] = $offer['used_ci'];
                $templateParams['sub']['[desc_shop_ci_val]'][] = $offer['cash_amount'];
                
                $viewLinkText = $postService->_updateByGivenText($lang_array['TAMOIL_OFFER_LINK_TEXT'], $replaceTxt);
                $templateParams['sub']['[view_detail_link]'][] = $viewLinkText;
                
                        
                //$this->_log('Email sent - '. json_encode($templateParams['to']), 'cardsold_notifications');
                $bodyData = "<br/>";
                $subject =  "Acquisto Voucher";
                $response = $email_template_service->sendMailWithCustomParams($templateParams, $subject, $bodyData, $templateId, "AMILON_COUPON");
                $this->_log('[AmilonOfferService:sendCoupon] Coupon mail has been sent to user : '. json_encode($receiver).' [LINE:'.__LINE__.']');
            } catch (\Exception $ex) {
                $this->_log('[AmilonOfferService:sendCoupon] Failed to send coupon file  in a mail to userID: '.$userId. ' with Exception: '. $ex->getMessage(), true);
            }
        }else{
            $this->sendMsc($receiver , $offer , $offer_detail );
        }
    }
    public function sendUps($receiver , $offer , $offer_detail ){
        $receiver = is_array($receiver) ? $receiver : array();
        $this->_log('[AmilonOfferService:sendMsc] Sending email  in a mail to user: '.  json_encode($receiver));
        $postService = $this->container->get('post_detail.service');
        if(!empty($receiver)  ){
            try{
                $email_template_service = $this->container->get('email_template.service');
                //$templateId = $this->container->getParameter('sendgrid_tamoil_coupon_template');
                $templateId ="beae9bd1-f36b-432c-8c5e-70d61eb8fa30";

                $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
                $accept_link = $angular_app_hostname.'voucher/'.$offer['offer_id']."/50916";
                $wallet_link = $angular_app_hostname.'wallets';
                $link_url = "<a href='$wallet_link'>qui</a>";
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
                    'activatedDate'=>$activatedDate
                    );
                
                $templateParams = array();
                $toName = trim(ucfirst($receiver['first_name']).' '.ucfirst($receiver['last_name']));
                $templateParams['to'][] = $receiver['email'];
                $templateParams['sub']['[receiver_name]'][] = $toName;
                $templateParams['sub']['[buy_date]'][] = $current_date;
                $templateParams['sub']['[body_title]'][] = "Acquisto Voucher";
                $templateParams['sub']['[body_text_1]'][] = "Complimenti per il Buono Acquisto. <br> UN REGALO DI NATALE DAVVERO SPECIALE com MSC CROCIERE <br><br>"
                    ."Ti ringraziamo per gli Acquisti Consapevoli eseguiti su SixthContinent,"
                    ."<br>utili a te e a tutta la comunità mondiale per costruire<br>un' Economia Equa e Sostenibile nel tempo.";
                $templateParams['sub']['[url_card_amilon]'][] = $offer_detail["result"]["promotion_type"]["defaultImg"];
                $templateParams['sub']['[body_text_3]'][] = $postService->_updateByGivenText($lang_array['TAMOIL_OFFER_TEXT_3'], $replaceTxt);
                $templateParams['sub']['[desc_title]'][] = "Acquisto Voucher ";
                $templateParams['sub']['[desc_offer_title]'][] = " Voucher costo";
                $templateParams['sub']['[desc_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_CI_TITLE'];
                $templateParams['sub']['[desc_shop_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_SHOP_TITLE'];
                $templateParams['sub']['[desc_offer_val]'][] = $offer['offer_value'];
                $templateParams['sub']['[desc_ci_val]'][] = $offer['used_ci'];
                $templateParams['sub']['[desc_shop_ci_val]'][] = $offer['cash_amount'];
                
                $viewLinkText = $postService->_updateByGivenText($lang_array['TAMOIL_OFFER_LINK_TEXT'], $replaceTxt);
                $templateParams['sub']['[view_detail_link]'][] = $viewLinkText;
                
                        
                //$this->_log('Email sent - '. json_encode($templateParams['to']), 'cardsold_notifications');
                $bodyData = "<br/>";
                $subject =  "Acquisto Voucher";
                $cofanetto = dirname(dirname(dirname(dirname(__DIR__))))."/web/uploads/attachments/msc/cofanetto.pdf";
                $response = $email_template_service->sendMailWithCustomParams($templateParams, $subject, $bodyData, $templateId, "MSC_COUPON" , $cofanetto );
                
                $templateParams['sub']['[receiver_name]'][] = "Acquisto Voucer, effettuato da ".$toName." <br> Email: ".$receiver['email'];
                $templateParams['to'][] = $this->container->getParameter('msc_mail');
                $templateParams['sub']['[desc_offer_val]'][] = $offer['offer_value'];
                $templateParams['sub']['[desc_ci_val]'][] = $offer['used_ci'];
                $templateParams['sub']['[desc_shop_ci_val]'][] = $offer['cash_amount'];
                $templateParams['sub']['[body_title]'][] = "Acquisto Voucer,  tramite SixthContinent";
                $templateParams['sub']['[desc_offer_title]'][] = " Voucher costo";
                $templateParams['sub']['[desc_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_CI_TITLE'];
                $templateParams['sub']['[desc_shop_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_SHOP_TITLE'];
                $response = $email_template_service->sendMailWithCustomParams($templateParams, $subject, $bodyData, $templateId, "MSC_COUPON_AGNCY"  );
                
                $this->_log('[AmilonOfferService:sendMsc] Coupon mail has been sent to user : '. json_encode($receiver).' [LINE:'.__LINE__.']');
            } catch (\Exception $ex) {
                $this->_log('[AmilonOfferService:sendMsc] Failed to send coupon file  in a mail to userID: '.$userId. ' with Exception: '. $ex->getMessage(), true);
            }
        }
        
    }
    public function sendMsc($receiver , $offer , $offer_detail ){
        $receiver = is_array($receiver) ? $receiver : array();
        $this->_log('[AmilonOfferService:sendMsc] Sending email  in a mail to user: '.  json_encode($receiver));
        $postService = $this->container->get('post_detail.service');
        if(!empty($receiver)  ){
            try{
                $email_template_service = $this->container->get('email_template.service');
                //$templateId = $this->container->getParameter('sendgrid_tamoil_coupon_template');
                $templateId ="beae9bd1-f36b-432c-8c5e-70d61eb8fa30";

                $angular_app_hostname = $this->container->getParameter('angular_app_hostname');
                $accept_link = $angular_app_hostname.'voucher/'.$offer['offer_id']."/50916";
                $wallet_link = $angular_app_hostname.'wallets';
                $link_url = "<a href='$wallet_link'>qui</a>";
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
                    'activatedDate'=>$activatedDate
                    );
                
                $templateParams = array();
                $toName = trim(ucfirst($receiver['first_name']).' '.ucfirst($receiver['last_name']));
                $templateParams['to'][] = $receiver['email'];
                $templateParams['sub']['[receiver_name]'][] = $toName;
                $templateParams['sub']['[buy_date]'][] = $current_date;
                $templateParams['sub']['[body_title]'][] = "Acquisto Voucher";
                $templateParams['sub']['[body_text_1]'][] = "Complimenti per il Buono Acquisto. <br> UN REGALO DI NATALE DAVVERO SPECIALE com MSC CROCIERE <br><br>"
                    ."Ti ringraziamo per gli Acquisti Consapevoli eseguiti su SixthContinent,"
                    ."<br>utili a te e a tutta la comunità mondiale per costruire<br>un' Economia Equa e Sostenibile nel tempo.";
                $templateParams['sub']['[url_card_amilon]'][] = $offer_detail["result"]["promotion_type"]["defaultImg"];
                $templateParams['sub']['[body_text_3]'][] = $postService->_updateByGivenText($lang_array['TAMOIL_OFFER_TEXT_3'], $replaceTxt);
                $templateParams['sub']['[desc_title]'][] = "Acquisto Voucher ";
                $templateParams['sub']['[desc_offer_title]'][] = " Voucher costo";
                $templateParams['sub']['[desc_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_CI_TITLE'];
                $templateParams['sub']['[desc_shop_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_SHOP_TITLE'];
                $templateParams['sub']['[desc_offer_val]'][] = $offer['offer_value'];
                $templateParams['sub']['[desc_ci_val]'][] = $offer['used_ci'];
                $templateParams['sub']['[desc_shop_ci_val]'][] = $offer['cash_amount'];
                
                $viewLinkText = $postService->_updateByGivenText($lang_array['TAMOIL_OFFER_LINK_TEXT'], $replaceTxt);
                $templateParams['sub']['[view_detail_link]'][] = $viewLinkText;
                
                        
                //$this->_log('Email sent - '. json_encode($templateParams['to']), 'cardsold_notifications');
                $bodyData = "<br/>";
                $subject =  "Acquisto Voucher";
                $cofanetto = dirname(dirname(dirname(dirname(__DIR__))))."/web/uploads/attachments/msc/cofanetto.pdf";
                $response = $email_template_service->sendMailWithCustomParams($templateParams, $subject, $bodyData, $templateId, "MSC_COUPON" , $cofanetto );
                
                $templateParams['sub']['[receiver_name]'][] = "Acquisto Voucer, effettuato da ".$toName." <br> Email: ".$receiver['email'];
                $templateParams['to'][] = $this->container->getParameter('msc_mail');
                $templateParams['sub']['[desc_offer_val]'][] = $offer['offer_value'];
                $templateParams['sub']['[desc_ci_val]'][] = $offer['used_ci'];
                $templateParams['sub']['[desc_shop_ci_val]'][] = $offer['cash_amount'];
                $templateParams['sub']['[body_title]'][] = "Acquisto Voucer,  tramite SixthContinent";
                $templateParams['sub']['[desc_offer_title]'][] = " Voucher costo";
                $templateParams['sub']['[desc_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_CI_TITLE'];
                $templateParams['sub']['[desc_shop_ci_title]'][] = $lang_array['TAMOIL_OFFER_DESC_SHOP_TITLE'];
                $response = $email_template_service->sendMailWithCustomParams($templateParams, $subject, $bodyData, $templateId, "MSC_COUPON_AGNCY"  );
                
                $this->_log('[AmilonOfferService:sendMsc] Coupon mail has been sent to user : '. json_encode($receiver).' [LINE:'.__LINE__.']');
            } catch (\Exception $ex) {
                $this->_log('[AmilonOfferService:sendMsc] Failed to send coupon file  in a mail to userID: '.$userId. ' with Exception: '. $ex->getMessage(), true);
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
    public function saveAmilonOffer(Sixthcontinentconnecttransaction $SixcTransaction , Transaction $Transaction , $offer , $offer_detail) {
        $em = $this->em;
        $time_h = new \DateTime('now');
        $time = time();
        $amilon_card = new AmilonCard;
        $amilon_card->setAmilonCardId($SixcTransaction->getCardPreference());
        //during creation the prooduct ode will be wmpty it will be filled in update
        $amilon_card->setProductCode("");
        $amilon_card->setAvailableAmount($SixcTransaction->getTransactionValue());
        $amilon_card->setCommercialPromotionId($SixcTransaction->getApplicationId());
        $amilon_card->setCurrency($SixcTransaction->getCurrency());
        $amilon_card->setDedication("");
        $amilon_card->setInitAmount($SixcTransaction->getTransactionValue());
        $amilon_card->setSellerId($SixcTransaction->getShopId());
        $amilon_card->setSixcTransactionId($Transaction->getSixcTransactionId());
        $amilon_card->setConnectTrsId($SixcTransaction->getId());
        $amilon_card->setTimeCreated($time);
        $amilon_card->setTimeCreatedH($time_h);
        $amilon_card->setTimeUpdatedH($time);
        $amilon_card->setTimeUpdated($time);
        $amilon_card->setTimeUpdatedH($time_h);
        $amilon_card->setValidityEndDate(null);
        $amilon_card->setValidityEndDateH(null);
        $amilon_card->setValidityStartDate(null);
        $amilon_card->setValidityStartDateH(null);
        $amilon_card->setLink(null);
        $walletsCitizen = $em->getRepository("WalletBundle:WalletCitizen")
                            ->getWalletData($SixcTransaction->getUserId());
        $walletCitizen = $walletsCitizen[0];
        $amilon_card->setWalletCitizenId($walletCitizen->getId());
        $amilon_card->setMaxUsageInitPrice(100);
        
        $postService = $this->container->get('post_detail.service');
        $receiver = $postService->getUserData($SixcTransaction->getUserId());
        try {
            if($offer_detail["result"]["commercialPromotionTypeId"]!= "352306"  &&  ( $offer_detail["result"]["commercialPromotionTypeId"] < 352850 ||  $offer_detail["result"]["commercialPromotionTypeId"]  > 352901 ) ){
            //MSC corciere and UPS
                $em->persist($amilon_card);
                $em->flush();
            }
            $this->sendCoupon($receiver, $offer , $offer_detail);
            
        } catch (\Exception $ex) {
            $this->_log('[AmilonfferService:saveAmilonOffer] '. $ex->getMessage(), true);
        }
        return true;
    }
    
    public function testExistCardCreated(Transaction $Transaction){
        
    }
     public function getSoapInstance($wsd) {
        $soapclient = new SoapClient($wsd);
        return $soapclient;
    }

    public function requestCardToAmilon(AmilonCard $amilon , $userId) {
        $wsdl = $angular_app_hostname = $this->container->getParameter('amilon_wsdl');
        $username = $angular_app_hostname = $this->container->getParameter('amilon_username');
        $password = $angular_app_hostname = $this->container->getParameter('amilon_passwd');
        $contractCode = $angular_app_hostname = $this->container->getParameter('amilon_contract_code');
        $params = array(
            'username' => $username,
            'password' => $password,
            'contractCode' => $contractCode,
            "includeAvailabilityInformation" => true
        );
        $request = $this->prepareOffer($params , $amilon , $userId );
        $soapclient = $this->getSoapInstance($wsdl);
        $response = $soapclient->CreateOrder($request );

        if($response->CreateOrderResult->OperationResult =="ok"){
            $voucher =   $response->CreateOrderResult->Vouchers->Voucher; 
            $amilon = $this->updateOfferFromAmilonService($voucher ,$amilon->getId() );

            return $amilon;
        }else{
            $this->_log(' Error [AmilonfferService:requestCardToAmilon] ', $response );
            return false;
        }
    }

    public function prepareOffer($params, AmilonCard $amilon  , $userId) {
        $postService = $this->container->get('post_detail.service');
        $receiver = $postService->getUserData($userId);
        $eur ="3f2504e0-4f89-11d3-9a0c-0305e82c1111";
        $ExternalOrderCode=""; // has to bi gien by amilon
        $ShippingAddress ="Milano Adress";
        $GrossAmount = $amilon->getInitAmount();
        $TotalRequestedCodes = "1";
        $ProductCode = $amilon->getAmilonCardId();
        $Name = $receiver['first_name'];
        $Surname = $receiver['last_name'];
        $Email = $receiver['email'];
        $PriceForEach = $GrossAmount ;
        $Quantity =  $TotalRequestedCodes;
        $Dedication =$amilon->getSixcTransactionId();
        $OrderFrom = "SixthContinent" ;
        $OrderTo = $amilon->getWalletCitizenId();

        $order_data["orderData"]["Order"] = array(
            "ExternalOrderCode" => $ExternalOrderCode,
            "CurrencyCode" => $eur,
            "ShippingAddress" => $ShippingAddress,
            "GrossAmount" => $GrossAmount,
            "TotalRequestedCodes" => $TotalRequestedCodes);
        $order_data["orderData"]["OrderRows"]["OrderRowData"] = array(
            "ExternalOrderCode" => $ExternalOrderCode,
            "ProductCode" => $ProductCode,
            "Name" => $Name,
            "Surname" => $Surname,
            "Email" => $Email,
            "Dedication" => $Dedication,
            "OrderFrom" => $OrderFrom,
            "OrderTo" => $OrderTo,
            "PriceForEach" => $PriceForEach,
            "Quantity" => $Quantity
        );

        $request = array_merge(  $order_data  , $params);
        
        return $request;

    }
    
    public function getAmilonCard(AmilonCard $amilon , $userId) {
        if($amilon->getLink()!= null){
           return $amilon;
        }else{
            
            return $this->requestCardToAmilon($amilon , $userId);
        }
        
    }
    public function updateOfferFromAmilonService($param , $id) {
        $em =  $this->em;
        $amilon_card = $em->getRepository("WalletBundle:AmilonCard")
                ->findOneBy(array( 'id'=>$id));
        $amilon_card->setLink($param->VoucherLink);
        $validityStartDateH = new \DateTime($param->ValidityStartDate);
        $validityEndDateH = new \DateTime($param->ValidityEndDate);
        $amilon_card->setValidityStartDateH($validityStartDateH);
        $amilon_card->setValidityEndDateH($validityEndDateH);
        $amilon_card->setValidityStartDate($validityStartDateH->getTimestamp());
        $amilon_card->setValidityEndDate($validityEndDateH->getTimestamp());
        $amilon_card->setProductCode($param->ProductCode);
        $amilon_card->setDedication($param->Dedication);

        
        try {
            $em->persist($amilon_card);
            $em->flush();
        } catch (\Exception $ex) {
            $this->_log('[AmilonfferService:updateOfferFromAmilonService] '. $ex->getMessage(), true);
        }
        return $amilon_card;
        
    }
    /**
     * 
     * @param AmilonCard $amilon
     * @param type $userId
     * @return AmilonCard $code_paid
     */
    public function getCardPaid(AmilonCard $amilon , $userId)  {
    $em =  $this->em;
    $code_paid = $em->getRepository("CommercialPromotionBundle:CodePaid")
                ->bookCodePaid($amilon ,$userId );    
    return $code_paid;
     
    }
       


}
