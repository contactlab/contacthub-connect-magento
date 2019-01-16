<?php
$installer = $this;
$installer->startSetup();
if ($installer->tableExists($installer->getTable('contactlab_hub/previouscustomers'))) {
    $installer->getConnection()
        ->addIndex(
            $installer->getTable('contactlab_hub/previouscustomers'),
            $installer->getIdxName('contactlab_hub/previouscustomers', array('email'), Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
            array('email'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
        );
}
$installer->endSetup();