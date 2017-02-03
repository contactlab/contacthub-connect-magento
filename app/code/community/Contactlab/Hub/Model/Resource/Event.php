<?php
class Contactlab_Hub_Model_Resource_Event 
extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('contactlab_hub/event', 'entity_id');
    }
    
}
