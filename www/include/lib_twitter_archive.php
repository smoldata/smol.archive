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

		$endpoint_id = str_replace('/', '_', $endpoint);
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
			if ($rsp['ok'] && $rsp['created_id']){
				$saved_ids[] = $rsp['created_id'];
			} else {
				# something something error handling
			}
		}

		foreach ($saved_ids as $id){
			$rsp = db_insert('twitter_archive', array(
				'status_id' => addslashes($id),
				'account_id' => $account['id'],
				'type' => $endpoint_id
			));
			if (! $rsp['ok']){
				# something something log the error?
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
