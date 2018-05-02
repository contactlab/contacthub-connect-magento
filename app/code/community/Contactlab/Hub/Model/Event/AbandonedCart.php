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

        $exchangeRate = (float)$quote->getStoreToQuoteRate();
        if($exchangeRate == 1)
        {
            $exchangeRate = $this->_helper()->getExchangeRate($quote->getStoreId());
        }

		$arrayProducts = array();
        $totTax = 0;
        $totDiscount = 0;
		foreach($quote->getAllItems() as $item)
		{
			if(!$item->getParentItemId())
            {
                $price = (float)$item->getPriceInclTax();
                $tax = (float)$item->getTaxAmount();
                $totTax+= $tax;
                $discount = abs((float)$item->getDiscountAmount());
                $totDiscount+= $discount;
                $qty = (int)$item->getQty();
                $subtotal = $item->getRowTotalInclTax() - $item->getDiscountAmount();

                $objProduct = $this->_getObjProduct($item->getProductId(), $quote->getStoreId());
                $objProduct->type = 'sale';
                $objProduct->price = $this->_helper()->convertToBaseRate($price, $exchangeRate);
                $objProduct->tax = $this->_helper()->convertToBaseRate($tax, $exchangeRate);
                $objProduct->discount = $this->_helper()->convertToBaseRate($discount, $exchangeRate);
                $objProduct->quantity = $qty;
                $objProduct->subtotal = $this->_helper()->convertToBaseRate($subtotal, $exchangeRate);

                if($quote->getCouponCode())
                {
                    $objProduct->coupon = $quote->getCouponCode();
                }
                $arrayProducts[] = $objProduct;
            }
		}		


        //$properties->abandonedCartUrl = Mage::getUrl('', array('_store' => $quote->getStoreId())).'checkout/cart/';
        $amount = new stdClass();
        $total = $quote->getGrandTotal();

        $amount->total = $this->_helper()->convertToBaseRate($total, $exchangeRate);
        $amount->tax = $this->_helper()->convertToBaseRate($totTax, $exchangeRate);
        $amount->discount = $this->_helper()->convertToBaseRate($totDiscount, $exchangeRate);

        $local = new stdClass();
        $local->currency = $quote->getQuoteCurrencyCode();
        $local->exchangeRate = $exchangeRate;
        $amount->local = $local;
        $properties->amount = $amount;

        $properties->products = $arrayProducts;

		$this->_eventForHub->properties = $properties;
		return parent::_composeHubEvent();
	}
}