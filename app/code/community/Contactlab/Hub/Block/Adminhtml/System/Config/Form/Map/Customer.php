<?php

class Contactlab_Hub_Block_Adminhtml_System_Config_Form_Map_Customer
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    const XML_PATH_HUB_FIELD = 'contactlab_hub_customer';
    const XML_PATH_HUB_FIELD_ATTRIBUTE = 'customer_mapping';
    const XML_PATH_HUB_FIELD_SOURCE_MODEL = 'contactlab_hub/adminhtml_system_config_source_customer_attributes';
    const XML_JS_FUNCTION_NAME = 'ContactlabHubMapCustomer';

    protected $_addRowButtonHtml = array();
    protected $_removeRowButtonHtml = array();

    protected $_rows = 0;

    /**
     * Returns add new field Customer element
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $fieldId = $this->getElement()->getId();

        $html = '<div id="'.$fieldId.'">
                    <table style="display:none"><tbody id="'.static::XML_PATH_HUB_FIELD.'_mapping_template">';
        $html .= $this->_getRowTemplateHtml(-1);
        $html .= '</tbody></table>';

        $html .= '<table></div>';
        $html .= '<thead><tr>';
        $html .= '<th>' . $this->__('Hub Attribute') . '</th><th>' . $this->__('Hub Type') .
            '</th><th>' . $this->__('Magento Attribute') . '</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody id="'.static::XML_PATH_HUB_FIELD.'_mapping_container">';

        $count = 1;
        if ($this->_getValue(static::XML_PATH_HUB_FIELD_ATTRIBUTE))
        {
            for ($i = count($this->_getValue(static::XML_PATH_HUB_FIELD_ATTRIBUTE)); $i > 0; $i--)
            {
                if(!$this->_getValue(static::XML_PATH_HUB_FIELD_ATTRIBUTE . '/' . $count))
                {
                    $count++;
                }
                $html .= $this->_getRowTemplateHtml($count);
                $count++;
            }
        }

        $html .= '</tbody></table>';
        $html .= $this->_getAddRowButtonHtml();

        $html .= "<script type=\"text/javascript\">
                    var ".static::XML_JS_FUNCTION_NAME."RowGenerator = function() {
                      this.count = $count;
                    };

                    ".static::XML_JS_FUNCTION_NAME."RowGenerator.prototype.add = function() {
                      var html = $('".static::XML_PATH_HUB_FIELD."_mapping_template').innerHTML;
                      html = html.replace(
                        /\[".static::XML_PATH_HUB_FIELD_ATTRIBUTE."\]\[-1\]/g,
                        '[".static::XML_PATH_HUB_FIELD_ATTRIBUTE."][' + (this.count++) + ']'
                      );
                      Element.insert(
                        $('".static::XML_PATH_HUB_FIELD."_mapping_container'),
                        {bottom: html}
                      );
                    };

                    var ".static::XML_JS_FUNCTION_NAME."RowGenerator = new ".static::XML_JS_FUNCTION_NAME."RowGenerator();
                 </script>";

        return $html;
    }

    /**
     * Retrieve html template for setting
     *
     * @param int $rowIndex
     * @return string
     */
    protected function _getRowTemplateHtml($rowIndex = null)
    {
        $value = $rowIndex !== null ? (array) $this->_getValue(static::XML_PATH_HUB_FIELD_ATTRIBUTE . '/' . $rowIndex) : array();
        $value += array('hub_attribute' => '','hub_type' => '' , 'magento_attribute' => '');

        $html = '<tr>';
        $html .= '<td>';
        $html .= '<input name="'
            . $this->getElement()->getName() . '['.static::XML_PATH_HUB_FIELD_ATTRIBUTE.'][' . $rowIndex . '][hub_attribute]" value="'
            . $value['hub_attribute'] . '" ' . $this->_getDisabled() . '/> ';
        $html .= '</td><td>';
        $html .= '<select name="' . $this->getElement()->getName();
        $html .= '['. static::XML_PATH_HUB_FIELD_ATTRIBUTE .'][' . $rowIndex . '][hub_type]" ';
        $html .= $this->_getDisabled() . '>';
        $types = Mage::getSingleton('contactlab_hub/adminhtml_system_config_source_customer_type');
        foreach ($types->toOptionArray() as $type)
        {
            if(is_array($type['value']))
            {
                $html .= '<optgroup label="'.$type['label'].'">';
                foreach ($type['value'] as $opt)
                {
                    //var_dump($label, $value);
                    $html .= '<option value="' . $opt['value'] . '"';
                    $html .= ($value['hub_type'] == $opt['value'] ? 'selected="selected"' : '');
                    $html .= '>' . $opt['label'] . '</option>';
                }
                $html .= '</optgroup>';
            }
            else
            {
                $html .= '<option value="' . $type['value'] . '"';
                $html .= ($value['hub_type'] == $type['value'] ? 'selected="selected"' : '');
                $html .= '>' . $type['label'] . '</option>';
            }
        }
        $html .= '</select> ';
        $html .= '</td><td>';
        $html .= '<select name="' . $this->getElement()->getName();
        $html .= '['. static::XML_PATH_HUB_FIELD_ATTRIBUTE .'][' . $rowIndex . '][magento_attribute]" ';
        $html .= $this->_getDisabled() . '>';

        $attributes = Mage::getSingleton(static::XML_PATH_HUB_FIELD_SOURCE_MODEL);

        foreach ($attributes->toOptionArray() as $option)
        {
            if(is_array($option['value']))
            {
                $html .= '<optgroup label="'.$option['label'].'">';
                foreach ($option['value'] as $opt)
                {
                    //var_dump($label, $value);
                    $html .= '<option value="' . $opt['value'] . '"';
                    $html .= ($value['magento_attribute'] == $opt['value'] ? 'selected="selected"' : '');
                    $html .= '>' . $opt['label'] . '</option>';
                }
                $html .= '</optgroup>';
            }
            else
            {
                $html .= '<option value="' . $option['value'] . '"';
                $html .= ($value['magento_attribute'] == $option['value'] ? 'selected="selected"' : '');
                $html .= '>' . $option['label'] . '</option>';
            }
        }

        $html .= '</select> ';
        $html .= '</td><td>';
        $html .= $this->_getRemoveRowButtonHtml();
        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }

    protected function _getDisabled()
    {
        return $this->getElement()->getDisabled() ? 'disabled' : '';
    }

    protected function _getValue($key)
    {
        return $this->getElement()->getData('value/' . $key);
    }

    protected function _getSelected($key, $value)
    {
        return $this->getElement()->getData('value/' . $key) == $value ? 'selected="selected"' : '';
    }

    protected function _getAddRowButtonHtml()
    {
        $container = isset($container) ? $container : null;

        if (!isset($this->_addRowButtonHtml[$container])) {
            $_cssClass = 'add';

            if (version_compare(Mage::getVersion(), '1.6', '<')) {
                $_cssClass .= ' ' . $this->_getDisabled();
            }

            $this->_addRowButtonHtml[$container] = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setType('button')
                ->setClass($_cssClass)
                ->setLabel($this->__('Add'))
                ->setOnClick(static::XML_JS_FUNCTION_NAME."RowGenerator.add()")
                ->setDisabled($this->_getDisabled())
                ->toHtml();
        }

        return $this->_addRowButtonHtml[$container];
    }

    protected function _getRemoveRowButtonHtml()
    {
        if (!$this->_removeRowButtonHtml) {
            // @codingStandardsIgnoreStart
            $_cssClass = 'delete v-middle';
            // @codingStandardsIgnoreEnd

            if (version_compare(Mage::getVersion(), '1.6', '<')) {
                $_cssClass .= ' ' . $this->_getDisabled();
            }

            $this->_removeRowButtonHtml = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setType('button')
                ->setClass($_cssClass)
                ->setLabel($this->__('Delete'))
                ->setOnClick("Element.remove($(this).up('tr'))")
                ->setDisabled($this->_getDisabled())
                ->toHtml();
        }

        return $this->_removeRowButtonHtml;
    }
    
}
