<?php

/**
 * Class Mage42_PostcodeNL_Block_Jsinit
 */
class Mage42_PostcodeNL_Block_Jsinit extends Mage_Core_Block_Template
{
    /**
     * @return string
     * @throws Mage_Core_Exception
     */
    protected function _toHtml()
    {
        if (is_link(dirname(Mage::getModuleDir('', 'Mage42_PostcodeNL'))) && !Mage::getStoreConfig('dev/template/allow_symlink'))
            throw new Mage_Core_Exception('Postcode.nl API Development: Symlinks not enabled! (Enable them at Admin -> System -> Configuration -> Advanced -> Developer -> Template Settings)');
        return parent::_toHtml();
    }
}