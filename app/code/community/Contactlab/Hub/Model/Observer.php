<?php
class Contactlab_Hub_Model_Observer
{
    protected $_helper = null;

    private function _helper()
    {
        if ($this->_helper == null) {
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
    
    public function handleConfigChanges($observer)
    {
        $this->_setEnabledFrom($observer);
    }
    
    protected function _setEnabledFrom($observer)
    {
        $allStores = Mage::app()->getStores();
        foreach ($allStores as $storeId => $val) {
            if (!$this->_getConfig('cron_previous_customers/previous_date')) {
                $this->_helper()->setConfigData('contactlab_hub/cron_previous_customers/previous_date', date('Y-m-d H:i:s'), 'stores', $storeId);
            }
        }
    }
    
    protected function _getEvent()
    {
        return Mage::getModel('contactlab_hub/event');
    }

    public function placeHubTracking(Varien_Event_Observer $observer)
    {
        if (!$this->_helper()->isJsTrackingEnabled()) {
            return;
        }
        $controller = $observer->getAction();
        $routeName = $controller->getRequest()->getRouteName();
        $controllerName = $controller->getRequest()->getControllerName();
        $actionName = $controller->getRequest()->getActionName();
        $controllerRoute = $routeName.'_'.$controllerName.'_'.$actionName;
        //echo '<pre>'.$controllerRoute.'</pre>';
        
        $hubJs = "\n<!-- ContactHubJs -->\n<script>\nwindow.ch=function(){(ch.q=ch.q||[]).push(arguments)}; ".$this->_helper()->getJsConfigData();
        
        switch ($controllerRoute) {
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

        $hubJs.="\n</script>\n<script async src='https://assets.contactlab.it/contacthub/sdk-browser/latest/contacthub.min.js'></script>\n<!-- END ContactHubJs -->";
        
        if ($hubJs) {
            $layout = $controller->getLayout();
            $block = $layout->createBlock('core/text');
            $block->setText($hubJs);
            if ($layout->getBlock('before_body_end')) {
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
    
    public function traceProductView($observer)
    {
        if ($this->_helper()->isJsTrackingEnabled()) {
            return;
        }
        $event = Mage::getModel('contactlab_hub/event_viewedProduct');
        $event->setEvent($observer->getEvent());
        $event->trace();
        return $observer;
    }

    public function traceProductCategoryView($observer)
    {
        if ($this->_helper()->isJsTrackingEnabled()) {
            return;
        }
        $event = Mage::getModel('contactlab_hub/event_viewedProductCategory');
        $event->setEvent($observer->getEvent());
        $event->trace();
        return $observer;
    }

    public function traceSearch(Varien_Event_Observer $observer)
    {
        if ($this->_helper()->isJsTrackingEnabled()) {
            return;
        }
        $event = Mage::getModel('contactlab_hub/event_searched');
        $event->setEvent($observer->getEvent());
        $event->trace();
        return $observer;
    }

    public function traceCustomerLogin($observer)
    {
        $cookie = json_decode(Mage::getSingleton('core/cookie')->get('_ch'), true);
        if ($cookie && $cookie['customerId']) {
            $this->_helper()->deleteTrackingCookie();
        }
        
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

        // Customer logged out, create a new session id
        if (!$this->_helper()->isJsTrackingEnabled()) {
            $this->_helper()->deleteTrackingCookie();
        }
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
        if ($subscriber->getSubscriberStatus() == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
            if (!$subscriber->getCreatedAt()) {
                $subscriber->setCreatedAt(date('Y-m-d H:i:s'));
            }
            $subscriber->setLastSubscribedAt(date('Y-m-d H:i:s'));
        } elseif ($subscriber->getSubscriberStatus() == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
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
    
	public function traceOrderEvents($observer)
	{	
		$order = $observer->getEvent()->getOrder();	
		if (!$order->getId()) {
			//order not saved in the database
			return $this;
		}			
		if (
				(in_array($order->getStatus(), $this->_helper()->getOrderStatusToBeSent($order->getStoreId())))
				&& (!$order->getContactlabHubExported())
				)
		{
			$event = Mage::getModel('contactlab_hub/event_checkout');
			$event->setEvent($observer->getEvent());
			$event->trace();
			$order->setContactlabHubExported(1);
			$order->save();
		}		
		$OldStatus = $order->getOrigData('status');
		$NewStatus = $order->getStatus();
		if (
				($NewStatus == Mage_Sales_Model_Order::STATE_CANCELED)
				&& ($OldStatus != $NewStatus)
				)
		{
			$event = Mage::getModel('contactlab_hub/event_cancelOrder');
			$event->setEvent($observer->getEvent());
			$event->trace();
		}		
		return $observer;
	}
}
