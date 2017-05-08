<?php
class Contactlab_Hub_Model_Event_Subscription extends Contactlab_Hub_Model_Event
{
	protected function _assignData()
	{	
		if(!$this->_getSid())
		{
			return;
		}
		$evtName = 'campaignUnsubscribed';		
		$eventData = array();		
		$email = $this->getEvent()->getSubscriber()->getEmail();
		$subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);		
		if($subscriber->getId())
		{
			$oldSubscriberStatus = $subscriber->getSubscriberStatus();
		} 
		else 
		{
			$oldSubscriberStatus = Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
		}
		
		$newSubscriberStatus = $this->getEvent()->getSubscriber()->getSubscriberStatus();
		if($oldSubscriberStatus != $newSubscriberStatus) 
		{
			if ($newSubscriberStatus == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) 
			{
				$status = 1;
				$evtName = 'campaignSubscribed';
			} 
			else if ($newSubscriberStatus == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) 
			{
				$status = 0;
			} 
			else 
			{
				$status = -1;
			}
			if ($status >= 0) 
			{				
				$this->setName($evtName)					
					->setModel('subscription')
					->setIdentityEmail($email)
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
		$properties = new stdClass();
		$properties->listId = $this->_helper()->getConfigData('events/campaignName');
		$properties->channel = "EMAIL";		
		$this->_eventForHub->properties = $properties;
		return parent::_composeHubEvent();
	}
	
}
