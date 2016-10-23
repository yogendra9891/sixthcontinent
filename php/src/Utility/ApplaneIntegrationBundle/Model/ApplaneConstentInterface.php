<?php

namespace Utility\ApplaneIntegrationBundle\Model;

interface ApplaneConstentInterface {
    
 const URL_INSERT = 'insert';
 
 const URL_UPDATE = 'update';
 
 const URL_DELETE = 'delete';
 
 const URL_QUERY = 'query';
 
 const QUERY_INSERT = 'insert';
 
 const QUERY_UPDATE = 'update';
 
 const QUERY_DELETE = 'delete';
 
 const QUERY_CODE = 'query';
  
 const ACTION_INSERT = 'insert';
 
 const ACTION_UPDATE = 'update';
 
 const ACTION_DELETE = 'delete';
 
 const INNER_ACTION_INSERT = 'insert';
 
 const INNER_ACTION_UPDATE = 'update';
 
 const INNER_ACTION_DELETE = 'delete';
 
 const SIX_CONTINENT_CITIZEN_COLLECTION = 'sixc_citizens';

 const SIX_CONTINENT_CUSTOMER_CHOICE = 'customer_choice';
 
 const FOLLOWERS = 'followers';
 
 const MY_FRIENDS = 'my_friends';
 
 const SIX_CONTINENT_CITIZEN_BUCKS_COLLECTION = 'sixc_bucks';

 const SIX_CONTINENT_SHOP_INCOME_COLLECTION = 'sixc_shop_income';
 
 const SIX_TIME_CONSTANT = 'T00:00:00.000Z';
 
 const TRANSACTION_COLLECTION = 'sixc_transactions';
 
 const SIX_CONTINENT_SHOP_COLLECTION = 'sixc_shops';
 
 const CITIZEN_CREDIT_INVOKE = 'invoke';
 
 const CITIZEN_CREDIT_QUERY = 'function=UtilityService.shopWiseCitizenIncome&parameters';
 
 const SIX_CONTINENT_CITIZEN_CARDS_COLLECTION = 'sixc_citizens_cards';
 
 #constant for erp export
 const SIX_TIPO_QUIETANZA = 'SCC';
 
 const SIX_CAUSALE = 'CA';
 
 const SIX_PROGRESS_CONST = 'T';

 const SIX_CONTINENT_SHOP_INCOME = 'sixc_shop_income';

 const TRANSACTION_COLLECTION_STATUS = 'Approved';
 
 const TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID1 = '553209267dfd81072b176bba';
 
 const TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID2 = '553209267dfd81072b176bbc';
 
 const SIX_CONTINENT_COUPON_COLLECTION = 'avail_coupn';
 
 const SIX_CONTINENT_SHOPPING_CARD_TRANSACTION_INITIATED = 'Initiated';
 
 const SIX_CONTINENT_SHOPPING_CARD_TRANSACTION_WITH_CREDIT = 'With credits'; 
 
 const TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID3 = '553209267dfd81072b176bb6';
   
 const TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID4 = '553209267dfd81072b176bb8';
 
 const CI_NOTIFICATION_AMOUNT = 0.01;

 const SHOP_INCOME_START_DATE = '2015-04-21T00:00:00+02:00';
 
 const TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID_SIX_PERCENT = '553209267dfd81072b176bbc';
 
 const TRANSACTION_COLLECTION_TRANSACTION_TYPE_ID_TEN_PERCENT = '553209267dfd81072b176bba';
 
 CONST COMPLETED = 'COMPLETED';
 
 CONST IN_COMPLETE = 'INCOMPLETE';
 
 CONST APPROVED = 'Approved';

 CONST PENDING  = 'PENDING';
 
 CONST PROCESSING = 'PROCESSING';
 
 CONST ERROR = 'ERROR';
 
 CONST BUY = 'buy';
 
 CONST SALE = 'sale';
 
 CONST REJECTED = 'Rejected';
 
 CONST CONFIRMED = 'CONFIRMED';
 
 CONST SUCCESS = 'SUCCESS';
 
 CONST CANCELED = 'CANCELED';
 
 CONST TRANSACTION_ERROR_MESSAGE = 'TRANSACTION_ERROR';
 
 CONST TRANSACTION_ERROR_CODE = 1068;
 
 CONST TRANSACTION_CANCELED_ERROR_MESSAGE = 'TRANSACTION_CANCELED';
 
 CONST TRANSACTION_CANCELED_ERROR_CODE = 1069;
 
 CONST TRANSACTION_PROCESSING_ERROR_MESSAGE = 'TRANSACTION_PROCESSING';
 
 CONST TRANSACTION_PROCESSING_ERROR_CODE = 1070;
 
 CONST TRANSACTION_PENDING_ERROR_MESSAGE = 'TRANSACTION_PENDING';
 
 CONST TRANSACTION_PENDING_ERROR_CODE = 1071;
 
