<?php
class Contactlab_Hub_Model_Event_RemoveToWishlist extends Contactlab_Hub_Model_Event_AddToWishlist
{
	protected function _assignData()
	{						
		//$eventModel = 'product';	
		$item = $this->getEvent()->getData('data_object');
    	$product = $item->getProduct();				
		$eventData = array(
						'product_id' => $product->getId()
					);
		$this->setName('removedWishlist')
			->setModel('removeToWishlist')
			->setEventData(json_encode($eventData));
		
		return Contactlab_Hub_Model_Event::_assignData();
	}
	
}