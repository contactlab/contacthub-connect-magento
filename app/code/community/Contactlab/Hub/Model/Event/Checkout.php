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
		$exchangeRate = (float)$order->getStoreToOrderRate();
		$this->_helper()->log($exchangeRate);
		if($exchangeRate == 1)
		{
			$exchangeRate = $this->_helper()->getExchangeRate($order->getStoreId());	
		}
		$this->_helper()->log($exchangeRate);
		$amount->total = $this->_helper()->convertToBaseRate($order->getGrandTotal(), $exchangeRate);
		$amount->revenue = $this->_helper()->convertToBaseRate(($order->getGrandTotal() - $order->getShippingAmount() - $order->getShippingTaxAmount()), $exchangeRate);
		$amount->shipping = $this->_helper()->convertToBaseRate(($order->getShippingAmount() + $order->getShippingTaxAmount()), $exchangeRate);
		$amount->tax = $this->_helper()->convertToBaseRate($order->getTaxAmount(), $exchangeRate);
		$amount->discount = $this->_helper()->convertToBaseRate($order->getDiscountAmount(), $exchangeRate);
		$local = new stdClass();
		$local->currency = $order->getOrderCurrencyCode();				
		$local->exchangeRate = $exchangeRate;
		$amount->local = $local;
		$properties->amount = $amount;
		$arrayProducts = array();
		foreach($order->getAllItems() as $item)
		{
			if(!$item->getParentItemId())
			{
				$objProduct = $this->_getObjProduct($item->getProductId());			
				$objProduct->type = 'sale';
				$objProduct->price = $this->_helper()->convertToBaseRate($item->getPrice(), $exchangeRate);
				$objProduct->subtotal = $this->_helper()->convertToBaseRate($item->getRowTotal(), $exchangeRate);
				$objProduct->quantity = (int)$item->getQtyOrdered();
				$objProduct->discount = $this->_helper()->convertToBaseRate($item->getDiscountAmount(), $exchangeRate);
				$objProduct->tax = $this->_helper()->convertToBaseRate($item->getTaxAmount(), $exchangeRate);
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
	
	protected function _helper()
	{
		return Mage::helper('contactlab_hub');
	}
}