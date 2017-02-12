<?php

	include('include/init.php');

	if ($GLOBALS['cfg']['user']) {
		$GLOBALS['smarty']->display('page_home.txt');
	} else {
		$GLOBALS['smarty']->display('page_signin.txt');
	}
