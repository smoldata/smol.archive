<?php

	include('include/init.php');
	loadlib('twitter_api');

	login_ensure_loggedin();

	$rsp = twitter_api_oauth_request_token();
	$token = array();
	parse_str($rsp['body'], $token);

	if ($token['oauth_callback_confirmed'] != 'true'){
		$GLOBALS['smarty']->assign('error_request_token', 1);
		$GLOBALS['smarty']->display('page_error_twitter_oauth.txt');
		exit;
	}

	#
	# go ask the user for permission
	#

	$url = twitter_api_oauth_auth_url($token['oauth_token']);
	header("location: {$url}");
	exit;	
