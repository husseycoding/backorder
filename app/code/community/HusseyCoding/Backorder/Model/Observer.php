<?php
class HusseyCoding_Backorder_Model_Observer
{
    const XML_PATH_BACKORDER_NOTIFY_TEMPLATE = 'backorder/general/backorder_notify_template';
    const XML_PATH_BACKORDER_NOTIFY_RECIPIENT = 'trans_email/ident_sales/email';
    const XML_PATH_BACKORDER_NOTIFY_SENDER = 'trans_email/ident_sales/name';
    
    private $_helper;
    
    public function frontendControllerActionPredispatchCheckout($observer)
    {
        if (Mage::helper('backorder')->isEnabled() && Mage::helper('backorder')->acceptEnabled()):
            $controller = Mage::app()->getRequest()->getControllerName();
            if ($controller != 'cart' && $controller != 'index'):
                $helper = Mage::helper('backorder');
                if (!$helper->hasAccepted()):
                    $url = Mage::getUrl('checkout/cart');
                    $error = $helper->__('You must accept the estimated product dispatch date(s) to checkout.');
                    Mage::getSingleton('checkout/session')->addError($error);
                    $observer->getControllerAction()->getResponse()->setRedirect($url);
                endif;
            endif;
        endif;
    }
    
    public function frontendSalesOrderPlaceAfter($observer)
    {
        if (Mage::helper('backorder')->isEnabled() && Mage::helper('backorder')->acceptEnabled()):
            Mage::getSingleton('customer/session')->setBackorderAccepted(false);
            Mage::getSingleton('customer/session')->setBackorderAcceptedIds(array());
            Mage::register('is_backorder_email', true);
        endif;
        
        if (Mage::getStoreConfig('backorder/general/backorder_notify')):
            $count = 0;
            $backitems = array();
            foreach ($observer->getOrder()->getAllVisibleItems() as $item):
                if ($estimate = $item->getBackorderEstimate()):
                    $count++;
                    $sku = $item->getSku();
                    $name = $item->getName();
                    $estimate = strtotime($estimate);
                    $estimate = date('jS F', $estimate);
                    $backitems[] = array('name' => $name, 'estimate' => $estimate, 'sku' => $sku);
                endif;
            endforeach;

            if ($count):
                Mage::unregister('backorder_items');
                Mage::register('backorder_items', $backitems);
                $translate = Mage::getSingleton('core/translate');
                $translate->setTranslateInline(false);
                $mailTemplate = Mage::getModel('core/email_template');
                $mailTemplate
                    ->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo(Mage::getStoreConfig(self::XML_PATH_BACKORDER_NOTIFY_RECIPIENT))
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_BACKORDER_NOTIFY_TEMPLATE),
                        array('name' => Mage::getStoreConfig(self::XML_PATH_BACKORDER_NOTIFY_SENDER), 'email' => Mage::getStoreConfig(self::XML_PATH_BACKORDER_NOTIFY_RECIPIENT)),
                        Mage::getStoreConfig(self::XML_PATH_BACKORDER_NOTIFY_RECIPIENT),
                        Mage::getStoreConfig(self::XML_PATH_BACKORDER_NOTIFY_SENDER),
                        array(
                            'increment_id' => $observer->getOrder()->getIncrementId(),
                            'count' => $count,
                            'customer_name' => $observer->getOrder()->getCustomerName()
                        )
                );
                $translate->setTranslateInline(true);
            endif;
        endif;
    }
    
    public function globalSalesOrderPlaceAfter($observer)
    {
        $estimates = $this->_setOrderEstimates($observer->getOrder());
    }
    
    private function _setOrderEstimates($order)
    {
        $bundleitems = array();
        $childids = array();
        foreach ($order->getAllItems() as $item):
            if ($parent = $item->getParentItemId()):
                $childids[$parent][] = $item;
            else:
                if ($item->getProductType() == 'configurable'):
                    $sku = $item->getSku();
                    $product = Mage::getModel('catalog/product');
                    if ($productid = $product->getIdBySku($sku)):
                        $product->load($productid);
                        if ($product->getId()):
                            if ($estimate = $this->_getEstimateDate($product)):
                                if ($epoch = strtotime($estimate)):
                                    if ($timestamp = date('Y-m-d H:i:s', $epoch)):
                                        $item->setBackorderEstimate($timestamp);
                                    endif;
                                endif;
                            endif;
                        endif;
                    endif;
                elseif ($item->getProductType() == 'bundle'):
                    $bundleitems[] = $item;
                else:
                    if ($estimate = $this->_getEstimateDate($item->getProduct())):
                        if ($epoch = strtotime($estimate)):
                            if ($timestamp = date('Y-m-d H:i:s', $epoch)):
                                $item->setBackorderEstimate($timestamp);
                            endif;
                        endif;
                    endif;
                endif;
            endif;
        endforeach;

        foreach ($bundleitems as $bundleitem):
            $estimates = array();
            if (isset($childids[$bundleitem->getId()])):
                foreach ($childids[$bundleitem->getId()] as $childitem):
                    if ($estimate = $this->_getEstimateDate($childitem->getProduct())):
                        $epoch = strtotime($estimate);
                        $estimates[$epoch] = $estimate;
                    endif;
                endforeach;
                if (!empty($estimates)):
                    ksort($estimates);
                    $estimate = end($estimates);
                    if ($epoch = strtotime($estimate)):
                        if ($timestamp = date('Y-m-d H:i:s', $epoch)):
                            $bundleitem->setBackorderEstimate($timestamp);
                        endif;
                    endif;
                endif;
            endif;
        endforeach;
    }
    
    private function _getEstimateDate($product)
    {
        return $this->_getHelper()->getEstimatedDispatch($product);
    }
    
    private function _getHelper()
    {
        if (!isset($this->_helper)):
            $this->_helper = Mage::helper('backorder');
        endif;
        
        return $this->_helper;
    }
}
