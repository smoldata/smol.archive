<?php

	include('include/init.php');
	
	if ($GLOBALS['cfg']['user']){
		$GLOBALS['smarty']->display('page_flow.txt');
	} else {
		$GLOBALS['smarty']->display('page_about.txt');
	}
