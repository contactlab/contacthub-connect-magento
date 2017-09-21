<?php
class Contactlab_Hub_Model_Event_ViewedProduct extends Contactlab_Hub_Model_Event
{
    protected function _assignData()
    {
        if (!$this->_getSid()) {
            return;
        }
        $product = Mage::registry('current_product');
        $eventData = array('product_id' => $product->getId());
        $this->setName('viewedProduct')
            ->setModel('ViewedProduct')
            ->setEventData(json_encode($eventData));
        

        return parent::_assignData();
    }
    
    protected function _composeHubEvent()
    {
        if (!$this->_eventForHub) {
            $this->_eventForHub = new stdClass();
        }
        $eventData = json_decode($this->getEventData());
        $this->_eventForHub->properties = $this->_getObjProduct($eventData->product_id);
        return parent::_composeHubEvent();
    }
}
