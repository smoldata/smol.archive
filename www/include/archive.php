<?php

	include('include/init.php');
	loadlib('smol_accounts');

	$accounts = smol_accounts_get_accounts($GLOBALS['cfg']['user']);

	$smarty->assign('crumb_auth_twitter', 'auth_twitter');

	$GLOBALS['smarty']->display('page_archive.txt');
