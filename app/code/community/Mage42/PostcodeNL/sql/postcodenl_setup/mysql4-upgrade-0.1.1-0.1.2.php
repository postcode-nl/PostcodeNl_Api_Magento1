<?php

$installer = $this;
$installer->startSetup();
$connection = $installer->getConnection();

$connection->addColumn(
    $installer->getTable('sales_flat_order'),
    'used_postcodenl_api',
    [
        'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
        'length' => 1,
        'comment' => 'Shows if the PostcodeNL Api was used for this order'
    ]
);

$connection->addColumn(
    $installer->getTable('sales_flat_quote'),
    'used_postcodenl_api',
    [
        'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
        'length' => 1,
        'comment' => 'Shows if the PostcodeNL Api was used for this order'
    ]
);

$installer->endSetup();