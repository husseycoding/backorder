<?php
class HusseyCoding_Backorder_Block_Notify_Items extends Mage_Core_Block_Template
{
    public function getBackorderItems()
    {
        return Mage::registry('backorder_items');
    }
}