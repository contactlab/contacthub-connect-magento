<?php
class Contactlab_Hub_Model_Adminhtml_System_Config_Source_Cron_ExtraProperties
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options)
        {
	        $attributes = Mage::getModel('customer/entity_address_attribute_collection');
	        $result = array();
	        foreach ($attributes as $attribute)
	        {
	        	if ($attribute->getIsUserDefined())
	        	{
	        		$result[] = array('label' => $attribute->getFrontendLabel(), 'value' => $attribute->getAttributeCode());
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
	        
	        $attributes1 = Mage::getModel('customer/entity_attribute_collection');
	        $result1 = array();
	        foreach ($attributes1 as $attribute1)
	        {
	        	if ($attribute1->getIsUserDefined())
	        	{	        	
	        		$result1[] = array('label' => $attribute1->getFrontendLabel(), 'value' => $attribute1->getAttributeCode());
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
        }  
        $this->_options = $options;
        return  $this->_options;        
    }        

}
