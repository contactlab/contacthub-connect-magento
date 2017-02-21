<?php
class Contactlab_Hub_Model_Event_RemoveToCompare extends Contactlab_Hub_Model_Event_AddToCompare
{
	protected function _assignData()
	{				
		if(!$this->_getSid())
		{
			return;
		}	
		$product = $this->getEvent()->getProduct();				
		$eventData = array(
						'product_id' => $product->getId()
					);
		$this->setName('removedCompare')
			->setModel('removeToCompare')
			->setEventData(json_encode($eventData));
		
		return Contactlab_Hub_Model_Event::_assignData();
	}
}