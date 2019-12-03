<?php
class Mage42_PostcodeNL_Model_Observer
{
   public function saveUsedPostcodenlApi($observer)
   {
       $event = $observer->getEvent();
       /** @var Mage_Sales_Model_Order $order */
       $order = $event->getOrder();
       $order->setCustomerNote("Used postcodenl API");
   }
}