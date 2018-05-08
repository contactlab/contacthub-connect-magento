<?php
class Contactlab_Hub_Model_Adminhtml_System_Config_Source_Customer_Attributes
{
    public function toOptionArray()
    {
        $options[] = array('label' => Mage::helper('contactlab_hub')->__('Select an attribute'), 'value' => '');

        $attributes1 = Mage::getModel('customer/entity_attribute_collection');
        $result1[] = array('label' => Mage::helper('contactlab_hub')->__('Customer Id'), 'value' => 'entity_id');
        $exclude = array();
        foreach ($attributes1 as $attribute1)
        {
            if(!in_array($attribute1->getAttributeCode(), $exclude))
            {
                //if ($attribute1->getIsUserDefined())
                if($attribute1->getFrontendLabel())
                {
                    $result1[] = array('label' => $attribute1->getFrontendLabel(), 'value' => $attribute1->getAttributeCode());
                    //$result1[] = array('label' => $attribute1->getAttributeCode(), 'value' => $attribute1->getAttributeCode());
                }
            }
        }

        if($result1)
        {
            $options[] =
                array(
                    'label' => Mage::helper('contactlab_hub')->__('Customer Attributes'),
                    'value' => $result1
                );
        }


        $attributes = Mage::getModel('customer/entity_address_attribute_collection');
        $result = array();
        $exclude = array('city', 'street', 'region', 'region_id', 'postcode', 'country', 'country_id');
        foreach ($attributes as $attribute)
        {
            if(!in_array($attribute->getAttributeCode(), $exclude))
            {
                //if ($attribute->getIsUserDefined())
                if($attribute->getFrontendLabel())
                {
                    $result[] = array('label' => $attribute->getFrontendLabel(), 'value' => $attribute->getAttributeCode());
                    //$result[] = array('label' => $attribute->getAttributeCode(), 'value' => $attribute->getAttributeCode());
                }
            }
        }

        if($result)
        {
            $options[] =
                array(
                    'label' => Mage::helper('contactlab_hub')->__('Customer Address Attributes'),
                    'value' => $result
                );
        }

        return  $options;
    }

}
