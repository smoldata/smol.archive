<?php

	include('include/init.php');

	$smarty->assign('crumb_auth_twitter', 'auth_twitter');
	$GLOBALS['smarty']->display('page_archive.txt');
