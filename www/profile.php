<?php

	include('include/init.php');
	loadlib('users');
	loadlib('smol_accounts');

	$username = get_str('username');
	$esc_username = addslashes($username);
	$rsp = db_fetch_accounts("
		SELECT *
		FROM users
		WHERE username = '$esc_username'
	");

	if (! $rsp['rows']){
		error_404();
	}

	$user = $rsp['rows'][0];
	$smarty->assign_by_ref('user', $user);

	$accounts = smol_accounts_get_accounts($user);
	$smarty->assign_by_ref('accounts', $accounts);

	if (empty($accounts) && $user['id'] == $GLOBALS['cfg']['user']['id']){
		# setup current user's accounts
		$smarty->assign('crumb_auth_twitter', 'auth_twitter');
		$GLOBALS['smarty']->display('page_archive.txt');
		exit;
	}
	
	if (empty($accounts)){
		$smarty->assign('no_accounts', 1);
	}

	$GLOBALS['smarty']->display('page_profile.txt');

	/*
	
	$args = array(
		'count_fields' => '*'
	);

	$page = get_int32('page');
	if ($page){
		$args['page'] = $page;
	}

	$per_page = get_int32('per_page');
	if ($per_page && $per_page > 0 && $per_page <= 1000){
		$args['per_page'] = $per_page;
	} else {
		$per_page = $GLOBALS['cfg']['pagination_per_page'];
	}
	
	$rsp = db_fetch_paginated("
		SELECT s.* FROM twitter_status AS s, twitter_archive AS a
		WHERE a.type = 'statuses_user_timeline'
		  AND a.status_id = s.id
		ORDER BY s.created_at DESC
	", $args);

	$pagination = $rsp['pagination'];
	$GLOBALS['smarty']->assign_by_ref("pagination", $pagination);

	$pagination_url = $GLOBALS['cfg']['abs_root_url'];
	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);
	$GLOBALS['smarty']->assign("per_page", $per_page);

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

	$GLOBALS['smarty']->assign_by_ref('tweets', $tweets); */
	
