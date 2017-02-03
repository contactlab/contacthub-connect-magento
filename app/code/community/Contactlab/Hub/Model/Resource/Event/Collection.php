<?php
class Contactlab_Hub_Model_Resource_Event_Collection 
extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Define resource model
     *
     */
    protected function _construct()
    {
        $this->_init('contactlab_hub/event');        
    }

    /**
     * Returns pairs slider_id - title
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray('event_id', 'event_type');
    }


    /**
     * Get SQL for get record count.
     * Extra GROUP BY strip added.
     *
     * @return Varien_Db_Select
     * @codeCoverageIgnore
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();

        $countSelect->reset(Zend_Db_Select::GROUP);

        return $countSelect;
    }

}
