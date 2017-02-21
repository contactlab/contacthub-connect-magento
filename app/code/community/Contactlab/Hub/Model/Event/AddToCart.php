<?php
class Contactlab_Hub_Model_Event_AddToCart extends Contactlab_Hub_Model_Event
{
	protected function _assignData()
	{		
		if(!$this->_getSid())
		{
			return;
		}
		$item = $this->getEvent()->getQuoteItem();
		$product = $this->getEvent()->getProduct();		
		if($product)
		{
			$eventData = array(
							'product_id' => $product->getId(),
							'qty' => $item->getQty()
						);
			$this->setName('addedProduct')
				->setModel('addToCart')
				->setEventData(json_encode($eventData));
		}
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
		$this->_eventForHub->properties->quantity = $eventData->qty;
		return parent::_composeHubEvent();
	}
}