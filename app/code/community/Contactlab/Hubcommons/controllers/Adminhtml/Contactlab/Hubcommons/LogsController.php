<?php

/**
 * Logs controller.
 */
class Contactlab_Hubcommons_Adminhtml_Contactlab_Hubcommons_LogsController extends Mage_Adminhtml_Controller_Action {

    /**
     * Index.
     */
    public function indexAction() {
        $this->_title($this->__('Logs'));
        $this->loadLayout()->_setActiveMenu('contactlab/contactlab');
        return $this->renderLayout();
    }

    /**
     * Grid.
     */
    public function gridAction() {
        return $this->loadLayout(false)->renderLayout();
    }

    /**
     * Truncate the table.
     */
    public function truncateAction() {
        Mage::getModel("contactlab_hubcommons/log")->getResource()->truncateTable();
        $this->_redirect('*/*');
    }

    /**
     * Is this controller allowed?
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('newsletter/contactlab/logs');
    }
}
