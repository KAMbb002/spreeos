<?php

class Tilkor_Deal_Block_Adminhtml_Deal_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      parent::__construct();
      $this->setId('dealGrid');
      $this->setDefaultSort('deal_id');
      $this->setDefaultDir('ASC');
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      $collection = Mage::getModel('deal/deal')->getCollection();
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      $this->addColumn('deal_id', array(
          'header'    => Mage::helper('deal')->__('ID'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'deal_id',
      ));

      $this->addColumn('name', array(
          'header'    => Mage::helper('deal')->__('name'),
          'align'     =>'left',
          'index'     => 'name',
      ));
	   /*$this->addColumn('identifier', array(
          'header'    => Mage::helper('deal')->__('identifier'),
          'align'     =>'left',
          'index'     => 'identifier',
      ));
*/

	
      $this->addColumn('url', array(
			'header'    => Mage::helper('deal')->__('url'),
			'width'     => '150px',
			'index'     => 'url',
      ));
	 

      /*$this->addColumn('status', array(
          'header'    => Mage::helper('deal')->__('deal type '),
          'align'     => 'left',
          'width'     => '80px',
          'index'     => 'status',
          'type'      => 'options',
          'options'   => array(
              1 => 'day deal',
              2 => 'spreeos sale',
			  3=>'editor pics'
          ),
		  
      ));*/
	  
        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('deal')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('deal')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
		
		$this->addExportType('*/*/exportCsv', Mage::helper('deal')->__('CSV'));
		$this->addExportType('*/*/exportXml', Mage::helper('deal')->__('XML'));
	  
      return parent::_prepareColumns();
  }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('deal_id');
        $this->getMassactionBlock()->setFormFieldName('deal');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('deal')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('deal')->__('Are you sure?')
        ));

        $statuses = Mage::getSingleton('deal/status')->getOptionArray();

        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('deal')->__('Change status'),
             'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('deal')->__('Status'),
                         'values' => $statuses
                     )
             )
        ));
        return $this;
    }

  public function getRowUrl($row)
  {
      return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }

}