<?php
/**
 * Created by PhpStorm.
 * User: ildelux
 * Date: 15/10/18
 * Time: 17:20
 */

class Contactlab_Hub_Block_Adminhtml_Logs_Grid_Renderer_Status
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $options = Mage::getSingleton('contactlab_hub/adminhtml_system_config_source_eventStatus');
        $status = '';
        foreach ($options->toOptionArray() as $option)
        {
            if ($option['value'] == $value)
            {
                $status = $option['label'];
            }
        }
        return '<span class="contactlab-hub-event-status status'.$value.'" title ="'. $status.'"></span>';
    }
}