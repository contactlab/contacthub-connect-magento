<?php 
class Contactlab_Hub_Block_Adminhtml_System_Config_Field_Readonly
extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
  //    die(var_dump($element));
        $html = '<textarea id="'.$element->getHtmlId().'" readonly name="'.$element->getName().'" '.$element->serialize($element->getHtmlAttributes()).' >';
        $html .= $element->getEscapedValue();
        $html .= "</textarea>";
        $html .= $element->getAfterElementHtml();
        return $html;
    }

}