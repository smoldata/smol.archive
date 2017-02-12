<?php

	include('include/init.php');
	loadlib('twitter_api');
	loadlib('twitter_users');
	
	if ($GLOBALS['cfg']['user']){
		$twitter_accounts = twitter_users_get_accounts($GLOBALS['cfg']['user']);
		foreach ($twitter_accounts as $index => $account){
			$info = twitter_api_get($account, 'users/show', array(
				'user_id' => $account['twitter_id']
			));
			$twitter_accounts[$index] = array_merge($info, $account);
		}
		$GLOBALS['smarty']->assign_by_ref('twitter_accounts', $twitter_accounts);
		$GLOBALS['smarty']->display('page_home.txt');
	} else {
		$GLOBALS['smarty']->display('page_signup.txt');
	}
