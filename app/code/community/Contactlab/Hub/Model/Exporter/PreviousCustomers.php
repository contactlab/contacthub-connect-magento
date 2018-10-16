<?php
class Contactlab_Hub_Model_Exporter_PreviousCustomers extends Mage_Core_Model_Abstract
{
	const PARTIAL_EXPORT	= 'partial';
	const FULL_EXPORT 		= 'full';
	
	protected $_mode;
	protected $_helper;	
	protected $_connectionWrite;
	protected $_connectionRead;
	protected $_tranche;
	protected $_trancheLimit;		

	protected $_customerTable;
	protected $_subscriberTable;
	protected $_storeTable;
	protected $_storeGroupTable;
	protected $_websiteTable;
	protected $_previouscustomersTable;
	
	protected $_canExportOrders;
	
	protected function _construct()
    {
		$this->_customerTable = Mage::getSingleton('core/resource')->getTableName('customer/entity');
		$this->_subscriberTable = Mage::getSingleton('core/resource')->getTableName('newsletter/subscriber');		
		$this->_storeTable = Mage::getSingleton('core/resource')->getTableName('core/store');		
		$this->_storeGroupTable = Mage::getSingleton('core/resource')->getTableName('core/store_group');
		$this->_websiteTable = Mage::getSingleton('core/resource')->getTableName('core/website');
		$this->_previouscustomersTable = Mage::getSingleton('core/resource')->getTableName('contactlab_hub/previouscustomers');
		$this->_quoteTable = Mage::getSingleton('core/resource')->getTableName('sales/quote');
		$this->_canExportOrders = true;
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
	
	/**
	 * Is enabled.
	 * @return bool
	 */
	protected function isEnabled() 
	{	
		return $this->_getConfig('cron_previous_customers/enabled')?true:false;
	}
	
	protected function _getPreviousDate()
	{		
		return Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime($this->_getConfig('cron_previous_customers/previous_date')));
	}
	
	protected function _init($resourceModel = null)
	{
		$this->_trancheLimit = $this->_getConfig('cron_previous_customers/limit');
		$this->_mode = self::PARTIAL_EXPORT;		
		if($this->getMode())
		{
			$this->_mode = 	$this->getMode();
		}				
		return $this;
	}
	
	public function resetExport()
	{
		$allStores = Mage::app()->getStores();
		foreach ($allStores as $storeId => $val)
		{
			$this->setStoreId($storeId);
			$this->_helper()->setConfigData('contactlab_hub/cron_previous_customers/enabled', 1, 'stores', $this->getStoreId());
            $this->_helper()->setConfigData('contactlab_hub/cron_previous_customers/previous_date', date('Y-m-d H:i:s'), 'stores', $this->getStoreId());
			$this->_setUnexported();
			$this->_canExportOrders = false;
		}		
		return $this;
	}

	public function export()
	{			
		$allStores = Mage::app()->getStores();
		foreach ($allStores as $storeId => $val)
		{
			$this->setStoreId($storeId);			
			if ((!$this->isEnabled()) || (!$this->_getPreviousDate())) 
			{
                $this->_helper()->log("Module export is disabled");
				continue;
			}			
			$this->_init();
			$this->_insertCustomers();
			$this->_insertSubscribers();
			$this->_createEvents();
		}
		
		return "Export done";
	}

	
	protected function _getPreviousCustomers()
	{		
		$query = "	SELECT * FROM ".$this->_previouscustomersTable." WHERE  store_id IN (0, ".$this->getStoreId().") AND is_exported = 0 ";
		
		if($this->_mode == self::PARTIAL_EXPORT)
		{
			$query .=" LIMIT 0,".$this->_trancheLimit;
		}					
		$results = $this->_getReadConnection()->fetchAll($query);
		return $results;
	}
	
	protected function _insertCustomers()
	{			
		$query = "	SELECT ce.entity_id as customer_id										
					,ce.store_id
					,ce.created_at
					,ce.email
					FROM ".$this->_customerTable." as ce					
					LEFT OUTER JOIN ".$this->_previouscustomersTable." as chp ON ce.email = chp.email
					WHERE ce.created_at < '".$this->_getPreviousDate()."'
					AND ce.store_id IN (0, ".$this->getStoreId().")
					AND chp.id IS NULL	";	
		if($this->_mode == self::PARTIAL_EXPORT)
		{
			$exportable = $this->_trancheLimit - count($this->_getPreviousCustomers());
			if($exportable > 0)
			{
				$query .=" LIMIT 0, ". $exportable;
			}
		}
		$results = $this->_getReadConnection()->fetchAll($query);
		foreach ($results as $row)
		{				
			/* CUSTOMER INFORMATIONS */
			$row['remote_ip'] = $this->_getRemoteIp($row['customer_id']);
			$query = "	INSERT INTO ".$this->_previouscustomersTable." SET ".$this->_buildInsertQuery($row);
			//echo var_dump($row);
			$this->_getWriteConnection()->query($query, $row);			
		}		
	
		return $this;
	}
	
	protected function _insertSubscribers()
	{	
	    $query = "  SELECT ne.customer_id
                    ,ne.store_id
                    ,ne.subscriber_email as email
                    FROM ".$this->_subscriberTable." as ne
                    LEFT OUTER JOIN ".$this->_previouscustomersTable." as chp ON ne.subscriber_email = chp.email
                    WHERE ne.store_id IN (0, ".$this->getStoreId().")
                    AND ne.subscriber_status = ".Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED."
                    AND chp.id IS NULL   ";
	    if($this->_mode == self::PARTIAL_EXPORT)
	    {
	        $exportable = $this->_trancheLimit - count($this->_getPreviousCustomers());
	        if($exportable > 0)
	        {
	            $query .=" LIMIT 0, ". $exportable;
	        }
	    }	    
	    
	    return $this;
	}
	
