<?php

class Mage42_PostcodeNL_Model_System_Config_Source_AutocompleteCountry extends Mage_Core_Model_Config_Data
{
    protected $_options;

    public function toOptionArray($isMultiselect=false)
    {
        $result = array();
        if (!$this->_options) {
            $this->_options = Mage::helper('mage42_postcodenl')->lookupAccountCountries();
        }

        for ($i = 0; $i < sizeof($this->_options['countries']); $i++) {
            array_push($result, array('value' => $this->_options['countries'][$i], 'label' => $this->_options['countries'][$i]));
        }
        $options = $this->_options;
        return $result;
    }
}