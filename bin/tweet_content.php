<?php
	include('init_local.php');
	loadlib('twitter_status');

	$options = array(
		'plaintext' => true
	);

	$id = $argv[1];
	if ($argv[2] == 'html'){
		$options['plaintext'] = false;
	}

	if (! $id){
		die("Usage: php tweet_content.php [id] [plaintext|html]\n");
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

	$status = json_decode($rsp['rows'][0]['json'], 'as hash');
	if ($status['retweeted_status']){
		$status = $status['retweeted_status'];
	}
	echo twitter_status_content($status, $options) . "\n";
