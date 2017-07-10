<?php
class Tilkor_Deal_Block_Adminhtml_Deal extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_deal';
    $this->_blockGroup = 'deal';
    $this->_headerText = Mage::helper('deal')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('deal')->__('Add Item');
    parent::__construct();
  }
}