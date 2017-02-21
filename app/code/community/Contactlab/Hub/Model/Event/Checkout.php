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
		
		$this->_eventForHub->properties->orderId = strval($order->getIncrementId());
		$this->_eventForHub->properties->type = 'sale';
		$this->_eventForHub->properties->storeCode = "".$order->getStoreId();
		/*"TODO paymentMethod -> cash","creditcard","debitcard","paypal","other" da definire in backoffice con un match 
		$this->_eventForHub->properties->paymentMethod = 'cash';			
		*/
		$this->_eventForHub->properties->amount->total = (float)$order->getGrandTotal();
		$this->_eventForHub->properties->amount->revenue = (float)($order->getGrandTotal() - $order->getShippingAmount() - $order->getShippingTaxAmount());
		$this->_eventForHub->properties->amount->shipping = (float)($order->getShippingAmount() + $order->getShippingTaxAmount());
		$this->_eventForHub->properties->amount->tax = (float)$order->getTaxAmount();
		$this->_eventForHub->properties->amount->discount = (float)$order->getDiscountAmount();
		$this->_eventForHub->properties->amount->local->currency = $order->getOrderCurrencyCode();
		$this->_eventForHub->properties->amount->local->exchangeRate = (float)$order->getStoreToOrderRate();
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
		$this->_eventForHub->properties->products = $arrayProducts;
		
		return parent::_composeHubEvent();
	}
}