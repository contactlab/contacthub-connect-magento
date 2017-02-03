<?php
class Contactlab_Hub_Model_Task_ExportEvents extends Contactlab_Hubcommons_Model_Task_Abstract {

    /**
     * Run the task (calls the helper).
     */
    protected function _runTask() 
    {    		
        /* @var $task Contactlab_Hubcommons_Model_Task */
        $task = $this->getTask();
                
    	$this->setExporter(Mage::getModel("contactlab_hub/exporter_events")
			->setTask($this->getTask()));
        return $this->getExporter()->export($this);
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
        return "Export Events";
    }
   
}
