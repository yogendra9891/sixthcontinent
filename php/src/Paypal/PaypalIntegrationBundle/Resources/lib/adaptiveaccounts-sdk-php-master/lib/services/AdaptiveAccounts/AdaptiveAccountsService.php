<?php 

/**
 * AUTO GENERATED code for AdaptiveAccounts
 */
class AdaptiveAccountsService extends PPBaseService {

	// Service Version
	private static $SERVICE_VERSION = "1.2.0";

	// Service Name
	private static $SERVICE_NAME = "AdaptiveAccounts";

    // SDK Name
	protected static $SDK_NAME = "adaptiveaccounts-php-sdk";
	
	// SDK Version
	protected static $SDK_VERSION = "2.7.107";

	public function __construct($config = null) {
		parent::__construct(self::$SERVICE_NAME, 'NV', $config);
	}


	/**
	 * Service Call: CreateAccount
	 * @param CreateAccountRequest $createAccountRequest
	 * @param mixed $apiCredential - Optional API credential - can either be
	 * 		a username configured in sdk_config.ini or a ICredential object
	 *      created dynamically 		
	 * @return CreateAccountResponse
	 * @throws APIException
	 */
	public function CreateAccount($createAccountRequest, $apiCredential = NULL) {
		$ret = new CreateAccountResponse();
        $apiContext = new PPApiContext($this->config);
        $handlers = array(
            new PPPlatformServiceHandler($apiCredential, self::$SDK_NAME, self::$SDK_VERSION),
        );
		$apiContext = new PPApiContext($this->config);
        $handlers = array(
            new PPPlatformServiceHandler($apiCredential, self::$SDK_NAME, self::$SDK_VERSION),
        );
		$resp = $this->call('AdaptiveAccounts', 'CreateAccount', $createAccountRequest, $apiContext, $handlers);
		$ret->init(PPUtils::nvpToMap($resp));
		return $ret;
	}
	 

	/**
	 * Service Call: GetUserAgreement
	 * @param GetUserAgreementRequest $getUserAgreementRequest
	 * @param mixed $apiCredential - Optional API credential - can either be
	 * 		a username configured in sdk_config.ini or a ICredential object
	 *      created dynamically 		
	 * @return GetUserAgreementResponse
	 * @throws APIException
	 */
	public function GetUserAgreement($getUserAgreementRequest, $apiCredential = NULL) {
		$ret = new GetUserAgreementResponse();
		$apiContext = new PPApiContext($this->config);
        $handlers = array(
            new PPPlatformServiceHandler($apiCredential, self::$SDK_NAME, self::$SDK_VERSION),
        );
		$resp = $this->call('AdaptiveAccounts', 'GetUserAgreement', $getUserAgreementRequest, $apiContext, $handlers);
		$ret->init(PPUtils::nvpToMap($resp));
		return $ret;
	}
	 

	/**
	 * Service Call: GetVerifiedStatus
	 * @param GetVerifiedStatusRequest $getVerifiedStatusRequest
	 * @param mixed $apiCredential - Optional API credential - can either be
	 * 		a username configured in sdk_config.ini or a ICredential object
	 *      created dynamically 		
	 * @return GetVerifiedStatusResponse
	 * @throws APIException
	 */
	public function GetVerifiedStatus($getVerifiedStatusRequest, $apiCredential = NULL) {
		$ret = new GetVerifiedStatusResponse();
		$apiContext = new PPApiContext($this->config);
        $handlers = array(
            new PPPlatformServiceHandler($apiCredential, self::$SDK_NAME, self::$SDK_VERSION),
        );
		$resp = $this->call('AdaptiveAccounts', 'GetVerifiedStatus', $getVerifiedStatusRequest, $apiContext, $handlers);
		$ret->init(PPUtils::nvpToMap($resp));
		return $ret;
	}
	 

	/**
	 * Service Call: AddBankAccount
	 * @param AddBankAccountRequest $addBankAccountRequest
	 * @param mixed $apiCredential - Optional API credential - can either be
	 * 		a username configured in sdk_config.ini or a ICredential object
	 *      created dynamically 		
	 * @return AddBankAccountResponse
	 * @throws APIException
	 */
	public function AddBankAccount($addBankAccountRequest, $apiCredential = NULL) {
		$ret = new AddBankAccountResponse();
		$apiContext = new PPApiContext($this->config);
        $handlers = array(
            new PPPlatformServiceHandler($apiCredential, self::$SDK_NAME, self::$SDK_VERSION),
        );
		$resp = $this->call('AdaptiveAccounts', 'AddBankAccount', $addBankAccountRequest, $apiContext, $handlers);
		$ret->init(PPUtils::nvpToMap($resp));
		return $ret;
	}
	 

	/**
	 * Service Call: AddPaymentCard
	 * @param AddPaymentCardRequest $addPaymentCardRequest
	 * @param mixed $apiCredential - Optional API credential - can either be
	 * 		a username configured in sdk_config.ini or a ICredential object
	 *      created dynamically 		
	 * @return AddPaymentCardResponse
	 * @throws APIException
	 */
	public function AddPaymentCard($addPaymentCardRequest, $apiCredential = NULL) {
		$ret = new AddPaymentCardResponse();
		$apiContext = new PPApiContext($this->config);
        $handlers = array(
            new PPPlatformServiceHandler($apiCredential, self::$SDK_NAME, self::$SDK_VERSION),
        );
		$resp = $this->call('AdaptiveAccounts', 'AddPaymentCard', $addPaymentCardRequest, $apiContext, $handlers);
		$ret->init(PPUtils::nvpToMap($resp));
		return $ret;
	}
	 

