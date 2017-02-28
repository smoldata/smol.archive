<?php

	include('include/init.php');
	loadlib('twitter_api');
	loadlib('twitter_users');
	loadlib('twitter_status');
	
	if ($GLOBALS['cfg']['user']){

		$twitter_accounts = twitter_users_get_accounts($GLOBALS['cfg']['user']);
		$GLOBALS['smarty']->assign_by_ref('twitter_accounts', $twitter_accounts);
		$rsp = db_fetch("
			SELECT s.*
			FROM twitter_status AS s, twitter_archive AS a
			WHERE a.type = 'statuses_user_timeline'
			  AND a.status_id = s.id
			ORDER BY s.created_at DESC
			LIMIT 36
		");

		$tweets = $rsp['rows'];
		foreach ($tweets as $index => $tweet){
			$status = json_decode($tweet['json'], 'as hash');
			if ($status['retweeted_status']){
				$status = $status['retweeted_status'];
				$tweets[$index]['screen_name'] = $status['user']['screen_name'];
				$tweets[$index]['retweeted'] = true;
			}
			$tweets[$index]['html'] = twitter_status_content($status);
			$tweets[$index]['profile_image'] = twitter_status_profile_image($status);
			$tweets[$index]['display_name'] = $status['user']['name'];
			$tweets[$index]['permalink'] = twitter_status_permalink($status);
		}

		$GLOBALS['smarty']->assign_by_ref('tweets', $tweets);
		$GLOBALS['smarty']->display('page_home.txt');
	} else {
		$GLOBALS['smarty']->display('page_signup.txt');
	}
