<?php

	include('include/init.php');
	loadlib('smol_accounts');
	loadlib('smol_archive');

	$username = get_str('username');
	login_ensure_loggedin("$username/accounts/");

	if ($username != $GLOBALS['cfg']['user']['username']){
		error_403();
	}

	$accounts = smol_accounts_get_user_accounts($GLOBALS['cfg']['user'], 'include disabled');

	$twitter_accounts = array();
	$account_lookup = array();

	foreach ($accounts as $account){

		$account_id = $account['id'];
		$account_lookup[$account_id] = $account;

		$rsp = smol_archive_filters($account);
		if ($rsp['ok']){
			$account['filters'] = $rsp['filters'];
		}

		if ($account['service'] == 'twitter'){
			$twitter_accounts[] = $account;
		} else if ($account['service'] == 'mlkshk'){
			$account['add_filters'] = smol_archive_mlkshk_add_filters($account);
			$mlkshk_accounts[] = $account;
		}
	}

	$account_id = post_int32('account_id');
	$action = post_str('action');
	$add_filter = post_str('add_filter');

	if ($account_id && $action){

		$crumb_key = 'modify_account';
		if (! crumb_check($crumb_key) ||
		    ! $account_lookup[$account_id]){
			error_403();
		}

		$action = strtolower($action);
		if ($action == 'remove'){
			$rsp = smol_accounts_remove_account($account_id);
		}
		else if ($action == 'disable'){
			$rsp = smol_accounts_disable_account($account_id);
		}
		else if ($action == 'enable'){
			$rsp = smol_accounts_enable_account($account_id);
		}

		$url = $GLOBALS['cfg']['abs_root_url'] . $username . "/accounts/";
		if (! $rsp['ok']){
			$url .= '?error=1';
		}
		header("Location: $url");
		exit;
	}

	if ($add_filter && $account_id){

		$account = $account_lookup[$account_id];

		$crumb_key = 'modify_account';
		if (! crumb_check($crumb_key) ||
		    ! $account_lookup[$account_id]){
			error_403();
		}

		if ($account['service'] == 'twitter'){
			$rsp = smol_archive_twitter_add_filter($account, $add_filter);
		} else if ($account['service'] == 'mlkshk'){
			$rsp = smol_archive_mlkshk_add_filter($account, $add_filter);
		}

		$url = $GLOBALS['cfg']['abs_root_url'] . $username . "/accounts/";
		if (! $rsp['ok']){
			$url .= '?error=1';
		}
		header("Location: $url");
		exit;
	}

	$GLOBALS['smarty']->assign_by_ref('twitter_accounts', $twitter_accounts);
	$GLOBALS['smarty']->assign_by_ref('mlkshk_accounts', $mlkshk_accounts);

	$GLOBALS['smarty']->assign('crumb_auth_account', 'auth_account');
	$GLOBALS['smarty']->assign('crumb_modify_account', 'modify_account');

	$GLOBALS['smarty']->display('page_accounts.txt');