	private function _buildInsertQuery($data)
	{
		$query = '';
		foreach($data as $key => $val)
		{
			$query = $query . $key . ' =:' .$key . ', ';
		}		
		$query = substr($query, 0, -2);
		return $query; 
	}
	
	protected function _getStoreLocale($storeId)
	{
		return Mage::getStoreConfig('general/locale/code', $storeId);
	}
	
	protected function _getRemoteIp($customerId)
	{
		$query= "SELECT remote_ip FROM ".$this->_quoteTable." WHERE customer_id = ".$customerId." ORDER BY created_at limit 0,1";
		$remoteIp = $this->_getReadConnection()->fetchOne($query);
		if(!$remoteIp)
		{
			if (!empty($_SERVER['HTTP_CLIENT_IP'])) 
			{
    			$remoteIp =  $_SERVER['HTTP_CLIENT_IP'];
			} 
			else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) 
			{
    			$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    			$remoteIp =  trim($ips[count($ips) - 1]); //real IP address behind proxy IP			
			} 
			else 
			{
    			$remoteIp =  $_SERVER['REMOTE_ADDR']; //no proxy found
			}
		}
		return $remoteIp;
	}
		
	
		
	protected function _createEvents()
	{
		if(count($this->_getPreviousCustomers()) > 0)
		{			
			$this->_tranche = $this->_getPreviousCustomers();
			
			foreach($this->_tranche as $previousCustomer)
			{			    
			    if($previousCustomer['customer_id'])
			    {
        				$event = Mage::getModel('contactlab_hub/event');
        				$event->setName('formCompiled')
        						->setModel('register')
        						->setCreatedAt($previousCustomer['created_at'])
        						->setStoreId($previousCustomer['store_id'])
        						->setIdentityEmail($previousCustomer['email'])
        						->setEnvRemoteIp($previousCustomer['remote_ip'])
        						->setNeedUpdateIdentity(true)
        				;
        				$event->save();       			


        				//if(!$previousCustomer['orders_exported'])
                        if($this->_helper()->canExportPreviousOrder($previousCustomer['store_id']))
        				{
        					$orders = $this->_getCustomerOrders($previousCustomer['customer_id']);
        					if(count($orders) > 0)
        					{
        						foreach($orders as $order)
        						{
        							$eventData = array(
        									'increment_id' => $order->getIncrementId(),
        							);
        							$model = 'checkout';
        							$order->getStatus();
        							/*
        							if ($order->getStatus() == Mage_Sales_Model_Order::STATE_CANCELED)
        							{
        								$model = 'cancelOrder';
        							}
        							*/
        							$event = Mage::getModel('contactlab_hub/event');
        							$event->setName('completedOrder')
        								->setModel($model)
        								->setCreatedAt($order->getCreatedAt())
        								->setStoreId($order->getStoreId())
        								->setIdentityEmail($order->getCustomerEmail())
        								->setNeedUpdateIdentity(true)
        								->setEnvRemoteIp($order->getRemoteIp())
        								->setEventData(json_encode($eventData));
        							$event->save();
        							/*
        							$query = "UPDATE ".$this->_previouscustomersTable." SET orders_exported = 1 WHERE id = ".$previousCustomer['id'];
        							$this->_getWriteConnection()->query($query);
        							*/
        						}
        					}
        					/*
        					else 
        					{
        						$query = "UPDATE ".$this->_previouscustomersTable." SET orders_exported = 1 WHERE id = ".$previousCustomer['id'];
        						$this->_getWriteConnection()->query($query);
        					}
        					*/
        				}	
        			}	
        			else
        			{
        			    $event = Mage::getModel('contactlab_hub/event');
        			    $event->setName('campaignSubscribed')
        			    ->setModel('subscription')
        			    ->setCreatedAt(date('Y-m-d H:i:s'))
        			    ->setStoreId($previousCustomer['store_id'])
        			    ->setIdentityEmail($previousCustomer['email'])
        			    ->setEventData(json_encode(array()))
        			    ->setNeedUpdateIdentity(true)
        			    ;
        			    $event->save();
        			}
        			$this->_setExportedPrevious($previousCustomer['id']);
			}							
		}
		else
		{
            $this->_helper()->log("No previous customers to export");
            $this->_helper()->setConfigData('contactlab_hub/cron_previous_customers/enabled', 0, 'stores', $this->getStoreId());
            $this->_helper()->setConfigData('contactlab_hub/cron_previous_customers/export_order', 0, 'stores', $this->getStoreId());
		}
		return $this;
	}

	protected function _getCustomerOrders( $customerId )
	{
		$orderCollection = Mage::getResourceModel('sales/order_collection')
			->addFieldToSelect('*')
			->addFieldToFilter('customer_id', array('eq' => $customerId))	
			->addFieldToFilter('created_at', array('lt' => $this->_getPreviousDate()))
			->addFieldToFilter('status', array('neq' => Mage_Sales_Model_Order::STATE_CANCELED))
			->setOrder('created_at', 'desc');
		return $orderCollection;
	}
	
	
	protected function _setUnexported()
	{			
		$query = "UPDATE ".$this->_previouscustomersTable." SET is_exported = 0 WHERE store_id IN (0, ".$this->getStoreId().");";
		$this->_getWriteConnection()->query($query);
		Mage::helper("contactlab_hub")->log("Previous customer export reset succesfull");
		return $this;
	}
	
	protected function _setExportedPrevious($previousCustomerId)
	{
		$query = "UPDATE ".$this->_previouscustomersTable." SET is_exported = 1 WHERE id = ".$previousCustomerId;
		$this->_getWriteConnection()->query($query);
		return $this;
	}
}	
