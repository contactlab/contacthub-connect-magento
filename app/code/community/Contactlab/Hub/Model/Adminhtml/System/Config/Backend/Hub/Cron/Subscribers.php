<?php
class Contactlab_Hub_Model_Adminhtml_System_Config_Backend_Hub_Cron_Subscribers
extends Mage_Core_Model_Config_Data
{
    const CRON_STRING_PATH = 'crontab/jobs/contactlab_hub_import_subscribers/schedule/cron_expr';  
    
    protected function _afterSave()
    {
    	$frequency = $this->getData('groups/cron_subscribers/fields/frequency/value');
        $time = $this->getData('groups/cron_subscribers/fields/time/value');
        $repeatMinutes = $this->getData('groups/cron_subscribers/fields/repeat_minutes/value');
        $repeatHours = $this->getData('groups/cron_subscribers/fields/repeat_hours/value');
        
        $frequencyMinutes = Contactlab_Hub_Model_Adminhtml_System_Config_Source_Cron_Frequency::CRON_MINUTES;
        $frequencyHourly = Contactlab_Hub_Model_Adminhtml_System_Config_Source_Cron_Frequency::CRON_HOURLY;
        $frequencyDaily = Contactlab_Hub_Model_Adminhtml_System_Config_Source_Cron_Frequency::CRON_DAILY;
        $frequencyWeekly = Contactlab_Hub_Model_Adminhtml_System_Config_Source_Cron_Frequency::CRON_WEEKLY;
        $frequencyMonthly = Contactlab_Hub_Model_Adminhtml_System_Config_Source_Cron_Frequency::CRON_MONTHLY;
    
           
        if($frequency == $frequencyMinutes)
        {
        	$cronExprArray = array(
				($repeatMinutes > 1) ? '*/'.$repeatMinutes : '*', 				# Minute
        		'*',         				                          			# Hour
        		'*',     														# Day of the Month
        		'*',                                                			# Month of the Year
        		'*',       														# Day of the Week
        	);        	
        }
    	elseif($frequency == $frequencyHourly)
        {
        	$cronExprArray = array(
				intval($time[1]),  												# Minute
        		($repeatHours > 1) ? '*/'.$repeatHours : '*',					# Hour
        		'*',     														# Day of the Month
        		'*',                                                			# Month of the Year
        		'*',       														# Day of the Week
        	);        	
        }
    	else
    	{    	
	        $cronExprArray = array(
	            intval($time[1]),                                   # Minute
	            intval($time[0]),                                   # Hour
	            ($frequency == $frequencyMonthly) ? '1' : '*',      # Day of the Month
	            '*',                                                # Month of the Year
	            ($frequency == $frequencyWeekly) ? '1' : '*',       # Day of the Week
	        );	        
    	}
        $cronExprString = join(' ', $cronExprArray);
        
        try {
            Mage::getModel('core/config_data')
                ->load(self::CRON_STRING_PATH, 'path')
                ->setValue($cronExprString)
                ->setPath(self::CRON_STRING_PATH)
                ->save();    
        }
        catch (Exception $e) {
            throw new Exception(Mage::helper('cron')->__('Unable to save the cron expression.'));
    
        }
    }

}
