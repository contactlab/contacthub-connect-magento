<?php
class Contactlab_Hub_Model_Adminhtml_System_Config_Source_Customer_Type
{
    public function toOptionArray()
    {
        $options[] = array('label' => Mage::helper('contactlab_hub')->__('Select an attribute'), 'value' => '');
        $options[] = array('label' => Mage::helper('contactlab_hub')->__('Base'), 'value' => 'base');
        $options[] = array('label' => Mage::helper('contactlab_hub')->__('Consents'), 'value' => 'consents');
        $options[] = array('label' => Mage::helper('contactlab_hub')->__('Extended'), 'value' => 'extended');

        return  $options;
    }

}
