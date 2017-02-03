<?php
class Contactlab_Hub_Model_Task_ImportSubscribers extends Contactlab_Hubcommons_Model_Task_Abstract {

    /**
     * Run the task (calls the helper).
     */
    protected function _runTask() {
        if ($this->getTask()->getConfigFlag("contactlab_hubcommons/soap/enable")) {
            $this->_checkSubscriberDataExchangeStatus();
        }
        return Mage::getModel("contactlab_hub/importer_subscribers")
	        ->setTask($this->getTask())
	        ->import($this);
    }

    /**
     * Get the name.
     */
    public function getName() {
        return "Import subscribers";
    }

}
