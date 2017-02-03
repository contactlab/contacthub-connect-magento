<?php

/**
 * Test controller.
 */
class Contactlab_Hubcommons_Adminhtml_Contactlab_Hubcommons_TestController extends Mage_Adminhtml_Controller_Action {

    /**
     * Index.
     */
    public function indexAction() {
        $this->_title($this->__('Test job'));
        $this->loadLayout()->_setActiveMenu('contactlab/contactlab');
        return $this->renderLayout();
    }

    /**
     * Queue action.
     */
    public function queueAction() {
        Mage::getModel("contactlab_hubcommons/cron")->addTestQueue();
        return $this->_redirect('*/contactlab_hubcommons_tasks');
    }

}
