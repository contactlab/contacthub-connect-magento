<?php

/**
 * Task grid.
 */
class Contactlab_Hubcommons_Block_Adminhtml_Tasks_Status extends Mage_Adminhtml_Block_Abstract {
    /**
     * Internal constructor, that is called from real constructor
     *
     */
    protected function _construct() {
        parent::_construct();
		$this->setTemplate("contactlab/hubcommons/tasks/status.phtml");
	}
	
	protected function getRequestStatus() {
		return $this->getUrl('*/*/getRequestStatus');
	}
}
