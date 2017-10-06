<?php
class Contactlab_Hub_Model_Event_RemoveToCompare extends Contactlab_Hub_Model_Event_AddToCompare
{
    protected function _assignData()
    {
        if (!$this->_getSid()) {
            return;
        }

        // This is actually a Mage_Catalog_Model_Product_Compare_Item object
        $product = $this->getEvent()->getProduct();
        $eventData = array(
                        'product_id' => $product->getProductId()
                    );
        $this->setName('removedCompare')
            ->setModel('removeToCompare')
            ->setEventData(json_encode($eventData));
        
        return Contactlab_Hub_Model_Event::_assignData();
    }
}
