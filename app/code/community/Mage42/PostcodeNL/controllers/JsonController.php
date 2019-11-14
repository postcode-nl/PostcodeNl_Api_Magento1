<?php
class Mage42_PostcodeNL_JsonController extends Mage_Core_Controller_Front_Action
{
    public function lookupAction() {
        /** @var Mage42_PostcodeNL_Helper_Data $helper */
        $helper = Mage::helper('mage42_postcodenl');

        if ($this->getRequest()->getParam('et')) {
            $helper->setEnrichType($this->getRequest()->getParam('et'));
        }

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(
            json_encode(
                $helper->lookupAddress(
                    $this->getRequest()->getParam('countryId'),
                    $this->getRequest()->getParam('postcode'),
                    $this->getRequest()->getParam('houseNumber'),
                    $this->getRequest()->getParam('houseNumberAddition')
                )
            )
        );
    }

    public function autocompletepostalAction() {
        /** @var Mage42_PostcodeNL_Helper_Data $helper */
        $helper = Mage::helper('mage42_postcodenl');

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(
            json_encode(
                $helper->autocompletePostal(
                    $this->getRequest()->getParam('countryId'),
                    $this->getRequest()->getParam('postcode')
                )
            )
        );
    }

    public function autocompletestreetAction() {
        /** @var Mage42_PostcodeNL_Helper_Data $helper */
        $helper = Mage::helper('mage42_postcodenl');

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(
            json_encode(
                $helper->autocompleteStreet(
                    $this->getRequest()->getParam('countryId'),
                    $this->getRequest()->getParam('cityId'),
                    $this->getRequest()->getParam('postcode'),
                    $this->getRequest()->getParam('streetName')
                )
            )
        );
    }

    public function autocompletehousenumberAction() {
        /** @var Mage42_PostcodeNL_Helper_Data $helper */
        $helper = Mage::helper('mage42_postcodenl');

        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(
            json_encode(
                $helper->autocompleteHouseNumber(
                    $this->getRequest()->getParam('countryId'),
                    $this->getRequest()->getParam('cityId'),
                    $this->getRequest()->getParam('streetId'),
                    $this->getRequest()->getParam('postcode'),
                    $this->getRequest()->getParam('validation'),
                    $this->getRequest()->getParam('houseNumber'),
                    (strtolower($this->getRequest()->getParam('countryId')) === "be") ? $this->getRequest()->getParam('language') : ''
                )
            )
        );
    }
}