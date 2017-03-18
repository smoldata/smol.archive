<?php

	# Usage:
	#   cd /usr/local/smoldata/thingmonger
	#   sudo -u www-data php bin/migrate_db.php

	include('init_local.php');
	loadlib('smol_meta');

	# n.b. this should be run by a human, do not put this on a crontab,
	# at least not for now. (20170318/dphiffer)

	set_time_limit(0);
	$lockfile_path = $GLOBALS['cfg']['smol_data_dir'] . 'migrate_db.lock';
	if (file_exists($lockfile_path)){
		echo "Aborting because lockfile exists: $lockfile_path\n";
		exit;
	}
	touch($lockfile_path);
	if (! file_exists($lockfile_path)){
		echo "Aborting because lockfile was not created: $lockfile_path\n";
		exit;
	}

	// Account ID 0 is just a way of saying "everyone's account"
	$account = array(
		'id' => 0
	);

	$db_version = smol_meta_get($account, 'db_version');
	if ($db_version){
		echo "Current db_version: $db_version\n";
	}

	echo "mysql root password: ";
	system('stty -echo');
	$password = trim(fgets(STDIN));
	system('stty echo');
	// add a new line since the users CR didn't echo
	echo "\n";

	$alters_dir = dirname(__DIR__) . '/schema/alters';
	echo "Inspecting $alters_dir for migrations\n";
	$alters = glob("$alters_dir/*.schema");
	foreach ($alters as $alter_file){

		if (! preg_match('/(\d{8})\.([^.]+)\.schema$/', $alter_file, $matches)){
			echo "Unexpected file $alter_file (skipping)\n";
			continue;
		}

		echo "Processing $alter_file\n";

		$date = $matches[1];
		$cluster = $matches[2];

		$dbhost = $GLOBALS['cfg'][$cluster]['host'];
		$dbname = $GLOBALS['cfg'][$cluster]['name'];
		if (! $dbhost || ! $dbname || ! $password){
			echo "Missing one of: dbhost/dbname/password\n";
			exit;
		}

		# This will put the mysql root password into the `ps` process,
		# listing which is... maybe not such a great idea? We should fix
		# that, but not today. (20170318/dphiffer)

		$output = array();
		exec("/usr/bin/mysql -h $dbhost -u root -p$password $dbname < $alter_file", $output);
		if ($output){
			echo "mysql says:\n";
			echo implode("\n", $output);
			exit;
		}

		# For more complex migrations, run the companion .php script
		$alter_script = "$alters_dir/$date.$cluster.php";
		if (file_exists($alter_script)){
			echo "Migrating $date.$cluster.php\n";
			$alters_function = "alters_{$cluster}_{$date}";
			include($alter_script);
			if (function_exists($alters_function)){
				# Ok, here goes!
				$rsp = $alters_function();
				if (! $rsp['ok']){
					var_export($rsp);
					exit;
				} else {
					echo "Updated {$rsp['count']} records\n";
				}
			} else {
				echo "Warning: could not find $alters_function\n";
			}
		}

		# Bump the db_version
		$db_version = $date;
	}

	echo "New database version: $db_version\n";

	smol_meta_set($account, 'db_version', $db_version);
	unlink($lockfile_path);
