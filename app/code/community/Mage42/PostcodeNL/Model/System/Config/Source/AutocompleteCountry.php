<?php

class Mage42_PostcodeNL_Model_System_Config_Source_AutocompleteCountry extends Mage_Core_Model_Config_Data
{
    public function toOptionArray($isMultiselect = false)
    {
        $response = Mage::helper('mage42_postcodenl')->_internationalGetSupportedCountries();

        if (isset($response['mage42_postcodenl_message']))
        {
            return [
                ['value' => 'error', 'label' => $response['mage42_postcodenl_message']]
            ];
        }
        
        // Unset unexpected cache-control array property, to be able to loop through the countries
        unset($response['cache-control']);
        $result = array();
        foreach ($response as $country)
        {
            $result[] = array(
                'value' => $country['iso2'] . '-' . $country['iso3'],
                'label' => $country['name']
            );
        }

        return $result;
    }
}
