<?php
class Contactlab_Hub_Model_Event_Searched extends Contactlab_Hub_Model_Event
{
    protected function _assignData()
    {
        if (!$this->_getSid()) {
            return;
        }
        $properties = new stdClass();
        $properties->keyword = $this->_helper()->clearStrings(Mage::app()->getRequest()->getParam('q'));

        $currentLayer = Mage::registry('current_layer');
        $properties->resultCount = ($currentLayer instanceof Varien_Object) ? count($currentLayer->getProductCollection()->getAllIds()) : 0;

        $this->setName('searched')
            ->setModel('Searched')
            ->setEventData(json_encode($properties));

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
