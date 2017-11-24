<?php
class Contactlab_Hub_Model_Task_CleanEvents extends Contactlab_Hubcommons_Model_Task_Abstract {

    /**
     * Run the task (calls the helper).
     */
    protected function _runTask() 
    {    		                      
        return Mage::getModel('contactlab_hub/event')->cleanEvents();
    }

    /**
     * Called after the run.
     */
    protected function _afterRun() {
        if ($this->hasExporter()) {
            $this->getExporter()->afterExport();
        }
    }

    /**
     * Get the name.
     */
    public function getName() {
        return "Clean Events";
    }
   
}
