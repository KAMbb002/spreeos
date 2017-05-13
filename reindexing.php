<?php
 $mageFilename=$_SERVER['DOCUMENT_ROOT'].'/app/Mage.php';
 require_once $mageFilename;
 $app = Mage::app('admin');
 umask(0);
 for ($index = 1; $index <= 8; $index++) {
     $process = Mage::getModel('index/process')->load($index);
     $process->reindexAll();
 }
 ?>