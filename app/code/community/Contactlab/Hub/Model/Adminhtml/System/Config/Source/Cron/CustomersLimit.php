<?php
class Contactlab_Hub_Model_Adminhtml_System_Config_Source_Cron_CustomersLimit
{
    protected static $_options;

    public function toOptionArray()
    {
        if (!self::$_options) {
        	$limit = array();
        	for ($i=100; $i< 10001; $i+=100)
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
