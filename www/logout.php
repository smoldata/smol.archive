<?php
	include("include/init.php");

	login_ensure_loggedin();


	#
	# crumb key
	#

	$crumb_key = 'logout';
	$smarty->assign("crumb_key", $crumb_key);


	#
	# sign out?
	#

	if (post_isset('done') && crumb_check($crumb_key)){

		login_do_logout();

		header("Location: {$GLOBALS['cfg']['abs_root_url']}");
		exit;
	}


	#
	# output
	#

	$smarty->display("page_logout.txt");
