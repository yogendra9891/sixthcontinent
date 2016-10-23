<?php

namespace Transaction\WalletBundle\Interfaces;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
interface IWallet {
    
    /**
     * @param $postObj
     */
    public function createWallet($postObj, $returnData);
    
    /**
     * @param $postObj
     */

}

