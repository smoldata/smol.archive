<?php

	include('include/init.php');
	loadlib('smol_accounts');

	$username = get_str('username');
	login_ensure_loggedin("$username/network/");

	if ($username != $GLOBALS['cfg']['user']['username']){
		# Maybe consider letting people see each other's networks, but
		# not yet. (20170319/dphiffer)
		error_403();
	}

	$user = $GLOBALS['cfg']['user'];
	$following = smol_accounts_get_following_usernames($user);
	$usernames = smol_accounts_get_all_usernames();

	$GLOBALS['smarty']->assign_by_ref('usernames', $usernames);
	$GLOBALS['smarty']->assign_by_ref('following', $following);
	
	$crumb_follow = crumb_generate('api', 'users.follow');
	$GLOBALS['smarty']->assign('crumb_follow', $crumb_follow);

	$crumb_unfollow = crumb_generate('api', 'users.unfollow');
	$GLOBALS['smarty']->assign('crumb_unfollow', $crumb_unfollow);

	$GLOBALS['smarty']->display('page_network.txt');
