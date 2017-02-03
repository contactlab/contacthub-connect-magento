<?php

/**
 * Test controller.
 */
class Contactlab_Hubcommons_Adminhtml_Contactlab_Hubcommons_ReleaseNotesController extends Mage_Adminhtml_Controller_Action {

    /**
     * Index of release notes.
     */
    public function indexAction() {
        $this->_title($this->__('ContactLab release notes'));
        $this->loadLayout()->_setActiveMenu('contactlab/contactlab');
        return $this->renderLayout();
    }

    /**
     * Is this controller allowed?
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('newsletter/contactlab/release_notes');
    }
}
