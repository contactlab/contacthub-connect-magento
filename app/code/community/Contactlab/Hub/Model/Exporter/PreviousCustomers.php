<?php
class Contactlab_Hub_Model_Exporter_PreviousCustomers extends Contactlab_Hubcommons_Model_Exporter_Abstract
{
	const PARTIAL_EXPORT	= 'partial';
	const FULL_EXPORT 		= 'full';
	
	protected $_mode;
	protected $_helper;	
	protected $_connectionWrite;
	protected $_connectionRead;
	protected $_tranche;
	protected $_trancheLimit;		
	protected $_folderName;
	protected $_filename;
	protected $_delimiter;
	
	
	private function _helper() {
		if ($this->_helper == null) {
			$this->_helper = Mage::helper('contactlab_hub');
		}
		return $this->_helper;
	}
	
	private function _getConfig($key) {
		return $this->_helper()->getConfigData($key);
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
		return $this->_getConfig('cron_previous_customers/enabled')?true:false;
	}
	
	protected function _getPreviousDate()
	{		
		return Mage::getModel('core/date')->date('Y-m-d', strtotime($this->_getConfig('cron_previous_customers/previous_date')));
	}
	
	/**
	 * Get file name.
	 * @return string
	 */
	protected function getFileName() {
		//return $this->getTask()->getConfig("contactlab_subscribers/global/export_filename");
		return $this->_folderName.DS.$this->_filename;
	}
	
	protected function _manageFolder()
	{
		if(!file_exists($this->_folderName))
		{
			mkdir($this->_folderName, 0777, true);
		}
		return $this;
	}
	
	protected function _init()
	{		
		$this->_delimiter =';';
		$this->_folderName = Mage::getBaseDir('var').DS.'contacthub'.DS.'export';
		$this->_manageFolder();				
		$this->_filename =  'customers_'.date('YmdHis').'.csv.tmp';
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
		Mage::helper('contactlab_hub')->setConfigData('cron_previous_customers/enabled', 1);
		Mage::helper('contactlab_hub')->setConfigData('cron_previous_customers/previous_date', date('Y-m-d H:i:s'));
		$this->_setUnexported();
		return $this;
	}
	
	public function export(Contactlab_Hubcommons_Model_Task_Interface $task)
	//public function export()
	{	
		if ((!$this->isEnabled()) || (!$this->_getPreviousDate())) 
		{
			Mage::helper("contactlab_hubcommons")->logWarn("Module export is disabled");
			return "Module export is disabled";
		}
		
		$this->_init();
		$this->_fillPreviousCustomerTable();
		/*
		$this->_writeTranche();
	
		if ($this->_useLocalServer()) 
		{
			Mage::helper("contactlab_hubcommons")->logNotice("Exporting locally to $filename");
			rename($this->getFileName(), str_replace('.tmp', '', $this->getFileName()));
		}
		if ($this->_useRemoteServer()) 
		{
			$filename = str_replace('.tmp', '', $this->getFileName());
			rename($this->getFileName(), $filename);
			$this->_putFile(realpath($filename), basename($filename));
			sleep(2);
			unlink(realpath($filename));
		}
		$this->afterFileCopy();
		*/
		$this->_createEvents();
		
		$this->_setExportedTranche();
		
		return "Export done";
	}
	
	protected function _fillPreviousCustomerTable()
	{
		$this->_insertSubscribers();
		$this->_insertCustomers();					
	}
	
	protected function _getPreviousCustomers()
	{
		$query = "	SELECT * FROM contactlab_hub_previouscustomers ";
		
		if($this->_mode == self::PARTIAL_EXPORT)
		{
			$query .="where is_exported = 0 LIMIT 0,".$this->_trancheLimit;
		}		
		$results = $this->_getReadConnection()->fetchAll($query);
		return $results;
	}
	
