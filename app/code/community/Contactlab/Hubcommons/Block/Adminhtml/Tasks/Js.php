<?php

/**
 * Task grid.
 */
class Contactlab_Hubcommons_Block_Adminhtml_Tasks_Js extends Mage_Adminhtml_Block_Abstract {
    /**
     * Internal constructor, that is called from real constructor
     *
     */
    protected function _construct() {
        parent::_construct();
		$this->setTemplate("contactlab/hubcommons/tasks/js.phtml");
	}
	
	protected function getStatusUrl() {
		return $this->getUrl('*/*/getStatus');
	}
}
