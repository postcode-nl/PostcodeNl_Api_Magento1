<?php

class Mage42_PostcodeNL_Model_System_Config_Source_ApiSecretComment extends Mage_Core_Model_Config_Data
{
    public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
    {
        $postcodenl = Mage::helper('mage42_postcodenl');
        $response = $postcodenl->_accountInfo();
        if (isset($response['hasAccess'])) {
            return $response['hasAccess'] == 1 ? "<a href='https://account.postcode.nl' target='_blank'>Manage your Postcode.nl account</a>"
                : "<a href='https://account.postcode.nl' target='_blank'>Register a Postcode.nl account</a>";
        } else {
            return "<a href='https://account.postcode.nl' target='_blank'>Register a Postcode.nl account</a>";
        }
    }
}