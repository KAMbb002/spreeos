<?php

$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('deal')};
CREATE TABLE {$this->getTable('deal')} (
  `deal_id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
   `identifier` varchar(255) NOT NULL default '',
  `filename` varchar(255) NOT NULL default '',
  `url` text NOT NULL default '',
  `status` smallint(6) NOT NULL default '0',
  `created_time` datetime NULL,
  `update_time` datetime NULL,
  PRIMARY KEY (`deal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup(); 