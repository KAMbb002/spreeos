<?php
class Tilkor_Deal_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
    	
    	/*
    	 * Load an object by id 
    	 * Request looking like:
    	 * http://site.com/deal?id=15 
    	 *  or
    	 * http://site.com/deal/id/15 	
    	 */
    	/* 
		$deal_id = $this->getRequest()->getParam('id');

  		if($deal_id != null && $deal_id != '')	{
			$deal = Mage::getModel('deal/deal')->load($deal_id)->getData();
		} else {
			$deal = null;
		}	
		*/
		
		 /*
    	 * If no param we load a the last created item
    	 */ 
    	/*
    	if($deal == null) {
			$resource = Mage::getSingleton('core/resource');
			$read= $resource->getConnection('core_read');
			$dealTable = $resource->getTableName('deal');
			
			$select = $read->select()
			   ->from($dealTable,array('deal_id','title','content','status'))
			   ->where('status',1)
			   ->order('created_time DESC') ;
			   
			$deal = $read->fetchRow($select);
		}
		Mage::register('deal', $deal);
		*/

			
		$this->loadLayout();     
		$this->renderLayout();
    }
}