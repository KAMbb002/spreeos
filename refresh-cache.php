<?php
 error_reporting(E_ALL); ini_set('display_errors', 1); 
   $mageFilename=$_SERVER['DOCUMENT_ROOT'].'/app/Mage.php';
    
   require_once $mageFilename;
   Mage::app()->getCacheInstance()->flush(); 
   echo "Cache Refresh.";
?>
