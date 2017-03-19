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
	$account_ids = array();

	foreach ($accounts as $account){
		$account_ids[] = $account['id'];
		if ($account['service'] == 'twitter'){
			$account['tweet_count'] = smol_archive_filter_count($account, 'tweets');
			$account['fave_count'] = smol_archive_filter_count($account, 'faves');
			$twitter_accounts[] = $account;
		} else if ($account['service'] == 'mlkshk'){
			# $account['tweet_count'] = smol_archive_filter_count($account, 'tweets');
			# $account['fave_count'] = smol_archive_filter_count($account, 'faves');
			$mlkshk_accounts[] = $account;
		}
	}

	$account_id = post_int32('account_id');
	$action = post_str('action');

	if ($account_id && $action){

		$crumb_key = 'modify_account';
		if (! crumb_check($crumb_key) ||
		    ! in_array($account_id, $account_ids)){
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

	$smarty->assign_by_ref('twitter_accounts', $twitter_accounts);
	$smarty->assign_by_ref('mlkshk_accounts', $mlkshk_accounts);

	$smarty->assign('crumb_auth_account', 'auth_account');
	$smarty->assign('crumb_modify_account', 'modify_account');

	$GLOBALS['smarty']->display('page_accounts.txt');
