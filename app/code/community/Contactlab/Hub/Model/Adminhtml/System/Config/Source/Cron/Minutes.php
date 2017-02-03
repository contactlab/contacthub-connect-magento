<?php
class Contactlab_Hub_Model_Adminhtml_System_Config_Source_Cron_Minutes
{
    protected static $_options;

    public function toOptionArray()
    {
        if (!self::$_options) {
        	$hours = array();
        	for ($i=1; $i< 60; $i++)
        	{
        		$hours[] = array(
			       				'label' => $i,
			       				'value' => $i,
			           		);
        	}
        	self::$_options = $hours;            
        }
        return self::$_options;
    }

}
