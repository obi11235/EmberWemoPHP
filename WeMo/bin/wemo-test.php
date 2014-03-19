<?php

	define('EXCEPTION_LOG_FILE', 'error_log');
	require_once('/var/www/ember/system/include/common.inc.php');
	#Debug::enable();
	
	$ip = Site::getSetting('wemo_ip'); 
	
	$switch = new WeMo_Switch($ip);

	echo 'State: '.$switch->getState().PHP_EOL;
	$switch->flipSwitch();
	sleep(3);
	echo 'State: '.$switch->getState().PHP_EOL;
	$switch->flipSwitch();
	sleep(3);

	$continue = true;
	while($continue)
	{
		if($switch->switchChanged())
		{
			echo 'Fliped: '.$switch->getState().PHP_EOL;
			$continue = false;
		}
		sleep(3);
	}

	echo 'Done'.PHP_EOL;
