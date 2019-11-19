<?php

class Mage42_PostcodeNL_Model_System_Config_Source_AutocompleteCountry extends Mage_Core_Model_Config_Data
{
    protected $_options;

    public function toOptionArray($isMultiselect=false)
    {
        $result = array();
        if (!$this->_options) {
            $jsonData = Mage::helper('mage42_postcodenl')->_internationalGetSupportedCountries();
            if (sizeof($jsonData) > 0)
                $this->_options['countries'] = $jsonData;
        }
        for ($i = 0; $i < sizeof($this->_options['countries']); $i++) {
            array_push($result, array('value' => $this->_options['countries'][$i]['iso2'] . '-' . $this->_options['countries'][$i]['iso3'], 'label' => $this->_options['countries'][$i]['name']));
        }
        return $result;
    }
}