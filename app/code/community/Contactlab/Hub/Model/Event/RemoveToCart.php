<?php
class Contactlab_Hub_Model_Event_RemoveToCart extends Contactlab_Hub_Model_Event_AddToCart
{
	protected function _assignData()
	{	
		if(!$this->_getSid())
		{
			return;
		}
		$item = $this->getEvent()->getQuoteItem();
		$product = $item->getProduct();				
		$eventData = array(
						'product_id' => $product->getId(),
						'qty' => $item->getQty()
					);
		$this->setName('removedProduct')
			->setModel('removeToCart')
			->setEventData(json_encode($eventData));		
		return Contactlab_Hub_Model_Event::_assignData();
	}
	
}