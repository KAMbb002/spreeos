update core_config_data set `value`='http://magento1.codazon.com/fastest/' 
	where `path`='web/unsecure/base_url';
update core_config_data set `value`='http://magento1.codazon.com/fastest/' 
	where `path`='web/secure/base_url';
update `admin_user` SET `username` = 'codazon', `first_failure` = NULL, `lock_expires` = NULL WHERE `admin_user`.`username` = 'admin';
update core_config_data set `value`='<script>
  (function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,\'script\',\'https://www.google-analytics.com/analytics.js\',\'ga\');

  ga(\'create\', \'UA-77341792-1\', \'auto\');
  ga(\'send\', \'pageview\');

</script>' 
	where `path`='design/head/includes';
