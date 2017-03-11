<?php

	include('include/init.php');
	loadlib('twitter_api');
	loadlib('smol_accounts');

	login_ensure_loggedin();
	$GLOBALS['smarty']->assign('service', 'Twitter');

	$add_account = post_bool('add_account');
	if ($add_account){

		#
		# step zero: check the crumb
		#

		$crumb_key = 'auth_twitter';
		if (! crumb_check($crumb_key)){
			error_403();
		}

		#
		# step one: get a request token
		#

		$rsp = twitter_api_oauth_request_token();
		$args = array();
		parse_str($rsp['body'], $args);

		if ($args['oauth_callback_confirmed'] != 'true'){
			$GLOBALS['smarty']->assign('error_request_token', 1);
			$GLOBALS['smarty']->display('page_error_auth.txt');
			exit;
		}

		#
		# step two: go ask the user for permission
		#

		$url = twitter_api_oauth_auth_url($args['oauth_token']);
		header("Location: {$url}");
		exit;
	}

	else {

		#
		# step three: handle the callback request
		#

		$token_key = get_str('oauth_token');
		$token_secret = get_str('oauth_verifier');

		if (! $token_key ||
		    ! $token_secret){
			$GLOBALS['smarty']->assign('error_verifier', 1);
			$GLOBALS['smarty']->display('page_error_auth.txt');
			exit;
		}

		#
		# step four: grab an access token
		#

		$rsp = twitter_api_oauth_access_token($token_key, $token_secret);
		$args = array();
		parse_str($rsp['body'], $args);
		if (! $args['oauth_token'] ||
		    ! $args['oauth_token_secret']){
				$GLOBALS['smarty']->assign('error_access_token', 1);
				$GLOBALS['smarty']->display('page_error_auth.txt');
				exit;
		}

		$account = array(
			'service' => 'twitter',
			'ext_id' => $args['user_id'],
			'screen_name' => $args['screen_name'],
			'token' => $args['oauth_token'],
			'secret' => $args['oauth_token_secret']
		);

		$rsp = smol_accounts_add_account($GLOBALS['cfg']['user'], $account);
		if (! $rsp['ok']){
			$GLOBALS['smarty']->assign('error_db_insert', 1);
			$GLOBALS['smarty']->display('page_error_auth.txt');
			exit;
		}

		#
		# step five: where shall we bounce to?
		#

		$url = $GLOBALS['cfg']['abs_root_url'];

		if ($redir){
			if (substr($redir, 0, 1) == '/') $redir = substr($redir, 1);
			$url .= $redir;
		}

		header("Location: {$url}");
		exit;

	}
