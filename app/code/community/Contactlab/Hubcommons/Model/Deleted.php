<?php

/**
 * Class Contactlab_Hubcommons_Model_Deleted.
 * @method bool getIsCustomer
 * @method string getEmail
 * @method Contactlab_Hubcommons_Model_Deleted setTaskId($value)
 */
class Contactlab_Hubcommons_Model_Deleted extends Mage_Core_Model_Abstract {
	
	public function _construct() {
		parent::_construct ();
		$this->_init('contactlab_hubcommons/deleted' );
	}
	
}
