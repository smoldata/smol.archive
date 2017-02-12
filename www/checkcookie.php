<?php
	include("include/init.php");


	#
	# do we have a valid cookie set?
	#

	if (!login_check_login()){

		$smarty->display("page_error_cookie.txt");
		exit;
	}


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
