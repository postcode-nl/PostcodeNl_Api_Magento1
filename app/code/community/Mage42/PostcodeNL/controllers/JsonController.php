<?php

/**
 * Class Mage42_PostcodeNL_JsonController
 * (c) www.3webapps.com 2019
 */
class Mage42_PostcodeNL_JsonController extends Mage_Core_Controller_Front_Action
{
    /**
     * @throws Mage42_PostcodeNL_Helper_Exception_AuthenticationException
     * @throws Mage42_PostcodeNL_Helper_Exception_BadRequestException
     * @throws Mage42_PostcodeNL_Helper_Exception_CurlException
     * @throws Mage42_PostcodeNL_Helper_Exception_ForbiddenException
     * @throws Mage42_PostcodeNL_Helper_Exception_InvalidJsonResponseException
     * @throws Mage42_PostcodeNL_Helper_Exception_ServerUnavailableException
     * @throws Mage42_PostcodeNL_Helper_Exception_TooManyRequestsException
     * @throws Mage42_PostcodeNL_Helper_Exception_UnexpectedException
     */
    public function autocompleteAction() {
        /** @var Mage42_PostcodeNL_Helper_Data $helper */
        $helper = Mage::helper('mage42_postcodenl');
        $this->getResponse()->setHeader('Expires', "", true);
        $this->getResponse()->setHeader('Pragma', "", true);
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $context = key($this->getRequest()->getParams());
        $term = $this->getRequest()->getParam($context);
        $response = $helper->_internationalAutocomplete(
            $context,
            $term
        );
        $this->_cacheControl($this, $response);
    }

    /**
     * @throws Mage42_PostcodeNL_Helper_Exception_AuthenticationException
     * @throws Mage42_PostcodeNL_Helper_Exception_BadRequestException
     * @throws Mage42_PostcodeNL_Helper_Exception_CurlException
     * @throws Mage42_PostcodeNL_Helper_Exception_ForbiddenException
     * @throws Mage42_PostcodeNL_Helper_Exception_InvalidJsonResponseException
     * @throws Mage42_PostcodeNL_Helper_Exception_ServerUnavailableException
     * @throws Mage42_PostcodeNL_Helper_Exception_TooManyRequestsException
     * @throws Mage42_PostcodeNL_Helper_Exception_UnexpectedException
     */
    public function addressdetailsAction() {
        /** @var Mage42_PostcodeNL_Helper_Data $helper */
        $helper = Mage::helper('mage42_postcodenl');

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $context = key($this->getRequest()->getParams());
        $response = $helper->_internationalGetDetails(
            $context
        );
        $this->_cacheControl($this, $response);
    }

    /**
     * @param $action
     * @param $response
     */
    protected function _cacheControl($action, $response) {
        if (isset($response['Cache-Control'])) {
            $cacheControl = $response['Cache-Control'];
            unset($response['Cache-Control']);
            unset($response['Expires']);
            $action->getResponse()->clearHeader('Cache-Control')->clearHeader('Expires')->clearHeader('Pragma')->setHeader('Cache-Control', $cacheControl)->setBody(json_encode($response));
        } else {
            $action->getResponse()->setBody(json_encode($response));
        }
    }
}