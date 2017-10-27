<?php
class Contactlab_Hub_Model_Event_RemoveToWishlist extends Contactlab_Hub_Model_Event
{
	protected function _assignData()
	{						
		if(!$this->_getSid())
		{
			return;
		}
		$item = $this->getEvent()->getData('data_object');
    	$product = $item->getProduct();				
		$this->setName('removedWishlist')
			->setModel('removeToWishlist')
			->setEventData(json_encode($this->_toHubProduct($product)));
		
		return parent::_assignData();
    }

    protected function _composeHubEvent()
    {
        if (!$this->_eventForHub) {
            $this->_eventForHub = new stdClass();
        }
        $eventData = json_decode($this->getEventData());
        $this->_eventForHub->properties = json_decode($this->getEventData());
        return parent::_composeHubEvent();
    }
}