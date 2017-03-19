<?php

	loadlib('data_twitter');
	loadlib('data_mlkshk');
	loadlib('smol_archive_twitter');
	loadlib('smol_archive_mlkshk');

	########################################################################

	function smol_archive_get_items($accounts, $args){

		# This is probably not a good idea
		extract($args);

		$account_ids = array();
		$account_lookup = array();

		foreach ($accounts as $account){
			$esc_id = addslashes($account['id']);
			$account_ids[] = $esc_id;
			$account_lookup[$esc_id] = $account;
		}
		$account_ids = implode(', ', $account_ids);

		$args = array(
			'count_fields' => '*'
		);

		$where_clause = "account_id IN ($account_ids)";

		if ($search){
			$esc_search = addslashes($search);
			$where_clause .= " AND MATCH (content) AGAINST ('$esc_search')";
			$GLOBALS['smarty']->assign("search", $search);
		}

		if ($service){
			$esc_service = addslashes($service);
			$where_clause .= " AND service = '$esc_service'";
		}

		if ($filter){
			$esc_filter = addslashes($filter);
			$where_clause .= " AND filter = '$esc_filter'";
		}

		if ($page){
			$args['page'] = $page;
		}

		if ($per_page && $per_page > 0 && $per_page <= 1000){
			$args['per_page'] = $per_page;
		} else {
			$per_page = $GLOBALS['cfg']['pagination_per_page'];
		}

		$rsp = db_fetch_paginated("
			SELECT DISTINCT data_id, target_id, account_id, service, filter
			FROM smol_archive
			WHERE $where_clause
			ORDER BY created_at DESC, id DESC
		", $args);

		$items = $rsp['rows'];
		$pagination = $rsp['pagination'];
		$data = array();

		if (! $items){
			# We got nothin'
			return array(
				'ok' => 1,
				'items' => $items,
				'pagination' => false
			);
		}

		$service_ids = array();
		$target_id_lookup = array();
		foreach ($items as $item){
			$service = $item['service'];
			if (! $service_ids[$service]){
				$service_ids[$service] = array();
			}
			$esc_data_id = addslashes($item['data_id']);
			array_push($service_ids[$service], $esc_data_id);

			if (! $target_ids[$service]){
				$target_ids[$service] = array();
			}
			$target_id_lookup[$service][$esc_data_id] = $item['target_id'];
		}

		foreach ($service_ids as $service => $data_ids){
			$data_table = "data_$service";
			$data_ids = "'" . implode("', '", $data_ids) . "'";
			$rsp = db_fetch("
				SELECT *
				FROM $data_table
				WHERE id IN ($data_ids)
			");
			foreach ($rsp['rows'] as $row){
				$id = $row['id'];
				$data[$id] = $row;
			}
		}

		# This is a structure for exchanging 'target_id's for 'index's
		$target_id_rev_lookup = array();

		foreach ($items as $index => $item){

			$data_id = $item['data_id'];
			$service = $item['service'];

			# This next part is kind of fiddly. It is meant to
			# group common target_id items together, like if you
			# faved *and* retweeted the same thing. This will make
			# a single item reflect both fave and RT.
			# (20170318/dphiffer)
			$target_id = $target_id_lookup[$service][$data_id];
			if ($target_id_rev_lookup[$target_id]){
				# Ok, we are now dealing with an old item index
				unset($items[$index]);
				$index = $target_id_rev_lookup[$target_id];
				$merge_data_item = $items[$index]['data'];
			} else {
				$target_id_rev_lookup[$target_id] = $index;
				$merge_data_item = null;
			}

			$data_item = $data[$data_id];

			$account_id = $item['account_id'];
			$account = $account_lookup[$account_id];

			# this is inefficient (better to bundle into a SELECT ... IN () query)
			$items[$index]['user'] = users_get_by_id($account['user_id']);

			$values_function = "data_{$service}_template_values";
			$items[$index]['data'] = $values_function($account, $item, $data_item, $merge_data_item);
			$items[$index]['template'] = "inc_{$service}_item.txt";
		}
		
		return array(
			'ok' => 1,
			'items' => $items,
			'pagination' => $pagination
		);
	}

	########################################################################

	function smol_archive_account($account, $verbose=false){
		if ($account['service'] == 'twitter'){
			smol_archive_twitter_save_data($account, $verbose);
		} else if ($account['service'] == 'mlkshk'){
			smol_archive_mlkshk_save_data($account, $verbose);
		}
	}

	########################################################################

	function smol_archive_escaped_item($account, $filter, $saved){

		# Most services won't need to care about this, but for Twitter:
		# retweets should be providing their own target_id.
		if (! $saved['target_id']){
			$saved['target_id'] = $saved['data_id'];
		}

		$timestamp = strtotime($saved['created_at']);
		$created_at = date('Y-m-d H:i:s', $timestamp);
		$archived_at = date('Y-m-d H:i:s');
		$esc_item = array(
			'data_id' => addslashes($saved['data_id']),
			'target_id' => addslashes($saved['target_id']),
			'account_id' => addslashes($account['id']),
			'service' => addslashes($account['service']),
			'filter' => addslashes($filter),
			'content' => addslashes($saved['content']),
			'created_at' => $created_at,
			'archived_at' => $archived_at
		);
		return $esc_item;
	}

	########################################################################

	function smol_archive_save_items($account, $filter, $items, $verbose=false){

		$saved_ids = array_keys($items);
		$saved_id_list = "'" . implode("', '", $saved_ids) . "'";
		$esc_account_id = addslashes($account['id']);
		$esc_filter = addslashes($filter);
		$rsp = db_fetch("
			SELECT data_id
			FROM smol_archive
			WHERE account_id = $esc_account_id
			  AND filter = '$esc_filter'
			  AND data_id IN ($saved_id_list)
		");
		if (! $rsp['ok']){
			if ($verbose){
				echo "error inspecting archive ";
				var_export($rsp);
			}
			return $rsp;
		}

		$existing_ids = array();
		foreach ($rsp['rows'] as $row){
			$existing_ids[] = $row['data_id'];
		}

		$count = 0;
		foreach ($items as $id => $item){
			if (! in_array($id, $existing_ids)){
				$rsp = db_insert('smol_archive', $item);
				if (! $rsp['ok']){
					if ($verbose){
						echo "error archiving item ";
						var_export($rsp);
					}
				} else {
					$count++;
				}
			}
		}

		if ($verbose){
			echo "archived $count $filter\n";
		}
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
