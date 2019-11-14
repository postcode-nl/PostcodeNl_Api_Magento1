<?php
class Mage42_PostcodeNL_Helper_Data extends Mage_Core_Helper_Abstract
{
    const API_TIMEOUT = 3;
    const API_URL = 'https://api.postcode.nl';
    const ACCOUNT_URL = 'https://api.postcode.eu';
    const API_VERSION = 'v1';

    protected $_modules = null;

    protected $_enrichType = 0;

    protected $_httpResponseRaw = null;
    protected $_httpResponseCode = null;
    protected $_httpResponseCodeClass = null;
    protected $_httpClientError = null;
    protected $_debuggingOverride = false;


    /**
     * Get the html for initializing validation script.
     *
     * @param bool $getAdminConfig
     *
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
				var PCNLAPI_CONFIG = {
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
						streetNameLabel: "' . htmlspecialchars($this->__('Street')) . '",
						streetNameTitle: "' . htmlspecialchars($this->__('Street')) . '",
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
     * @return array|string
     */
    public function lookupAccountAccess()
    {
        $response = array();
        try {
            $url = $this->_getAccountUrl() . '/account/v1/info';
            $jsonData = $this->_callApiUrlGet($url);
            if (isset($jsonData['hasAccess'])) {
               return $jsonData['hasAccess'] == 1 ? "<h4 style='color: green'>valid</h4>" : "<h4 style='color: red'>invalid</h4>";
            } else {
                return "<h4 style='color: red'>invalid</h4>";
            }
        } catch (Exception $e) {
            return array_merge($response, $this->_errorResponse());
        }
    }

    /**
     * @return array
     */
    public function lookupAccountCountries()
    {
        $response = array();
        try {
            $url = $this->_getAccountUrl() . '/account/v1/info';
            $jsonData = $this->_callApiUrlGet($url);
            if (isset($jsonData['countries'])) {
                $response['countries'] = $jsonData['countries'];
                return $response;
            }

        } catch (Exception $e) {
            return array_merge($response, $this->_errorResponse());
        }
    }

    /**
     * Lookup information about a Dutch address by countryId, postcode, house number, and house number addition
     *
     * @param string $countryId
     * @param string $postcode
     * @param string $houseNumber
     * @param string $houseNumberAddition
     *
     * @return array
     */
    public function lookupAddress($countryId, $postcode, $houseNumber, $houseNumberAddition)
    {
        // Check if we are we enabled, configured & capable of handling an API request
        $message = $this->_checkApiReady();
        if ($message)
            return $message;

        // Some basic user data 'fixing', remove any not-letter, not-number characters
        $postcode = preg_replace('~[^a-z0-9]~i', '', $postcode);

        switch($countryId) {
            case "NL":
                // Basic postcode format checking
                if (!preg_match('~^[1-9][0-9]{3}[a-z]{2}$~i', $postcode)) {
                    $response['message'] = $this->__('Invalid postcode format, use `1234AB` format.');
                    $response['messageTarget'] = 'postcode';
                    return $response;
                }
                break;
            case "BE":
                if (!preg_match('~^(?:(?:[1-9])(?:\d{3}))$~i', $postcode)) {
                    $response['message'] = $this->__('Invalid postcode format, use `1234` format.');
                    $response['messageTarget'] = 'postcode';
                    return $response;
                }
                break;
            default:
                // Basic postcode format checking
                if (!preg_match('~^[1-9][0-9]{3}[a-z]{2}$~i', $postcode)) {
                    $response['message'] = $this->__('Invalid postcode format, use `1234AB` format.');
                    $response['messageTarget'] = 'postcode';
                    return $response;
                }
                break;
        }

        return $this->_lookupAddress($countryId, $postcode, $houseNumber, $houseNumberAddition);
    }

