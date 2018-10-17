<?php
/**
 * Created by PhpStorm.
 * User: giovanni.colangelo
 * Date: 20/05/2016
 * Time: 11:47
 */
class Contactlab_Hub_Block_System_Config_Form_Field_Tags extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('tagname', array(
            'label' => Mage::helper('adminhtml')->__('Tag'),
            'style' => 'width:100%',
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add tag');
        parent::__construct();
    }
}