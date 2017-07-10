<?php

class Tilkor_Deal_Block_Adminhtml_Deal_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('deal_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('deal')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('deal')->__('Item Information'),
          'title'     => Mage::helper('deal')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('deal/adminhtml_deal_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}