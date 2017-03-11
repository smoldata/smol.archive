<?php

	include('include/init.php');
	loadlib('twitter_api');
	loadlib('twitter_status');
	
	if ($GLOBALS['cfg']['user']){
		$GLOBALS['smarty']->display('page_flow.txt');
	} else {
		$GLOBALS['smarty']->display('page_about.txt');
	}
