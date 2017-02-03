<?php
class Contactlab_Hub_Model_Event_AbandonedCart extends Contactlab_Hub_Model_Event
{
	protected function _assignData()
	{				
		//$eventModel = 'order';		
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
		
		$this->_eventForHub->properties->orderId = strval($quote->getEntityId());		
		$this->_eventForHub->properties->storeCode = "".$quote->getStoreId();		
		//$this->_eventForHub->properties->abandonedCartUrl = Mage::getUrl('', array('_store' => $quote->getStoreId())).'checkout/cart/';		
		$this->_eventForHub->properties->amount->total = (float)$quote->getGrandTotal();
		//$this->_eventForHub->properties->amount->revenue = (float)($quote->getGrandTotal() - $quote->getShippingAmount() - $quote->getShippingTaxAmount());
		//$this->_eventForHub->properties->amount->shipping = (float)($quote->getShippingAmount() + $quote->getShippingTaxAmount());
		$this->_eventForHub->properties->amount->tax = (float)$quote->getTaxAmount();
		//$this->_eventForHub->properties->amount->discount = (float)$quote->getDiscountAmount();
		$this->_eventForHub->properties->amount->local->currency = $quote->getQuoteCurrencyCode();
		$this->_eventForHub->properties->amount->local->exchangeRate = (float)$quote->getStoreToQuoteRate();
		$arrayProducts = array();
		foreach($quote->getAllItems() as $item)
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
		$this->_eventForHub->properties->products = $arrayProducts;
		
		return parent::_composeHubEvent();
	}
}