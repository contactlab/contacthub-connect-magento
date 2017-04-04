<?php
class Contactlab_Hub_Model_Exporter_AbandonedCarts extends Contactlab_Hubcommons_Model_Exporter_Abstract
{
	protected $_helper;	
	protected $_connectionWrite;
	protected $_connectionRead;	
	protected $_delimiter;
	
	protected $_abandonedCartTable;
	protected $_subscriberTable;
	
	protected function _construct()
	{
		$this->_abandonedCartTable = Mage::getSingleton('core/resource')->getTableName('contactlab_hub/abandoned_carts');
		$this->_subscriberTable = Mage::getSingleton('core/resource')->getTableName('newsletter/subscriber');
	}
	
	private function _helper() {
		if ($this->_helper == null) {
			$this->_helper = Mage::helper('contactlab_hub');
		}
		return $this->_helper;
	}
	
	private function _getConfig($key) {
		return $this->_helper()->getConfigData($key, $this->getStoreId());
	}
	
	protected function _getWriteConnection()
	{
		if(!$this->_connectionWrite)
		{
			$this->_connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
		}
		return $this->_connectionWrite;
	}
	
	protected function _getReadConnection()
	{
		if(!$this->_connectionRead)
		{
			$this->_connectionRead = Mage::getSingleton('core/resource')->getConnection('core_read');
		}
		return $this->_connectionRead;
	}
	
	/** Write xml object. */
	protected function writeXml()
	{
		return $this;
	}
	
	/**
	 * Is enabled.
	 * @return bool
	 */
	protected function isEnabled()
	{		
		return $this->_getConfig('events/abandonedCart') ? true : false;
	}
	
	protected function getFileName() {
		return $this;
	}
	
	
	public function export(Contactlab_Hubcommons_Model_Task_Interface $task)
	//public function export()
	{
		$allStores = Mage::app()->getStores();
		foreach ($allStores as $storeId => $val)
		{
			$this->setStoreId($storeId);			
			if (!$this->isEnabled())
			{
				Mage::helper("contactlab_hubcommons")->logWarn("Module export is disabled");
				return "Module export is disabled";
			}
			$this->_collectAbandonedCarts();
			$this->_createEventsFromAbandonedCarts();
			
		}		
		return "Export done";
	}
	
	
	protected function _collectAbandonedCarts()
	{	
			
		$minMinutes = (int)$this->_helper()->getConfigData('events/min_minutes_from_last_update');		
		$minMinutesFromLastUpdate = new Zend_Date();
		$minMinutesFromLastUpdate->subMinute($minMinutes);
		
		$maxMinutes = (int)$this->_helper()->getConfigData('events/max_minutes_from_last_update');
		$maxMinutesFromLastUpdate = new Zend_Date();
		$maxMinutesFromLastUpdate->subMinute($maxMinutes);
		
		
		$collection = Mage::getModel('sales/quote')->getCollection();
		$collection->addFieldToSelect(array('store_id','customer_email','created_at','updated_at','remote_ip'));					
		$collection->addFieldToFilter('main_table.reserved_order_id', array('null' => true))
					->addFieldToFilter('main_table.customer_email', array('notnull' => true))
					->addFieldToFilter('main_table.items_count', array('gt' => 0))
					->addFieldToFilter('main_table.store_id', array('eq' => $this->getStoreId()));
		if($minMinutes)
		{
			//$collection->addFieldToFilter('main_table.updated_at', array('gt' => $minMinutesFromLastUpdate->get('YYYY-MM-dd HH:mm:ss')));			
			$collection->getSelect()->where("(main_table.updated_at + INTERVAL ".$minMinutes." MINUTE) < ? ", date('Y-m-d H:i:s'));
		}
		
		if($maxMinutes)
		{
			$collection->addFieldToFilter('main_table.updated_at', array('gt' => $maxMinutesFromLastUpdate->get('YYYY-MM-dd HH:mm:ss')));
		}
		
		$subscribers = !$this->_helper()->getConfigData('events/send_to_not_subscribed');
		if($subscribers)
		{			
			$collection->getSelect()->join( array('subscribers'=> $this->_subscriberTable), 'subscribers.subscriber_email = main_table.customer_email', array());
			$collection->addFieldToFilter('subscribers.subscriber_status', array('eq' => Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED));
		}
		/*
		$this->_helper()->log('ABANDONED CART');
		$this->_helper()->log($collection->getSelect());
		$this->_helper()->log('FINE ABANDONED CART');
		*/
		
		//echo $collection->getSelect()."\n\n";
		
		
		foreach ($collection as $cart)
		{	
			$query = "
						SELECT * from ".$this->_abandonedCartTable." 
						WHERE quote_id = ".$cart->getEntityId()."
						ORDER BY updated_at DESC
						LIMIT 0,1											
			";
			$oldAbandonedCart = $this->_getReadConnection()->fetchRow($query);					
			if(strtotime($cart->getUpdatedAt()) > strtotime($oldAbandonedCart['updated_at']))
			{
				$query = "
						INSERT INTO ".$this->_abandonedCartTable." (quote_id, store_id, email, created_at, updated_at, abandoned_at, remote_ip)
						VALUES (".$cart->getEntityId().", ".$cart->getStoreId().", '".$cart->getCustomerEmail()."', '".$cart->getCreatedAt()."', '".$cart->getUpdatedAt()."', '".date('Y-m-d H:i:s')."', '".$cart->getRemoteIp()."')
				";			
				$this->_getWriteConnection()->query($query);
			}
		}
							
		return $this;
	}
	
	
	protected function _createEventsFromAbandonedCarts()
	{
		$query = "SELECT * FROM ".$this->_abandonedCartTable." WHERE is_exported = 0";		
		$abandonedCarts = $this->_getReadConnection()->fetchAll($query);
		foreach ($abandonedCarts as $cart)
		{
			$cart = new Varien_Object($cart);			
			$evt = new Varien_Object();
			$evt->setQuote($cart);
			$event = Mage::getModel('contactlab_hub/event_abandonedCart');
			$event->setEvent($evt);
			$event->trace();
			
			$query = "UPDATE ".$this->_abandonedCartTable." SET is_exported = 1 WHERE id = ".$cart->getId();
			$this->_getWriteConnection()->query($query);
		}
	}

	

	
}	