 CONST SUCCESS_CODE = 101;
 
 CONST APPLANE_STATUS = 'applane_status';
 
 CONST TRANSACTION_STATUS = 'transaction_status';
 
 CONST IPN_NOTIFICATION_URL = 'webapi/ipncallbackresponse';
 
 const OFFERS_COLLECTION = 'sixc_offers';
 
 const OFFERS_TYPE_COUPONS = '551ce49e2aa8f00f20d9328f';
 
 const OFFERS_TYPE_CARDS = '551ce49e2aa8f00f20d93295';
 
 const QUERY_BATCH = 'batchquery';
 
 CONST OFFER_REASON = 'C';
 
 CONST TRANSACTION_VIA_PAYPAL = 'PAYPAL';
 
  CONST EXPIRED = 'EXPIRED';
  
  CONST APPLANE_SUCCESS_CODE = 200;
  
  CONST CHAINED_PAYPAL_FEE_PAYER = 'CHAINED_PAYPAL_FEE_PAYER';
  
  CONST CI_RETURN_FEE_PAYER = 'CI_RETURN_FEE_PAYER';
  
  CONST INSTANT_CI_REASON = 'CI';
  
  CONST CARD_SOLD_TODAY_BY_SHOP_OWNER = 'service/getcardssoldtodaybyshopowner';
 
  CONST CITIZEN_FOR_CI_REDISTRIBUTION = 'service/getcitizensforciredistribution';
  
  CONST CITIZEN_FOR_CI_REDISTRIBUTION_DAYS = 10;
  
  CONST CONNECT_IPN_NOTIFICATION_URL = 'webapi/connectipncallback';
  
  CONST INITIATED = 'Initiated';
  
  CONST CONNECT_TRANSACTION_ID = '558b9b6b3176a9736e7745ea';
  
  CONST APPLANE_BUSSINESS_CATEGORY_COLLECTION = 'sixc_shop_categories';
  
  CONST APPLANE_BUSSINESS_SUB_CATEGORY_COLLECTION = 'sub_category';
  
  CONST CONNECT_TRANSACTION_CAUSALE = 'ECO';
  
  CONST CONNECT_TRANSACTION_CODICE = 'PC';
  
  CONST CONNECT_TRANSACTION_DESCRIPTION1 = "CORRISPETTIVO PUBBLICITA'";
  
  CONST CONNECT_TRANSACTION_DESCRIPTION2 = "PER VENDITE EFFETTUATE\nTRANSAZIONI DI RIFERIMENTO.\n";
  
  CONST CONNECT_SIXTHCONTINENT_TRANSACTION_ID_CONSTANT = "sixthcontinent_trs_id:";
  
  CONST CONNECT_SIXTHCONTINENT_PAYPAL_TRANSACTION_ID_CONSTANT = "paypal_trs_id:";
  
  CONST SEMI_COLON = ';';
  CONST CONNECT_INCASSI_TRANSACTION_CAUSALE = 'RCECO';
  CONST PURCHASE_COUNTER = -2;
  CONST CONNECT_PURCHASE_TRANSACTION_CODICE = 'CARD';
  CONST PARAMETERS = 'parameters';
  CONST BUYNOW = 'buynow';
  CONST SERVICE = 'service';
  CONST TAMOIL_OFFER_PURCHASE_CODE = 'PAY_ONCE_OFFER';
  CONST TAMOIL_OFFER_NAME = 'SixthContinent';
  CONST TAMOIL_OFFER_CURRENCY = 'EUR';
  CONST TAMOIL_OFFER_CARTISI_PURCHASE_CODE = 'OFFERPURCHASE';
  CONST TAMOIL_OFFER_URL_CONSTANT = 'txn_id';
  CONST TAMOIL_CONTRACT_CONSTANT = 'offer_contact_';
  CONST ECOMMERCE_TYPE = 'EP';
  CONST TAMOIL_EXPORT_FILE_NAME = 'TAMOIL_BUONI_CARB_STATO_';
  CONST TAMOIL_EXPORT_COUNTER_LENGTH = 4;
  CONST TAMOIL_EXPORT_FILE_EXTENSION = 'txt';
  CONST TAMOIL_EXPORT_DAYS_COUNTER = 4;
  CONST FRIDAY_INDEX = 5;
  CONST TAMOIL_COUPON_FIRST_ROW_CONSTANT = 'H0BCSTATO';
  CONST TAMOIL_FOOTER_START_CONSTANT = 'F0';
  CONST TAMOIL_COUPON_LAST_ROW_CONSTANT_LENGTH = 8;
  CONST TAMOIL_COUPON_EXPORT_ACTIVATION_STATUS = '01'; 
  CONST TAMOIL_COUPON_EXPORT_DS_STATUS = 'attivato        '; //8 spaces
  CONST TAMOIL_COUPON_EXPORT_INITIAL_CONSTS = 'D0';
  
}