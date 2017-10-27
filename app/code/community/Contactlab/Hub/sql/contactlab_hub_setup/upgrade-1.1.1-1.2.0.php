<?php
$installer = $this;
$installer->startSetup();

// Event_data is now used to store all the event properties
$installer->run("
ALTER TABLE {$installer->getTable("contactlab_hub/event")}
    CHANGE COLUMN `event_data` `event_data` VARCHAR(2048) CHARACTER SET 'utf8' COLLATE 'utf8_bin' NOT NULL DEFAULT '';
");

$installer->endSetup();
