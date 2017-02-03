<?php

/** Task error mail template block. */
class Contactlab_Hubcommons_Block_Adminhtml_Email_Template extends Mage_Adminhtml_Block_Template {
	/** Get events with alert flg. */
    public function getErrorTasks() {
        return Mage::helper("contactlab_hubcommons/tasks")->getErrorTasks();
    }
}
