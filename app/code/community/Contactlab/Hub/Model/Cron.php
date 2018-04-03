<?php 
class Contactlab_Hub_Model_Cron extends Mage_Core_Model_Abstract
{	
	protected $_helper = null;
	
	private function _helper() {
		if ($this->_helper == null) {
			$this->_helper = Mage::helper('contactlab_hub');
		}
		return $this->_helper;
	}
	
	private function _getConfig($key) {
		return $this->_helper()->getConfigData($key);
	}
	
	private function _isExportPreviousCustomersEnabled() 
	{
		$enable = false;
		$allStores = Mage::app()->getStores();
		foreach ($allStores as $storeId => $val)
		{
			$enable = $enable || $this->_helper()->getConfigData('cron_previous_customers/enabled', $storeId);
		}
		return $enable;
	}
	
	private function _isImportSubscribersEnabled() {
		return $this->_getConfig('cron_subscribers/enabled')?true:false;
	}
	
	/**
	 * Log function call.
	 * @param String $functionName
	 * @param String $storeId
	 */
	public function logCronCall($functionName, $storeId = false)
	{
		Mage::helper('contactlab_hubcommons')
		->logCronCall(
				"Contactlab_Subscribers_Model_Cron::$functionName", $storeId
				);
	}
	
	public function exportEvents()
	{		
		$this->logCronCall("addExportEventsQueue");	
		return Mage::getModel("contactlab_hubcommons/task")
			->setTaskCode("ExportEventsTask")
			->setModelName('contactlab_hub/task_exportEvents')
			->setDescription('Export Events')
			->save();
	}
	
	public function exportPreviousCustomers()
	{					
		if (!$this->_isExportPreviousCustomersEnabled() )
		{
			return;
		}		
	
		$this->logCronCall("addExportPreviousCustomersQueue");		
		return Mage::getModel("contactlab_hubcommons/task")		
			->setTaskCode("ExportPreviousCustomersTask")
			->setModelName('contactlab_hub/task_exportPreviousCustomers')
			->setDescription('Export Previous Coustomers')	
			->save();
	}
	
	public function getAbandonedCarts()
	{
		Mage::getModel("contactlab_hub/exporter_abandonedCarts")->export();
	}
	
	/**
	 * Add importer task to queue.
	 */
	public function importSubscribers() 
	{
		if (!$this->_isImportSubscribersEnabled()) 
		{
			return;
		}		
		$this->logCronCall("addImportSubscribersQueue", $storeId);
		return Mage::getModel("contactlab_hubcommons/task")		
			->setTaskCode("ImportSubscribersTask")
			->setModelName('contactlab_hub/task_importSubscribers')
			->setDescription('Import Subscribers')
			->save();
	}
	
	/**
	 * Clean Old Events
	 */
	public function cleanEvents()
	{
	    $this->logCronCall("cleanOldEvents");
	    return Mage::getModel("contactlab_hubcommons/task")
	    ->setTaskCode("CleanEventsTask")
	    ->setModelName('contactlab_hub/task_cleanEvents')
	    ->setDescription('Clean Events')
	    ->save();
	}
}