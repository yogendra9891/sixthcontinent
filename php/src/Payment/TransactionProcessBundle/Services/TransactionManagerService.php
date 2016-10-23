<?php
namespace Payment\TransactionProcessBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Transaction\TransactionBundle\Entity\UserGiftCardPurchased;

// service method class for user object.
class TransactionManagerService
{
    protected $em;
    protected $dm;
    protected $container;
    public $coupons;
    public $premimum;
    public $gift_card;
    public $momosy_cards;
    public $total_citizen_income;
    public $amount;
    public $balance_amount;
    public $used_coupons;
    public $used_gift_cards;
    public $used_momosy_cards;
    public $used_premium_positions;
    public $gift_card_setting;
    public $block_citizen_income;
    public $total_citizen_income_available;
    
    protected $base_six = 1000000;

    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container)
    {
        $this->em        = $em;
        $this->dm        = $dm;
        $this->container = $container;
        $this->balance_amount = 0;
        $this->used_coupons   = 0;
        $this->used_premium_positions = 0;
        $this->used_gift_cards = 0;
        $this->used_momosy_cards = 0;
        $this->total_used = 0;
        $this->gift_card_packets = 0;
        $this->remaining_gift_cards = 0;
        $this->used_remaining_gift_cards = 0;
        $this->gift_card_setting = 1;
        $this->open_shop_premium = 0;
        $this->block_citizen_income = 0;
        $this->total_citizen_income_available = 0;
    }
   
    /**
     * find the user cards available and intialize these variables..
     * @param int $user_id
     * @param int $shop_id
     * @param int $amount
     * @return none
     */
    public function userCards($user_id, $shop_id, $amount, $credit_level) {
        //intialise the variable
        $gift_cards_packets = array();
        $coupons = $premimum = $gift_cards = $momosy_cards = $total_citizen_income = $balanced_gift_cards = $open_shop_premimum = $blocked_citizen_amount = 0; //initialize the variables.
        
        //finding the user discount position.
        $user_discount_position = $this->em->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                                       ->findOneBy(array('userId' => $user_id));
        if ($user_discount_position) {
            $total_citizen_income   = $user_discount_position->getCitizenIncome();
            $blocked_citizen_amount = $user_discount_position->getBlockCitizenIncome(); // find blocked income for a user.
        }
            
        //calculate the half(50%) amount for using the available cards
        $amount_half = $this->calculateHalfAmount($amount);
               
        //set the cards variables by available amount.
        $this->coupons   = $coupons;
        $this->premimum  = $premimum;
        $this->momosy_cards = $momosy_cards;
        $this->total_citizen_income = $total_citizen_income;
        $this->amount = $amount_half;
        $this->gift_card_packets = $gift_cards_packets;
        $this->remaining_gift_cards = $balanced_gift_cards;
        $this->open_shop_premium   = $open_shop_premimum;
        $this->gift_card_setting = $credit_level; //for above minimum is 1 and below maximum is 0
        $this->block_citizen_income = $blocked_citizen_amount;
        $this->total_citizen_income_available = $total_citizen_income - $blocked_citizen_amount;
        return true;
    }
    
    
    /**
     * Update user cards
     * @param int $user_id
     * @param int $shop_id
     * @param int $used_coupons
     * @param int $used_premium
     * @param int $used_gift_cards
     * @param int $used_momosy_cards
     * @return boolean
     */
    public function updateUserCards($user_id, $shop_id, $used_coupons, $used_premium, $used_gift_cards, $used_momosy_cards, $gift_card_packet_data, $remaining_gift_cards, $used_remaining_gift_cards)
    {
        //finding the user shots(Premimum), gift_cards, momosy cards.
        $shop_user_credit = $this->em->getRepository('WalletManagementWalletBundle:UserShopCredit')
                                 ->findOneBy(array('userId' => $user_id, 'shopId' => $shop_id));

        if ($shop_user_credit) {
            $coupons     = $shop_user_credit->getBalanceShots();
            $gift_cards   = $shop_user_credit->getBalanceGiftCard();
            $momosy_cards = $shop_user_credit->getBalanceMomosyCard();
            
            //get balanced coupons
            $balanced_coupons = $this->getBalancedCoupons($coupons, $used_coupons);
            
            //get balanced gift card
            $balanced_gift_cards = $this->getBalancedGiftCards($used_remaining_gift_cards, $remaining_gift_cards, $gift_cards);
            
            //get balanced momosy cards
            $balanced_momosy_cards = $this->getBalancedMomosyCards($momosy_cards, $used_momosy_cards);
            
            //update in the userShopCreditTable
            $shop_user_credit->setBalanceShots($balanced_coupons);
            $shop_user_credit->setBalanceGiftCard($remaining_gift_cards);
            $shop_user_credit->setBalanceMomosyCard($balanced_momosy_cards);
            $this->em->persist($shop_user_credit);
            $this->em->flush();
            
            //add the gift cards for the shop used
            $this->saveUsedGiftCards($user_id, $shop_id, $gift_card_packet_data, $remaining_gift_cards);
        }
        
        //finding the user discount position.
        $user_discount_position = $this->em->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                                       ->findOneBy(array('userId' => $user_id));
        if ($user_discount_position) {
            $premimum             = $user_discount_position->getBalanceDp();
            $total_citizen_income = $user_discount_position->getCitizenIncome();
            
            //get balanced premium position
            $balanced_premium_positions = $this->getBalancedPremiumPosition($premimum, $used_premium);
            $balanced_citizen_income = $total_citizen_income-$used_gift_cards;
            //update in the UserDiscountPosition Table
            $user_discount_position->setBalanceDp($balanced_premium_positions);
            $user_discount_position->setCitizenIncome($balanced_citizen_income);
            $this->em->persist($user_discount_position);
            $this->em->flush();
        }
        
        //finding the shop discount position.
        $shop_discount_position = $this->em->getRepository('StoreManagerStoreBundle:Store')
                                       ->findOneBy(array('id' => $shop_id));
        if ($shop_discount_position) {
            $shop_premium_positions = $shop_discount_position->getBalanceDp();
            $shop_balanced_premium_positions = $this->getBalancedPremiumPosition($shop_premium_positions, $used_premium);
            $shop_discount_position->setBalanceDp($shop_balanced_premium_positions);
            $this->em->persist($shop_discount_position);
            $this->em->flush();
        }
        return true;
    }
    
    /**
     * getBalancedCoupons
     * @param int $coupons
     * @param int $used_coupons
     * @return int
     */
    public function getBalancedCoupons($coupons, $used_coupons)
    {
        return $balanced_coupons = $coupons - $used_coupons;;
    }
    
    /**
     * getBalancedGiftCards
     * @param int $gift_cards
     * @param int $used_gift_cards
     * @return int
     */
    public function getBalancedGiftCards($used_remaining_gift_cards, $remaining_gift_cards, $gift_cards)
    {
        return $balanced_gift_cards = ($gift_cards - $used_remaining_gift_cards) + $remaining_gift_cards;
    }
    
    /**
     * getBalancedMomosyCards
     * @param int $momosy_cards
     * @param int $used_momosy_cards
     * @return int
     */
    public function getBalancedMomosyCards($momosy_cards, $used_momosy_cards)
    {
        return $balanced_momosy_cards = $momosy_cards - $used_momosy_cards;
    }
    
    /**
     * getBalancedPremiumPosition
     * @param type $premimum
     * @param type $used_premium
     * @return int
     */
    public function getBalancedPremiumPosition($premimum, $used_premium)
    {
       return $balanced_premium_positions = $premimum - $used_premium; 
    }
    
    /**
     * Save user gift cards
     * @param int $user_id
     * @param int $shop_id
     * @param array $gift_card_packet_data
     * @param int $remaining_gift_cards
     */
    public function saveUsedGiftCards($user_id, $shop_id, $gift_card_packet_data, $remaining_gift_cards)
    {
        $result = array();
        $em = $this->em;
        $count = 1;
        $ctime = strtotime(date('Y-m-d'));
        //check if shop has already transaction on same date
        $result =  $em->getRepository('TransactionTransactionBundle:UserGiftCardPurchased')
                      ->findBy(array('shopId' => $shop_id, 'date' => $ctime));
       if($result){
           $count = count($result)+1;
       }
       $gift_cards_array = unserialize($gift_card_packet_data);
      
       //get current time
       $purchase_year = date('Y', $ctime);
       $purchase_month = date('m', $ctime);
       $purchase_day = date('d', $ctime);
       $date_string = $purchase_day.$purchase_month.$purchase_year;
       //check if gift card exist
       if(count($gift_cards_array) > 0){
          foreach($gift_cards_array as $gift_card){
            $gift_card_id = "SC_".$shop_id."_".$date_string."_".$count;
            $user_gift_card_purchased = new UserGiftCardPurchased();
            $user_gift_card_purchased->setGiftCardId($gift_card_id);
            $user_gift_card_purchased->setUserId($user_id);
            $user_gift_card_purchased->setShopId($shop_id);
            $user_gift_card_purchased->setGiftCardAmount($gift_card);
            $user_gift_card_purchased->setDate($ctime);
            $user_gift_card_purchased->setDataJob($ctime);
            $em->persist($user_gift_card_purchased);
            $count = $count+1;
          }
          $user_gift_card_purchased->setRemainingGiftCard($remaining_gift_cards);
          $em->flush();
       }
        return true;
    }
    
    
    
    /**
     * Get transaction Object
     * @param int $tid
     * @return array
     */
    public function getTransactionObject($tid)
    {
         //check if user has already used the transaction id
        $em = $this->em;
        
        $tm = $em->getRepository('PaymentTransactionProcessBundle:Transaction')
                            ->findOneBy(array('id' => $tid));
        $tid = $tm->getId();
        
//        $coupons = 0;
//        $premimum_position = 0;
//        $gift_card   = $this->convertAmountToInt($tm->getGiftCard());
//        $momosy_card = $this->convertAmountToInt($tm->getMomosyCard());
//        $total_citizen_income = $this->convertAmountToInt($tm->getTotalCitizenIncome());
//        $amount = $this->convertAmountToInt($tm->getTotalAmount()); //50% amount of total amount
//        $balance_amount = $this->convertAmountToInt($tm->getBalanceAmount());
//        $used_coupons   = $this->convertAmountToInt($tm->getUsedCoupons());
//        $used_premium_position = $this->convertAmountToInt($tm->getUsedPremiumPosition());
//        $used_gift_card   = $this->convertAmountToInt($tm->getUsedGiftCard());
//        $used_momosy_card = $this->convertAmountToInt($tm->getUsedMomosyCard());
//        $used_remaining_gift_cards = $this->convertAmountToInt($tm->getUsedRemainingGiftCards());
//        $remaining_gift_cards = $tm->getRemainingGiftCards();
//        $total_used = $tm->getTotalUsed(); //all used cards amount
        $shop_id  =  $tm->getSellerId(); 
        $user_id  =  $tm->getBuyerId(); 
//        //$total_amount = $this->convertAmountToInt($total_amount); 
        $shop_id = $shop_id;
//        //$balance_amount = $total_amount - $total_used;
//       
        $user_service = $this->container->get('user_object.service');
        $stores_object = $user_service->getStoreObjectService($shop_id);
        $users_object = $user_service->userObjectService($user_id); 
       
        //get transaction comment and rate
//        $comment_rate = $this->getTransactionCommentAndRate($tid);
        $transaction_data_response = array(
            'transaction_id' => $tid,
            'user_id'=>$user_id,
            'shop_id'=>$shop_id,
            'user_info'=>$users_object,
            'store_info' =>$stores_object,
            'amount'=>$this->converToEuro($tm->getTransactionAmount()),
            //'coupons' => $this->converToEuro($coupons),
            //'used_coupons' => $this->converToEuro($used_coupons),
            //'premium_position' => $this->converToEuro($premimum_position),
            //'used_premium_position' => $this->converToEuro($used_premium_position),
            //'used_gift_card' => $this->converToEuro($used_gift_card),
            //'gift_card_packets' => $gift_card_packet_euro,
            //'momosy_card' => $this->converToEuro($momosy_card),
            //'used_momosy_card' => $this->converToEuro($used_momosy_card),
           // 'total_citizen_income' => $this->converToEuro($total_citizen_income),
            //'cpg_amount' => $this->converToEuro($amount),
            'total_used'=>$this->converToEuro($tm->getTransactionAmount()),
            'total_credit_used'=>$this->converToEuro($tm->getTotalCreditUsed()),
            'total_discount_used'=>$this->converToEuro($tm->getDiscountUsed()),
            'balance_amount' => $this->converToEuro($tm->getCashPaid()),
            //'used_remaining_gift_cards' => $this->converToEuro($used_remaining_gift_cards),
            //'remaining_gift_cards' => $this->converToEuro($remaining_gift_cards),
            'status' => $tm->getStatus(),
            'status_date' => $tm->getStatusDate(),
            //'citizen_status' => $tm->getStatus(),
            //'citizen_comment' => $comment_rate['comment'],
            //'citizen_rate' => $comment_rate['rating'],
            //'date' => $tm->getUpdatedAt(),
            'is_calculated' => $tm->getStatus()
            
        );
        return $transaction_data_response;
        
    }
    
    /**
     * Get Transaction rate and comment
     * @param type $tid
     * @return array
     */
    public function getTransactionCommentAndRate($tid)
    {
        $dm = $this->dm;
        $tid = (string)$tid;
        $data = array('comment' => null, 'rating' => null);
        $response = $dm->getRepository('PaymentPaymentProcessBundle:TransactionComment')
                            ->findOneBy(array('transaction_id' => $tid));
        if($response){
            $comment = $response->getComment();
            $rating = $response->getRating();
            $data = array('comment' => $comment, 'rating' => $rating);
        }
        return $data;
    }
    
    
    /**
     * convert amount into int
     * @param type $amount
     * @return int
     */
    private function convertAmountToInt($amount) {
        return (int)$amount;
    }
    
     /**
     * 
     * @param type $amount
     * @return type
     */
    public function converToEuro($amount) {
        if($amount>0) {
            $amount_euro =  $amount/$this->base_six;
            
        }else {
            $amount_euro = $amount;
        }
        return $amount_euro;
    }

    /**
     * Get multi transaction Object
     * @param int $tid
     * @return array
     */
    public function getMultiTransactionObject($user_id)
    {
         //check if user has already used the transaction id
        $em = $this->em;
        $transaction_final_response = array();
        $transaction_list = $em->getRepository('PaymentPaymentProcessBundle:PaymentProcessCredit')
                            ->findBy(array('userId' => (int)$user_id));
        if(count($transaction_list)>0) {
            foreach($transaction_list as $tm) {
                $tid = $tm->getId();
                $coupons = $this->convertAmountToInt($tm->getCoupons());
                $premimum_position = $this->convertAmountToInt($tm->getPremiumPosition());
                $gift_card   = $this->convertAmountToInt($tm->getGiftCard());
                $momosy_card = $this->convertAmountToInt($tm->getMomosyCard());
                $total_citizen_income = $this->convertAmountToInt($tm->getTotalCitizenIncome());
                $amount = $this->convertAmountToInt($tm->getTotalAmount()); //50% amount of total amount
                $balance_amount = $this->convertAmountToInt($tm->getBalanceAmount());
                $used_coupons   = $this->convertAmountToInt($tm->getUsedCoupons());
                $used_premium_position = $this->convertAmountToInt($tm->getUsedPremiumPosition());
                $used_gift_card   = $this->convertAmountToInt($tm->getUsedGiftCard());
                $used_momosy_card = $this->convertAmountToInt($tm->getUsedMomosyCard());
                $used_remaining_gift_cards = $this->convertAmountToInt($tm->getUsedRemainingGiftCards());
                $remaining_gift_cards = $tm->getRemainingGiftCards();
                $total_used = $tm->getTotalUsed(); //all used cards amount
                $shop_id  =  $tm->getShopId(); 
                $user_id  =  $tm->getUserId(); 
                //$total_amount = $this->convertAmountToInt($total_amount); 
                $shop_id = $shop_id;
                //$balance_amount = $total_amount - $total_used;

                $user_service = $this->container->get('user_object.service');
                $stores_object = $user_service->getStoreObjectService($shop_id);
                $users_object = $user_service->userObjectService($user_id); 


                $gift_card_packets = unserialize($tm->getGiftCardPacketData());

                $gift_card_packet_euro = array();
                if(count($gift_card_packets)>0) {
                    $gift_card_packet_euro = array_map(function($gift_card_arr_rec) {
                        return $this->converToEuro($gift_card_arr_rec);
                    }, $gift_card_packets);
                }

                //get transaction comment and rate
                $comment_rate = $this->getTransactionCommentAndRate($tid);
                $transaction_data_response = array(
                    'transaction_id' => $tid,
                    'user_id'=>$user_id,
                    'shop_id'=>$shop_id,
                    'user_info'=>$users_object,
                    'store_info' =>$stores_object,
                    'amount'=>$this->converToEuro($tm->getTotalAmount()),
                    'coupons' => $this->converToEuro($coupons),
                    'used_coupons' => $this->converToEuro($used_coupons),
                    'premium_position' => $this->converToEuro($premimum_position),
                    'used_premium_position' => $this->converToEuro($used_premium_position),
                    'used_gift_card' => $this->converToEuro($used_gift_card),
                    'gift_card_packets' => $gift_card_packet_euro,
                    'momosy_card' => $this->converToEuro($momosy_card),
                    'used_momosy_card' => $this->converToEuro($used_momosy_card),
                    'total_citizen_income' => $this->converToEuro($total_citizen_income),
                    'cpg_amount' => $this->converToEuro($amount),
                    'total_used'=>$this->converToEuro($total_used),
                    'balance_amount' => $this->converToEuro($balance_amount),
                    'used_remaining_gift_cards' => $this->converToEuro($used_remaining_gift_cards),
                    'remaining_gift_cards' => $this->converToEuro($remaining_gift_cards),
                    'shop_status' => $tm->getShopStatus(),
                    'citizen_status' => $tm->getCitizenStatus(),
                    'citizen_comment' => $comment_rate['comment'],
                    'citizen_rate' => $comment_rate['rating'],
                    'date' => $tm->getUpdatedAt()            
                );
                $transaction_final_response[$tid] = $transaction_data_response;
            }
        }
       
        return $transaction_final_response;
    }
    
    /**
     * calculate the half amount
     * @param int $amount
     * @return int $half_amount
     */
    public function calculateHalfAmount($amount) {
        return $amount/2;
    }
    
    /**
     * calculated the blocked amount for a user(Pending Gift Cards purchased) and increase.
     * @param int $user_id
     * @return boolean
     */
    public function calculateBlockedCredit($user_id) {
        $em = $this->em;
        //calculate the blocked citizen credit
        $blocked_amount = $em->getRepository('PaymentTransactionProcessBundle:CitizenCredits')
                             ->calclulateBlockedCredit($user_id);
        if ($blocked_amount > 0) { //update blocked citizen credit in user discount position table.
            $user_discount_position = $em->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                                         ->findOneBy(array('userId' => $user_id));
            if ($user_discount_position) {
                $current_blocked_credit = $user_discount_position->getBlockCitizenIncome();
                try {
                   $user_discount_position->setBlockCitizenIncome($blocked_amount);
                   $em->persist($user_discount_position);
                   $em->flush();
                } catch (\Exception $ex) {
               } 
            }
        }
        return true;
    }
    
    /**
     * update the blocked amount for a user(Pending Gift Cards purchased) and decrease on transaction canceled/approved.
     * @param object $transaction_object
     * @return boolean
     */
    public function reduceBlockedCredit($transaction_object) {
        $em = $this->em;
        //get used gift cards amount
        $used_gift_card_amount = $transaction_object->used_gift_cards;
        $citizen_id = $transaction_object->user_id;
        if ($used_gift_card_amount > 0) { //update blocked citizen credit in user discount position table.
            $user_discount_position = $em->getRepository('WalletManagementWalletBundle:UserDiscountPosition')
                                         ->findOneBy(array('userId' => $citizen_id));
            if ($user_discount_position) {
                $current_blocked_credit = $user_discount_position->getBlockCitizenIncome();
                $amount = $current_blocked_credit - $used_gift_card_amount;
                $block_amount = ($amount >= 0 ? $amount : 0);
                try {
                   $user_discount_position->setBlockCitizenIncome($block_amount);
                   $em->persist($user_discount_position);
                   $em->flush();
                } catch (\Exception $ex) {
               } 
            }
        }
        return true;
    }
}
