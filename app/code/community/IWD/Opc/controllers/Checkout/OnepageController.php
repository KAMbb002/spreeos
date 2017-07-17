<?php
$iwd_av_class = false;

if (! Mage::helper ( 'opc' )->isEnable ()) {
	// check if IWD AddressValidation exists
	$path = Mage::getBaseDir ( 'app' ) . DS . 'code' . DS . 'local' . DS;
	$file = 'IWD/AddressVerification/controllers/OnepageController.php';
	// load IWD OPC class
	if (file_exists ( $path . $file )){		
		// check if IWD AV enabled
		if (Mage::helper ( 'addressverification' )->isAddressVerificationEnabled ()){
			$iwd_av_class = true;
		}
	}
}

if (! $iwd_av_class) {
	echo "starting......";
	require_once Mage::getModuleDir ( 'controllers', 'Mage_Checkout' ) . DS . 'OnepageController.php';
	class IWD_Opc_Checkout_OnepageController extends Mage_Checkout_OnepageController {

		echo "starting22222......";
		exit;
		/**
		 * Checkout page
		 */
		public function indexAction() {
			echo "11111";
			$scheme = Mage::app ()->getRequest ()->getScheme ();
			if ($scheme == 'http') {
				$secure = false;
			} else {
				$secure = true;
			}
			
			echo "22222222";

			if (Mage::helper ( 'opc' )->isEnable ()) {
				$this->_redirect ( 'onepage', array (
						'_secure' => $secure 
				) );

				echo "33333";exit;
				return;
			} else {
				echo "4444";exit;
				parent::indexAction ();
			}
		}
	}
} else {
	require_once Mage::getModuleDir ( 'controllers', 'IWD_AddressVerification' ) . DS . 'OnepageController.php';
	class IWD_Opc_Checkout_OnepageController extends IWD_AddressVerification_OnepageController {
	}
}