    /**
     * @param string $countryId
     * @param string $postcode
     * @param string $houseNumber
     * @param string $houseNumberAddition
     *
     * @return array
     */
    public function _lookupAddress($countryId, $postcode, $houseNumber, $houseNumberAddition)
    {
        $response = array();

        try {
            //$url = $this->_getServiceUrl() . '/rest/addresses/' . rawurlencode($postcode). '/'. rawurlencode($houseNumber) . '/'. rawurlencode($houseNumberAddition);
            $url = $this->_getServiceUrl() . '/' . rawurlencode(strtolower($countryId)) . '/' . rawurlencode(self::API_VERSION) . '/addresses/postcode/' . rawurlencode($postcode). '/'. rawurlencode($houseNumber) . '/'. rawurlencode($houseNumberAddition);
            $jsonData = $this->_callApiUrlGet($url);
            if ($this->_getStoreConfig('mage42_postcodenl/development_config/api_showcase'))
                $response['showcaseResponse'] = $jsonData;

            if ($this->isDebugging())
                $response['debugInfo'] = $this->_getDebugInfo($url, $jsonData);

            if ($this->_httpResponseCode == 200 && is_array($jsonData) && isset($jsonData['postcode'])) {
                return array_merge($response, $jsonData);
            }

            if (isset($jsonData['exceptionId'])) {
                if ($this->_httpResponseCode == 400 || $this->_httpResponseCode == 404) {
                    switch ($jsonData['exceptionId'])
                    {
                        case 'PostcodeNl_Controller_Address_PostcodeTooShortException':
                        case 'PostcodeNl_Controller_Address_PostcodeTooLongException':
                        case 'PostcodeNl_Controller_Address_NoPostcodeSpecifiedException':
                        case 'PostcodeNl_Controller_Address_InvalidPostcodeException':
                            $response['message'] = $this->__('Invalid postcode format, use `1234AB` format.');
                            $response['messageTarget'] = 'postcode';
                            break;
                        case 'PostcodeNl_Service_PostcodeAddress_AddressNotFoundException':
                            $response['message'] = $this->__('Unknown postcode + housenumber combination.');
                            $response['messageTarget'] = 'housenumber';
                            break;
                        case 'PostcodeNl_Controller_Address_InvalidHouseNumberException':
                        case 'PostcodeNl_Controller_Address_NoHouseNumberSpecifiedException':
                        case 'PostcodeNl_Controller_Address_NegativeHouseNumberException':
                        case 'PostcodeNl_Controller_Address_HouseNumberTooLargeException':
                        case 'PostcodeNl_Controller_Address_HouseNumberIsNotAnIntegerException':
                            $response['message'] = $this->__('Housenumber format is not valid.');
                            $response['messageTarget'] = 'housenumber';
                            break;
                        default:
                            $response['message'] = $this->__('Incorrect address.');
                            $response['messageTarget'] = 'housenumber';
                            break;
                    }

                    return $response;
                }
            }

            return array_merge($response, $this->_errorResponse());
        } catch (Exception $e) {
            return array_merge($response, $this->_errorResponse());
        }
    }

    /**
     * @param $countryId
     * @param $postcode
     * @return array|bool
     */
    public function autocompletePostal($countryId, $postcode)
    {
        $message = $this->_checkApiReady();
        if ($message)
            return $message;

        $postcode = preg_replace('~[^a-z0-9]~i', '', $postcode);

        return $this->_autocompletePostal($countryId, $postcode);
    }

