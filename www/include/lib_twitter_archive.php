<?php
	loadlib('twitter_api');
	loadlib('twitter_status');

	########################################################################

	function twitter_archive_timeline($account){

		$rsp = twitter_api_get($account, 'statuses/user_timeline', array(
			'user_id' => $account['twitter_id'],
			'count' => 200,
			'tweet_mode' => 'extended'
		));
		if (! $rsp['ok']){
			return $rsp;
		}

		$saved_ids = array();
		foreach ($rsp['result'] as $tweet){
			dumper("saving {$tweet['id']}");
			$rsp = twitter_status_save_status($tweet);
			if ($rsp['ok']){
				$saved_ids[] = $rsp['saved_id'];
			} else {
				dumper($rsp);
			}
		}
		
		foreach ($saved_ids as $id){
			$rsp = db_insert('twitter_archive', array(
				'status_id' => addslashes($id),
				'account_id' => $account['id'],
				'type' => 'timeline'
			));
			if (! $rsp['ok']){
				dumper($rsp);
				# something something log the error?
			}
		}

		return array(
			'ok' => 1,
			'saved_ids' => $saved_ids
		);
	}

	# the end
