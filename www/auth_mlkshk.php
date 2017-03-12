<?php

	include('include/init.php');
	loadlib('mlkshk_api');
	loadlib('smol_accounts');

	login_ensure_loggedin();
	$GLOBALS['smarty']->assign('service', 'mlkshk');

	# Are we kicking off the process or handling an OAuth callback?
	$add_account = post_bool('add_account');

	if ($add_account){

		#
		# step zero: check the crumb
		#

		$crumb_key = 'auth_account';
		if (! crumb_check($crumb_key)){
			error_403();
		}

		#
		# step one: go ask the user for permission
		#

		$url = mlkshk_api_get_auth_url();
		header("Location: {$url}");
		exit;
	}

	else {

		#
		# step three: handle the callback request
		#

		$code = get_str('code');
		if (! $code){
			$GLOBALS['smarty']->assign('error_no_code', 1);
			$GLOBALS['smarty']->display('page_error_auth.txt');
			exit;
		}

		#
		# step four: request a token
		#

		$rsp = mlkshk_api_get_auth_token($code);
		$args = json_decode($rsp['body'], 'as hash');
		if (! $args['access_token'] ||
		    ! $args['secret']){
			$GLOBALS['smarty']->assign('error_access_token', 1);
			$GLOBALS['smarty']->display('page_error_auth.txt');
			exit;
		}

		#
		# step five: figure out our user info
		#

		$account = array(
			'service' => 'mlkshk',
			'ext_id' => null,
			'screen_name' => null,
			'token' => $args['access_token'],
			'secret' => $args['secret']
		);
		$rsp = mlkshk_api_get($account, 'user');
		if (! $rsp['ok']){
			$GLOBALS['smarty']->assign('error_user_info', 1);
			$GLOBALS['smarty']->display('page_error_auth.txt');
			exit;
		}

		#
		# step six: save the account!
		#

		$account['ext_id'] = $rsp['result']['id'];
		$account['screen_name'] = $rsp['result']['name'];

		$rsp = smol_accounts_add_account($GLOBALS['cfg']['user'], $account);
		if (! $rsp['ok']){
			$GLOBALS['smarty']->assign('error_db_insert', 1);
			$GLOBALS['smarty']->display('page_error_auth.txt');
			exit;
		}

		#
		# step seven: redirect!
		#

		$url = $GLOBALS['cfg']['abs_root_url'] . $GLOBALS['cfg']['user']['username'] . '/accounts/';
		header("Location: $url");
		exit;
	}