    /**
     * @param $countryId
     * @param $postcode
     * @return array
     */
    public function _autocompletePostal($countryId, $postcode)
    {
        $response = array();

        try {
            $url = $this->_getServiceUrl() . '/' . rawurlencode(strtolower($countryId)) . '/' . rawurlencode(self::API_VERSION) . '/autocomplete/postal-area/' . rawurlencode($postcode);
            $jsonData = $this->_callApiUrlGet($url);
            if ($this->_getStoreConfig('mage42_postcodenl/development_config/api_showcase'))
                $response['showcaseResponse'] = $jsonData;

            if ($this->isDebugging())
                $response['debugInfo'] = $this->_getDebugInfo($url, $jsonData);

            if ($this->_httpResponseCode == 200 && is_array($jsonData) && (isset($jsonData[0]['postcode']) || (isset($jsonData[0]['municipalityName']) || isset($jsonData[0]['cityName'])))) {
                return array_merge($response, $jsonData);
            }

            if (isset($jsonData['exceptionId'])) {
                if ($this->_httpResponseCode == 400 || $this->_httpResponseCode == 404) {
                    switch ($jsonData['exceptionId'])
                    {
                        case 'PostcodeNl_Controller_Belgium_AutoComplete_PostalAreaTooShortException':
                        case 'PostcodeNl_Controller_Germany_AutoComplete_PostalAreaTooShortException':
                            $response['message'] = $this->__('The specified postal area parameter is too short..');
                            $response['messageTarget'] = 'postcode';
                            break;
                        default:
                            $response['message'] = $this->__('Incorrect address.');
                            $response['messageTarget'] = 'housenumber';
                            break;
                    }

                    return $response;
                }
            }

            return array_merge($response, $this->_errorResponse());
        } catch (Exception $e) {
            return array_merge($response, $this->_errorResponse());
        }
    }

    /**
     * @param $countryId
     * @param $cityId
     * @param $postcode
     * @param $streetName
     * @return array|bool
     */
    public function autocompleteStreet($countryId, $cityId, $postcode, $streetName)
    {
        $message = $this->_checkApiReady();
        if ($message)
            return $message;

        $postcode = preg_replace('~[^0-9]~i', '', $postcode);

        return $this->_autocompleteStreet($countryId, $cityId, $postcode, $streetName);
    }

    /**
     * @param $countryId
     * @param $cityId
     * @param $postcode
     * @param $streetName
     * @return array
     */
    public function _autocompleteStreet($countryId, $cityId, $postcode, $streetName)
    {
        $response = array();

        try {
            $url = $this->_getServiceUrl() . '/' . rawurlencode(strtolower($countryId)) . '/' . rawurlencode(self::API_VERSION) . '/autocomplete/street/' . rawurlencode($cityId) . '/' . rawurlencode($postcode) . '/' . rawurlencode($streetName);
            $jsonData = $this->_callApiUrlGet($url);
            if ($this->_getStoreConfig('mage42_postcodenl/development_config/api_showcase'))
                $response['showcaseResponse'] = $jsonData;

            if ($this->isDebugging())
                $response['debugInfo'] = $this->_getDebugInfo($url, $jsonData);

            if ($this->_httpResponseCode == 200 && is_array($jsonData) && (isset($jsonData[0]['postcode']) || (isset($jsonData[0]['municipalityName']) || isset($jsonData[0]['cityName'])))) {
                return array_merge($response, $jsonData);
            }

            if (isset($jsonData['exceptionId'])) {
                if ($this->_httpResponseCode == 400 || $this->_httpResponseCode == 404) {
                    switch ($jsonData['exceptionId'])
                    {
                        case 'PostcodeNl_Controller_Belgium_AutoComplete_PostalAreaTooShortException':
                        case 'PostcodeNl_Controller_Germany_AutoComplete_PostalAreaTooShortException':
                            $response['message'] = $this->__('The specified postal area parameter is too short..');
                            $response['messageTarget'] = 'postcode';
                            break;
                        default:
                            $response['message'] = $this->__('Incorrect address.');
                            $response['messageTarget'] = 'housenumber';
                            break;
                    }

                    return $response;
                }
            }

            return array_merge($response, $this->_errorResponse());
        } catch (Exception $e) {
            return array_merge($response, $this->_errorResponse());
        }
    }

    /**
     * @param $countryId
     * @param $cityId
     * @param $streetId
     * @param $postcode
     * @param $validation
     * @param $houseNumber
     * @param string $language
     * @return array|bool
     */
    public function autocompleteHouseNumber($countryId, $cityId, $streetId, $postcode, $validation, $houseNumber, $language = '')
    {
        $message = $this->_checkApiReady();
        if ($message)
            return $message;

        $postcode = preg_replace('~[^0-9]~i', '', $postcode);

        return $this->_autocompleteHouseNumber($countryId, $cityId, $streetId, $postcode, $validation, $houseNumber, $language);
    }