	protected function _insertSubscribers()
	{	
		$query = "	SELECT ns.subscriber_id
					,1 as is_subscribed
					,ns.subscriber_email as email
					,cs.store_id
					,cs.name as store_name
					,cs.website_id
					,cw.name as website_name
					,cs.group_id
					,csg.name as group_name						
					FROM newsletter_subscriber as ns 
					INNER JOIN core_store as cs ON ns.store_id = cs.store_id
					INNER JOIN core_store_group as csg ON cs.group_id = csg.group_id
					INNER JOIN core_website as cw ON cs.website_id = cw.website_id
					LEFT OUTER JOIN contactlab_hub_previouscustomers as chp ON ns.subscriber_email = chp.email
					WHERE ns.customer_id = 0 
					AND chp.id IS NULL	";
		if($this->_mode == self::PARTIAL_EXPORT)
		{
			$exportable = $this->_trancheLimit - count($this->_getPreviousCustomers());
			if($exportable > 0)
			{
				$query .="LIMIT 0, ". $exportable;
			}
		}
		//echo $query."\n";
		//Mage::log($query, null, 'fra.log');
		$results = $this->_getReadConnection()->fetchAll($query);			
		foreach ($results as $row)
		{
			$row['language'] = $this->_getStoreLocale($row['store_id']);				
			$query = "	INSERT INTO contactlab_hub_previouscustomers SET ".$this->_buildInsertQuery($row);			  									 	
			$this->_getWriteConnection()->query($query, $row);
		}
		return $this;
	}
	
