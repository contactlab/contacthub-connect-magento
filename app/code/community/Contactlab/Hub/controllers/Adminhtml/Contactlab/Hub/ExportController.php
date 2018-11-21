<?php
class Contactlab_Hub_Adminhtml_Contactlab_Hub_ExportController extends Mage_Adminhtml_Controller_Action 
{
    /**
     * Queue action.
     */
    public function queueAction() 
    {    	
        Mage::getModel("contactlab_hub/cron")->exportPreviousCustomers();            	
        return $this->_redirect('adminhtml/contactlab_hub_logs');
    }

    /**
     * Reset action.
     */
    public function resetAction()
    {    	
    	Mage::getModel("contactlab_hub/exporter_PreviousCustomers")->resetExport();
    	Mage::getModel("contactlab_hub/cron")->exportPreviousCustomers();
    	return $this->_redirect('adminhtml/contactlab_hub_logs');
    }
    
    /**
     * Is this controller allowed?
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
