<?php
	include('init_local.php');
	loadlib('smol_accounts');
	loadlib('smol_archive');

	$verbose = false;
	if ($argv){
		foreach ($argv as $arg){
			if ($arg == '--verbose' ||
			    $arg == '-v'){
				$verbose = true;
			}
		}
	}

	set_time_limit(0);
	$lockfile_path = $GLOBALS['cfg']['smol_data_dir'] . 'download.lock';
	if (file_exists($lockfile_path)){
		die("Aborting because lockfile exists: $lockfile_path\n");
	}
	touch($lockfile_path);

	$rsp = db_fetch("
		SELECT *
		FROM smol_account
	");
	if (! $rsp['ok']){
		var_export($rsp);
		exit;
	}

	$twitter_endpoints = array(
		'tweets' => 'statuses/user_timeline',
		'faves' => 'favorites/list'
	);

	foreach ($rsp['rows'] as $account){
		if ($account['service'] == 'twitter'){
			$endpoints = $twitter_endpoints;
			$archive_function = 'smol_archive_twitter';
		}
		else {
			continue;
		}
		foreach ($endpoints as $filter => $endpoint){
			$rsp = $archive_function($account, $filter, $endpoint);
			if (! $rsp['ok'] && $verbose){
				echo "Error archiving $filter for account {$account['id']}:\n";
				var_export($rsp);
			}
		}
	}

	unlink($lockfile_path);