	protected function _insertCustomers()
	{				
		$query = "	SELECT ce.entity_id as customer_id
					,1 as is_customer
					,ns.subscriber_id
					,IF(ns.subscriber_id IS NULL, 0,1) as is_subscribed 
					,cs.store_id
					,cs.name as store_name
					,cs.website_id
					,cw.name as website_name
					,cs.group_id
					,csg.name as group_name
					FROM customer_entity as ce
					LEFT OUTER JOIN newsletter_subscriber as ns ON ce.email = ns.subscriber_email
					INNER JOIN core_store as cs ON ce.store_id = cs.store_id
					INNER JOIN core_store_group as csg ON cs.group_id = csg.group_id
					INNER JOIN core_website as cw ON cs.website_id = cw.website_id
					LEFT OUTER JOIN contactlab_hub_previouscustomers as chp ON ce.email = chp.email
					WHERE ce.created_at < '".$this->_getPreviousDate()."'
					AND chp.id IS NULL	";	
		if($this->_mode == self::PARTIAL_EXPORT)
		{
			$exportable = $this->_trancheLimit - count($this->_getPreviousCustomers());
			if($exportable > 0)
			{
				$query .="LIMIT 0, ". $exportable;
			}
		}
		//echo $query."\n";
		Mage::log($query, null, 'fra.log');
		$results = $this->_getReadConnection()->fetchAll($query);
		foreach ($results as $row)
		{				
			/* CUSTOMER INFORMATIONS */
			$customer = Mage::getModel('customer/customer')->load($row['customer_id']);
			if($customer)
			{
				//var_dump($customer->getData());
				$row['prefix'] = $customer->getPrefix();
				$row['firstname'] = $customer->getFirstname();
				$row['middlename'] = $customer->getMiddlename();
				$row['lastname'] = $customer->getLastname();
				$row['suffix'] = $customer->getSuffix();
				$row['dob'] = $customer->getDob() ? date('Y-m-d', strtotime($customer->getDob())) : null;				
				$row['gender'] = $customer->getGender();
				$row['email'] = $customer->getEmail();
				$row['taxvat'] = $customer->getTaxvat();
				$row['created_at'] = date('Y-m-d H:i:s', strtotime($customer->getCreatedAt()));
				/* BILLING INFORMATIONS */
				$billing = $customer->getDefaultBillingAddress();
				if($billing)
				{
					//var_dump($billing->getData());
					$row['billing_prefix'] = $billing->getPrefix();
					$row['billing_firstname'] = $billing->getFirstname();
					$row['billing_middlename'] = $billing->getMiddlename();
					$row['billing_lastname'] = $billing->getLastname();
					$row['billing_suffix'] = $billing->getSuffix();
					$row['billing_country_id'] = $billing->getCountryId();
					$row['billing_region_id'] = $billing->getRegionId();
					$row['billing_region'] = $billing->getRegion();
					$row['billing_postcode'] = $billing->getPostcode();
					$row['billing_city'] = $billing->getCity();
					$row['billing_street'] = implode(" ", $billing->getStreet());
					$row['billing_telephone'] = $billing->getTelephone();
					$row['billing_fax'] = $billing->getFax();
					$row['billing_company'] = $billing->getCompany();					
				}
				/* SHIPPING INFORMATIONS */
				$shipping = $customer->getDefaultShippingAddress();
				if($shipping)
				{
					//var_dump($shipping->getData());					
					$row['shipping_prefix'] = $shipping->getPrefix();
					$row['shipping_firstname'] = $shipping->getFirstname();
					$row['shipping_middlename'] = $shipping->getMiddlename();
					$row['shipping_lastname'] = $shipping->getLastname();
					$row['shipping_suffix'] = $shipping->getSuffix();					
					$row['shipping_country_id'] = $shipping->getCountryId();				
					$row['shipping_region_id'] = $shipping->getRegionId();
					$row['shipping_region'] = $shipping->getRegion();
					$row['shipping_postcode'] = $shipping->getPostcode();
					$row['shipping_city'] = $shipping->getCity();
					$row['shipping_street'] = implode(" ", $shipping->getStreet());
					$row['shipping_telephone'] = $shipping->getTelephone();				
					$row['shipping_fax'] = $shipping->getFax();
					$row['shipping_company'] = $shipping->getCompany();		
				}
				/* EXTRA INFORMATIONS */
				$row['language'] = $this->_getStoreLocale($row['store_id']);
				$row['extra_properties'] = json_encode($this->_getExtraProperties($customer));
				//var_dump($row);				
				$query = "	INSERT INTO contactlab_hub_previouscustomers SET ".$this->_buildInsertQuery($row);						 						
				//echo var_dump($row);				
				$this->_getWriteConnection()->query($query, $row);	
			}
			else
			{
				throw new Zend_Exception(sprintf('There\'s been a problem exporting customer %s', $row['customer_id']));
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
	
	protected function _getExtraProperties($customer)
	{
		$extraProperties = array();
		$configExrtraProperties = explode(',', $this->_getConfig('cron_previous_customers/extra_properties'));		
		foreach ($configExrtraProperties as $attributeCode)
		{
			$attribute = Mage::getModel('eav/entity_attribute')->getCollection()->addFieldToFilter('attribute_code', array('in' => $attributeCode) )->getFirstItem();
	
			if($attribute->getEntityTypeId() == 1)
			{
				if($attribute->getBackendType() == 'int')
				{
					$value = Mage::getResourceSingleton('customer/customer')
							->getAttribute($attributeCode)
							->getSource()
							->getOptionText($customer->getData($attributeCode));
					$extraProperties[$attributeCode] = $value;
				}
				else
				{
					$extraProperties[$attributeCode] = $customer->getData($attributeCode);
				}
			
			}
			else 
			{
				/* BILLING INFORMATIONS */
				$billing = $customer->getDefaultBillingAddress();
				if($billing)
				{
					if($billing->getData($attributeCode))
					{
						if($attribute->getBackendType() == 'int')
						{
							$value = Mage::getResourceSingleton('customer/address')
								->getAttribute($attributeCode)
								->getSource()
								->getOptionText($billing->getData($attributeCode));
							$extraProperties['billing_'.$attributeCode] = $value;
						}
						else
						{
							$extraProperties['billing_'.$attributeCode] = $billing->getData($attributeCode);
						}
					}
				}
				/* SHIPPING INFORMATIONS */
				$shipping = $customer->getDefaultShippingAddress();
				if($shipping)
				{					
					if($shipping->getData($attributeCode))
					{	
						if($attribute->getBackendType() == 'int')
						{
							$value = Mage::getResourceSingleton('customer/address')
								->getAttribute($attributeCode)
								->getSource()
								->getOptionText($shipping->getData($attributeCode));
							$extraProperties['shipping_'.$attributeCode] = $value;
						}
						else
						{
							$extraProperties['shipping_'.$attributeCode] = $shipping->getData($attributeCode);
						}
					}
				}
			}
		}
		return $extraProperties;
	}
	/*
	protected function _writeTranche()
	{
		if(count($this->_getPreviousCustomers()) > 0)
		{
			$this->_writeHeader();
			$this->_tranche = $this->_getPreviousCustomers();		
			if($output = fopen($this->getFileName(),'a+'))
			{				
				foreach($this->_tranche as $previousCustomer)
				{	
					//var_dump($previousCustomer);
					unset($previousCustomer['id']);
					unset($previousCustomer['is_exported']);
					fputcsv($output, $previousCustomer, $this->_delimiter);								
				}
				fclose($output);			
			}
			else 
			{
				Mage::helper("contactlab_hubcommons")->logAlert($this->getFileName()." is not writeable");
			}		
		}
		else
		{
			Mage::helper("contactlab_hubcommons")->logNotice("No previous customers to export");
			Mage::helper('contactlab_hub')->setConfigData('cron_previous_customers/enabled', 0);
		}
		return $this;
	}
	
	protected function _writeHeader($header)
	{						
		if($output = fopen($this->getFileName(),'w+'))
		{	
			foreach($this->_getPreviousCustomers() as $previousCustomer)
			{			
				unset($previousCustomer['id']);
				unset($previousCustomer['is_exported']);
				fputcsv($output, array_keys($previousCustomer), $this->_delimiter);							
				break;
			}
			fclose($output);
		}
		else
		{
			Mage::helper("contactlab_hubcommons")->logAlert($this->getFileName()." is not writeable");
		}		
		return $this;
	}
	
	public function afterFileCopy()
	{
		$this->_setExportedTranche();
		return $this;
	}
	*/
	
	
	protected function _createEvents()
	{
		if(count($this->_getPreviousCustomers()) > 0)
		{			
			$this->_tranche = $this->_getPreviousCustomers();
			
			foreach($this->_tranche as $previousCustomer)
			{			
				$event = Mage::getModel('contactlab_hub/event');
				$event->setName('loggedIn')
						->setModel('login')
						->setCreatedAt($previousCustomer['created_at'])
						->setStoreId($previousCustomer['store_id'])
						->setIdentityEmail($previousCustomer['email'])
						->setNeedUpdateIdentity(true)
				;
				$event->save();			
								
			}			
		}
		else
		{
			Mage::helper("contactlab_hubcommons")->logNotice("No previous customers to export");
			Mage::helper('contactlab_hub')->setConfigData('cron_previous_customers/enabled', 0);
		}
		return $this;
	}

	protected function _setUnexported()
	{
		$query = "UPDATE contactlab_hub_previouscustomers SET is_exported = 0";
		$this->_getWriteConnection()->query($query);
		Mage::helper("contactlab_hubcommons")->logNotice("Previous customer export reset succesfull");
		return $this;
	}
	
	protected function _setExportedTranche()
	{
		if($this->_tranche)
		{
			foreach($this->_tranche as $previousCustomer)
			{
				$query = "UPDATE contactlab_hub_previouscustomers SET is_exported = 1 WHERE id = ".$previousCustomer['id'];
				$this->_getWriteConnection()->query($query);
			}
			Mage::helper("contactlab_hubcommons")->logNotice(count($this->_tranche)." previous customers exported");
		}
		return $this;
	}
	
	
	/** Put file into sftp or localhost. */
	private function _putFile($filename, $realFilename) {
		$sftp = new Contactlab_Hubcommons_Model_Ssh_Net_SFTP(
				$this->getTask()->getConfig("contactlab_hubcommons/connection/remote_server"));
		if (!$sftp->login(
				$this->getTask()->getConfig("contactlab_hubcommons/connection/sftp_username"),
				$this->getTask()->getConfig("contactlab_hubcommons/connection/sftp_password"))) {
					throw new Zend_Exception('Login Failed');
				}
	
				$sftp->put($realFilename, $filename, NET_SFTP_LOCAL_FILE);
				$this->_checkUploadedFile($filename, $realFilename, $sftp);
	
				$sftp->_disconnect(0);
	}
	
	/** Check uploaded file existence. */
	private function _checkUploadedFile($localFile, $remoteFile, $sftp) {
		$localFileSize = filesize($localFile);
		$remoteStat = $sftp->lstat($remoteFile);
		if (!$remoteStat) {
			throw new Zend_Exception(sprintf('There\'s been a problem during file upload: uploaded file %s not found', $remoteFile));
		}
		$this->getTask()->addEvent("Remote file info: " . print_r($remoteStat, true));
		$remoteFileSize = $remoteStat['size'];
		if ($localFileSize != $remoteFileSize) {
			throw new Zend_Exception(sprintf(
					'There\'s been a problem during file upload: original (%s) file\'s lenght is %d while uploaded '
					. '(%s) file\'s lenght is %d!', $localFile, $localFileSize, $remoteFile, $remoteFileSize));
		}
	}
}	