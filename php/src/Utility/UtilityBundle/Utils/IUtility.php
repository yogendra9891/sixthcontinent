<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Utility\UtilityBundle\Utils;
/**
 *
 * @author ddeffolap294
 */
interface IUtility {
    //put your code here
    const DATE_TODAY = 0;
    
    const DATE_TOMORROW = 1;
    
    const DATE_YESTERDAY = -1;
    
    const DATE_FORMAT_ISO = DATE_RFC3339;
}
