<?php

	include('include/init.php');
	loadlib('users');
	loadlib('smol_accounts');
	loadlib('data_twitter');

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

	$services = smol_accounts_get_services($user);
	$smarty->assign_by_ref('services', $services);

	if (empty($services) && $user['id'] == $GLOBALS['cfg']['user']['id']){

		# No accounts? Let's add at least one.

		$url = $GLOBALS['cfg']['abs_root_url'] . "$username/archive/";
		header("Location: {$url}");
		exit;
	}
	
	if (empty($services)){
		$smarty->assign('no_accounts', 1);
	}

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
		SELECT DISTINCT data_id, service
		FROM smol_archive
		ORDER BY archived_at DESC
	", $args);

	$pagination = $rsp['pagination'];
	$GLOBALS['smarty']->assign_by_ref("pagination", $pagination);

	$pagination_url = $GLOBALS['cfg']['abs_root_url'];
	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);
	$GLOBALS['smarty']->assign("per_page", $per_page);

	$items = $rsp['rows'];
	$data = array();

	$service_ids = array();
	foreach ($items as $item){
		$service = $item['service'];
		if (! $service_ids[$service]){
			$service_ids[$service] = array();
		}
		$esc_data_id = addslashes($item['data_id']);
		array_push($service_ids[$service], $esc_data_id);
	}

	foreach ($service_ids as $service => $data_ids){
		$data_table = "data_$service";
		$data_ids = implode(',', $data_ids);
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

	foreach ($items as $index => $item){
		$id = $item['data_id'];
		$service = $item['service'];
		$values_function = "data_{$service}_template_values";
		$items[$index]['data'] = $values_function($data[$id]);
		$items[$index]['template'] = "inc_{$service}_item.txt";
	}

	$GLOBALS['smarty']->assign_by_ref('items', $items);
	$GLOBALS['smarty']->display('page_profile.txt');
