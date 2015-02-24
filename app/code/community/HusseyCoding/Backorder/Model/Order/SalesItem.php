<?php
class HusseyCoding_Backorder_Model_Order_SalesItem extends Mage_Sales_Model_Order_Item
{
    public function getName()
    {
        if ($this->_isOrderEmail()):
            if ($estimate = Mage::helper('backorder')->getEstimatedDispatch($this->getProduct())):
                return parent::getName() . ' - Estimated dispatch: ' . $estimate;
            endif;
        endif;
        
        return parent::getName();
    }
    
    private function _isOrderEmail()
    {
        if (Mage::registry('is_backorder_email')):
            return true;
        endif;
        
        return false;
    }
}
