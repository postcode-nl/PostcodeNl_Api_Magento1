<?php
class Mage42_PostcodeNL_Helper_Data extends Mage_Core_Helper_Abstract
{
    const SESSION_HEADER_KEY = 'X-Autocomplete-Session';
    const API_TIMEOUT = 3;
    const ACCOUNT_URL = 'https://api.postcode.nl';
    const API_VERSION = 'v1';

    protected $_curlHandler;
    protected $_mostRecentResponseHeaders = [];

    protected $_modules = null;

    protected $_enrichType = 0;

    protected $_httpResponseRaw = null;
    protected $_httpResponseCode = null;
    protected $_httpResponseCodeClass = null;
    protected $_httpClientError = null;
    protected $_debuggingOverride = false;

    public function __construct()
    {
        if (!extension_loaded('curl'))
            throw new Mage42_PostcodeNL_Helper_Exception_CurlNotLoadedException('Cannot use Postcode.nl International Autocomplete client, the server needs to have the PHP `cURL` extension installed.');

        $this->_curlHandler = curl_init();
        curl_setopt($this->_curlHandler, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($this->_curlHandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_curlHandler, CURLOPT_CONNECTTIMEOUT, self::API_TIMEOUT);
        curl_setopt($this->_curlHandler, CURLOPT_TIMEOUT, self::API_TIMEOUT);
        curl_setopt($this->_curlHandler, CURLOPT_USERAGENT, static::class . '/' . static::API_VERSION . ' PHP/' . PHP_VERSION);

        if (isset($_SERVER['HTTP_REFERER']))
            curl_setopt($this->_curlHandler, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);

        curl_setopt($this->_curlHandler, CURLOPT_HEADERFUNCTION, function($curl, $header) {
            $length = strlen($header);
            $headerParts = explode(':', $header, 2);
            if (count($headerParts) < 2)
                return $length;
            $headerName = $headerParts[0];
            $headerValue = $headerParts[1];
            $this->_mostRecentResponseHeaders[strtolower(trim($headerName))][] = trim($headerValue);
            return $length;
        });

    }

    /**
     * @param bool $getAdminConfig
     * @return string
     */
    public function getJsinit($getAdminConfig = false)
    {
        if ($getAdminConfig && !$this->_getStoreConfig('mage42_postcodenl/advanced_config/admin_validation_enabled'))
            return '';

        $baseUrl = $this->_getMagentoLookupUrl($getAdminConfig);

        $html = '
			<script type="text/javascript">
			//<![CDATA[
				var MAGE42PCNL_CONFIG = {
					baseUrl: "' . htmlspecialchars($baseUrl) . '",
					autocompleteCountries: '. $this->_getAllowedCountries() .',
					useStreet2AsHouseNumber: ' . $this->_getConfigBoolString('mage42_postcodenl/advanced_config/use_street2_as_housenumber') . ',
					useStreet3AsHouseNumberAddition: ' . $this->_getConfigBoolString('mage42_postcodenl/advanced_config/use_street3_as_housenumber_addition') . ',
					blockPostOfficeBoxAddresses: '. $this->_getConfigBoolString('mage42_postcodenl/advanced_config/block_postofficeboxaddresses') . ',
					neverHideCountry: ' . $this->_getConfigBoolString('mage42_postcodenl/advanced_config/never_hide_country') . ',
					showcase: ' . $this->_getConfigBoolString('mage42_postcodenl/development_config/api_showcase') . ',
					debug: ' . ($this->isDebugging() ? 'true' : 'false') . ',
					translations: {
						defaultError: "' . htmlspecialchars($this->__('Unknown postcode + housenumber combination.')) . '",
						postcodeInputLabel: "' . htmlspecialchars($this->__('Postcode')) . '",
						postcodeInputTitle: "' . htmlspecialchars($this->__('Postcode')) . '",
						houseNumberAdditionUnknown: "' . htmlspecialchars($this->__('Housenumber addition `{addition}` is unknown.')) . '",
						houseNumberAdditionRequired: "' . htmlspecialchars($this->__('Housenumber addition required.')) . '",
						houseNumberLabel: "' . htmlspecialchars($this->__('Housenumber')) . '",
						houseNumberTitle: "' . htmlspecialchars($this->__('Housenumber')) . '",
						houseNumberAdditionLabel: "' . htmlspecialchars($this->__('Housenumber addition')) . '",
						houseNumberAdditionTitle: "' . htmlspecialchars($this->__('Housenumber addition')) . '",
						streetNameLabel: "' . htmlspecialchars($this->__('Address')) . '",
						streetNameTitle: "' . htmlspecialchars($this->__('Address')) . '",
						streetNamePlaceholder: "' . htmlspecialchars($this->__('City, street or postcode')) . '",
						selectAddition: "' . htmlspecialchars($this->__('Select...')) . '",
						noAdditionSelect: "' . htmlspecialchars($this->__('No addition.')) . '",
						noAdditionSelectCustom: "' . htmlspecialchars($this->__('`No addition`')) . '",
						additionSelectCustom: "' . htmlspecialchars($this->__('`{addition}`')) . '",
						apiShowcase: "' . htmlspecialchars($this->__('API Showcase')) . '",
						apiDebug: "' . htmlspecialchars($this->__('API Debug')) . '",
						disabledText: "' . htmlspecialchars($this->__('- disabled -')) . '",
						infoLabel: "' . htmlspecialchars($this->__('Address validation')) . '",
						infoText: "' . htmlspecialchars($this->__('Fill out your postcode and housenumber to auto-complete your address.')) . '",
						manualInputLabel: "' . htmlspecialchars($this->__('Manual input')) . '",
						manualInputText: "' . htmlspecialchars($this->__('Fill out address information manually')) . '",
						outputLabel: "' . htmlspecialchars($this->__('Validated address')) . '",
						postOfficeBoxNotAllowed: "' . htmlspecialchars($this->__('Post office box not allowed.')) . '"
					}
				};
			//]]>
			</script>';

        return $html;
    }

    /**
     * @return array|string
     */
    public function lookupAccountUser()
    {
        $response = array();
        try {
            $url = $this->_getAccountUrl() . '/account/v1/info';
            $jsonData = $this->_callApiUrlGet($url);
            if (isset($jsonData['name'])) {
                return "<h4>".$jsonData['name']."</h4>";
            } else {
                return "Could not find a user linked to these API keys";
            }
        } catch (Exception $e) {
            return array_merge($response, $this->_errorResponse());
        }
    }

    /**
     * @param $context
     * @param $term
     * @param $session
     * @return mixed
     * @throws Mage42_PostcodeNL_Helper_Exception_AuthenticationException
     * @throws Mage42_PostcodeNL_Helper_Exception_BadRequestException
     * @throws Mage42_PostcodeNL_Helper_Exception_CurlException
     * @throws Mage42_PostcodeNL_Helper_Exception_ForbiddenException
     * @throws Mage42_PostcodeNL_Helper_Exception_InvalidJsonResponseException
     * @throws Mage42_PostcodeNL_Helper_Exception_ServerUnavailableException
     * @throws Mage42_PostcodeNL_Helper_Exception_TooManyRequestsException
     * @throws Mage42_PostcodeNL_Helper_Exception_UnexpectedException
     *
     * @see https://api.postcode.nl/documentation/international/v1/Autocomplete/autocomplete
     */
    public function _internationalAutocomplete($context, $term, $session = null)
    {
        return $this->_performApiCall('international/v1/autocomplete/' . rawurlencode($context) . '/' . rawurlencode($term), $session ? $session : $this->generateSessionString());
    }

    /**
     * @param $context
     * @param $session
     * @return mixed
     * @throws Mage42_PostcodeNL_Helper_Exception_AuthenticationException
     * @throws Mage42_PostcodeNL_Helper_Exception_BadRequestException
     * @throws Mage42_PostcodeNL_Helper_Exception_CurlException
     * @throws Mage42_PostcodeNL_Helper_Exception_ForbiddenException
     * @throws Mage42_PostcodeNL_Helper_Exception_InvalidJsonResponseException
     * @throws Mage42_PostcodeNL_Helper_Exception_ServerUnavailableException
     * @throws Mage42_PostcodeNL_Helper_Exception_TooManyRequestsException
     * @throws Mage42_PostcodeNL_Helper_Exception_UnexpectedException
     *
     * @see https://api.postcode.nl/documentation/international/v1/Autocomplete/getDetails
     */
    public function _internationalGetDetails($context, $session = null)
    {
        return $this->_performApiCall('international/v1/address/' . rawurlencode($context), $session ? $session : $this->generateSessionString());
    }

    /**
     * @return mixed
     * @throws Mage42_PostcodeNL_Helper_Exception_AuthenticationException
     * @throws Mage42_PostcodeNL_Helper_Exception_BadRequestException
     * @throws Mage42_PostcodeNL_Helper_Exception_CurlException
     * @throws Mage42_PostcodeNL_Helper_Exception_ForbiddenException
     * @throws Mage42_PostcodeNL_Helper_Exception_InvalidJsonResponseException
     * @throws Mage42_PostcodeNL_Helper_Exception_ServerUnavailableException
     * @throws Mage42_PostcodeNL_Helper_Exception_TooManyRequestsException
     * @throws Mage42_PostcodeNL_Helper_Exception_UnexpectedException
     *
     * @see https://api.postcode.nl/documentation/international/v1/Autocomplete/getSupportedCountries
     */
    public function _internationalGetSupportedCountries()
    {
        return $this->_performApiCall('international/v1/supported-countries', null);
    }

    /**
     * @param $postcode
     * @param $houseNumber
     * @param $houseNumberAddition
     * @return mixed
     * @throws Mage42_PostcodeNL_Helper_Exception_AuthenticationException
     * @throws Mage42_PostcodeNL_Helper_Exception_BadRequestException
     * @throws Mage42_PostcodeNL_Helper_Exception_CurlException
     * @throws Mage42_PostcodeNL_Helper_Exception_ForbiddenException
     * @throws Mage42_PostcodeNL_Helper_Exception_InvalidJsonResponseException
     * @throws Mage42_PostcodeNL_Helper_Exception_InvalidPostcodeException
     * @throws Mage42_PostcodeNL_Helper_Exception_ServerUnavailableException
     * @throws Mage42_PostcodeNL_Helper_Exception_TooManyRequestsException
     * @throws Mage42_PostcodeNL_Helper_Exception_UnexpectedException
     *
     * @see https://api.postcode.nl/documentation
     */
    public function _dutchAddressByPostcode($postcode, $houseNumber, $houseNumberAddition)
    {
        $postcode = trim($postcode);
        if (!$this->_isValidDutchPostcodeFormat($postcode))
            throw new Mage42_PostcodeNL_Helper_Exception_InvalidPostcodeException(sprintf('Postcode `%s` has an invalid format, it should be in the format 1234AB.', $postcode));
        $urlParts = [
            'nl/v1/addresses/postcode',
            rawurlencode($postcode),
            $houseNumber,
        ];
        if ($houseNumberAddition !== null)
            $urlParts[] = rawurlencode($houseNumberAddition);
        return $this->_performApiCall(implode('/', $urlParts), null);
    }

    /**
     * @return mixed
     * @throws Mage42_PostcodeNL_Helper_Exception_AuthenticationException
     * @throws Mage42_PostcodeNL_Helper_Exception_BadRequestException
     * @throws Mage42_PostcodeNL_Helper_Exception_CurlException
     * @throws Mage42_PostcodeNL_Helper_Exception_ForbiddenException
     * @throws Mage42_PostcodeNL_Helper_Exception_InvalidJsonResponseException
     * @throws Mage42_PostcodeNL_Helper_Exception_ServerUnavailableException
     * @throws Mage42_PostcodeNL_Helper_Exception_TooManyRequestsException
     * @throws Mage42_PostcodeNL_Helper_Exception_UnexpectedException
     */
    public function _accountInfo()
    {
        return $this->_performApiCall('account/v1/info', null);
    }

    protected function _getApiCallResponseHeaders()
    {
        return $this->_mostRecentResponseHeaders;
    }

    protected function _isValidDutchPostcodeFormat($postcode)
    {
        return (bool) preg_match('~^[1-9]\d{3}\s?[a-zA-Z]{2}$~', $postcode);
    }

    public function __destruct()
    {
        curl_close($this->_curlHandler);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function generateSessionString()
    {
        $phpversion = (float) phpversion();
        if ($phpversion === 0)
            throw new Mage42_PostcodeNL_Helper_Exception_NotAValidPHPVersionException('Please check your PHP version');
        if ($phpversion > 7.0)
            return bin2hex(random_bytes(8));
        else
            return bin2hex(openssl_random_pseudo_bytes(8));
    }

    /**
     * @param $path
     * @param $session
     * @return mixed
     * @throws Mage42_PostcodeNL_Helper_Exception_AuthenticationException
     * @throws Mage42_PostcodeNL_Helper_Exception_BadRequestException
     * @throws Mage42_PostcodeNL_Helper_Exception_CurlException
     * @throws Mage42_PostcodeNL_Helper_Exception_ForbiddenException
     * @throws Mage42_PostcodeNL_Helper_Exception_InvalidJsonResponseException
     * @throws Mage42_PostcodeNL_Helper_Exception_ServerUnavailableException
     * @throws Mage42_PostcodeNL_Helper_Exception_TooManyRequestsException
     * @throws Mage42_PostcodeNL_Helper_Exception_UnexpectedException
     */
    protected function _performApiCall($path, $session)
    {
        $url = $this->_getServiceUrl() .'/' . $path;
        curl_setopt($this->_curlHandler, CURLOPT_URL, $url);
        curl_setopt($this->_curlHandler, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->_curlHandler, CURLOPT_USERPWD, $this->_getKey() . ':' . $this->_getSecret());
        if ($session !== null)
            curl_setopt($this->_curlHandler, CURLOPT_HTTPHEADER, [
               static::SESSION_HEADER_KEY . ': ' . $session,
            ]);

        $this->_mostRecentResponseHeaders = [];
        $response = curl_exec($this->_curlHandler);
        $responseStatusCode = curl_getinfo($this->_curlHandler, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($this->_curlHandler);
        $curlErrorNr = curl_errno($this->_curlHandler);
        if ($curlError !== '')
            throw new Mage42_PostcodeNL_Helper_Exception_CurlException('Connection error number `%s`: `%s`.', [$curlErrorNr, $curlError]);
        $jsonResponse = json_decode($response, true);
        switch ($responseStatusCode)
        {
            case 200:
                if (!is_array($jsonResponse))
                    throw new Mage42_PostcodeNL_Helper_Exception_InvalidJsonResponseException('Invalid JSON response from the server for request: ' . $url);
                return $jsonResponse;
            case 400:
                throw new Mage42_PostcodeNL_Helper_Exception_BadRequestException(vsprintf('Server response code 400, bad request for `%s`.', [$url]));
            case 401:
                throw new Mage42_PostcodeNL_Helper_Exception_AuthenticationException('Could not authenticate your request, please make sure your API credentials are correct.');
            case 403:
                throw new Mage42_PostcodeNL_Helper_Exception_ForbiddenException('Your account currently has no access to the international API, make sure you have an active subscription.');
            case 429:
                throw new Mage42_PostcodeNL_Helper_Exception_TooManyRequestsException('Too many requests made, please slow down: ' . $response);
            case 503:
                throw new Mage42_PostcodeNL_Helper_Exception_ServerUnavailableException('The international API server is currently not available: ' . $response);
            default:
                throw new Mage42_PostcodeNL_Helper_Exception_UnexpectedException(vsprintf('Unexpected server response code `%s`.', [$responseStatusCode]));
        }
    }

    /**
     * @param $path
     * @return mixed
     */
    protected function _getStoreConfig($path)
    {
        return Mage::getStoreConfig($path);
    }

    /**
     * @return string
     */
    protected function _getKey()
    {
        return trim($this->_getStoreConfig('mage42_postcodenl/config/api_key'));
    }

    /**
     * @return string
     */
    protected function _getSecret()
    {
        return trim($this->_getStoreConfig('mage42_postcodenl/config/api_secret'));
    }

    /**
     * @return array
     */
    protected function _getAllowedCountries() {
        return json_encode($this->_getStoreConfig('mage42_postcodenl/advanced_config/select_countries_for_autocomplete'));
    }

    /**
     * @return string
     */
    protected function _getServiceUrl()
    {
        $serviceUrl = trim($this->_getStoreConfig('mage42_postcodenl/development_config/api_url'));
        if (empty($serviceUrl))
            $serviceUrl = self::ACCOUNT_URL;

        return $serviceUrl;
    }

    /**
     * @return string
     */
    protected function _getAccountUrl()
    {
        $accountUrl = trim($this->_getStoreConfig('mage42_postcodenl/development_config/account_url'));
        if (empty($accountUrl))
            $accountUrl = self::ACCOUNT_URL;

        return $accountUrl;
    }

    /**
     * Check if we're currently in debug mode, and if the current user may see dev info.
     *
     * @return bool
     */
    public function isDebugging()
    {
        if ($this->_debuggingOverride)
            return true;

        return (bool)$this->_getStoreConfig('mage42_postcodenl/development_config/api_debug') && Mage::helper('core')->isDevAllowed();
    }

    /**
     * Set the debugging override flag.
     *
     * @param bool $toggle
     */
    protected function _setDebuggingOverride($toggle)
    {
        $this->_debuggingOverride = $toggle;
    }

    /**
     * @return string
     */
    protected function _getMagentoVersion()
    {
        // Detect enterprise
        if ($this->_getModuleInfo('Enterprise_CatalogPermissions') !== null)
            return 'MagentoEnterprise/'. Mage::getVersion();

        // Detect professional
        if ($this->_getModuleInfo('Enterprise_Enterprise') !== null)
            return 'MagentoProfessional/'. Mage::getVersion();

        return 'Magento/'. Mage::getVersion();
    }

    /**
     * @param $moduleName
     * @return mixed|null
     */
    protected function _getModuleInfo($moduleName)
    {
        $modules = $this->_getMagentoModules();

        if (!isset($modules[$moduleName]))
            return null;

        return $modules[$moduleName];
    }

    /**
     * @param $configKey
     * @return string
     */
    protected function _getConfigBoolString($configKey)
    {
        if ($this->_getStoreConfig($configKey))
            return 'true';

        return 'false';
    }

    /**
     * @param bool $inAdmin
     * @return string
     */
    protected function _getMagentoLookupUrl($inAdmin = false)
    {
        if ($inAdmin)
            return Mage::helper('adminhtml')->getUrl('*/pcnl/lookup', array('_secure' => true));

        return Mage::getUrl('mage42_postcodenl/json', array('_secure' => true));
    }
}