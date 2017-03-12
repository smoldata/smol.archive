<?php
	loadlib('twitter_api');
	loadlib('data_twitter');
	loadlib('smol_meta');

	########################################################################

	function smol_archive_twitter($account, $filter, $endpoint, $args=array()){

		$defaults = array(
			'user_id' => $account['ext_id'],
			'count' => 200,
			'tweet_mode' => 'extended'
		);
		$args = array_merge($defaults, $args);

		$esc_account_id = addslashes($account['id']);
		$esc_filter = addslashes($filter);
		$meta_name = "max_id_" . $esc_filter;
		
		$max_id = smol_meta_get($account, $meta_name);
		if ($max_id){
			$args['max_id'] = $max_id;
		}

		$rsp = twitter_api_get($account, $endpoint, $args);
		if (! $rsp['ok']){
			return $rsp;
		}

		$saved_items = array();
		foreach ($rsp['result'] as $item){
			$rsp = data_twitter_save($item);
			if ($rsp['ok']){
				$esc_id = addslashes($rsp['saved_id']);
				$esc_content = addslashes($rsp['content']);
				$timestamp = strtotime($item['created_at']);
				$created_at = date('Y-m-d H:i:s', $timestamp);
				$archived_at = date('Y-m-d H:i:s');
				$esc_item = array(
					'data_id' => $esc_id,
					'account_id' => $esc_account_id,
					'service' => 'twitter',
					'filter' => $esc_filter,
					'content' => $esc_content,
					'created_at' => $created_at,
					'archived_at' => $archived_at
				);
				$saved_items[$esc_id] = $esc_item;
			}
		}

		if (empty($saved_items)){
			smol_meta_set($account, $meta_name, 0);
			return array(
				'ok' => 1,
				'saved_ids' => array()
			);
		}

		$saved_ids = array_keys($saved_items);
		$saved_id_list = implode(', ', $saved_ids);
		$rsp = db_fetch("
			SELECT data_id
			FROM smol_archive
			WHERE account_id = $esc_account_id
			  AND filter = '$esc_filter'
			  AND data_id IN ($saved_id_list)
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		$existing_ids = array();
		foreach ($rsp['rows'] as $row){
			$existing_ids[] = $row['data_id'];
		}

		foreach ($saved_items as $id => $item){
			if (! in_array($id, $existing_ids)){
				$rsp = db_insert('smol_archive', $item);
				if (! $rsp['ok']){
					return $rsp;
				}
			}
		}

		if ($saved_items){
			$last_id = array_pop($saved_ids);
			smol_meta_set($account, $meta_name, $last_id);
		}

		return array(
			'ok' => 1,
			'saved_ids' => $saved_ids
		);
	}

	########################################################################

	function smol_archive_filter_count($account, $filter){
		$esc_id = addslashes($account['id']);
		$esc_filter = addslashes($filter);
		$rsp = db_fetch("
			SELECT COUNT(*) AS count
			FROM smol_archive
			WHERE account_id = $esc_id
			  AND filter = '$esc_filter'
		");
		if (! $rsp['ok']){
			return '(error)';
		}
		return number_format($rsp['rows'][0]['count']);
	}

	# the end
