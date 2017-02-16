<?php

	include('include/init.php');
	loadlib('twitter_api');
	loadlib('twitter_users');

	login_ensure_loggedin();

	$token_key = get_str('oauth_token');
	$token_secret = get_str('oauth_verifier');

	if (! $token_key ||
	    ! $token_secret){
		$GLOBALS['smarty']->assign('error_verifier', 1);
		$GLOBALS['smarty']->display('page_error_twitter_oauth.txt');
		exit;
	}

	$rsp = twitter_api_oauth_access_token($token_key, $token_secret);
	$token = array();
	parse_str($rsp['body'], $token);
	if (! $token['oauth_token'] ||
	    ! $token['oauth_token_secret']){
			$GLOBALS['smarty']->assign('error_access_token', 1);
			$GLOBALS['smarty']->display('page_error_twitter_oauth.txt');
			exit;
	}

	$rsp = twitter_users_add_account($GLOBALS['cfg']['user'], $token);
	if (! $rsp['ok']){
		$GLOBALS['smarty']->assign('error_db_insert', 1);
		$GLOBALS['smarty']->display('page_error_twitter_oauth.txt');
		exit;
	}
	#dumper($rsp);

	#
	# where shall we bounce to?
	#

	$url = $GLOBALS['cfg']['abs_root_url'];

	if ($redir){
		if (substr($redir, 0, 1) == '/') $redir = substr($redir, 1);
		$url .= $redir;
	}

	#
	# go!
	#

	header("location: {$url}");
	exit;
