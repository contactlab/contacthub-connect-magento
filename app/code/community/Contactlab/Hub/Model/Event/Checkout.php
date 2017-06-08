<?php
class Contactlab_Hub_Model_Event_Checkout extends Contactlab_Hub_Model_Event
{
	protected function _assignData()
	{				
		if(!$this->_getSid())
		{
			return;
		}		
		$order = $this->getEvent()->getOrder();		
		
		$eventData = array(
						'increment_id' => $order->getIncrementId(),						
					);
		$this->setName('completedOrder')
			->setModel('checkout')
			->setStoreId($order->getStoreId())
			->setIdentityEmail($order->getCustomerEmail())
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
		$order = Mage::getModel('sales/order')->loadByIncrementId($eventData->increment_id);
		
		$properties = new stdClass();
		$properties->orderId = strval($order->getIncrementId());
		$properties->type = 'sale';
		$properties->storeCode = "".$order->getStoreId();
		/*"TODO paymentMethod -> cash","creditcard","debitcard","paypal","other" da definire in backoffice con un match 
		$properties->paymentMethod = 'cash';			
		*/
		$amount = new stdClass();
		$amount->total = (float)$order->getGrandTotal();
		$amount->revenue = (float)($order->getGrandTotal() - $order->getShippingAmount() - $order->getShippingTaxAmount());
		$amount->shipping = (float)($order->getShippingAmount() + $order->getShippingTaxAmount());
		$amount->tax = (float)$order->getTaxAmount();
		$amount->discount = (float)$order->getDiscountAmount();
		$local = new stdClass();
		$local->currency = $order->getOrderCurrencyCode();
		$local->exchangeRate = (float)$order->getStoreToOrderRate();
		$amount->local = $local;
		$properties->amount = $amount;
		$arrayProducts = array();
		foreach($order->getAllItems() as $item)
		{
			if(!$item->getParentItemId())
			{
				$objProduct = $this->_getObjProduct($item->getProductId());			
				$objProduct->type = 'sale';
				$objProduct->price = (float)$item->getPrice();
				$objProduct->subtotal = (float)$item->getRowTotal();
				$objProduct->quantity = (int)$item->getQtyOrdered();
				$objProduct->discount = (float)$item->getDiscountAmount();
				$objProduct->tax = (float)$item->getTaxAmount();
				if($order->getCouponCode())
				{
					$objProduct->coupon = $order->getCouponCode();
				}
				$arrayProducts[] = $objProduct;	
			}
		}
		$properties->products = $arrayProducts;
		$this->_eventForHub->properties = $properties;
		return parent::_composeHubEvent();
	}
}