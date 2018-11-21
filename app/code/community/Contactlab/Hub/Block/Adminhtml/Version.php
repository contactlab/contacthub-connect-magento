<?php

/**
 * Test block to queue task.
 */
class Contactlab_Hub_Block_Adminhtml_Version extends Mage_Adminhtml_Block_Abstract {

    /**
     * Construct the block.
     */
    public function __construct() {
        $this->setTemplate("contactlab/hub/version.phtml");
        parent::__construct();
    }
    
    /**
     * Title of the block.
     * @return string
     */
    public function getTitle() {
        return $this->__("Plugin version");
    }
    
    /**
     * Get module versions.
     * @return \Varien_Data_Collection
     */
    public function getModulesVersion() {
        /* @var $helper Contactlab_Hub_Helper_Data */
        $helper = Mage::helper('contactlab_hub');
        return $helper->getModulesVersion();
    }
    
    /**
     * Only in debug mode.
     * @return typeDo print version?
     */
    public function doPrintVersion() {
        /* @var $helper Contactlab_Hub_Helper_Data */
        $helper = Mage::helper('contactlab_hub');
        return $helper->isDebug();
    }

    /**
     * Get platform version.
     * @return String
     */
    public function getPlatformVersion() {
        return Mage::helper('contactlab_hub')->getPlatformVersion();
    }
}
