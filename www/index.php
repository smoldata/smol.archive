<?php

	include('include/init.php');
	loadlib('twitter_api');
	loadlib('twitter_users');
	loadlib('twitter_tweet');
	
	if ($GLOBALS['cfg']['user']){
		$twitter_accounts = twitter_users_get_accounts($GLOBALS['cfg']['user']);
		$GLOBALS['smarty']->assign_by_ref('twitter_accounts', $twitter_accounts);
		$rsp = db_fetch("
			SELECT *
			FROM twitter_tweet
		");
		$status = json_decode($rsp['rows'][0]['json'], 'as hash');
		$tweet_html = twitter_tweet_content($status);
		$GLOBALS['smarty']->assign('tweet', $tweet_html);
		$GLOBALS['smarty']->display('page_home.txt');
	} else {
		$GLOBALS['smarty']->display('page_signup.txt');
	}
