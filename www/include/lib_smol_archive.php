<?php
	loadlib('smol_archive_twitter');

	########################################################################

	function smol_archive_account($account, $verbose=false){
		if ($account['service'] == 'twitter'){
			smol_archive_twitter_save_data($account, $verbose);
		}
	}

	########################################################################

	function smol_archive_escaped_item($account, $filter, $saved){
		$timestamp = strtotime($saved['created_at']);
		$created_at = date('Y-m-d H:i:s', $timestamp);
		$archived_at = date('Y-m-d H:i:s');
		$esc_item = array(
			'data_id' => addslashes($saved['data_id']),
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
		$saved_id_list = implode(', ', $saved_ids);
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
