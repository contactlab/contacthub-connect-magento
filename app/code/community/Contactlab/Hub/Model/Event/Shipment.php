<?php
class Contactlab_Hub_Model_Event_Shipment extends Contactlab_Hub_Model_Event
{
	protected function _assignData()
	{					
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
		
		$properties = new stdClass();
		$properties->orderId = strval($order->getIncrementId());		
		//properties->storeCode = "".$shipment->getStoreId();
		
		foreach($shipment->getAllTracks() as $track)
		{
			if($track->getTitle())
			{
				$properties->carrier = $track->getTitle();
			}
			if($track->getTrackNumber())
			{
				$properties->trackingCode = $track->getTrackNumber();
			}
			if($track->getWeight())
			{
				$properties->weight = $track->getWeight();
			}
		}
				
		//$properties->trackingUrl = '';
		
		if($shipment->getPackages())
		{
			$properties->packages = $shipment->getPackages();
		}
					
		$arrayProducts = array();
		foreach($shipment->getItemsCollection() as $item)
		{
		    $objProduct = $this->_getObjProduct($item->getProductId(), $order->getStoreId());			
			$objProduct->type = 'shipped';					
			$objProduct->quantity = (int)$item->getQty();
			$objProduct->weight = (float)$item->getWeight();			
			
			$arrayProducts[] = $objProduct;			
		}
		$extraProperties = new stdClass();
		$extraProperties->products = $arrayProducts;
		$properties->extraProperties = $extraProperties;
		$this->_eventForHub->properties = $properties;
		return parent::_composeHubEvent();
	}
}