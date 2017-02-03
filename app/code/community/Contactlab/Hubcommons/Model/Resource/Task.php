<?php

/**
 * Task resource.
 */
class Contactlab_Hubcommons_Model_Resource_Task extends Mage_Core_Model_Mysql4_Abstract {

    /**
     * Construct.
     */
    public function _construct() {
        $this->_init("contactlab_hubcommons/task", "task_id");
    }
}
