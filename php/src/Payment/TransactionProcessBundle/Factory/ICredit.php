<?php

namespace Payment\TransactionProcessBundle\Factory;
use Payment\TransactionProcessBundle\Services\TransactionManagerService;

interface ICredit{
    
    /**
     * Apply credit
     * @param TransactionManagerService $transaction_manager_object
     */
    public function applyCredit(TransactionManagerService $transaction_manager_object);
}
