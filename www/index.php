<?php

	include('include/init.php');
	loadlib('twitter_api');
	loadlib('twitter_users');
	loadlib('twitter_status');
	
	if ($GLOBALS['cfg']['user']){

		$twitter_accounts = twitter_users_get_accounts($GLOBALS['cfg']['user']);
		$GLOBALS['smarty']->assign_by_ref('twitter_accounts', $twitter_accounts);
		$rsp = db_fetch("
			SELECT twitter_status.*
			FROM twitter_status, twitter_archive
			WHERE twitter_archive.status_id = twitter_status.id
			ORDER BY twitter_status.created_at DESC
			LIMIT 36
		");

		$tweets = $rsp['rows'];
		foreach ($tweets as $index => $status){
			$raw_status = json_decode($status['json'], 'as hash');
			if ($raw_status['retweeted_status']){
				$id = $raw_status['retweeted_status']['id_str'];
				$rsp = twitter_status_get_by_id($twitter_accounts[0], $id);
				//dumper($rsp);
				$raw_status = $rsp['status'];
				$tweets[$index]['screen_name'] = $raw_status['user']['screen_name'];
				$tweets[$index]['retweeted'] = true;
			}
			$tweets[$index]['html'] = twitter_status_content($raw_status);
			$tweets[$index]['profile_image'] = twitter_status_profile_image($raw_status);
			$tweets[$index]['display_name'] = $raw_status['user']['name'];
			$tweets[$index]['permalink'] = twitter_status_permalink($raw_status);
			//dumper($tweets[$index]['json']);
		}

		$GLOBALS['smarty']->assign_by_ref('tweets', $tweets);
		$GLOBALS['smarty']->display('page_home.txt');
	} else {
		$GLOBALS['smarty']->display('page_signup.txt');
	}
