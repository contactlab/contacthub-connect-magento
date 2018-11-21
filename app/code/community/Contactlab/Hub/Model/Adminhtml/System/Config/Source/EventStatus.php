<?php
class Contactlab_Hub_Model_Adminhtml_System_Config_Source_EventStatus
{
    public function toOptionArray()
    {
        $options[] = array(
            'label' => Mage::helper('contactlab_hub')->__('Select an attribute'),
            'value' => ''
        );
        foreach ($this->toArray() as $key =>$value)
        {
            $options[] = array(
                'label' => $value,
                'value' => $key
            );
        }
        return  $options;
    }

    public function toArray()
    {
        $options = array();
        $options[Contactlab_Hub_Model_Event::CONTACTLAB_HUB_STATUS_TO_EXPORT] = Mage::helper('contactlab_hub')->__('Collected');
        $options[Contactlab_Hub_Model_Event::CONTACTLAB_HUB_STATUS_PROCESSING] = Mage::helper('contactlab_hub')->__('Running');
        $options[Contactlab_Hub_Model_Event::CONTACTLAB_HUB_STATUS_EXPORTED] = Mage::helper('contactlab_hub')->__('Exported');
        $options[Contactlab_Hub_Model_Event::CONTACTLAB_HUB_STATUS_FAILED] = Mage::helper('contactlab_hub')->__('Error');
        return $options;
    }
}
