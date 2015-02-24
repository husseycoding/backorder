<?php
class HusseyCoding_Backorder_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $accepted = $this->getRequest()->getPost('accepted');
        $accepted = !empty($accepted) && $accepted == 'true' ? true : false;
        
        Mage::getSingleton('customer/session')->setBackorderAccepted($accepted);
        
        if ($itemids = $this->getRequest()->getPost('itemids')):
            $itemids = explode(',', $itemids);
            Mage::getSingleton('customer/session')->setBackorderAcceptedIds($itemids);
        endif;
        
    }
}
