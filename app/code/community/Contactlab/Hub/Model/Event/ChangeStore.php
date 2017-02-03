<?php
class Contactlab_Hub_Model_Event_ChangeStore extends Contactlab_Hub_Model_Event
{
	protected function _assignData()
	{				
		//$eventModel = 'customer';	
	
		$tostore = Mage::app()->getRequest()->getParam('___store');
		$fromstore = Mage::app()->getRequest()->getParam('___from_store');
		if (!empty($tostore)) 
		{
			$newValue = Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getStoreId());	
			$oldValue = '';
			if (!empty($fromstore)) 
			{
				$storeId = Mage::getConfig()->getNode('stores')->{$fromstore}->{'system'}->{'store'}->{'id'};
				$oldValue = Mage::getStoreConfig('general/locale/code', intval($storeId));				
			}
			if (!empty($newValue)) 
			{				
				$eventData = array(
					'setting' 	=> 'LANGUAGE',
					'old_value' => $oldValue,
					'new_value' => $newValue
				);
				$this->setName('changedSetting')
					->setModel('changeStore')
					->setNeedUpdateIdentity(true)
					->setEventData(json_encode($eventData));
			}
		}					
		return parent::_assignData();
	}
	
	protected function _composeHubEvent()
	{
	
		if(!$this->_eventForHub)
		{
			$this->_eventForHub = new stdClass();
		}
		$eventData = json_decode($this->getEventData());
		$this->_eventForHub->properties->setting = "language";
		$this->_eventForHub->properties->oldValue = $eventData->old_value;
		$this->_eventForHub->properties->newValue = $eventData->new_value;
	
		return parent::_composeHubEvent();
	}
	
}