<?php

	include('include/init.php');
	loadlib('twitter_api');
	loadlib('twitter_users');
	
	if ($GLOBALS['cfg']['user']){
		$twitter_accounts = twitter_users_get_accounts($GLOBALS['cfg']['user']);
		$GLOBALS['smarty']->assign_by_ref('twitter_accounts', $twitter_accounts);
		$GLOBALS['smarty']->display('page_home.txt');
	} else {
		$GLOBALS['smarty']->display('page_signup.txt');
	}
