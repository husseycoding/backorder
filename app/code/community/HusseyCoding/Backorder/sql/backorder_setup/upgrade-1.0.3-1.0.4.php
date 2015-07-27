<?php
$installer = $this;
$installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'backorder_handling', array(
    'type' => 'varchar',
    'label' => 'Backorder Handling Time',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible' => true,
    'required' => false,
    'user_defined' => true,
    'searchable' => false,
    'filterable' => false,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'apply_to' => 'simple,configurable,bundle,grouped',
    'is_configurable' => false
));

foreach ($installer->getAllAttributeSetIds('catalog_product') as $setid):
    $attributeid = $installer->getAttributeId('catalog_product', 'backorder_handling');
    $groupid = $installer->getDefaultAttributeGroupId('catalog_product', $setid);
    $installer->addAttributeToSet('catalog_product', $setid, $groupid, $attributeid);
endforeach;