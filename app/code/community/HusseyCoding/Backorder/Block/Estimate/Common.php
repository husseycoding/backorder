<?php
class HusseyCoding_Backorder_Block_Estimate_Common extends Mage_Core_Block_Template
{
    public function getOrderEstimates()
    {
        $estimates = array();
        $order = Mage::registry('current_order');
        foreach ($order->getAllVisibleItems() as $item):
            if ($estimate = $item->getBackorderEstimate()):
                if ($epoch = strtotime($estimate)):
                    if ($string = date('l, jS F', $epoch)):
                        $estimates[$item->getId()] = $string;
                    endif;
                endif;
            endif;
            if (!isset($estimates[$item->getId()])):
                $estimates[$item->getId()] = '';
            endif;
        endforeach;
        
        return Mage::helper('core')->jsonEncode($estimates);
    }
    
    public function isEnabled()
    {
        return Mage::helper('backorder')->isEnabled();
    }
}
