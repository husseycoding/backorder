<?php
$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE `{$this->getTable('sales/order_item')}` ADD `backorder_estimate` TIMESTAMP NULL;
");

$installer->endSetup();