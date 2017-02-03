<?php
class Contactlab_Hub_Model_Observer 
{
    protected $_helper = null;

    private function _helper() 
    {
        if ($this->_helper == null) 
        {
            $this->_helper = Mage::helper('contactlab_hub');
        }
        return $this->_helper;
    }
    
    private function _getConfig($key) 
    {
        return $this->_helper()->getConfigData($key);
    }

    private function _isEnabled() 
    {
        //return $this->_getConfig('events/enabled')?true:false;
    	return true;
    }
	
    public function setEnabledFrom($observer)
    {
    	if ($this->_isEnabled())
    	{
    		if(!$this->_getConfig('cron_previous_customers/previus_date'))
    		{
    			$this->_helper()->setConfigData('cron_previous_customers/previus_date', date('Y-m-d H:i:s'));
    		}
    	}
    }
    
    
	protected function _getEvent() 
	{
		return Mage::getModel('contactlab_hub/event');
	}
	
	
	public function placeHubTracking(Varien_Event_Observer $observer)
	{
		
		$controller = $observer->getAction();
		$routeName = $controller->getRequest()->getRouteName();
		$controllerName = $controller->getRequest()->getControllerName();
		$actionName = $controller->getRequest()->getActionName();
		$controllerRoute = $routeName.'_'.$controllerName.'_'.$actionName;
		//echo '<pre>'.$controllerRoute.'</pre>';
		
		$hubJs = "
				<!-- ContactHubJs -->
				<script>
					window.ch=function(){(ch.q=ch.q||[]).push(arguments)};
					ch('config', ".$this->_helper()->getJsConfigData().");
		";
		
		switch ($controllerRoute)
		{
	
			case 'catalog_category_view':
				$hubJs.= $this->_helper()->getCategoryPageTracking();
				break;
					
			case 'catalog_product_view':
				$hubJs.= $this->_helper()->getProductPageTracking();
				break;
					
			case 'catalogsearch_result_index':
				$hubJs.= $this->_helper()->getSearchTracking();
				break;
	
		}
	
		$hubJs.="
				</script>
				<script async src='".Mage::getBaseUrl()."js/contactlab/hub/contacthub.min.js'></script>
				<!-- END ContactHubJs -->
		";
	
		
		if($hubJs)
		{
			$layout = $controller->getLayout();
			$block = $layout->createBlock('core/text');
			$block->setText($hubJs);
			if($layout->getBlock('before_body_end'))
			{
				
				$layout->getBlock('before_body_end')->append($block);
			}
		}
	}
	
	public function checkSubscription(Varien_Event_Observer $observer)
	{
		/*
			var_dump('pagina iscrizione newsletter');
			die();
			*/
	}
	
	
    public function traceCustomerLogin($observer) 
    {    	       
		$event = Mage::getModel('contactlab_hub/event_login');
		$event->setEvent($observer->getEvent());
		$event->trace();		
		return $observer;
	}
	
	
	public function traceCustomerLogout($observer)
	{		
		$event = Mage::getModel('contactlab_hub/event_logout');
		$event->setEvent($observer->getEvent());
		$event->trace();
		return $observer;		
	}
	
	
	public function traceCustomerRegister($observer) 
	{
		/*
		$event = Mage::getModel('contactlab_hub/event_register');
		$event->setEvent($observer->getEvent());
		$event->trace();
		return $observer;
		*/
	}
	
	
	public function traceSubscriptionSave($observer) 
	{	
		
		$subscriber = $observer->getEvent()->getSubscriber();	
		if($subscriber->getSubscriberStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED)
		{
			if(!$subscriber->getCreatedAt())
			{
				$subscriber->setCreatedAt(date('Y-m-d H:i:s'));
			}
			$subscriber->setLastSubscribedAt(date('Y-m-d H:i:s'));
		}
		elseif($subscriber->getSubscriberStatus() == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED)
		{
			$subscriber->setLastSubscribedAt();
		}
		
		$event = Mage::getModel('contactlab_hub/event_subscription');
		$event->setEvent($observer->getEvent()->setSubscriber($subscriber));
		$event->trace();
		return $observer;
	}
	
	
	public function traceChangeStoreEvent($observer)
	{
		$event = Mage::getModel('contactlab_hub/event_changeStore');
		$event->setEvent($observer->getEvent());
		$event->trace();
		return $observer;
	}
		
	
	public function traceCartAddEvent($observer)
	{
		$event = Mage::getModel('contactlab_hub/event_addToCart');
		$event->setEvent($observer->getEvent());
		$event->trace();
		return $observer;
	}
	
	
	public function traceQuoteRemoveEvent($observer)
	{
		$event = Mage::getModel('contactlab_hub/event_removeToCart');
		$event->setEvent($observer->getEvent());
		$event->trace();
		return $observer;
	}
	
	
	public function traceCheckoutEvent($observer)
	{
		$event = Mage::getModel('contactlab_hub/event_checkout');
		$event->setEvent($observer->getEvent());
		$event->trace();
		return $observer;
	}
	
	
	public function traceCompareAddEvent($observer)
	{
		$event = Mage::getModel('contactlab_hub/event_addToCompare');
		$event->setEvent($observer->getEvent());
		$event->trace();
		return $observer;
	}
	
	
	public function traceCompareRemoveEvent($observer)
	{
		$event = Mage::getModel('contactlab_hub/event_removeToCompare');
		$event->setEvent($observer->getEvent());
		$event->trace();
		return $observer;
	}
	
	
	public function traceWishlistAddEvent($observer)
	{
		$event = Mage::getModel('contactlab_hub/event_addToWishlist');
		$event->setEvent($observer->getEvent());
		$event->trace();
		return $observer;
	}
	
	
	public function traceWishlistRemoveEvent($observer)
	{				
		$event = Mage::getModel('contactlab_hub/event_removeToWishlist');
		$event->setEvent($observer->getEvent());
		$event->trace();
		return $observer;
	}
	
	public function traceShipmentEvent($observer)
	{		
		$event = Mage::getModel('contactlab_hub/event_shipment');
		$event->setEvent($observer->getEvent());
		$event->trace();
		return $observer;
	}
	
}
?>