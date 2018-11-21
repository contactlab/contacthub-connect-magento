<?php
class Contactlab_Hub_Model_Adminhtml_System_Config_Source_Cron_Frequency
{

    protected static $_options;

    const CRON_MINUTES	= 'I';
    const CRON_HOURLY	= 'H';
    const CRON_DAILY    = 'D';
    const CRON_WEEKLY   = 'W';
    const CRON_MONTHLY  = 'M';

    public function toOptionArray()
    {
        if (!self::$_options) {
            self::$_options = array(
          		array(
       				'label' => Mage::helper('cron')->__('Minutes'),
       				'value' => self::CRON_MINUTES,
           		),
            	array(
       				'label' => Mage::helper('cron')->__('Hourly'),
       				'value' => self::CRON_HOURLY,
           		),
                array(
                    'label' => Mage::helper('cron')->__('Daily'),
                    'value' => self::CRON_DAILY,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Weekly'),
                    'value' => self::CRON_WEEKLY,
                ),
                array(
                    'label' => Mage::helper('cron')->__('Monthly'),
                    'value' => self::CRON_MONTHLY,
                ),
            );
        }
        return self::$_options;
    }

}
