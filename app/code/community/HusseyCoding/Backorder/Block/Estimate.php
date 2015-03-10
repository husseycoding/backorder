<?php
class HusseyCoding_Backorder_Block_Estimate extends Mage_Core_Block_Template
{
    private $_ids = array();
    private $_product;
    private $_typeid;
    private $_childids = array();
    private $_bundleids = array();
    private $_nolead;
    private $_helper;
    private $_showorderbefore = array();
    private $_cartproducttypes = array();
    
    public function isEnabled()
    {
        return $this->_getHelper()->isEnabled();
    }
    
    public function acceptEnabled()
    {
        if ($this->_getHelper()->acceptEnabled()):
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
        return $this->_getEstimatesByIds($this->_getGroupedIds($product));
    }
    
    private function _getGroupedIds($product)
    {
        return Mage::getModel('catalog/product_type_grouped')->getChildrenIds($product->getId());
    }
    
    private function _getConfigurableEstimates($product)
    {
        return $this->_getEstimatesByIds($this->_getConfigurableIds($product));
    }
    
    private function _getConfigurableIds($product)
    {
        return Mage::getModel('catalog/product_type_configurable')->getChildrenIds($product->getId());
    }
    
    private function _getBundleEstimates($product)
    {
        $estimates = array();
        foreach ($this->_getBundleOptions($product) as $option):
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
    
    private function _getBundleOptions($product)
    {
        $optioncollection = $product->getTypeInstance(true)->getOptionsCollection($product);
        $selectioncollection = $product->getTypeInstance(true)->getSelectionsCollection(
            $product->getTypeInstance(true)->getOptionsIds($product),
            $product
        );
        
        return $optioncollection->appendSelections($selectioncollection);
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
        return $this->_getHelper()->getEstimatedDispatch($product);
    }
    
    public function getCartEstimates()
    {
        $return = array();
        $notmanaged = Mage::getStoreConfig('backorder/general/notmanaged');
        if ($cart = Mage::getModel('checkout/cart')->getQuote()):
            $empty = true;
            foreach ($cart->getAllItems() as $item):
                if ($parent = $item->getParentItemId()):
                    $this->_childids[$parent][] = $item;
                else:
                    $this->_ids[] = $item->getId();
                    $this->_cartproducttypes[] = $item->getProductType();
                    if ($item->getProductType() == 'configurable'):
                        if ($option = $item->getOptionByCode('simple_product')):
                            if ($estimate = $this->_getEstimateDate($option->getProduct())):
                                $return[] = $estimate;
                                $empty = false;
                                $this->_showorderbefore[] = false;
                            else:
                                $return[] = '';
                                if (!$this->_getHelper()->areManagingStock($option->getProduct())):
                                    if (!$notmanaged):
                                        $this->_showorderbefore[] = false;
                                    else:
                                        $this->_showorderbefore[] = true;
                                    endif;
                                else:
                                    $this->_showorderbefore[] = true;
                                endif;
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
                            $this->_showorderbefore[] = false;
                        else:
                            $return[] = '';
                            if (!$this->_getHelper()->areManagingStock($item->getProduct())):
                                if (!$notmanaged):
                                    $this->_showorderbefore[] = false;
                                else:
                                    $this->_showorderbefore[] = true;
                                endif;
                            else:
                                $this->_showorderbefore[] = true;
                            endif;
                        endif;
                    endif;
                endif;
            endforeach;
            
            foreach ($this->_bundleids as $bundleid):
                $showbundlebefore = false;
                $estimates = array();
                if (isset($this->_childids[$bundleid])):
                    foreach ($this->_childids[$bundleid] as $childitem):
                        if ($estimate = $this->_getEstimateDate($childitem->getProduct())):
                            $epoch = strtotime($estimate);
                            $estimates[$epoch] = $estimate;
                        elseif (!$showbundlebefore):
                            if (!$this->_getHelper()->areManagingStock($childitem->getProduct())):
                                if ($notmanaged):
                                    $showbundlebefore = true;
                                endif;
                            else:
                                $showbundlebefore = true;
                            endif;
                        endif;
                    endforeach;
                    if (!empty($estimates)):
                        ksort($estimates);
                        $return[$bundleid] = end($estimates);
                        $this->_showorderbefore[] = false;
                    else:
                        $this->_showorderbefore[] = $showbundlebefore;
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
    
    public function getCartShowOrderBefore()
    {
        if (!empty($this->_showorderbefore)):
            return '["' . implode('","', $this->_showorderbefore) . '"]';
        endif;
        
        return '[]';
    }
    
    public function getCartProductTypes()
    {
        if (!empty($this->_cartproducttypes)):
            return '["' . implode('","', $this->_cartproducttypes) . '"]';
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
        $cutoff = $this->_getHelper()->getCutOff();
        $return = strtotime($cutoff);
        $now = Mage::getModel('core/date')->timestamp();
        if ($now >= $return):
            $return = strtotime('tomorrow ' . $cutoff);
        endif;
        
        return $return;
    }
    
    public function getOrderBefore()
    {
        return $this->_getHelper()->getOrderBefore();
    }
    
    public function getNoLeadTimestamp()
    {
        if (!isset($this->_nolead)):
            $this->_nolead = $this->_getHelper()->getNoLeadTimestamp();
        endif;
        
        return $this->_nolead;
    }
    
    public function getNoLeadDateString()
    {
        if (!isset($this->_nolead)):
            $this->getNoLeadTimestamp();
        endif;
        
        $date = $this->_getHelper()->createDateString($this->_nolead);
        if (!empty($date)):
            return $date;
        endif;
        
        return false;
    }
    
    public function getProductShowOrderBefore()
    {
        $notmanaged = Mage::getStoreConfig('backorder/general/notmanaged');
        if ($this->_getProductType() == 'grouped'):
            $ids = $this->_getGroupedIds($this->_getProduct());
            $orderbefore = $this->_getOrderBeforeByIds($ids, $notmanaged);
        elseif ($this->_getProductType() == 'configurable'):
            $ids = $this->_getConfigurableIds($this->_getProduct());
            $orderbefore = $this->_getOrderBeforeByIds($ids, $notmanaged);
        elseif ($this->_getProductType() == 'bundle'):
            $orderbefore = array();
            $options = $this->_getBundleOptions($this->_getProduct());
            foreach ($options as $option):
                if ($selections = $option->getSelections()):
                    $optionid = $option->getId();
                    foreach ($selections as $selection):
                        $selectionid = $selection->getSelectionId();
                        $product = Mage::getModel('catalog/product')->load($selection->getId());
                        $orderbefore[$optionid][$selectionid] = true;
                        if (!$this->_getHelper()->areManagingStock($product)):
                            if (!$notmanaged):
                                $orderbefore[$optionid][$selectionid] = false;
                            endif;
                        endif;
                    endforeach;
                endif;
            endforeach;
        endif;
        
        if (!empty($orderbefore)):
            return Mage::helper('core')->jsonEncode($orderbefore);
        endif;
        
        if (!$this->_getHelper()->areManagingStock($this->_getProduct())):
            if (!$notmanaged):
                return 'false';
            endif;
        endif;
        
        return 'true';
    }
    
    private function _getOrderBeforeByIds($ids, $notmanaged)
    {
        $orderbefore = array();
        foreach ($ids as $group):
            foreach ($group as $id):
                $product = Mage::getModel('catalog/product')->load($id);
                $orderbefore[$product->getId()] = true;
                if (!$this->_getHelper()->areManagingStock($product)):
                    if (!$notmanaged):
                        $orderbefore[$product->getId()] = false;
                    endif;
                endif;
            endforeach;
        endforeach;

        return $orderbefore;
    }
    
    private function _getHelper()
    {
        if (!isset($this->_helper)):
            $this->_helper = Mage::helper('backorder');
        endif;
        
        return $this->_helper;
    }
}
