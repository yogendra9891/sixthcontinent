<?php

namespace Payment\PaymentDistributionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Payment\PaymentDistributionBundle\Entity\CitizenIncomeGainLog;
use Payment\PaymentDistributionBundle\Entity\PyamentDistributedAmount;

class PaymentDistributionController extends Controller
{
    
    /**
     * checking the parameters in requests is missing.
     * @param array $chk_params
     * @param object array $object_info
     * @return int
    */
    private function checkParamsAction($chk_params, $object_info) {
        $converted_array = (array) $object_info;
        foreach ($chk_params as $param) {
            if (array_key_exists($param, $converted_array) && ($converted_array[$param] != '')) {
                $check_error = 0;
            } else {
                $check_error = 1;
                $this->miss_param = $param;
                break;
            }
        }
        return $check_error;
    }
    
    /**
     * payment distribution
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function paymentdistributionAction() {    
        
        $user_id = 1;
        $store_id = 1459;
        $amount = 135000000;
        $transaction_id = 1636537;
        $coupon_amount = 15000000;
        $discount_position_amount = 20000000;
        $this->paymentDistribution($user_id,$store_id,$amount,$transaction_id,$coupon_amount,$discount_position_amount);
    }
    
    /**
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sixpercentcrondistributionAction() {
        /** get entity manager object **/
        $em   = $this->get('doctrine')->getManager();
        /** payment distribution related variables **/
        $citizen_country_per       = $this->container->getParameter('country_distribute_per');
        $citizen_affiliation_per   = $this->container->getParameter('citizen_affiliate_distribute_per');
        $friends_follower_affiliation_per = $this->container->getParameter('friends_follower_distribute_per');
        $sixthcontinent_per = $this->container->getParameter('sixthcontinent_distribute_per');
        $store_affiliation_per = $this->container->getParameter('store_affiliate_distribute_per');
        $purchaser_distribute_per = $this->container->getParameter('purchaser_distribute_per');
        /** get transaction approved yesterday **/
        $transaction_res = $em->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                ->getApprovedPreviousDayTransactions();
        if(count($transaction_res)>0) {
            foreach($transaction_res as $transaction_record) {
                $user_id = $transaction_record->getUserId();
                $store_id = $transaction_record->getShopId();
                $total_amount = $transaction_record->getTotalAmount();
                $transaction_id = $transaction_record->getId();
                $coupon_amount = $transaction_record->getUsedCoupons();
                $discount_position_amount = $transaction_record->getUsedPremiumPosition();
                /** amount after deducting coupon and discount position **/
                $amount = $total_amount - ($coupon_amount + $discount_position_amount);

                /** amount taht need to distribute to the store affiliator **/
                $store_affiliation_amount = ($amount*$store_affiliation_per)/100;

                /** amount to assign to citizen affiliator if user has **/
                $citizen_affiliator_amount = $amount*$citizen_affiliation_per/100;

                /** friend follower amount **/
                $friend_follower_amount = $amount*$friends_follower_affiliation_per/100;

                /** same country amount **/
                $same_country_amount = $amount*$citizen_country_per/100;

                /** amount to assign to purchased user **/
                $purchaser_distribute_amount = $amount*$purchaser_distribute_per/100;

                /** total amount that ned to be distributed **/
                $amount_to_distribute = $amount * ($citizen_country_per + $citizen_affiliation_per + $friends_follower_affiliation_per + $sixthcontinent_per + $store_affiliation_per + $purchaser_distribute_per)/100; 

                /** amount to assign to sixthcontinent **/
                $sixthcontinent_amount = $amount*$sixthcontinent_per/100;


                /** get$amount entity manager object **/
                $em = $this->getDoctrine()->getManager();
                $em_pay_row = $this->getDoctrine()->getManager();

                /** get count for user affiliator **/
                $user_affiliation_user = $em
                        ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                        ->getUserAffiliator($user_id);

                 /** check if user affiliator is present **/
                if ($user_affiliation_user == 0) {
                    $same_country_amount = $same_country_amount + $citizen_affiliator_amount;
                    $citizen_affiliator_amount = 0;
                }


                /** get count for shop affiliator **/
                $shop_affiliation_user = $em
                        ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                        ->getShopAffiliator($store_id);
                /** check if shop affiliator is present **/
                if ($shop_affiliation_user == 0) {
                    $same_country_amount = $same_country_amount + $store_affiliation_amount;
                    $store_affiliation_amount = 0;
                }

                /** get count for friends/follower **/
                $friends_follower_affiliation_user = $em
                        ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                        ->getFriendFollowerAffiliator($user_id);

                /** check if shop affiliator is present **/
                if ($friends_follower_affiliation_user == 0) {
                    $same_country_amount = $same_country_amount + $friend_follower_amount;
                    $friend_follower_amount = 0;
                }

                /** get user of same country **/ 
                $user_country_user = $em
                        ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                        ->getSameCountryUser($user_id);

                /** set amount to be distribute amoung friend/follower and same country citizen **/
                $friend_follower_distribute_amount = 0;
                $country_citizen_distribute_amount = (integer)($same_country_amount/$user_country_user); 
                if($friends_follower_affiliation_user !=0) {
                    $friend_follower_distribute_amount = (integer)($friend_follower_amount/$friends_follower_affiliation_user);
                }

                /** save distribution log in citizen income log table **/
                $em = $this->getDoctrine()->getManager();
                $time = new \DateTime("now");

                 /** check for log entry **/   
                $citizen_income_log = $em
                        ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGainLog')
                        ->findOneBy(array('transactionId' => (string)$transaction_id));

                if(count($citizen_income_log) == 0) {         

                    /** get entity manager object **/
                    $em_transaction = $this->getDoctrine()->getManager();
                    $connection = $em_transaction->getConnection();
                    $connection->beginTransaction();

                    try {

                        /** insert distribute amount in citizenincomegain table **/ 
                        $distribute_citizen_income = $em_transaction
                                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                                ->distributeCitizenIncomeGain($transaction_id,$user_id,$store_id,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount);

                        /** distribute amount to sixthcontient **/
                        $sixthcontinent_update = $em_transaction
                                ->getRepository('PaymentPaymentDistributionBundle:SixthContinentIncomeGain')
                                ->assignSixthcontinentCitizen($store_id,$user_id,$sixthcontinent_amount,$transaction_id);

                        /** function for saving the non distributed amount for further use **/
                        $non_distribute_amount = $em_transaction
                             ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                             ->saveNonDistributedAmount($store_id,$user_id,$amount_to_distribute,$transaction_id);

                         /** update user CI in userdiscountposition table **/
                        $sixthcontinent_update = $em_transaction
                            ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                            ->updateUserCitizenIncome($transaction_id);

                        /** function for saving the non distributed amount for further use **/            
                        $set_status = $em_transaction
                             ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                             ->SetStatusForUserGotCI($transaction_id); 
                        /** update is_distributed field **/
                        $payment_credit = $em_pay_row->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                       ->findOneBy(array('id' => $transaction_id));
//                        echo '<pre>';
//                        print_r($payment_credit);
//                        exit;
                        if($payment_credit) {
                            $payment_credit->setIsDistributed(1);
                            $em_pay_row->persist($payment_credit);
                            $em_pay_row->flush();
                        }
                        
                        /** commit the transactional **/
                        $em_transaction->getConnection()->commit();
                        $em_transaction->close();                
                        /** insert/update **/               
                        $this->updateCitizenIncomeGainLogLog($transaction_id,1,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount,$amount_to_distribute,$user_id,$store_id,$coupon_amount,$discount_position_amount,$total_amount,$distribute_citizen_income,0);

                    }
                    catch (\Exception $e) {       
                       $connection->rollback();
                       $em_transaction->close();
                        /** insert/update **/
                       $this->updateCitizenIncomeGainLogLog($transaction_id,0,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount,$amount_to_distribute,$user_id,$store_id,$coupon_amount,$discount_position_amount,$total_amount,$distribute_citizen_income,0);

                    }
                }
            }           
        }
        return new Response('Ok');
    }
    
    /**
     * payment distribution
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function paymentcrondistributionAction() {    
        /** get entity manager object **/
        $em = $this->getDoctrine()->getManager();
        /** query for fetching all failed transaction **/   
        $citizen_income_log = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGainLog')
                ->getYesterdayTransaction();
        $time = new \DateTime("now");
        if(count($citizen_income_log) > 0) {
           
            foreach($citizen_income_log as $citizen_income_log_record) {
                $transaction_id = $citizen_income_log_record->getTransactionId();
                $country_citizen_distribute_amount = $citizen_income_log_record->getCountryCitizenAmount();
                $friend_follower_distribute_amount = $citizen_income_log_record->getFriendsFollowerAmount();
                $citizen_affiliator_amount = $citizen_income_log_record->getCitizenAffiliateAmount();
                $store_affiliation_amount = $citizen_income_log_record->getShopAffiliateAmount();
                $purchaser_distribute_amount = $citizen_income_log_record->getPurchaserUserAmount();
                $sixthcontinent_amount = $citizen_income_log_record->getSixthcontinentAmount();
                $total_amount = $citizen_income_log_record->getTotalAmount();
                $amount_to_distribute = $citizen_income_log_record->getDistributedAmount();
                $user_id = $citizen_income_log_record->getUserId();
                $store_id = $citizen_income_log_record->getShopId();
                $coupon_amount = $citizen_income_log_record->getCouponAmount();
                $count = $citizen_income_log_record->getCitizenCount();
                $cron_status = $citizen_income_log_record->getCronStatus();
                $discount_position_amount = $citizen_income_log_record->getDiscountPositionAmount();                  
                
                $user_service = $this->get('user_object.service');
                $store_object_info = $user_service->getStoreObjectService($store_id);
                
                $transaction_count_res = $em
                     ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                     ->findBy(array('transactionId' =>$transaction_id));
                $count_transaction = count($transaction_count_res);
                
                if($count_transaction != $count) {
                    $citizen_income_log_record->setCronStatus(2);
                    /** persist the store object **/
                    $em->persist($citizen_income_log_record);
                    /** save the store info **/
                    $em->flush();
                }else {
                    /** get entity manager object **/
                    $em_transaction = $this->getDoctrine()->getManager();
                    $connection = $em_transaction->getConnection();
                    $connection->beginTransaction();

                    try {

                        /** update user CI in userdiscountposition table **/
                        $sixthcontinent_update = $em_transaction
                            ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                            ->updateUserCitizenIncome($transaction_id);

                        /** function for saving the non distributed amount for further use **/            
                        $set_status = $em_transaction
                             ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                             ->SetStatusForUserGotCI($transaction_id); 
                        
                        /** update the is_distribute =1 in sixthcontinentincomegain**/
                        $sixthcontinent_update = $em_transaction
                            ->getRepository('PaymentPaymentDistributionBundle:SixthContinentIncomeGain')
                            ->updateDistributeSixthcontinent($transaction_id,1);
                        
                        /** update distribution status **/
                        $transaction_dis_update = $em_transaction->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                                    ->updateAsDistributionComplete($transaction_id);
                        
                        /** update cron status **/
                        $update_cron_status = $em_transaction->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGainLog')
                                    ->SetStatusForCronStatus($transaction_id,1,1);
                        
                        #$citizen_income_log_record->setCronStatus(1);
                        #$citizen_income_log_record->setStatus(1);
                        #$citizen_income_log_record->setUpdatedAt($time);
                        /** persist the store object **/
                        #$em_transaction->persist($citizen_income_log_record);
                        /** save the store info **/
                        #$em_transaction->flush();
                        
                        /** commit the transactional **/
                        $em_transaction->getConnection()->commit();
                        $em_transaction->close();
                        /** insert/update  **/
                        #$this->updateCitizenIncomeGainLog($transaction_id,1,1);
                       
                       
                    }
                    catch (\Exception $e) {   
                        
                        $connection->rollback();
                        $em_transaction->close();
                        
                        
                        /** get entity manager object **/
                        $this->container->get('doctrine')->resetEntityManager();
                        /** reset the EM and all aias **/
                        $this->container->set('doctrine.orm.entity_manager', null);
                        $this->container->set('doctrine.orm.default_entity_manager', null);
                        /** get a fresh EM **/
                        $this->entityManager = $this->container->get('doctrine')->getEntityManager();  
                        $em_update = $this->getDoctrine()->getManager();
                        /** update cron status **/
                        $update_cron_status = $em_update->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGainLog')
                                    ->SetStatusForCronStatus($transaction_id,0,2);
                        
                        /** insert/update  **/
                        #$this->updateCitizenIncomeGainLog($transaction_id,0,2);                         
        
                        /** Code for email notification **/
                        /** get object of email template service **/
                        $email_template_service = $this->container->get('email_template.service');
                        /** get locale **/
                        $locale = $this->container->getParameter('locale');
                        $language_const_array = $this->container->getParameter($locale);
                        
                        $shop_name = '';
                        $shop_name_path = '';
                        if($store_object_info) {
                            $shop_name = $store_object_info['name'];
                            $shop_name_path = $store_object_info['thumb_path'];
                        }
                                            
                        $mail_text = sprintf($language_const_array['DISTRIBUTION_FAILED_SUBJECT_MAIL_TEXT'], $transaction_id,$shop_name);                                               
                        $bodyData = $mail_text; //making the link html from service
                        $subject = $language_const_array['DISTRIBUTION_FAILED_SUBJECT'] ;
                        $mail_body = sprintf($language_const_array['DISTRIBUTION_FAILED_SUBJECT_BODY'], $transaction_id , $shop_name );
                        $admin_email = $this->container->getParameter('sixthcontinent_admin_email');
                        $receivers = array();
                        $receivers[] = $admin_email;
                        $emailResponse = $email_template_service->sendMail($receivers, $bodyData, $mail_body, $subject, $shop_name_path, 'TRANSACTION',null,2,1);
       
                    }  
                }
                
            }
        }
        echo 'Ok'; exit;
    }
    
    /**
     * 
     * @param type $user_id
     * @param type $store_id
     * @param type $amount
     */
    public function paymentDistribution($user_id,$store_id,$total_amount,$transaction_id,$coupon_amount,$discount_position_amount) {
        
        $time = time();
        
        /** payment distribution related variables **/
        $citizen_country_per       = $this->container->getParameter('country_distribute_per');
        $citizen_affiliation_per   = $this->container->getParameter('citizen_affiliate_distribute_per');
        $friends_follower_affiliation_per = $this->container->getParameter('friends_follower_distribute_per');
        $sixthcontinent_per = $this->container->getParameter('sixthcontinent_distribute_per');
        $store_affiliation_per = $this->container->getParameter('store_affiliate_distribute_per');
        $purchaser_distribute_per = $this->container->getParameter('purchaser_distribute_per');
        
        
        
        /** get entity manager object **/
        $em = $this->getDoctrine()->getManager();
        
        /** amount after deducting coupon and discount position **/
        $amount = $total_amount - ($coupon_amount + $discount_position_amount);
        
        /** amount taht need to distribute to the store affiliator **/
        $store_affiliation_amount = ($amount*$store_affiliation_per)/100;
        
        /** amount to assign to citizen affiliator if user has **/
        $citizen_affiliator_amount = $amount*$citizen_affiliation_per/100;
        
        /** friend follower amount **/
        $friend_follower_amount = $amount*$friends_follower_affiliation_per/100;
        
        /** same country amount **/
        $same_country_amount = $amount*$citizen_country_per/100;
        
        /** amount to assign to purchased user **/
        $purchaser_distribute_amount = $amount*$purchaser_distribute_per/100;
        
        /** total amount that ned to be distributed **/
        $amount_to_distribute = $amount * ($citizen_country_per + $citizen_affiliation_per + $friends_follower_affiliation_per + $sixthcontinent_per + $store_affiliation_per + $purchaser_distribute_per)/100; 
        
        /** amount to assign to sixthcontinent **/
        $sixthcontinent_amount = $amount*$sixthcontinent_per/100;
        
        
        /** get$amount entity manager object **/
        $em = $this->getDoctrine()->getManager();
        
        /** get count for user affiliator **/
        $user_affiliation_user = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                ->getUserAffiliator($user_id);
        
         /** check if user affiliator is present **/
        if ($user_affiliation_user == 0) {
            $same_country_amount = $same_country_amount + $citizen_affiliator_amount;
            $citizen_affiliator_amount = 0;
        }
        
       
        /** get count for shop affiliator **/
        $shop_affiliation_user = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                ->getShopAffiliator($store_id);
        /** check if shop affiliator is present **/
        if ($shop_affiliation_user == 0) {
            $same_country_amount = $same_country_amount + $store_affiliation_amount;
            $store_affiliation_amount = 0;
        }
        
        /** get count for friends/follower **/
        $friends_follower_affiliation_user = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                ->getFriendFollowerAffiliator($user_id);
        
        /** check if shop affiliator is present **/
        if ($friends_follower_affiliation_user == 0) {
            $same_country_amount = $same_country_amount + $friend_follower_amount;
            $friend_follower_amount = 0;
        }
        
        /** get user of same country **/ 
        $user_country_user = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                ->getSameCountryUser($user_id);
        
        /** set amount to be distribute amoung friend/follower and same country citizen **/
        $friend_follower_distribute_amount = 0;
        $country_citizen_distribute_amount = (integer)($same_country_amount/$user_country_user); 
        if($friends_follower_affiliation_user !=0) {
            $friend_follower_distribute_amount = (integer)($friend_follower_amount/$friends_follower_affiliation_user);
        }
        
      
        /** check for log entry **/   
        $citizen_income_log = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGainLog')
                ->findOneBy(array('transactionId' => (string)$transaction_id,'status'=>1));
        
        if(count($citizen_income_log) == 0) {         
            
            /** get entity manager object **/
            $em_transaction = $this->getDoctrine()->getManager();
            $connection = $em_transaction->getConnection();
            $connection->beginTransaction();
            
            try {
                
                /** insert distribute amount in citizenincomegain table **/ 
                $distribute_citizen_income = $em_transaction
                        ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                        ->distributeCitizenIncomeGain($transaction_id,$user_id,$store_id,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount);

                /** distribute amount to sixthcontient **/
                $sixthcontinent_update = $em_transaction
                        ->getRepository('PaymentPaymentDistributionBundle:SixthContinentIncomeGain')
                        ->assignSixthcontinentCitizen($store_id,$user_id,$sixthcontinent_amount,$transaction_id);

                
                 /** update user CI in userdiscountposition table **/
                $sixthcontinent_update = $em_transaction
                    ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                    ->updateUserCitizenIncome($transaction_id);
              
                /** function for saving the non distributed amount for further use **/
                $non_distribute_amount = $em_transaction
                     ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                     ->saveNonDistributedAmount($store_id,$user_id,$amount_to_distribute,$transaction_id);

                /** function for saving the non distributed amount for further use **/
                $set_status = $em_transaction
                     ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                     ->SetStatusForUserGotCI($transaction_id);
                
                /** insert to amount distribute to different category for this transaction id**/
                $this->updatePaymentDistributedAmount($em_transaction,$transaction_id,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount);
                
                /** commit the transactional **/
                $em_transaction->getConnection()->commit();
                $em_transaction->close();                
                /** insert/update  **/               
                $this->updateCitizenIncomeGainLogLog($transaction_id,1,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount,$amount_to_distribute,$user_id,$store_id,$coupon_amount,$discount_position_amount,$total_amount,$distribute_citizen_income,0,1);
                
            }
            catch (\Exception $e) {       
               
               $connection->rollback();
               $em_transaction->close();
                /** insert/update  **/
               $this->updateCitizenIncomeGainLogLog($transaction_id,0,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount,$amount_to_distribute,$user_id,$store_id,$coupon_amount,$discount_position_amount,$total_amount,$distribute_citizen_income,0,0);
              
            }
        }
               
        echo 'Ok'; exit;
    }
    
    /**
     * 
     * @param type $transaction_id
     * @param type $status
     * @return boolean
     */
    public function updateCitizenIncomeGainLog($transaction_id,$status,$cron_status) {
        
         /** get entity manager object **/
        
        $this->container->get('doctrine')->resetEntityManager();
        /** reset the EM and all aias **/
        $this->container->set('doctrine.orm.entity_manager', null);
        $this->container->set('doctrine.orm.default_entity_manager', null);
        /** get a fresh EM **/
        $this->entityManager = $this->container->get('doctrine')->getEntityManager();        
        
        $em = $this->getDoctrine()->getManager();
        $time = new \DateTime("now");
        
        $citizen_income_log_check = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGainLog')
                ->findOneBy(array('transactionId' => $transaction_id));
        if($citizen_income_log_check) {
            $citizen_income_log_check->setStatus($status);
            $citizen_income_log_check->setUpdatedAt($time);
            $citizen_income_log_check->setCronStatus($cron_status);
            /** persist the store object **/
            $em->persist($citizen_income_log_check);
            /** save the store info **/
            $em->flush();
        }
        
        return true;
    }
    
    /**
     * 
     * @param type $transaction_id
     * @param type $status
     * @return boolean
     */
    public function updateCitizenIncomeGainLogLog($transaction_id,$status,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount,$amount,$user_id,$store_id,$coupon_amount,$discount_position_amount,$total_amount,$count,$cron_status,$job_status) {
        
         /** get entity manager object **/
        
        $this->container->get('doctrine')->resetEntityManager();
        /** reset the EM and all aias **/
        $this->container->set('doctrine.orm.entity_manager', null);
        $this->container->set('doctrine.orm.default_entity_manager', null);
        /** get a fresh EM **/
        $this->entityManager = $this->container->get('doctrine')->getEntityManager();        
        
        $em = $this->getDoctrine()->getManager();
        $time = new \DateTime("now");
        
        $citizen_income_log_check = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGainLog')
                ->findOneBy(array('transactionId' => (string)$transaction_id));
        if(count($citizen_income_log_check) > 0) {
            $citizen_income_log_check->setStatus($status);
            $citizen_income_log_check->setUpdatedAt($time);
            $citizen_income_log_check->setJobStatus($job_status);
            /** persist the store object **/
            $em->persist($citizen_income_log_check);
            /** save the store info **/
            $em->flush();
        }else {
            $citizen_income_log = new CitizenIncomeGainLog();
            /** set CitizenIncomeGainLog  fields **/        
            $citizen_income_log->setTransactionId($transaction_id);
            $citizen_income_log->setCitizenAffiliateAmount($citizen_affiliator_amount);
            $citizen_income_log->setShopAffiliateAmount($store_affiliation_amount);
            $citizen_income_log->setFriendsFollowerAmount($friend_follower_distribute_amount);
            $citizen_income_log->setPurchaserUserAmount($purchaser_distribute_amount);
            $citizen_income_log->setCountryCitizenAmount($country_citizen_distribute_amount);
            $citizen_income_log->setSixthcontinentAmount($sixthcontinent_amount);
            $citizen_income_log->setTotalAmount($total_amount);
            $citizen_income_log->setDistributedAmount($amount);
            $citizen_income_log->setCouponAmount($coupon_amount);
            $citizen_income_log->setDiscountPositionAmount($discount_position_amount);
            $citizen_income_log->setUserId($user_id);
            $citizen_income_log->setShopId($store_id);
            $citizen_income_log->setCitizenCount($count);
            $citizen_income_log->setJobStatus($job_status);
            $citizen_income_log->setCronStatus($cron_status);
            $citizen_income_log->setStatus($status);
            $citizen_income_log->setCreatedAt($time);
            $citizen_income_log->setUpdatedAt($time);
            /** persist the store object **/
            $em->persist($citizen_income_log);
            /** save the store info **/
            $em->flush();
        } 
        
        return true;
    }
    
    
    /**
     * save the amount to be distributed different category
     * @param type $transaction_id
     * @param type $country_citizen_distribute_amount
     * @param type $friend_follower_distribute_amount
     * @param type $citizen_affiliator_amount
     * @param type $store_affiliation_amount
     * @param type $purchaser_distribute_amount
     * @param type $sixthcontinent_amount
     * @return boolean
     */
    public function updatePaymentDistributedAmount($em_transaction,$transaction_id,$country_citizen_distribute_amount,$friend_follower_distribute_amount,$citizen_affiliator_amount,$store_affiliation_amount,$purchaser_distribute_amount,$sixthcontinent_amount) {
             
        //echo  $transaction_id."and".$country_citizen_distribute_amount."and".$friend_follower_distribute_amount."and".$citizen_affiliator_amount."and".$store_affiliation_amount."and".$purchaser_distribute_amount."and".$sixthcontinent_amount;exit;
        $em = $em_transaction;
        $time = new \DateTime("now");
        
        $payment_distributed_log = new PyamentDistributedAmount();
        /** set PyamentDistributedAmount  fields **/        
        $payment_distributed_log->setTransactionId($transaction_id);
        $payment_distributed_log->setCitizenAffiliateAmount($citizen_affiliator_amount);
        $payment_distributed_log->setShopAffiliateAmount($store_affiliation_amount);
        $payment_distributed_log->setFriendsFollowerAmount($friend_follower_distribute_amount);
        $payment_distributed_log->setPurchaserUserAmount($purchaser_distribute_amount);
        $payment_distributed_log->setCountryCitizenAmount($country_citizen_distribute_amount);
        $payment_distributed_log->setSixthcontinentAmount($sixthcontinent_amount);
        $payment_distributed_log->setCreatedAt($time);
        /** persist the PyamentDistributedAmount object **/
        $em->persist($payment_distributed_log);
        /** save the PyamentDistributedAmount info **/
        $em->flush();
    
        return true;
    }
    
    
    /**
     * make log of distribute the amount to sixthcontinent
     * @param type $transaction_id
     * @param type $status
     * @return boolean
     */
    public function distributeSixthAmountLog($transaction_id,$status) {
        
        /** get entity manager object **/
        $em = $this->getDoctrine()->getManager();
        $time = new \DateTime("now");
        
        $citizen_income_log_check = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGainLog')
                ->findOneBy(array('transactionId' => (string)$transaction_id));
        if(count($citizen_income_log_check) > 0) {
            $citizen_income_log_check->setStatus($status);
            /** persist the store object **/
            $em->persist($citizen_income_log_check);
            /** save the store info **/
            $em->flush();
        }else {
            $citizen_income_log = new CitizenIncomeGainLog();
            /** set CitizenIncomeGainLog  fields **/        
            $citizen_income_log->setTransactionId($transaction_id);
            $citizen_income_log->setStatus($status);
            $citizen_income_log->setCreatedAt($time);
            /** persist the store object **/
            $em->persist($citizen_income_log);
            /** save the store info **/
            $em->flush();
        } 
        
        return true;
    }
    
    /**
     * remove duplicate log entries from distributed tables
     * @param type $transaction_id
     */
    public function removeDuplicateDistributedEntries($transaction_id) {  
        
        $return_res = 0;
        /** get entity manager object **/
        $em = $this->getDoctrine()->getManager();
        
        /** check for log entry **/   
        $citizen_income_log = $em
                ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGainLog')
                ->findOneBy(array('transactionId' => (string)$transaction_id,'status'=>1));
        
        if(count($citizen_income_log) == 0) {
            
            /** remove entries from CitizenIncomeGain table **/
            $citizen_income_gain = $em
                    ->getRepository('PaymentPaymentDistributionBundle:CitizenIncomeGain')
                    ->deleteCitizenIncomeGain($transaction_id);
            
            
            /** remove entries from SixthContinentIncomeGain table **/
            $sixth_continent_income_gain = $em
                    ->getRepository('PaymentPaymentDistributionBundle:SixthContinentIncomeGain')
                    ->deleteSixthIncomeGain($transaction_id); 
            
            /** remove entries from NotdistributedCI table **/
            $non_distributed_amount = $em
                    ->getRepository('PaymentPaymentDistributionBundle:NonDistributedCIAmount')
                    ->deleteNonDistributedAmount($transaction_id); 
            
            
            $return_res = 1;
        }
        
        return $return_res;
    }
}
