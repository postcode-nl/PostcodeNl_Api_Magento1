<?php
class Mage42_PostcodeNL_Model_Observer
{
    public function ordercomment($observer)
    {
        $_order = $observer->getEvent()->getOrder();
        $_order->addStatusToHistory($_order->getStatus(),'some messagge',false);
        $_order->save();
    }
}