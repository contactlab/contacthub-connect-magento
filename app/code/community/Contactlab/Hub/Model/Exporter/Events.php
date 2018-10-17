<?php
class Contactlab_Hub_Model_Exporter_Events extends Mage_Core_Model_Abstract
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

	public function export()
	{	
		$pageSize = $this->_helper()->getConfigStoredData('cron_events/limit') ? : 30;
		$events = Mage::getModel('contactlab_hub/event')->getUnexportedEvents($pageSize);
		foreach($events as $event)
		{
			$event->export();			
		}
		return "Export done";
	}
}	
