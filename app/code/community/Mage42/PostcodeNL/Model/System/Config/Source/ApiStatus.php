<?php

class Mage42_PostcodeNL_Model_System_Config_Source_ApiStatus extends Mage_Core_Model_Config_Data
{
    public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
    {
        $postcodenl = Mage::helper('mage42_postcodenl');
        $response = $postcodenl->_accountInfo();
        if (isset($response['mage42_postcodenl_message']))
            return "<h4 style='color: red'>".$response['mage42_postcodenl_message']."</h4>";
        if (isset($response['hasAccess'])) {
            return $response['hasAccess'] == 1 ? "<h4 style='color: green'>valid</h4>" : "<h4 style='color: red'>invalid</h4>";
        } else {
            return "<h4 style='color: red'>invalid</h4>";
        }
    }
}