    /**
     * @param $countryId
     * @param $cityId
     * @param $streetId
     * @param $postcode
     * @param $validation
     * @param $houseNumber
     * @param $language
     * @return array
     */
    public function _autocompleteHouseNumber($countryId, $cityId, $streetId, $postcode, $validation, $houseNumber, $language)
    {
        $response = array();

        try {
            $url = (strtolower($countryId) == 'be')
                ? $this->_getServiceUrl() . '/' . rawurlencode(strtolower($countryId)) . '/' . rawurlencode(self::API_VERSION) . '/autocomplete/house-number/' . rawurlencode($cityId) . '/' . rawurlencode($streetId) . '/' . rawurlencode($postcode) . '/' . rawurlencode($language) . '/' . rawurlencode($validation) . '/' . rawurlencode($houseNumber)
                : $this->_getServiceUrl() . '/' . rawurlencode(strtolower($countryId)) . '/' . rawurlencode(self::API_VERSION) . '/autocomplete/house-number/' . rawurlencode($cityId) . '/' . rawurlencode($streetId) . '/' . rawurlencode($postcode) . '/' . rawurlencode($validation) . '/' . rawurlencode($houseNumber);
            $jsonData = $this->_callApiUrlGet($url);
            if ($this->_getStoreConfig('mage42_postcodenl/development_config/api_showcase'))
                $response['showcaseResponse'] = $jsonData;
            if ($this->isDebugging())
                $response['debugInfo'] = $this->_getDebugInfo($url, $jsonData);
            if ($this->_httpResponseCode == 200 && is_array($jsonData) && isset($jsonData[0]['postcode']))
                return array_merge($response, $jsonData);
            if (isset($jsonData['exceptionId'])) {
                if ($this->_httpResponseCode == 400 || $this->_httpResponseCode == 404) {
                    switch ($jsonData['exceptionId'])
                    {
                        case 'PostcodeNl_Controller_Belgium_AutoComplete_PostalAreaTooShortException':
                        case 'PostcodeNl_Controller_Germany_AutoComplete_PostalAreaTooShortException':
                            $response['message'] = $this->__('The specified postal area parameter is too short..');
                            $response['messageTarget'] = 'postcode';
                            break;
                        default:
                            $response['message'] = $this->__('Incorrect address.');
                            $response['messageTarget'] = 'housenumber';
                            break;
                    }

                    return $response;
                }
            }
            return array_merge($response, $this->_errorResponse());
        } catch (Exception $e) {
            return array_merge($response, $this->_errorResponse());
        }
    }

    /**
     * Set the enrichType number, or text/class description if not in known enrichType list
     *
     * @param mixed $enrichType
     */
    public function setEnrichType($enrichType)
    {
        $this->_enrichType = preg_replace('~[^0-9a-z\-_,]~i', '', $enrichType);
        if (strlen($this->_enrichType) > 40)
            $this->_enrichType = substr($this->_enrichType, 0, 40);
    }

    /**
     * @param $url
     * @param $jsonData
     * @return array
     */
    protected function _getDebugInfo($url, $jsonData)
    {
        return array(
            'requestUrl' => $url,
            'rawResponse' => $this->_httpResponseRaw,
            'responseCode' => $this->_httpResponseCode,
            'responseCodeClass' => $this->_httpResponseCodeClass,
            'parsedResponse' => $jsonData,
            'httpClientError' => $this->_httpClientError,
            'configuration' => array(
                'url' => $this->_getServiceUrl(),
                'key' => $this->_getKey(),
                'secret' => substr($this->_getSecret(), 0, 6) .'[hidden]',
                'showcase' => $this->_getStoreConfig('mage42_postcodenl/development_config/api_showcase'),
                'debug' => $this->_getStoreConfig('mage42_postcodenl/development_config/api_debug'),
            ),
            'magentoVersion' => $this->_getMagentoVersion(),
            'extensionVersion' => $this->_getExtensionVersion(),
            'modules' => $this->_getMagentoModules(),
        );
    }

