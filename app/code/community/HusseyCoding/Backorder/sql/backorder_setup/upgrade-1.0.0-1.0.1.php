<?php
$installer = $this;
foreach ($installer->getAllAttributeSetIds('catalog_product') as $setid):
    $attributeid = $installer->getAttributeId('catalog_product', 'backorder');
    $groupid = $installer->getDefaultAttributeGroupId('catalog_product', $setid);
    $installer->addAttributeToSet('catalog_product', $setid, $groupid, $attributeid);
endforeach;