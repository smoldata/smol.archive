<?php

	include('include/init.php');
	loadlib('users');
	loadlib('smol_accounts');
	loadlib('smol_archive');

	$username = get_str('username');
	$esc_username = addslashes($username);
	$rsp = db_fetch("
		SELECT *
		FROM users
		WHERE username = '$esc_username'
	");

	if (! $rsp['rows']){
		error_404();
	}

	$page_title = htmlentities($username);

	$user = $rsp['rows'][0];
	$GLOBALS['smarty']->assign_by_ref('user', $user);
	if ($GLOBALS['cfg']['user'] && $user['id'] != $GLOBALS['cfg']['user']['id']){
		$is_following = smol_accounts_is_following($GLOBALS['cfg']['user'], $user);
		$GLOBALS['smarty']->assign('is_following', $is_following);
	}

	$accounts = smol_accounts_get_user_accounts($user);
	$services = smol_accounts_get_services($accounts);
	$GLOBALS['smarty']->assign_by_ref('services', $services);

	if (empty($accounts)){
		$GLOBALS['smarty']->assign('no_accounts', 1);
	} else {

		$page_title = $username;

		$service = get_str('service');
		if ($service){
			if (! $services[$service]){
				error_404();
			}
			$page_title .= " / {$services[$service]['label']}";
		}

		$search = get_str('search');
		if ($search){
			$page_title = "Search: $search / $username";
		}
		
		$filter = get_str('filter');
		if ($filter){
			$page_title .= " / $filter";
		}

		$page = get_int32('page');
		if ($page > 1){
			$page_title .= " / page $page";
		}
		$GLOBALS['smarty']->assign('page_title', $page_title);

		$view = 'everything';
		if ($search){
			$view = 'search';
		} else if ($filter == 'faves'){
			$view = 'faves';
		} else if ($service){
			$view = $service;
		}
		$GLOBALS['smarty']->assign('view', $view);

		$args = array(
			'search' => $search,
			'service' => $service,
			'filter' => $filter,
			'page' => $page,
			'per_page' => get_int32('per_page')
		);

		$rsp = smol_archive_get_items($accounts, $args);
		if (! $rsp['ok']){
			$GLOBALS['smarty']->assign('archive_error', 1);
		}
		$items = $rsp['items'];
		
		$pagination = $rsp['pagination'];
		$GLOBALS['smarty']->assign_by_ref("pagination", $pagination);

		if ($service){
			$pagination_url = $GLOBALS['cfg']['abs_root_url'] . "$username/$service/";
		} else {
			$pagination_url = $GLOBALS['cfg']['abs_root_url'] . "$username/";
		}
		$GLOBALS['smarty']->assign("pagination_url", $pagination_url);
		$GLOBALS['smarty']->assign("per_page", $per_page);

		$GLOBALS['smarty']->assign_by_ref('items', $items);
	}

	$crumb_follow = crumb_generate('api', 'users.follow');
	$GLOBALS['smarty']->assign('crumb_follow', $crumb_follow);

	$crumb_unfollow = crumb_generate('api', 'users.unfollow');
	$GLOBALS['smarty']->assign('crumb_unfollow', $crumb_unfollow);

	$crumb_fave = crumb_generate('api', 'item.fave');
	$GLOBALS['smarty']->assign('crumb_fave', $crumb_fave);

	$GLOBALS['smarty']->display('page_profile.txt');
