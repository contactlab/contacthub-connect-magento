<?php
/**
 * Created by PhpStorm.
 * User: ildelux
 * Date: 15/10/18
 * Time: 16:39
 */

class Contactlab_Hub_Block_Adminhtml_Logs_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct()
    {
        parent::__construct();
        $this->setId('grid_id');
        // $this->setDefaultSort('COLUMN_ID');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('contactlab_hub/event')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id',
                array(
                    'header'        => Mage::helper('contactlab_hub')->__('Store View'),
                    'index'         => 'store_id',
                    'type'          => 'store',
                    'store_all'     => true,
                    'store_view'    => true,
                    'sortable'      => false,
                    'filter_condition_callback' => array($this, '_filterStoreCondition'),
                )
            );
        }
        $this->addColumn('name',
           array(
               'header'=> Mage::helper('contactlab_hub')->__('Name'),
               'align'     => 'left',
               'index' => 'name'
           )
        );
        $this->addColumn('identity_email',
            array(
                'header'=> Mage::helper('contactlab_hub')->__('Email'),
                'align'     => 'left',
                'index' => 'identity_email'
            )
        );
        $this->addColumn('session_id',
            array(
                'header'=> Mage::helper('contactlab_hub')->__('Session'),
                'align'     => 'left',
                'index' => 'session_id'
            )
        );
        $this->addColumn('env_remote_ip',
            array(
                'header'=> Mage::helper('contactlab_hub')->__('Remote IP'),
                'align'     => 'left',
                'index' => 'env_remote_ip'
            )
        );
        $this->addColumn('created_at',
            array(
                'header'=> Mage::helper('contactlab_hub')->__('Created At'),
                'type' => 'datetime',
                'align'     => 'left',
                'index' => 'created_at'
            )
        );
        $this->addColumn('status',
            array(
                'header'=> Mage::helper('contactlab_hub')->__('Status'),
                'type' => 'options',
                'index' => 'status',
                'options' => Mage::getSingleton('contactlab_hub/adminhtml_system_config_source_eventStatus')->toArray(),
                'renderer'  => 'Contactlab_Hub_Block_Adminhtml_Logs_Grid_Renderer_Status',
                'width' => '50px',
                'align'     => 'center',
            )
        );

        $this->addExportType('*/*/exportCsv', $this->__('CSV'));

        $this->addExportType('*/*/exportExcel', $this->__('Excel XML'));
        
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
       return false;
    }

    protected function _prepareMassaction()
    {
        $modelPk = Mage::getModel('contactlab_hub/event')->getResource()->getIdFieldName();
        $this->setMassactionIdField($modelPk);
        $this->getMassactionBlock()->setFormFieldName('ids');
        // $this->getMassactionBlock()->setUseSelectAll(false);
        $this->getMassactionBlock()->addItem('delete', array(
             'label'=> $this->__('Delete'),
             'url'  => $this->getUrl('*/*/massDelete'),
        ));
        return $this;
    }

    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
    }
}
