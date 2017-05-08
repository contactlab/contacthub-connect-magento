<?php
class Contactlab_Hub_Model_Event_AbandonedCart extends Contactlab_Hub_Model_Event
{
	protected function _assignData()
	{					
		$quote = $this->getEvent()->getQuote();		
		
		$eventData = array(
						'quote_id' => $quote->getQuoteId(),						
					);
		$this->setName('abandonedCart')
			->setModel('abandonedCart')
			->setStoreId($quote->getStoreId())
			->setIdentityEmail($quote->getEmail())
			->setEnvRemoteIp($quote->getRemoteIp())
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
		
		$store = Mage::getSingleton('core/store')->load($this->getStoreId());
		$quote = Mage::getModel('sales/quote')->setStore($store)->load($eventData->quote_id);
		
		$properties = new stdClass();
		$properties->orderId = strval($quote->getEntityId());		
		$properties->storeCode = "".$quote->getStoreId();		
		//$properties->abandonedCartUrl = Mage::getUrl('', array('_store' => $quote->getStoreId())).'checkout/cart/';
		$amount = new stdClass();
		$amount->total = (float)$quote->getGrandTotal();
		//$amount->revenue = (float)($quote->getGrandTotal() - $quote->getShippingAmount() - $quote->getShippingTaxAmount());
		//$amount->shipping = (float)($quote->getShippingAmount() + $quote->getShippingTaxAmount());
		$amount->tax = (float)$quote->getTaxAmount();
		//$amount->discount = (float)$quote->getDiscountAmount();
		$local = new stdClass();
		$local->currency = $quote->getQuoteCurrencyCode();
		$local->exchangeRate = (float)$quote->getStoreToQuoteRate();
		$amount->local = $local;
		$properties->amount = $amount;
		$arrayProducts = array();
		foreach($quote->getAllItems() as $item)
		{
			if(!$item->getParentItemId())
			{
				$objProduct = $this->_getObjProduct($item->getProductId());			
				$objProduct->type = 'sale';
				$objProduct->price = (float)$item->getPrice();
				$objProduct->subtotal = (float)$item->getRowTotal();
				$objProduct->quantity = (int)$item->getQty();
				$objProduct->discount = (float)$item->getDiscountAmount();
				$objProduct->tax = (float)$item->getTaxAmount();
				if($quote->getCouponCode())
				{
					$objProduct->coupon = $quote->getCouponCode();
				}
				$arrayProducts[] = $objProduct;
			}
		}		
		$properties->products = $arrayProducts;
		$this->_eventForHub->properties = $properties;
		return parent::_composeHubEvent();
	}
}