<?php

	loadlib('smol_meta');
	loadlib('twitter_api');
	loadlib('data_twitter');

	########################################################################
	
	function smol_archive_twitter_views($account){
		return array(
			'label' => 'Twitter'
		);
	}

	########################################################################

	function smol_archive_twitter_save_data($account, $verbose=false) {

		$endpoints = array(
			'tweets' => 'statuses/user_timeline',
			'faves' => 'favorites/list'
		);

		$items = array();
		foreach ($endpoints as $filter => $endpoint){

			$items[$filter] = array();

			// First get the new stuff
			$args = array();
			$rsp = smol_archive_twitter_query($account, $endpoint, $args, $verbose);
			if ($rsp['ok']){
				$items[$filter] = $rsp['result'];
			}

			// Next get the older stuff (with each run, max_id
			//   advances down the timeline)
			$max_id = smol_meta_get($account, "max_id_$filter");
			if ($max_id){

				$rsp = smol_archive_twitter_query($account, $endpoint, array(
					'max_id' => $max_id
				), $verbose);
				if ($rsp['ok']){
					$items[$filter] = array_merge($items[$filter], $rsp['result']);
				}

				if (! $rsp['result']){
					// Reached the end of the timeline,
					// reset for next time
					smol_meta_set($account, "max_id_$filter", 0);
				} else {
					// Continue where we left off next time
					$last_item = array_pop($rsp['result']);
					$last_id = $last_item['id_str'];
					smol_meta_set($account, "max_id_$filter", $last_id);
				}
			} else {
				// Continue where we left off next time
				$last_item = array_pop($rsp['result']);
				$last_id = $last_item['id_str'];
				smol_meta_set($account, "max_id_$filter", $last_id);
			}

			if ($verbose){
				echo "saving $filter data\n";
			}
			$saved_items = array();
			foreach ($items[$filter] as $item){
				$rsp = data_twitter_save($item);
				if ($rsp['ok']){
					$saved_item = smol_archive_escaped_item($account, $filter, $rsp);
					$data_id = $saved_item['data_id'];
					$saved_items[$data_id] = $saved_item;
				} else {
					echo "error saving item ";
					var_export($rsp);
				}
			}

			if (empty($saved_items)){
				if ($verbose){
					echo "(no data saved)\n";
				}
				continue;
			}

			if ($verbose){
				echo "archiving $filter items\n";
			}
			smol_archive_save_items($account, $filter, $saved_items, $verbose);
		}
	}

	########################################################################

	function smol_archive_twitter_query($account, $endpoint, $args=array(), $verbose=false) {

		$defaults = array(
			'user_id' => $account['ext_id'],
			'count' => 200,
			'tweet_mode' => 'extended'
		);
		$args = array_merge($defaults, $args);

		if ($verbose){
			echo "twitter_api_get $endpoint";
			if ($args['max_id']){
				echo " (max_id {$args['max_id']})";
			}
		}
		$rsp = twitter_api_get($account, $endpoint, $args);

		if (! $rsp['ok']){
			if ($verbose){
				var_export($rsp);
			}
		} else {
			if ($verbose){
				echo " found " . count($rsp['result']) . " items\n";
			}
		}

		return $rsp;
	}
