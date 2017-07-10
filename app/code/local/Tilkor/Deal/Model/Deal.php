<?php

class Tilkor_Deal_Model_Deal extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('deal/deal');
    }
}