    /**
     * @return array
     */
    public function testConnection()
    {
        // Default is not OK
        $status = 'error';
        $info = array();

        // Do a test address lookup
        $this->_setDebuggingOverride(true);
        $addressData = $this->lookupAddress('2012ES', '30', '');
        $this->_setDebuggingOverride(false);

        if (!isset($addressData['debugInfo']) && isset($addressData['message'])) {
            // Client-side error
            $message = $addressData['message'];
            if (isset($addressData['info']))
                $info = $addressData['info'];
        } else if ($addressData['debugInfo']['httpClientError']) {
            // We have a HTTP connection error
            $message = $this->__('Your server could not connect to the Postcode.nl server.');

            // Do some common SSL CA problem detection
            if (strpos($addressData['debugInfo']['httpClientError'], 'SSL certificate problem, verify that the CA cert is OK') !== false) {
                $info[] = $this->__('Your servers\' \'cURL SSL CA bundle\' is missing or outdated. Further information:');
                $info[] = '- <a href="https://stackoverflow.com/questions/6400300/https-and-ssl3-get-server-certificatecertificate-verify-failed-ca-is-ok" target="_blank">'. $this->__('How to update/fix your CA cert bundle') .'</a>';
                $info[] = '- <a href="https://curl.haxx.se/docs/sslcerts.html" target="_blank">'. $this->__('About cURL SSL CA certificates') .'</a>';
                $info[] = '';
            } else if (strpos($addressData['debugInfo']['httpClientError'], 'unable to get local issuer certificate') !== false) {
                $info[] = $this->__('cURL cannot read/access the CA cert file:');
                $info[] = '- <a href="https://curl.haxx.se/docs/sslcerts.html" target="_blank">'. $this->__('About cURL SSL CA certificates') .'</a>';
                $info[] = '';
            } else {
                $info[] = $this->__('Connection error.');
            }

            $info[] = $this->__('Error message:') . ' "'. $addressData['debugInfo']['httpClientError'] .'"';
            $info[] = '- <a href="https://www.google.com/search?q='. urlencode($addressData['debugInfo']['httpClientError'])  .'" target="_blank">'. $this->__('Google the error message') .'</a>';
            $info[] = '- '. $this->__('Contact your hosting provider if problems persist.');
        } else if (!is_array($addressData['debugInfo']['parsedResponse'])) {
            // We have not received a valid JSON response

            $message = $this->__('The response from the Postcode.nl service could not be understood.');
            $info[] = '- '. $this->__('The service might be temporarily unavailable, if problems persist, please contact <a href=\'mailto:info@postcode.nl\'>info@postcode.nl</a>.');
            $info[] = '- '. $this->__('Technical reason: No valid JSON was returned by the request.');
        } else if (is_array($addressData['debugInfo']['parsedResponse']) && isset($addressData['debugInfo']['parsedResponse']['exceptionId'])) {
            // We have an exception message from the service itself

            if ($addressData['debugInfo']['responseCode'] == 401) {
                if ($addressData['debugInfo']['parsedResponse']['exceptionId'] == 'PostcodeNl_Controller_Plugin_HttpBasicAuthentication_NotAuthorizedException')
                    $message = $this->__('`API Key` specified is incorrect.');
                else if ($addressData['debugInfo']['parsedResponse']['exceptionId'] == 'PostcodeNl_Controller_Plugin_HttpBasicAuthentication_PasswordNotCorrectException')
                    $message = $this->__('`API Secret` specified is incorrect.');
                else
                    $message = $this->__('Authentication is incorrect.');
            } else if ($addressData['debugInfo']['responseCode'] == 403) {
                $message = $this->__('Access is denied.');
            } else {
                $message = $this->__('Service reported an error.');
            }

            $info[] = $this->__('Postcode.nl service message:') .' "'. $addressData['debugInfo']['parsedResponse']['exception'] .'"';
        } else if (is_array($addressData['debugInfo']['parsedResponse']) && !isset($addressData['debugInfo']['parsedResponse']['postcode'])) {
            // This message is thrown when the JSON returned did not contain the data expected.

            $message = $this->__('The response from the Postcode.nl service could not be understood.');
            $info[] = '- '. $this->__('The service might be temporarily unavailable, if problems persist, please contact <a href=\'mailto:info@postcode.nl\'>info@postcode.nl</a>.');
            $info[] = '- '. $this->__('Technical reason: Received JSON data did not contain expected data.');
        } else {
            $message = $this->__('A test connection to the API was successfully completed.');
            $status = 'success';
        }

        return array(
            'message' => $message,
            'status' => $status,
            'info' => $info,
        );
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

    /**
     * @return int
     */
    protected function _curlHasSsl()
    {
        $curlVersion = curl_version();
        return $curlVersion['features'] & CURL_VERSION_SSL;
    }

    /**
     * @return array|bool
     */
    protected function _checkApiReady()
    {
        if (!$this->_debuggingOverride && !($this->_getStoreConfig('mage42_postcodenl/config/enabled') || $this->_getStoreConfig('mage42_postcodenl/advanced_config/admin_validation_enabled')))
            return array('message' => $this->__('Postcode.nl API not enabled.'));

        if ($this->_getServiceUrl() === '' || $this->_getKey() === '' || $this->_getSecret() === '')
            return array('message' => $this->__('Postcode.nl API not configured.'), 'info' => array($this->__('Configure your `API key` and `API secret`.')));

        return $this->_checkCapabilities();
    }

    /**
     * @return array|bool
     */
    protected function _checkCapabilities()
    {
        // Check for SSL support in CURL
        if (!$this->_curlHasSsl())
            return array('message' => $this->__('Cannot connect to Postcode.nl API: Server is missing SSL (https) support for CURL.'));

        return false;
    }

    /**
     * @param $url
     * @return mixed
     */
    protected function _callApiUrlGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::API_TIMEOUT);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->_getKey() .':'. $this->_getSecret());
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_getUserAgent());

        $this->_httpResponseRaw = curl_exec($ch);
        $this->_httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->_httpResponseCodeClass = (int)floor($this->_httpResponseCode / 100) * 100;
        $this->_httpClientError = curl_errno($ch) ? sprintf('cURL error %s: %s', curl_errno($ch), curl_error($ch)) : null;

        curl_close($ch);

        return json_decode($this->_httpResponseRaw, true);
    }

    /**
     * @return string
     */
    protected function _getExtensionVersion()
    {
        $extensionInfo = $this->_getModuleInfo('mage42_postcodenl');
        return $extensionInfo ? (string)$extensionInfo['version'] : 'unknown';
    }

    /**
     * @return string
     */
    protected function _getUserAgent()
    {
        return 'mage42_postcodenl_MagentoPlugin/' . $this->_getExtensionVersion() .' '. $this->_getMagentoVersion() .' PHP/'. phpversion() .' EnrichType/'. $this->_enrichType;
    }

    /**
     * @return array|null
     */
    protected function _getMagentoModules()
    {
        if ($this->_modules !== null)
            return $this->_modules;

        $this->_modules = array();
        foreach (Mage::getConfig()->getNode('modules')->children() as $name => $module) {
            $this->_modules[$name] = array();
            foreach ($module as $key => $value) {
                if (in_array((string)$key, array('active')))
                    $this->_modules[$name][$key] = (string)$value == 'true' ? true : false;
                else if (in_array((string)$key, array('codePool', 'version')))
                    $this->_modules[$name][$key] = (string)$value;
            }
        }

        return $this->_modules;
    }

    /**
     * @return array
     */
    protected function _errorResponse()
    {
        return array(
            'message' => $this->__('Validation error, please use manual input.'),
            'messageTarget' => 'housenumber',
            'useManual' => true,
        );
    }
}
