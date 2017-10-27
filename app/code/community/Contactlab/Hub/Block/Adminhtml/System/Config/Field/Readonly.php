<?php 
class Contactlab_Hub_Block_Adminhtml_System_Config_Field_Readonly
extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        // The core <depends> tag functionality only works for fields containing proper input elements
        $html = '<input id="'.$element->getHtmlId().'" hidden="hidden" />';
        $html .= $element->getValue();
        $html .= $element->getAfterElementHtml();
        return $html;
    }

}