<?php

namespace Transaction\TransactionSystemBundle\Interfaces;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
interface ICredit {
    
    /**
     * @param $postObj
     */
    public function getUsageCredits($postObj, $returnData);
    
    /**
     * @param $postObj
     */
    public function updateCredits($postObj);
}