	/**
	 * Service Call: SetFundingSourceConfirmed
	 * @param SetFundingSourceConfirmedRequest $setFundingSourceConfirmedRequest
	 * @param mixed $apiCredential - Optional API credential - can either be
	 * 		a username configured in sdk_config.ini or a ICredential object
	 *      created dynamically 		
	 * @return SetFundingSourceConfirmedResponse
	 * @throws APIException
	 */
	public function SetFundingSourceConfirmed($setFundingSourceConfirmedRequest, $apiCredential = NULL) {
		$ret = new SetFundingSourceConfirmedResponse();
		$apiContext = new PPApiContext($this->config);
        $handlers = array(
            new PPPlatformServiceHandler($apiCredential, self::$SDK_NAME, self::$SDK_VERSION),
        );
		$resp = $this->call('AdaptiveAccounts', 'SetFundingSourceConfirmed', $setFundingSourceConfirmedRequest, $apiContext, $handlers);
		$ret->init(PPUtils::nvpToMap($resp));
		return $ret;
	}
	 

	/**
	 * Service Call: CheckComplianceStatus
	 * @param CheckComplianceStatusRequest $checkComplianceStatusRequest
	 * @param mixed $apiCredential - Optional API credential - can either be
	 * 		a username configured in sdk_config.ini or a ICredential object
	 *      created dynamically 		
	 * @return CheckComplianceStatusResponse
	 * @throws APIException
	 */
	public function CheckComplianceStatus($checkComplianceStatusRequest, $apiCredential = NULL) {
		$ret = new CheckComplianceStatusResponse();
		$apiContext = new PPApiContext($this->config);
        $handlers = array(
            new PPPlatformServiceHandler($apiCredential, self::$SDK_NAME, self::$SDK_VERSION),
        );
		$resp = $this->call('AdaptiveAccounts', 'CheckComplianceStatus', $checkComplianceStatusRequest, $apiContext, $handlers);
		$ret->init(PPUtils::nvpToMap($resp));
		return $ret;
	}
	 

	/**
	 * Service Call: AddPartnerFinancialProduct
	 * @param AddPartnerFinancialProductRequest $addPartnerFinancialProductRequest
	 * @param mixed $apiCredential - Optional API credential - can either be
	 * 		a username configured in sdk_config.ini or a ICredential object
	 *      created dynamically 		
	 * @return AddPartnerFinancialProductResponse
	 * @throws APIException
	 */
	public function AddPartnerFinancialProduct($addPartnerFinancialProductRequest, $apiCredential = NULL) {
		$ret = new AddPartnerFinancialProductResponse();
		$apiContext = new PPApiContext($this->config);
        $handlers = array(
            new PPPlatformServiceHandler($apiCredential, self::$SDK_NAME, self::$SDK_VERSION),
        );
		$resp = $this->call('AdaptiveAccounts', 'AddPartnerFinancialProduct', $addPartnerFinancialProductRequest, $apiContext, $handlers);
		$ret->init(PPUtils::nvpToMap($resp));
		return $ret;
	}
	 

	/**
	 * Service Call: ActivateProduct
	 * @param ActivateProductRequest $activateProductRequest
	 * @param mixed $apiCredential - Optional API credential - can either be
	 * 		a username configured in sdk_config.ini or a ICredential object
	 *      created dynamically 		
	 * @return ActivateProductResponse
	 * @throws APIException
	 */
	public function ActivateProduct($activateProductRequest, $apiCredential = NULL) {
		$ret = new ActivateProductResponse();
		$apiContext = new PPApiContext($this->config);
        $handlers = array(
            new PPPlatformServiceHandler($apiCredential, self::$SDK_NAME, self::$SDK_VERSION),
        );
		$resp = $this->call('AdaptiveAccounts', 'ActivateProduct', $activateProductRequest, $apiContext, $handlers);
		$ret->init(PPUtils::nvpToMap($resp));
		return $ret;
	}
	 

	/**
	 * Service Call: UpdateComplianceStatus
	 * @param UpdateComplianceStatusRequest $updateComplianceStatusRequest
	 * @param mixed $apiCredential - Optional API credential - can either be
	 * 		a username configured in sdk_config.ini or a ICredential object
	 *      created dynamically 		
	 * @return UpdateComplianceStatusResponse
	 * @throws APIException
	 */
	public function UpdateComplianceStatus($updateComplianceStatusRequest, $apiCredential = NULL) {
		$ret = new UpdateComplianceStatusResponse();
		$apiContext = new PPApiContext($this->config);
        $handlers = array(
            new PPPlatformServiceHandler($apiCredential, self::$SDK_NAME, self::$SDK_VERSION),
        );
		$resp = $this->call('AdaptiveAccounts', 'UpdateComplianceStatus', $updateComplianceStatusRequest, $apiContext, $handlers);
		$ret->init(PPUtils::nvpToMap($resp));
		return $ret;
	}
	 
}