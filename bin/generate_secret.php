<?php

	# Usage:
	#   cd /usr/local/smoldata/thingmonger
	#   php bin/generate_secret.php

	include("init_local.php");
	loadlib("random");

	$length = 32;

	echo random_string($length) . "\n";
	exit();
