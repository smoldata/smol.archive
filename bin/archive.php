<?php
	include('init_local.php');
	loadlib('twitter_users');
	loadlib('twitter_archive');

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
	$lockfile_path = $GLOBALS['cfg']['smol_data_dir'] . 'archiving.lock';
	if (file_exists($lockfile_path)){
		die("Aborting archive.php because lockfile exists: $lockfile_path\n");
	}
	touch($lockfile_path);

	$rsp = db_fetch("
		SELECT *
		FROM twitter_account
	");
	if (! $rsp['ok']){
		var_export($rsp);
		exit;
	}

	$endpoints = array(
		'statuses/user_timeline',
		'favorites/list'
	);

	foreach ($rsp['rows'] as $account){
		foreach ($endpoints as $endpoint){
			$rsp = twitter_archive_endpoint($account, $endpoint);
			if (! $rsp['ok'] && $verbose){
				echo "Error archiving $endpoint for account {$account['id']}:\n";
				var_export($rsp);
			}
		}
	}

	unlink($lockfile_path);
