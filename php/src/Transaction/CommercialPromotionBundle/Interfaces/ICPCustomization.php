<?php

namespace Transaction\CommercialPromotionBundle\Interfaces;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
interface ICPCustomization{

    
    /**
     * 
     * @param Model $parentmodel 
     * @param Array $param
     */
    public function saveCustomization($parentmodel, $param);
    
    /**
     * 
     * @param type $parentmodel 
     * return Document $data
     */
    public function getCustomizationOffer($parentmodel );
    
    /**
     * 
     * @param object $objPromotion
     * return boolean
     */
//    public function deletePromotion($objPromotion);
    
    /**
     * 
     * @param object $param
     * return boolean
     */
//    public function updatePromotion($objPromotion);
    
    /**
     * 
     * @param object $param
     * return boolean
     */
//    public function getPromotionDetail($objPromotion);
    
    
}

