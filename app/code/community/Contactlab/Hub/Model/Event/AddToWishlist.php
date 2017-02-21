<?php
class Contactlab_Hub_Model_Event_AddToWishlist extends Contactlab_Hub_Model_Event
{
	protected function _assignData()
	{				
		if(!$this->_getSid())
		{
			return;
		}
		$product = $this->getEvent()->getItem()->getProduct();				
		$eventData = array(
						'product_id' => $product->getId()
					);
		$this->setName('addedWishlist')
			->setModel('addToWishlist')
			->setEventData(json_encode($eventData));
		
		return parent::_assignData();
	}
	
	protected function _composeHubEvent()
	{
		if(!$this->_eventForHub)
		{
			$this->_eventForHub = new stdClass();
		}
		$eventData = json_decode($this->getEventData());
		$this->_eventForHub->properties = $this->_getObjProduct($eventData->product_id);
		return parent::_composeHubEvent();
	}
}