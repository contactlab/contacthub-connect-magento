<?php
class Contactlab_Hub_Model_Event_CancelOrder extends Contactlab_Hub_Model_Event_Checkout
{
	protected function _assignData()
	{
		$order = $this->getEvent()->getOrder();
		parent::_assignData();		
		$this->setModel('cancelOrder')
			->setStoreId($order->getStoreId());
		return $this;
	}
	
	protected function _composeHubEvent()
	{			
		$eventForHub = parent::_composeHubEvent();

		$eventForHub->properties->type = 'return';
		$arrayProducts = array();
		foreach ($eventForHub->properties->products as $objProduct)
		{
			$objProduct->type = 'return';		
		}				
		$this->_eventForHub = $eventForHub;
		return $this->_eventForHub;
	}
}