<?php

	include('include/init.php');
	loadlib('users');
	loadlib('smol_accounts');
	loadlib('data_twitter');
	loadlib('data_mlkshk');

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
	$smarty->assign_by_ref('user', $user);
	if ($GLOBALS['cfg']['user'] && $user['id'] != $GLOBALS['cfg']['user']['id']){
		$is_following = smol_accounts_is_following($GLOBALS['cfg']['user'], $user);
		$smarty->assign('is_following', $is_following);
	}

	$accounts = smol_accounts_get_accounts($user);
	$services = smol_accounts_get_services($user);
	$smarty->assign_by_ref('services', $services);

	if (empty($accounts)){
		$smarty->assign('no_accounts', 1);
	} else {

		$account_ids = array();
		$account_lookup = array();
		foreach ($accounts as $account){
			$esc_id = addslashes($account['id']);
			$account_ids[] = $esc_id;
			$account_lookup[$esc_id] = $account;
		}
		$account_ids = implode(', ', $account_ids);

		$args = array(
			'count_fields' => '*'
		);

		$where_clause = "account_id IN ($account_ids)";

		$arg_search = get_str('search');
		if ($arg_search){
			$esc_search = addslashes($arg_search);
			$where_clause .= " AND MATCH (content) AGAINST ('$esc_search')";
			$GLOBALS['smarty']->assign("search", $arg_search);
			$page_title = $arg_search;
		}

		$arg_service = get_str('service');
		if ($arg_service){
			if (! $services[$arg_service]){
				error_404();
			}
			$esc_service = addslashes($arg_service);
			$where_clause .= " AND service = '$esc_service'";
		}

		$arg_filter = get_str('filter');
		if ($arg_filter){
			$esc_filter = addslashes($arg_filter);
			$where_clause .= " AND filter = '$esc_filter'";
		}

		$page = get_int32('page');
		if ($page){
			$args['page'] = $page;
			if ($page > 1){
				$page_title .= " / page $page";
			}
		}

		$per_page = get_int32('per_page');
		if ($per_page && $per_page > 0 && $per_page <= 1000){
			$args['per_page'] = $per_page;
		} else {
			$per_page = $GLOBALS['cfg']['pagination_per_page'];
		}

		$rsp = db_fetch_paginated("
			SELECT DISTINCT data_id, target_id, account_id, service, filter
			FROM smol_archive
			WHERE $where_clause
			ORDER BY created_at DESC, id DESC
		", $args);

		$pagination = $rsp['pagination'];
		$GLOBALS['smarty']->assign_by_ref("pagination", $pagination);

		if ($arg_service){
			$pagination_url = $GLOBALS['cfg']['abs_root_url'] . "$username/$arg_service/";
		} else {
			$pagination_url = $GLOBALS['cfg']['abs_root_url'] . "$username/";
		}
		$GLOBALS['smarty']->assign("pagination_url", $pagination_url);
		$GLOBALS['smarty']->assign("per_page", $per_page);

		$items = $rsp['rows'];
		$data = array();

		$service_ids = array();
		$target_id_lookup = array();
		foreach ($items as $item){
			$service = $item['service'];
			if (! $service_ids[$service]){
				$service_ids[$service] = array();
			}
			$esc_data_id = addslashes($item['data_id']);
			array_push($service_ids[$service], $esc_data_id);

			if (! $target_ids[$service]){
				$target_ids[$service] = array();
			}
			$target_id_lookup[$service][$esc_data_id] = $item['target_id'];
		}

		foreach ($service_ids as $service => $data_ids){
			$data_table = "data_$service";
			$data_ids = "'" . implode("', '", $data_ids) . "'";
			$rsp = db_fetch("
				SELECT *
				FROM $data_table
				WHERE id IN ($data_ids)
			");
			foreach ($rsp['rows'] as $row){
				$id = $row['id'];
				$data[$id] = $row;
			}
		}

		# This is a structure for exchanging 'target_id's for 'index's
		$target_id_rev_lookup = array();

		foreach ($items as $index => $item){

			$data_id = $item['data_id'];
			$service = $item['service'];

			# This next part is kind of fiddly. It is meant to
			# group common target_id items together, like if you
			# faved *and* retweeted the same thing. This will make
			# a single item reflect both fave and RT.
			# (20170318/dphiffer)
			$target_id = $target_id_lookup[$service][$data_id];
			if ($target_id_rev_lookup[$target_id]){
				# Ok, we are now dealing with an old item index
				unset($items[$index]);
				$index = $target_id_rev_lookup[$target_id];
				$merge_data_item = $items[$index]['data'];
			} else {
				$target_id_rev_lookup[$target_id] = $index;
				$merge_data_item = null;
			}

			$data_item = $data[$data_id];

			$account_id = $item['account_id'];
			$account = $account_lookup[$account_id];

			# this is inefficient (better to bundle into a SELECT ... IN () query)
			$items[$index]['user'] = users_get_by_id($account['user_id']);

			$values_function = "data_{$service}_template_values";
			$items[$index]['data'] = $values_function($account, $item, $data_item, $merge_data_item);
			$items[$index]['template'] = "inc_{$service}_item.txt";

		}

		$GLOBALS['smarty']->assign_by_ref('items', $items);
	}

	$view = 'everything';
	if ($arg_search){
		$view = 'search';
	} else if ($arg_filter == 'faves'){
		$view = 'faves';
	} else if ($arg_service){
		$view = $arg_service;
	}

	$GLOBALS['smarty']->assign('page_title', $page_title);
	$GLOBALS['smarty']->assign('view', $view);
	$GLOBALS['smarty']->display('page_profile.txt');
