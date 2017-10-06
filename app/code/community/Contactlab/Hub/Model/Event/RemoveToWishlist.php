<?php
class Contactlab_Hub_Model_Event_RemoveToWishlist extends Contactlab_Hub_Model_Event_AddToWishlist
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
		
		return Contactlab_Hub_Model_Event::_assignData();
	}
	
}