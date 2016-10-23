<?php

namespace Payment\PaymentProcessBundle\Factory;
use Payment\PaymentProcessBundle\Services\TransactionManagerService;
interface ICredit{
    
    /**
     * Apply credit
     * @param TransactionManagerService $transaction_manager_object
     */
    public function applyCredit(TransactionManagerService $transaction_manager_object);
}
