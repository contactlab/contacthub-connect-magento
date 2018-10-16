<?php
/**
 * Created by PhpStorm.
 * User: ildelux
 * Date: 15/10/18
 * Time: 16:39
 */
class Contactlab_Hub_Block_Adminhtml_Logs extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct()
    {
        $this->_blockGroup      = 'contactlab_hub';
        $this->_controller      = 'adminhtml_logs';
         $this->_headerText      = $this->__('Contactlab Hub Logs');
        // $this->_addButtonLabel  = $this->__('Add Button Label');
        parent::__construct();
            }

    public function getCreateUrl()
    {
        return $this->getUrl('*/*/new');
    }

}

