<?php
	include('init_local.php');

	$id = $argv[1];
	if (! $id){
		die("Usage: php tweet_json.php [id]\n");
	}

	$esc_id = addslashes($id);
	$rsp = db_fetch("
		SELECT *
		FROM twitter_status
		WHERE id = $esc_id
	");

	if (! $rsp['ok']){
		var_dump($rsp['ok']);
		exit;
	}

	echo $rsp['rows'][0]['json'];
