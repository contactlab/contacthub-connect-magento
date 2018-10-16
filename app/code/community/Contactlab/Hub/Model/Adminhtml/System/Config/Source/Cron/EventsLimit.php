<?php
class Contactlab_Hub_Model_Adminhtml_System_Config_Source_Cron_EventsLimit
{
    protected static $_options;

    public function toOptionArray()
    {
        if (!self::$_options) {
        	$limit = array();
        	for ($i=10; $i< 1001; $i+=10)
        	{
        		$limit[] = array(
			       				'label' => $i,
			       				'value' => $i,
			           		);
        	}
        	self::$_options = $limit;            
        }
        return self::$_options;
    }

}
