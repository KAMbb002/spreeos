<?php
class FacileCheckout_OnestepCheckout_IndexController extends Mage_Checkout_Controller_Action
{
	private $_current_layout = null;

    protected $_sectionUpdateFunctions = array(
    	'review'          => '_getReviewHtml',
    	'shipping-method' => '_getShippingMethodsHtml',
        'payment-method'  => '_getPaymentMethodsHtml',
    );
	
    public function preDispatch()
    {
        parent::preDispatch();
        $this->_preDispatchValidateCustomer();
        return $this;
    }

    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
            ->sendResponse();
        return $this;
    }

    protected function _expireAjax()
    {
        if (!$this->getOnestepcheckout()->getQuote()->hasItems()
            || $this->getOnestepcheckout()->getQuote()->getHasError()
            || $this->getOnestepcheckout()->getQuote()->getIsMultiShipping()) {
            $this->_ajaxRedirectResponse();
            return true;
        }
        $action = $this->getRequest()->getActionName();
        if (Mage::getSingleton('checkout/session')->getCartWasUpdated(true)
            && !in_array($action, array('index', 'progress'))) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        return false;
    }

    protected function _getUpdatedLayout()
    {$this->_initLayoutMessages('checkout/session');
        if ($this->_current_layout === null)
        {
            $layout = $this->getLayout();
            $update = $layout->getUpdate();            
            $update->load('onestepcheckout_index_updatecheckout');
            
            $layout->generateXml();
            $layout->generateBlocks();
            $this->_current_layout = $layout;
        }
        
        return $this->_current_layout;
    }
        
    protected function _getShippingMethodsHtml()
    {
    	$layout	= $this->_getUpdatedLayout();
        return $layout->getBlock('checkout.shipping.method')->toHtml();
    }

    protected function _getPaymentMethodsHtml()
    {
    	$layout	= $this->_getUpdatedLayout();
        return $layout->getBlock('checkout.payment.method')->toHtml();
    }

	protected function _getCouponDiscountHtml()
    {
    	$layout	= $this->_getUpdatedLayout();
        return $layout->getBlock('checkout.cart.coupon')->toHtml();
    }
    
    protected function _getReviewHtml()
    {
    	$layout	= $this->_getUpdatedLayout();
        return $layout->getBlock('checkout.review')->toHtml();
    }

    public function getOnestepcheckout()
    {
        return Mage::getSingleton('onestepcheckout/type_geo');
    }

    public function indexAction()
    {    	
        if (!Mage::helper('onestepcheckout')->isOnestepCheckoutEnabled())
        {
            Mage::getSingleton('checkout/session')->addError($this->__('The one page checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
                
        $quote = $this->getOnestepcheckout()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }

        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure'=>true)));

        $this->getOnestepcheckout()->initDefaultData()->initCheckout();
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $title	= Mage::getStoreConfig('onestepcheckout/general/title');
        $this->getLayout()->getBlock('head')->setTitle($title);
        $this->renderLayout();
    }
    
    public function successAction()
    {
    	$notallowed = $this->getRequest()->getParam('na', false);
    	if($notallowed)
    	{
			$this->_redirect('checkout/onepage');
			return;
    	}
    	
        $session = $this->getOnestepcheckout()->getCheckout();
        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }

       	$lastQuoteId = $session->getLastQuoteId();
        $lastOrderId = $session->getLastOrderId();
		$lastRecurringProfiles = $session->getLastRecurringProfileIds();

        /*if ($_SESSION['git_wrap'] == 'Yes') // Gift Wrap Condition
		{
		$writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
		//$query="UPDATE `sales_flat_order` SET `giftwrap` = '1',`heardus` = 'HEAR US FILED' WHERE `entity_id` ='$lastOrderId'";
		$query="UPDATE `sales_flat_order` SET `giftwrap` = '1' WHERE `entity_id` ='$lastOrderId'";
		$writeConnection->query($query);
		}
		// Gift Wrap Condition End*/
		
		if (!$lastQuoteId || (!$lastOrderId && empty($lastRecurringProfiles))) {
            $this->_redirect('checkout/cart');
            return;
        }
		
        $session->clear();
        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
		$this->renderLayout();
    }

    public function failureAction()
    {
        $lastQuoteId = $this->getOnestepcheckout()->getCheckout()->getLastQuoteId();
        $lastOrderId = $this->getOnestepcheckout()->getCheckout()->getLastOrderId();

        if (!$lastQuoteId || !$lastOrderId) {
            $this->_redirect('checkout/cart');
            return;
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    public function getAddressAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        $addressId = $this->getRequest()->getParam('address', false);
        if ($addressId) {
            $address = $this->getOnestepcheckout()->getAddress($addressId);

            if (Mage::getSingleton('customer/session')->getCustomer()->getId() == $address->getCustomerId()) {
                $this->getResponse()->setHeader('Content-type', 'application/x-json');
                $this->getResponse()->setBody($address->toJson());
            } else {
                $this->getResponse()->setHeader('HTTP/1.1','403 Forbidden');
            }
        }
    }
    
    public function updateCheckoutAction()
    {
        if ($this->_expireAjax() || !$this->getRequest()->isPost()) {
            return;
        }
		/*********** DISCOUNT CODES **********/
 
        $quote 				= $this->getOnestepcheckout()->getQuote();
        $couponData 		= $this->getRequest()->getPost('coupon', array());
        $processCoupon 		= $this->getRequest()->getPost('process_coupon', false);
       
        $couponChanged 		= false;
	    if ($couponData && $processCoupon) {
	            if (!empty($couponData['remove'])) {
	                $couponData['code'] = '';
	                 
	            }
	            $oldCouponCode = $quote->getCouponCode();
	            if ($oldCouponCode != $couponData['code']) {
	                try {
	                    $quote->setCouponCode(
	                        strlen($couponData['code']) ? $couponData['code'] : ''
	                    );
	                    $this->getRequest()->setPost('payment-method', true);
	                    $this->getRequest()->setPost('shipping-method', true);
	                    if ($couponData['code']) {
	                        $couponChanged = true;
	                    } else {
	                    	$couponChanged = true;
	                        Mage::getSingleton('checkout/session')->addSuccess($this->__('Coupon code was canceled.'));
	                    }
	                } catch (Mage_Core_Exception $e) {
	                	$couponChanged = true;
	                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
	                } catch (Exception $e) {
	                	$couponChanged = true;
	                    Mage::getSingleton('checkout/session')->addError($this->__('Cannot apply the coupon code.'));
	                }
	                
	            }
	        }
        
        /***********************************/ 
	        
        $bill_data = $this->getRequest()->getPost('billing', array());
        $bill_data = $this->_filterPostData($bill_data);
        $bill_addr_id = $this->getRequest()->getPost('billing_address_id', false);
        $result = array();
        $ship_updated = false;
        
        if ($this->_checkChangedAddress($bill_data, 'Billing', $bill_addr_id) || $this->getRequest()->getPost('payment-method', false))
        {
            if (isset($bill_data['email']))
            {
                $bill_data['email'] = trim($bill_data['email']);
            }
            
            $bill_result = $this->getOnestepcheckout()->saveBilling($bill_data, $bill_addr_id, false);

            if (!isset($bill_result['error']))
            {
                $pmnt_data = $this->getRequest()->getPost('payment', array());
                $this->getOnestepcheckout()->usePayment(isset($pmnt_data['method']) ? $pmnt_data['method'] : null);

                $result['update_section']['payment-method'] = $this->_getPaymentMethodsHtml();

                if (isset($bill_data['use_for_shipping']) && $bill_data['use_for_shipping'] == 1 && !$this->getOnestepcheckout()->getQuote()->isVirtual())
				{
                    $result['update_section']['shipping-method'] = $this->_getShippingMethodsHtml();
                    $result['duplicateBillingInfo'] = 'true';
                    
                    $ship_updated = true;
                }
            }
            else
            {
                $result['error_messages'] = $bill_result['message'];
            }
        }

        $ship_data = $this->getRequest()->getPost('shipping', array());
        $ship_addr_id = $this->getRequest()->getPost('shipping_address_id', false);
        $ship_method	= $this->getRequest()->getPost('shipping_method', false);

        if (!$ship_updated && !$this->getOnestepcheckout()->getQuote()->isVirtual())
        {
            if ($this->_checkChangedAddress($ship_data, 'Shipping', $ship_addr_id) || $ship_method) 
            {
                $ship_result = $this->getOnestepcheckout()->saveShipping($ship_data, $ship_addr_id, false);

                if (!isset($ship_result['error']))
                {
                    $result['update_section']['shipping-method'] = $this->_getShippingMethodsHtml();
                }
            }
        }

        $check_shipping_diff	= false;

        // check how many shipping methods exist
        $rates = Mage::getModel('sales/quote_address_rate')->getCollection()->setAddressFilter($this->getOnestepcheckout()->getQuote()->getShippingAddress()->getId())->toArray();
        if(count($rates['items'])==1)
        {
        	if($rates['items'][0]['code']!=$ship_method)
        	{
        		$check_shipping_diff	= true;

        		$result['reload_totals'] = 'true';
        	}
        }
        else        
			$check_shipping_diff	= true;

// get prev shipping method
		if($check_shipping_diff){
			$shipping = $this->getOnestepcheckout()->getQuote()->getShippingAddress();
			$shippingMethod_before = $shipping->getShippingMethod();
		}

        $this->getOnestepcheckout()->useShipping($ship_method);

        $this->getOnestepcheckout()->getQuote()->collectTotals()->save();

		if($check_shipping_diff){        
			$shipping = $this->getOnestepcheckout()->getQuote()->getShippingAddress();
			$shippingMethod_after = $shipping->getShippingMethod();
        
	        if($shippingMethod_before != $shippingMethod_after)
	        {
	        	$result['update_section']['shipping-method'] = $this->_getShippingMethodsHtml();
	        	$result['reload_totals'] = 'true';
	        }
	        else
	        	unset($result['reload_totals']);
        }
///////////////

        $result['update_section']['review'] = $this->_getReviewHtml();

        
        /*********** DISCOUNT CODES **********/
    	if ($couponChanged) {
            if ($couponData['code'] == $quote->getCouponCode()) {
                Mage::getSingleton('checkout/session')->addSuccess(
                    $this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponData['code']))
                );
            } else {
                Mage::getSingleton('checkout/session')->addError(
                    $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponData['code']))
                );
            }
            $method = str_replace(' ', '', ucwords(str_replace('-', ' ', 'coupon-discount')));          
            $result['update_section']['coupon-discount'] = $this->{'_get' . $method . 'Html'}();
           
        }
        /************************************/
        
        
        
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function forgotpasswordAction()
    {
        $session = Mage::getSingleton('customer/session');

        if ($this->_expireAjax() || $session->isLoggedIn()) {
            return;
        }

        $email = $this->getRequest()->getPost('email');
        $result = array('success' => false);
        
        if (!$email)
        {
			$result['error'] = Mage::helper('customer')->__('Please enter your email.');
        }
        else
        {
			if (!Zend_Validate::is($email, 'EmailAddress'))
			{
                $session->setForgottenEmail($email);
                $result['error'] = Mage::helper('checkout')->__('Invalid email address.');
            }
            else
            {
                $customer = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);
                if(!$customer->getId())
                {
                	$session->setForgottenEmail($email);
                    $result['error'] = Mage::helper('customer')->__('This email address was not found in our records.');
                }
                else
                {
                    try
                    {
						$new_pass = $customer->generatePassword();
                        $customer->changePassword($new_pass, false);
                        $customer->sendPasswordReminderEmail();
                        $result['success'] = true;
                        $result['message'] = Mage::helper('customer')->__('A new password has been sent.');
                    }
                    catch (Exception $e)
                    {
                        $result['error'] = $e->getMessage();
                    }
                }
            }
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function loginAction()
    {
        $session = Mage::getSingleton('customer/session');
        if ($this->_expireAjax() || $session->isLoggedIn()) {
            return;
        }

        $result = array('success' => false);

        if ($this->getRequest()->isPost())
        {
            $login_data = $this->getRequest()->getPost('login');
            if (empty($login_data['username']) || empty($login_data['password'])) {
            	$result['error'] = Mage::helper('customer')->__('Login and password are required.');
            }
            else
            {
				try
				{
                    $session->login($login_data['username'], $login_data['password']);
                    $result['success'] = true;
                    $result['redirect'] = Mage::getUrl('*/*/index');
                }
                catch (Mage_Core_Exception $e)
                {
                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $message = Mage::helper('customer')->__('Email is not confirmed. <a href="%s">Resend confirmation email.</a>', Mage::helper('customer')->getEmailConfirmationUrl($login_data['username']));
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                    $result['error'] = $message;
                    $session->setUsername($login_data['username']);
                }
            }
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveOrderAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
		
	
				
			
		
	$_SESSION['git_wrap']= $_POST['giftwrap']; // DEEPAK
	//exit;
	
		/*if ($_SESSION['git_wrap'] == 'Yes')
		
		{
			echo "DDDD";
			echo '<pre>';
			print_r($_SESSION);
			$_totals = Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
			$giftwrap_admin_price=(Mage::getStoreConfig('onestepcheckout/giftwrap/giftwrapoutput'));
			echo "TOTAL PRICE ::".$_totals;
			$base_grand_price_totals = $_totals + $giftwrap_admin_price; 
			echo "ADDEDD PRICE : ".$base_grand_price_totals;
			echo '</pre>';
			exit;
			
		}

		else
		{*/
        	$result = array();
        	try {
            $bill_data = $this->_filterPostData($this->getRequest()->getPost('billing', array()));
			$result = $this->getOnestepcheckout()->saveBilling($bill_data,$this->getRequest()->getPost('billing_address_id', false),true,true);
            if ($result)
            {
            	$result['error_messages'] = $result['message'];
            	$result['error'] = true;
                $result['success'] = false;
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }

            if ((!$bill_data['use_for_shipping'] || !isset($bill_data['use_for_shipping'])) && !$this->getOnestepcheckout()->getQuote()->isVirtual())
            {
				$result = $this->getOnestepcheckout()->saveShipping($this->_filterPostData($this->getRequest()->getPost('shipping', array())),$this->getRequest()->getPost('shipping_address_id', false), true, true);
                if ($result)
                {
                	$result['error_messages'] = $result['message'];
                	$result['error'] = true;
                    $result['success'] = false;
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }

            $agreements = Mage::helper('onestepcheckout')->getAgreeIds();
            if($agreements)
            {
				$post_agree = array_keys($this->getRequest()->getPost('agreement', array()));
				$is_different = array_diff($agreements, $post_agree);
                if ($is_different)
                {
                	$result['error_messages'] = Mage::helper('checkout')->__('Please agree to all the terms and conditions.');
                	$result['error'] = true;
                    $result['success'] = false;
                    
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }
            
            $result = $this->_saveOrderPurchase();

            if($result && !isset($result['redirect']))
            {
                $result['error_messages'] = $result['error'];
            }

            if(!isset($result['error']))
            {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request'=>$this->getRequest(), 'quote'=>$this->getOnestepcheckout()->getQuote()));
                $this->_subscribeNews();
            }

            Mage::getSingleton('customer/session')->setOrderCustomerComment($this->getRequest()->getPost('order-comment'));

            if (!isset($result['redirect']) && !isset($result['error']))
            {
            	$pmnt_data = $this->getRequest()->getPost('payment', false);
                if ($pmnt_data)
                    $this->getOnestepcheckout()->getQuote()->getPayment()->importData($pmnt_data);

                $this->getOnestepcheckout()->saveOrder();
                $redirectUrl = $this->getOnestepcheckout()->getCheckout()->getRedirectUrl();

                $result['success'] = true;
                $result['error']   = false;
                $result['order_created'] = true;
            }
        }
        catch (Mage_Core_Exception $e)
        {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnestepcheckout()->getQuote(), $e->getMessage());

            $result['error_messages'] = $e->getMessage();
            $result['error'] = true;
            $result['success'] = false;

            $goto_section = $this->getOnestepcheckout()->getCheckout()->getGotoSection();
            if ($goto_section)
            {
            	$this->getOnestepcheckout()->getCheckout()->setGotoSection(null);
                $result['goto_section'] = $goto_section;
            }

            $update_section = $this->getOnestepcheckout()->getCheckout()->getUpdateSection();
            if ($update_section)
            {
                if (isset($this->_sectionUpdateFunctions[$update_section]))
                {
                    $layout = $this->_getUpdatedLayout();

                    $updateSectionFunction = $this->_sectionUpdateFunctions[$update_section];
                    $result['update_section'] = array(
                        'name' => $update_section,
                        'html' => $this->$updateSectionFunction()
                    );
                }
                $this->getOnestepcheckout()->getCheckout()->setUpdateSection(null);
            }
			
            $this->getOnestepcheckout()->getQuote()->save();
        } 
        catch (Exception $e)
        {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnestepcheckout()->getQuote(), $e->getMessage());
            $result['error_messages'] = Mage::helper('checkout')->__('There was an error processing your order. Please contact support or try again later.');
            $result['error']    = true;
            $result['success']  = false;
            
            $this->getOnestepcheckout()->getQuote()->save();
        }

        if (isset($redirectUrl)) {
            $result['redirect'] = $redirectUrl;
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
		
		//}
    }

    protected function _saveOrderPurchase()
    {
    	$result = array();
    	
        try 
        {
            $pmnt_data = $this->getRequest()->getPost('payment', array());
            $result = $this->getOnestepcheckout()->savePayment($pmnt_data);

//print_r("pmnt_data".$result['fields']);exit;

            $redirectUrl = $this->getOnestepcheckout()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if ($redirectUrl)
            {
                $result['redirect'] = $redirectUrl;
            }
        }
        catch (Mage_Payment_Exception $e)
        {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        }
        catch (Mage_Core_Exception $e)
        {
            $result['error'] = $e->getMessage();
        }
        catch (Exception $e)
        {
            Mage::logException($e);
            $result['error'] = Mage::helper('checkout')->__('Unable to set Payment Method.');
        }
        return $result;
    }

    protected function _subscribeNews()
    {
        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('newsletter'))
        {
            $customerSession = Mage::getSingleton('customer/session');

            if($customerSession->isLoggedIn())
            	$email = $customerSession->getCustomer()->getEmail();
            else
            {
            	$bill_data = $this->getRequest()->getPost('billing');
            	$email = $bill_data['email'];
            }

            try {
                if (!$customerSession->isLoggedIn() && Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1)
                    Mage::throwException(Mage::helper('newsletter')->__('Sorry, subscription for guests is not allowed. Please <a href="%s">register</a>.', Mage::getUrl('customer/account/create/')));

                $ownerId = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email)->getId();
                
                if ($ownerId !== null && $ownerId != $customerSession->getId())
                    Mage::throwException(Mage::helper('newsletter')->__('Sorry, you are trying to subscribe email assigned to another user.'));

                $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
            }
            catch (Mage_Core_Exception $e) {
            }
            catch (Exception $e) {
            }
        }
    }

    protected function _filterPostData($data)
    {
        $data = $this->_filterDates($data, array('dob'));
        return $data;
    }
    
    protected function _checkChangedAddress($data, $addr_type = 'Billing', $addr_id = false)
    {
    	$method	= "get{$addr_type}Address";
        $address = $this->getOnestepcheckout()->getQuote()->{$method}();

        if(!$addr_id)
        {
        	if(($address->getRegionId()	!= $data['region_id']) || ($address->getPostcode() != $data['postcode']) || ($address->getCountryId() != $data['country_id']))
        		return true;
        	else
        		return false;
        }
        else{
        	if($addr_id != $address->getCustomerAddressId())
        		return true;
        	else
        		return false;
        }
    }
	
	// For OTP
	public function sendOtpAction(){
		$telephone = $this->getRequest()->getParam('telephone');
		$mobile = 9312439157;
		$otp = mt_rand(100000, 999999);;
		$generatedDate = date('Y-m-d H:i:s');
		$expireDate = date('Y-m-d H:i:s', strtotime("+10 minutes"));
		
		$resource = Mage::getSingleton('core/resource');
		$writeConnection = $resource->getConnection('core_write');
		$tableName = $resource->getTableName('otp_verification');
		
		$sqlDelete = "Delete FROM {$tableName} Where mobile='{$mobile}'";
		$writeConnection->query($sqlDelete);
		
		$query = "Insert Into {$tableName} (id, mobile, otp, generated_date, expire_date) Values ('','{$mobile}','{$otp}','{$generatedDate}','{$expireDate}')";
		$writeConnection->query($query);
		$this->sendOtpVeryfication($mobile, $otp);
    }
	
	public function sendOtpVeryfication($mobile, $otp){
		echo $mobile ."---". $otp;
    }
	
	public function verifyOtpAction(){
		$mobile = $this->getRequest()->getParam('telephone');
		$mobile = 9312439157;
		$otp = 107387;
		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');
		$tableName = $resource->getTableName('otp_verification');
		
		$currentDate = date('Y-m-d H:i:s');
		$query = "Select * FROM {$tableName} where (mobile='{$mobile}') and (otp='{$otp}' and expire_date>='{$currentDate}')";
		$result = $readConnection->fetchAll($query);
		
		$count = count($result);
		if($count == 0 ){
			echo "Errro";
		} else {
			echo "success";
		}

    }
	
	// End
}
