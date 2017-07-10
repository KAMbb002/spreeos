<?php
class Tilkor_Deal_Block_Deal extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getDeal()     
     { 
        if (!$this->hasData('deal')) {
            $this->setData('deal', Mage::registry('deal'));
        }
        return $this->getData('deal');
        
    }
}