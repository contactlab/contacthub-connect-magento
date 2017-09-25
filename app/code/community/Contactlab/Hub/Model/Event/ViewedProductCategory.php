<?php
class Contactlab_Hub_Model_Event_ViewedProductCategory extends Contactlab_Hub_Model_Event
{
    protected function _assignData()
    {
        if (!$this->_getSid()) {
            return;
        }

        $category = Mage::registry('current_category');
        $eventData = array(
            'category' => $this->_helper()->clearStrings($category->getName())
        );

        $this->setName('viewedProductCategory')
            ->setModel('ViewedProductCategory')
            ->setEventData(json_encode($eventData));

        return parent::_assignData();
    }
    
    protected function _composeHubEvent()
    {
        if (!$this->_eventForHub) {
            $this->_eventForHub = new stdClass();
        }
        $this->_eventForHub->properties = json_decode($this->getEventData());
        $event =  parent::_composeHubEvent();
        return $event;
    }
}
