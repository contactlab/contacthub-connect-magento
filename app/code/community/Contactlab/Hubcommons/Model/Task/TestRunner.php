<?php

/**
 * Test executer, for debug.
 */
class Contactlab_Hubcommons_Model_Task_TestRunner extends Contactlab_Hubcommons_Model_Task_Abstract {

    /**
     * Run the test task.
     */
    protected function _runTask() {
        // $args = $this->getArguments();
        // $doIt = count($args) > 0 && $args[0];
        // if (!$doIt) {
        // throw new Zend_Exception("Errore nel test");
        // }
        sleep(1);
        return "Ok";
    }

    /**
     * The name of the task.
     */
    public function getName() {
        return "Test task";
    }

}
