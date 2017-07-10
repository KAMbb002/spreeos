<?php

class Tilkor_Deal_Block_Adminhtml_Deal_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('deal_form', array('legend'=>Mage::helper('deal')->__('Item information')));
     
      $fieldset->addField('name', 'text', array(
          'label'     => Mage::helper('deal')->__('Name'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'name',
      ));
	 

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('deal')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('deal')->__('select deal type'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('deal')->__('deal of day'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('deal')->__('deal of month'),
              ),
          ),
      ));
     
      $fieldset->addField('url', 'editor', array(
          'name'      => 'url',
          'label'     => Mage::helper('deal')->__('url'),
          'title'     => Mage::helper('deal')->__('url'),
          'style'     => 'width:100px; height:50px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getDealData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getDealData());
          Mage::getSingleton('adminhtml/session')->setDealData(null);
      } elseif ( Mage::registry('deal_data') ) {
          $form->setValues(Mage::registry('deal_data')->getData());
      }
      return parent::_prepareForm();
  }
}