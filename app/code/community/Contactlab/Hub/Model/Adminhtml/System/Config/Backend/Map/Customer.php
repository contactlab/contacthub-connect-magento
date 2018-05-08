<?php

class Contactlab_Hub_Model_Adminhtml_System_Config_Backend_Map_Customer
    extends Mage_Adminhtml_Model_System_Config_Backend_Serialized
{
    const XML_PATH_HUB_FIELD_ATTRIBUTE = 'customer_mapping';

    /**
     * @return Mage_Core_Model_Abstract|void
     */
    protected function _beforeSave()
    {
        $_value = $this->getValue();
        unset($_value[static::XML_PATH_HUB_FIELD_ATTRIBUTE][-1]);
        $startOne = array_combine(range(1, count($_value[static::XML_PATH_HUB_FIELD_ATTRIBUTE])),
            array_values($_value[static::XML_PATH_HUB_FIELD_ATTRIBUTE]));
        $_value[static::XML_PATH_HUB_FIELD_ATTRIBUTE] = $startOne;
        $this->setValue($_value);
        parent::_beforeSave();
    }
}