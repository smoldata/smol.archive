<?php
	loadlib('twitter_api');
	loadlib('twitter_status');
	loadlib('twitter_meta');

	########################################################################

	function twitter_archive_endpoint($account, $endpoint, $args=array()){

		$defaults = array(
			'user_id' => $account['twitter_id'],
			'count' => 200,
			'tweet_mode' => 'extended'
		);
		$args = array_merge($defaults, $args);

		$esc_account_id = addslashes($account['id']);
		$endpoint_id = str_replace('/', '_', $endpoint);
		$esc_endpoint_id = addslashes($endpoint_id);

		$meta_name = "max_id_" . $endpoint_id;
		$max_id = twitter_meta_get($account, $meta_name);
		if ($max_id){
			$args['max_id'] = $max_id;
		}

		$rsp = twitter_api_get($account, $endpoint, $args);
		if (! $rsp['ok']){
			return $rsp;
		}

		$saved_ids = array();
		foreach ($rsp['result'] as $tweet){
			$rsp = twitter_status_save_status($tweet);
			if ($rsp['ok']){
				$saved_ids[] = addslashes($rsp['saved_id']);
			}
		}

		if (empty($saved_ids)){
			twitter_meta_set($account, $meta_name, 0);
			return array(
				'ok' => 1,
				'saved_ids' => array()
			);
		}

		$saved_id_list = implode(', ', $saved_ids);
		$rsp = db_fetch("
			SELECT status_id
			FROM twitter_archive
			WHERE account_id = $esc_account_id
			  AND type = '$esc_endpoint_id'
			  AND status_id IN ($saved_id_list)
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		$existing_ids = array();
		foreach ($rsp['rows'] as $row){
			$existing_ids[] = $row['status_id'];
		}

		foreach ($saved_ids as $id){
			if (! in_array($id, $existing_ids)){
				$rsp = db_insert('twitter_archive', array(
					'status_id' => addslashes($id),
					'account_id' => $esc_account_id,
					'type' => $endpoint_id
				));
			}
		}

		if ($saved_ids){
			$last_id = array_pop($saved_ids);
			twitter_meta_set($account, $meta_name, $last_id);
		}

		return array(
			'ok' => 1,
			'saved_ids' => $saved_ids
		);
	}

	# the end
