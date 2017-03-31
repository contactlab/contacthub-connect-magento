<?php
class Contactlab_Hub_Model_Exporter_AbandonedCarts extends Contactlab_Hubcommons_Model_Exporter_Abstract
{
	protected $_helper;	
	protected $_connectionWrite;
	protected $_connectionRead;
	
	protected $_delimiter;
	
	
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
			$collection->getSelect()->join( array('subscribers'=> newsletter_subscriber), 'subscribers.subscriber_email = main_table.customer_email', array());
			$collection->addFieldToFilter('subscribers.subscriber_status', array('eq' => Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED));
		}
		/*
		$this->_helper()->log('ABANDONED CART');
		$this->_helper()->log($collection->getSelect());
		$this->_helper()->log('FINE ABANDONED CART');
		*/
		
		echo $collection->getSelect()."\n\n";
		
		
		foreach ($collection as $cart)
		{	
			$query = "
						SELECT * from contactlab_hub_abandoned_carts 
						WHERE quote_id = ".$cart->getEntityId()."
						ORDER BY updated_at DESC
						LIMIT 0,1											
			";
			$oldAbandonedCart = $this->_getReadConnection()->fetchRow($query);					
			if(strtotime($cart->getUpdatedAt()) > strtotime($oldAbandonedCart['updated_at']))
			{
				$query = "
						INSERT INTO contactlab_hub_abandoned_carts (quote_id, store_id, email, created_at, updated_at, abandoned_at, remote_ip)
						VALUES (".$cart->getEntityId().", ".$cart->getStoreId().", '".$cart->getCustomerEmail()."', '".$cart->getCreatedAt()."', '".$cart->getUpdatedAt()."', '".date('Y-m-d H:i:s')."', '".$cart->getRemoteIp()."')
				";			
				$this->_getWriteConnection()->query($query);
			}
		}
		/*
		$query = "	
					INSERT INTO contactlab_hub_abandoned_carts
					SELECT 
						NULL,
						sfq.entity_id, 
						sfq.store_id,
						sfq.customer_email, 
						sfq.created_at, 
						sfq.updated_at,
						'".date('Y-m-d H:i:s')."',
						sfq.remote_ip,
						0
					FROM sales_flat_quote AS sfq
					WHERE sfq.reserved_order_id IS NULL 
						AND sfq.customer_email IS NOT NULL				
						AND sfq.entity_id NOT IN ( SELECT quote_id FROM contactlab_hub_abandoned_carts AS chac WHERE sfq.customer_email = chac.email AND sfq.updated_at <= chac.update_at )						
				";
		if($minMinutes)
		{
			$query.=" AND (sfq.updated_at + INTERVAL ".$minMinutes." MINUTE) < '".date('Y-m-d H:i:s')."'";					
		}
		
		if($maxMinutes)
		{
			$query.=" AND sfq.updated_at > '".  $maxMinutesFromLastUpdate->get('YYYY-MM-dd HH:mm:ss')."'";
		}
		

		$this->_helper()->log('ABANDONED CART');
		$this->_helper()->log($query);
		$this->_helper()->log('FINE ABANDONED CART');
		
		$this->_getWriteConnection()->query($query);
		*/
		
							
		return $this;
	}
	
	
	protected function _createEventsFromAbandonedCarts()
	{
		$query = "SELECT * FROM contactlab_hub_abandoned_carts WHERE is_exported = 0";		
		$abandonedCarts = $this->_getReadConnection()->fetchAll($query);
		foreach ($abandonedCarts as $cart)
		{
			$cart = new Varien_Object($cart);			
			$evt = new Varien_Object();
			$evt->setQuote($cart);
			$event = Mage::getModel('contactlab_hub/event_abandonedCart');
			$event->setEvent($evt);
			$event->trace();
			
			$query = "UPDATE contactlab_hub_abandoned_carts SET is_exported = 1 WHERE id = ".$cart->getId();
			$this->_getWriteConnection()->query($query);
		}
	}

	

	
}	
