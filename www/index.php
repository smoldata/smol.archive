<?php

	include('include/init.php');
	loadlib('smol_accounts');
	loadlib('smol_archive');

	if (! $GLOBALS['cfg']['user']){
		$GLOBALS['smarty']->display('page_about.txt');
	} else {
		$accounts = smol_accounts_get_following_accounts($GLOBALS['cfg']['user']);
		$services = smol_accounts_get_services($accounts);
		$GLOBALS['smarty']->assign_by_ref('services', $services);

		$page_title = 'Flow';

		$service = get_str('service');
		if ($service){
			if (! $services[$service]){
				error_404();
			}
			$page_title .= " / {$services[$service]['label']}";
		}

		$search = get_str('search');
		if ($search){
			$page_title = "Search: $search";
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
			$pagination_url = $GLOBALS['cfg']['abs_root_url'] . "$service/";
		} else {
			$pagination_url = $GLOBALS['cfg']['abs_root_url'];
		}
		$GLOBALS['smarty']->assign("pagination_url", $pagination_url);
		$GLOBALS['smarty']->assign("per_page", $per_page);

		$GLOBALS['smarty']->assign_by_ref('items', $items);

		$GLOBALS['smarty']->display('page_flow.txt');
	}
