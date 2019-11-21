<?php

class Mage42_PostcodeNL_Model_System_Config_Source_ApiUser extends Mage_Core_Model_Config_Data
{
    public function getCommentText(Mage_Core_Model_Config_Element $element, $currentValue)
    {
        $postcodenl = Mage::helper('mage42_postcodenl');
        $response =  $postcodenl->_accountInfo();
        if (isset($response['mage42_postcodenl_message']))
            return "<h4>".$response['mage42_postcodenl_message']."</h4>";

        if (isset($response['name'])) {
            return "<h4>".$response['name']."</h4>";
        } else {
            return "Could not find a user linked to these API keys";
        }
    }
}