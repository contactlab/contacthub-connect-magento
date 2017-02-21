<?php
class Contactlab_Hub_Model_Event_Logout extends Contactlab_Hub_Model_Event
{
	protected function _assignData()
	{	
		if(!$this->_getSid())
		{
			return;
		}
		$eventData = array();
		$this->setName('loggedOut')
			->setModel('logout')			
			->setEventData(json_encode($eventData));			
		return parent::_assignData();
	}
	
	protected function _composeHubEvent()
	{	
		if(!$this->_eventForHub)
		{
			$this->_eventForHub = new stdClass();
		}
		$this->_eventForHub->properties = new stdClass();
		return parent::_composeHubEvent();
	}
}