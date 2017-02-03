<?php
class Contactlab_Hub_Model_Event_Shipment extends Contactlab_Hub_Model_Event
{
	protected function _assignData()
	{				
		//$eventModel = 'shipment';		
		$shipment = $this->getEvent()->getShipment();
		$order = $shipment->getOrder();	
		$eventData = array(
						'shipment_id' => $shipment->getEntityId(),						
					);
		$this->setName('orderShipped')
			->setModel('shipment')
			->setIdentityEmail($order->getCustomerEmail())
			->setStoreId($order->getStoreId())
			->setNeedUpdateIdentity(true)
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
		$shipment = Mage::getModel('sales/order_shipment')->load($eventData->shipment_id);
		$order = $shipment->getOrder();
		
		$this->_eventForHub->properties->orderId = strval($order->getIncrementId());		
		//$this->_eventForHub->properties->storeCode = "".$shipment->getStoreId();
		
		foreach($shipment->getAllTracks() as $track)
		{
			if($track->getTitle())
			{
				$this->_eventForHub->properties->carrier = $track->getTitle();
			}
			if($track->getTrackNumber())
			{
				$this->_eventForHub->properties->trackingCode = $track->getTrackNumber();
			}
			if($track->getWeight())
			{
				$this->_eventForHub->properties->weight = $track->getWeight();
			}
		}
				
		$this->_eventForHub->properties->trackingUrl = '';		
		if($shipment->getPackages())
		{
			$this->_eventForHub->properties->packages = $shipment->getPackages();
		}
					
		$arrayProducts = array();
		foreach($shipment->getItemsCollection() as $item)
		{
			$objProduct = $this->_getObjProduct($item->getProductId());			
			$objProduct->type = 'shipped';					
			$objProduct->quantity = (int)$item->getQty();
			$objProduct->weight = (float)$item->getWeight();			
			
			$arrayProducts[] = $objProduct;			
		}
		$this->_eventForHub->properties->extraProperties->products = $arrayProducts;
		
		return parent::_composeHubEvent();
	}
}