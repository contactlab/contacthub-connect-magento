<?php
class Contactlab_Hub_Model_Event_AddToCart extends Contactlab_Hub_Model_Event
{
    protected function _assignData()
    {
        if (!$this->_getSid()) {
            return;
        }
        $item = $this->getEvent()->getQuoteItem();
        $product = $this->getEvent()->getProduct();

        if ($product) {
            $eventData = $this->_toHubProduct($product);
            $eventData->quantity = $item->getQty();
            
            $this->setName('addedProduct')
                ->setModel('addToCart')
                ->setEventData(json_encode($eventData));
        }
        return parent::_assignData();
    }
    
    protected function _composeHubEvent()
    {
        if (!$this->_eventForHub) {
            $this->_eventForHub = new stdClass();
        }
        $this->_eventForHub->properties = json_decode($this->getEventData());
        return parent::_composeHubEvent();
    }
}
