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
	
	public function exportEvents()
	{
        Mage::getModel('contactlab_hub/exporter_events')->export();

	}
	
	public function exportPreviousCustomers()
	{					
		if (!$this->_isExportPreviousCustomersEnabled() )
		{
			return;
		}
        Mage::getModel("contactlab_hub/exporter_previousCustomers")->export();
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
        Mage::getModel("contactlab_hub/importer_subscribers")->import();
	}
	
	/**
	 * Clean Old Events
	 */
	public function cleanEvents()
	{
        Mage::getModel('contactlab_hub/event')->cleanEvents();
	}
}