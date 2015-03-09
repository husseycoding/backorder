<?php
class HusseyCoding_Backorder_Helper_Data extends Mage_Core_Helper_Abstract
{
    private $_dashcount;
    
    public function isEnabled()
    {
        return Mage::getStoreConfig('backorder/general/enabled');
    }
    
    public function acceptEnabled()
    {
        return Mage::getStoreConfig('backorder/general/agreement');
    }
    
    public function hasAccepted()
    {
        $needsaccept = false;
        if ($cart = Mage::getModel('checkout/cart')->getQuote()):
            foreach ($cart->getAllItems() as $item):
                $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                if ($product->getBackorder()):
                    $needsaccept = true;
                    break;
                endif;
            endforeach;
        endif;
        
        if ($needsaccept):
            return Mage::getSingleton('customer/session')->getBackorderAccepted() ? true : false;
        endif;
        
        return true;
    }
    
    public function getEstimatedDispatch($product)
    {
        if (!empty($product) && $product->getId()):
            $product = Mage::getModel('catalog/product')->load($product->getId());
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            if ($this->_areManagingStock($stock)):
                if (!Mage::getStoreConfig('backorder/general/ignorestock')):
                    if ($stock->getQty() > 0 && $stock->getIsInStock()):
                        return false;
                    else:
                        if (!$stock->getBackorders()):
                            return false;
                        endif;
                    endif;
                endif;
            else:
                if (!Mage::getStoreConfig('backorder/general/notmanaged')):
                    return false;
                endif;
            endif;
            $backorder = $product->getBackorder();
            if (!empty($backorder)):
                $backorder = str_replace(' and ', ' + ', $backorder);
                if ($time = strtotime($backorder)):
                    return $this->_getEstimateString($time);
                endif;
            endif;
        endif;
        
        return false;
    }
    
    private function _areManagingStock($stock)
    {
        if ($stock->getUseConfigManageStock()):
            if (Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_ITEM . 'manage_stock')):
                return true;
            endif;
        else:
            if ($stock->getManageStock()):
                return true;
            endif;
        endif;
        
        return false;
    }
    
    private function _getEstimateString($time, $format = true)
    {
        $estimate = new Zend_Date($time, Zend_Date::TIMESTAMP);
        $digit = $estimate->get(Zend_Date::WEEKDAY_8601);
        if (($estimate->get(Zend_Date::WEEKDAY_8601) > 5) || ($estimate->isLater($this->_getCutOff(), Zend_Date::TIMES))):
            $estimate->set(strtotime('+1 weekday', $estimate->toString(Zend_Date::TIMESTAMP)), Zend_Date::TIMESTAMP);
        endif;
        $estimate = $this->_setDateAfterHolidays($estimate);
        $estimate = $estimate->toString(Zend_Date::TIMESTAMP);
        if ($format):
            $estimate = $this->_createDateString($estimate);
            if (!empty($estimate)):
                return $estimate;
            endif;
        endif;
        
        return !empty($estimate) ? $estimate : false;
    }
    
    public function createDateString($timestamp)
    {
        return $this->_createDateString($timestamp);
    }
    
    private function _createDateString($timestamp)
    {
        return date('l, jS F', $timestamp);
    }
    
    private function _getCutOff()
    {
        if ($time = Mage::getStoreConfig('backorder/general/cutoff')):
            if ($time = strtotime($time)):
                if ($time = date('H:i:s', $time)):
                    return $time;
                endif;
            endif;
        endif;
        
        return '17:00:00';
    }
    
    public function getCutOff()
    {
        return $this->_getCutOff();
    }
    
    public function getOrderBefore()
    {
        return Mage::getStoreConfig('backorder/general/orderbefore');
    }
    
    private function _setDateAfterHolidays($estimate)
    {
        $holidays = $this->_getHolidayDates();
        while (in_array($this->_getCurrent($estimate), $holidays)):
            $estimate->set(strtotime('+1 weekday', $estimate->toString(Zend_Date::TIMESTAMP)), Zend_Date::TIMESTAMP);
        endwhile;
        
        return $estimate;
    }
    
    private function _getCurrent($estimate)
    {
        $current = $estimate->toString(Zend_Date::TIMESTAMP);
        return date('Y-m-d', $current);
    }
    
    private function _getHolidayDates()
    {
        $fixedholidays = $this->_getValidDates(Mage::getStoreConfig('backorder/general/fixed_holidays'), 2);
        $dynamicholidays = $this->_getValidDates(Mage::getStoreConfig('backorder/general/dynamic_holidays'), 3);
        
        $thisyear = date('Y', Mage::getModel('core/date')->timestamp());
        $nextyear = date('Y', strtotime($thisyear . ' +1 year'));
        $holidays = array();
        
        foreach ($fixedholidays as $holiday):
            $holiday = explode('-', $holiday);
            $holidays[] = strtotime($thisyear . '-' . end($holiday) . '-' . reset($holiday));
            $holidays[] = strtotime($nextyear . '-' . end($holiday) . '-' . reset($holiday));
        endforeach;
        
        foreach ($dynamicholidays as $holiday):
            $parts = explode('-', $holiday);
            $day = (int) array_shift($parts);
            $week = array_shift($parts);
            $week = ltrim($week, '0');
            $month = (int) array_shift($parts);
            
            foreach (array($thisyear, $nextyear) as $year):
                if ($week == 'last'):
                    $weekcount = 0;
                    $daycount = date('t', mktime(0, 0, 0, $month, 1, $year));
                    for ($i = 1; $i <= $daycount; $i++):
                        $weekday = date('N', mktime(0, 0, 0, $month, $i, $year));
                        if ($weekday == $day):
                            $weekcount++;
                        endif;
                    endfor;
                    $week = $weekcount;
                endif;
                
                $earliest = (7 * ($week - 1)) + 1;
                $weekday = date("N", mktime(0, 0, 0, $month, $earliest, $year));
                
                if ($day == $weekday):
                    $offset = 0;
                elseif ($day < $weekday):
                    $offset = $day + (7 - $weekday);
                else:
                    $offset = ($day + (7 - $weekday)) - 7;
                endif;
                
                $date = mktime(0, 0, 0, $month, $earliest + $offset, $year);
                $holidays[] = $date;
            endforeach;
        endforeach;
        
        $return = array();
        foreach ($holidays as $date):
            $return[] = date('Y-m-d', $date);
        endforeach;

        return $return;
    }
    
    private function _getValidDates($config, $dashcount)
    {
        if (!empty($config)):
            $this->_dashcount = $dashcount;
            $dates = explode(',', $config);
            $dates = array_filter($dates, array($this, '_validateFormat'));
            if (!empty($dates)):
                return $dates;
            endif;
        endif;
        
        return array();
    }
    
    private function _validateFormat($date)
    {
        $date = trim($date);
        if (!empty($date)):
            switch ($this->_dashcount):
                case 2:
                    if (preg_match('/^[0-9]{1,2}-[0-9]{1,2}$/', $date)):
                        return true;
                    endif;
                    break;
                case 3:
                    if (preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{1,2}$/', $date) || preg_match('/^[0-9]{1,2}-last-[0-9]{1,2}$/', $date)):
                        return true;
                    endif;
                    break;
            endswitch;
        endif;
        
        return false;
    }
    
    public function getNoLeadTimestamp()
    {
        $now = Mage::getModel('core/date')->timestamp();
        
        return $this->_getEstimateString($now, false);
    }
}
