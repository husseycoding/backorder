<?php
class HusseyCoding_Backorder_Block_Estimate extends Mage_Core_Block_Template
{
    private $_ids = array();
    private $_product;
    private $_typeid;
    private $_childids = array();
    private $_bundleids = array();
    
    public function isEnabled()
    {
        return Mage::helper('backorder')->isEnabled();
    }
    
    public function acceptEnabled()
    {
        if (Mage::helper('backorder')->acceptEnabled()):
            return 'true';
        endif;
        
        return 'false';
    }
    
    public function getProductEstimate()
    {
        if ($this->_getProductType() == 'grouped'):
            return $this->_getGroupedEstimates($this->_getProduct());
        elseif ($this->_getProductType() == 'configurable'):
            return $this->_getConfigurableEstimates($this->_getProduct());
        elseif ($this->_getProductType() == 'bundle'):
            return $this->_getBundleEstimates($this->_getProduct());
        endif;
        
        return $this->_getEstimateDate($this->_getProduct());
    }
    
    private function _getProduct()
    {
        if (!isset($this->_product)):
            $this->_product = Mage::registry('current_product');
        endif;
        
        return $this->_product;
    }
    
    private function _getProductType()
    {
        if (!isset($this->_typeid)):
            $this->_typeid = $this->_getProduct()->getTypeId();
        endif;
        
        return $this->_typeid;
    }
    
    public function getProductType()
    {
        return $this->_getProductType();
    }
    
    private function _getGroupedEstimates($product)
    {
        $ids = Mage::getModel('catalog/product_type_grouped')->getChildrenIds($product->getId());
        
        return $this->_getEstimatesByIds($ids);
    }
    
    private function _getConfigurableEstimates($product)
    {
        $ids = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($product->getId());
        
        return $this->_getEstimatesByIds($ids);
    }
    
    private function _getBundleEstimates($product)
    {
        $estimates = array();
        $optionCollection = $product->getTypeInstance(true)->getOptionsCollection($product);
        $selectionCollection = $product->getTypeInstance(true)->getSelectionsCollection(
            $product->getTypeInstance(true)->getOptionsIds($product),
            $product
        );

        $options = $optionCollection->appendSelections($selectionCollection);
        foreach ($options as $option):
            if ($selections = $option->getSelections()):
                $optionid = $option->getId();
                foreach ($selections as $selection):
                    $selectionid = $selection->getSelectionId();
                    $simpleproduct = Mage::getModel('catalog/product')->load($selection->getId());
                    if ($estimate = $this->_getEstimateDate($simpleproduct)):
                        $estimates[$optionid][$selectionid]['estimate'] = $estimate;
                        $estimates[$optionid][$selectionid]['epoch'] = strtotime($estimate);
                    endif;
                endforeach;
            endif;
        endforeach;
        
        if (!empty($estimates)):
            return $estimates;
        endif;
        
        return false;
    }
    
    private function _getEstimatesByIds($ids)
    {
        foreach ($ids as $group):
            foreach ($group as $id):
                $product = Mage::getModel('catalog/product')->load($id);
                $estimate = $this->_getEstimateDate($product);
                if ($estimate):
                    $estimates[$product->getId()] = $estimate;
                endif;
            endforeach;
        endforeach;

        if (!empty($estimates)):
            return $estimates;
        endif;

        return false;
    }
    
    private function _getEstimateDate($product)
    {
        return Mage::helper('backorder')->getEstimatedDispatch($product);
    }
    
    public function getCartEstimates()
    {
        $return = array();
        if ($cart = Mage::getModel('checkout/cart')->getQuote()):
            $empty = true;
            foreach ($cart->getAllItems() as $item):
                if ($parent = $item->getParentItemId()):
                    $this->_childids[$parent][] = $item;
                else:
                    $this->_ids[] = $item->getId();
                    if ($item->getProductType() == 'configurable'):
                        if ($option = $item->getOptionByCode('simple_product')):
                            if ($estimate = $this->_getEstimateDate($option->getProduct())):
                                $return[] = $estimate;
                                $empty = false;
                            else:
                                $return[] = '';
                            endif;
                        endif;
                    elseif ($item->getProductType() == 'bundle'):
                        $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                        $bundleid = $item->getId();
                        $this->_bundleids[] = $bundleid;
                        $return[$bundleid] = '';
                        $empty = false;
                    else:
                        if ($estimate = $this->_getEstimateDate($item->getProduct())):
                            $return[] = $estimate;
                            $empty = false;
                        else:
                            $return[] = '';
                        endif;
                    endif;
                endif;
            endforeach;
            
            foreach ($this->_bundleids as $bundleid):
                $estimates = array();
                if (isset($this->_childids[$bundleid])):
                    foreach ($this->_childids[$bundleid] as $childitem):
                        if ($estimate = $this->_getEstimateDate($childitem->getProduct())):
                            $epoch = strtotime($estimate);
                            $estimates[$epoch] = $estimate;
                        endif;
                    endforeach;
                    if (!empty($estimates)):
                        ksort($estimates);
                        $return[$bundleid] = end($estimates);
                    endif;
                endif;
            endforeach;
            
            if (!$empty):
                return '["' . implode('","', $return) . '"]';
            endif;
        endif;
        
        return '[]';
    }
    
    public function getItemIds()
    {
        if (!empty($this->_ids)):
            return '["' . implode('","', $this->_ids) . '"]';
        endif;
        
        return '[]';
    }
    
    public function getHasAccepted()
    {
        if (Mage::getSingleton('customer/session')->getBackorderAccepted()):
            return 'true';
        endif;
            
        return 'false';
    }
    
    public function getAcceptedIds()
    {
        if ($ids = Mage::getSingleton('customer/session')->getBackorderAcceptedIds()):
            return '["' . implode('","', $ids) . '"]';
        endif;
        
        return '[]';
    }
    
    public function isProduct()
    {
        if (Mage::registry('current_product')):
            return 'true';
        endif;
        
        return 'false';
    }
    
    public function isCart()
    {
        $request = Mage::app()->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        if ($module == 'checkout' && $controller = 'cart' && $action = 'index'):
            return 'true';
        endif;
        
        return 'false';
    }
    
    public function getCutOff()
    {
        $cutoff = Mage::helper('backorder')->getCutOff();
        $return = strtotime($cutoff);
        $now = Mage::getModel('core/date')->timestamp();
        if ($now >= $return):
            $return = strtotime('tomorrow ' . $cutoff);
        endif;
        
        return $return;
    }
    
    public function getOrderBefore()
    {
        return Mage::helper('backorder')->getOrderBefore();
    }
}
