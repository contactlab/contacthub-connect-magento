<?php

class Contactlab_Hubcommons_Model_Resource_Deleted extends Mage_Core_Model_Mysql4_Abstract {
	public function _construct() {
		$this->_init('contactlab_hubcommons/deleted', 'deleted_entity_id');
	}
}
