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
		if ($verbose){
			echo "Aborting because lockfile exists: $lockfile_path\n";
		}
		exit;
	}
	touch($lockfile_path);

	$rsp = db_fetch("
		SELECT smol_account.*, users.username
		FROM smol_account, users
		WHERE smol_account.user_id = users.id
		  AND smol_account.enabled = 1
	");
	if (! $rsp['ok']){
		var_export($rsp);
		exit;
	}

	foreach ($rsp['rows'] as $account){
		if ($verbose){
			echo "smol_archive_account {$account['username']} {$account['service']}:{$account['screen_name']} (account id {$account['id']})\n";
		}
		smol_archive_account($account, $verbose);
	}

	unlink($lockfile_path);
