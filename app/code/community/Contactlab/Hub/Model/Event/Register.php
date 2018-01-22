<?php

class Contactlab_Hub_Model_Event_Register extends Contactlab_Hub_Model_Event
{

    protected function _assignData()
    {
        if(!$this->_getSid())
        {
            return;
        }
        $eventData = array();
        $this->setName('formCompiled')
            ->setModel('register')
            ->setNeedUpdateIdentity(false)
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
        $properties = new stdClass();
        $properties->formName = 'registeredEcommerce';
        $properties->formId = 'registeredEcommerce';
        $this->_eventForHub->properties = $properties;
        return parent::_composeHubEvent();
    }
}