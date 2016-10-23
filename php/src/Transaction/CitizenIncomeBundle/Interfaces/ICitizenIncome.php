<?php

namespace Transaction\CitizenIncomeBundle\Interfaces;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
interface ICitizenIncome {
    
    /**
     * 
     * @param int $time_end_transaction (users that when the last transaction done they were registered)
     * @param type $transaction_id
     * @return type
     */
    public function getTotalUsers( $time_end_transaction, $transaction_id);
    
    /**
     * 
     * @param type $init_time
     * @param type $end_time
     * @param type $transaction_id
     */
    public function getTotalAmountBaseCurrency($init_time,$end_time,$transaction_id);
    
    /**
     * @param array $single_shares :
     *      single_share amout to give to each wallet
     *      remainder remainder for next transaction
     * @param array $total_user :
     *      total_user : count users
     *      users :users array 
     *      
     * @param int $type
     * @param string $sixc_transaction_id
     */
    public function updateCitizenWallet($single_share, $total_user, $time_init_transaction, $time_end_transaction, $sixc_transaction_id);
    
    /**
     * 
     * @param array $single_share
     * @param array $total_user
     * @param int $time_init_transaction : init transaction time 
     * @param int $time_end_transaction : end transaction time
     * @param int  $sixc_transaction_id : EUR in general is the most valauable currency
     */
    public function updateHistory($hasBeenShared, $single_share, $total_user, $time_init_transaction, $time_end_transaction  , $sixc_transaction_id);
}

