<?php
class HusseyCoding_Backorder_Model_Observer
{
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
    }
}
