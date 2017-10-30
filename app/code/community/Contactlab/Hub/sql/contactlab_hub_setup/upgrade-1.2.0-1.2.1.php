<?php

$installer = $this;
$installer->startSetup();
$salesOrderTable = $installer->getTable('sales/order');

$installer->getConnection()
->addColumn($salesOrderTable,'contactlab_hub_exported', array(
		'type'      => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
		'nullable'  => false,
		'default'	=> 0,
		'comment'   => 'Exported to Contactlab Hub'
));
$installer->endSetup();