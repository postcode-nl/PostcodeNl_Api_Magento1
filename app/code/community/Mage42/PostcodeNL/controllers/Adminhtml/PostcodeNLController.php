<?php
class Mage42_PostcodeNL_Adminhtml_PostcodeNLController extends Mage_Adminhtml_Controller_Action
{
    public function lookupAction()
    {
        /** @var Mage42_PostcodeNL_Helper_Data $helper */
        $helper = Mage::helper('mage42_postcodenl');
        if ($this->getRequest()->getParam('et'))
            $helper->setEnrichType($this->getRequest()->getParam('et'));
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(
            json_encode(
                $helper->lookupAddress(
                    $this->getRequest()->getParam('postcode'),
                    $this->getRequest()->getParam('houseNumber'),
                    $this->getRequest()->getParam('houseNumberAddition')
                )
            )
        );
    }
    protected function _isAllowed()
    {
        return true;
    }
}

