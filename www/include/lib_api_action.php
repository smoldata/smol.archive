<?php

	loadlib('smol_accounts');

	########################################################################

	function api_action_fave(){

		$target_id = post_str('target_id');
		if (! $target_id){
			api_output_error(400, 'Please include a target ID to fave.');
		}

		$service = post_str('service');
		if ($service != 'twitter'){
			api_output_error(400, 'Please include an item service to fave.');
		}

		$action = post_str('action');
		if ($action != 'fave' &&
		    $action != 'unfave'){
			api_output_error(400, 'Please set action to fave or unfave.');
		}

		# TODO: hit external APIs to fave

		$accounts = smol_accounts_get_user_accounts($GLOBALS['cfg']['user']);
		foreach ($accounts as $account){
			if ($account['service'] == $service){
				$account_id = $account['id'];
				break;
			}
		}
		if (! $account_id){
			api_output_error(400, "Could not find $service account to $action with.");
		}

		$esc_account_id = addslashes($account_id);
		$esc_id = addslashes($target_id);

		if ($action == 'fave'){

			$rsp = db_fetch("
				SELECT content, created_at
				FROM data_$service
				WHERE id = $esc_id
			");
			if (! $rsp['rows']){
				api_output_error(400, "Could not find the item you are trying to $action.");
			}

			$item = $rsp['rows'][0];
			$esc_content = addslashes($item['content']);
			$esc_created_at = addslashes($item['created_at']);
			$now = date('Y-m-d H:i:s');

			$rsp = db_insert('smol_archive', array(
				'account_id' => $esc_account_id,
				'service' => 'twitter',
				'filter' => 'faves',
				'data_id' => $esc_id,
				'target_id' => $esc_id,
				'content' => $esc_content,
				'created_at' => $esc_created_at,
				'archived_at' => $now
			));
			if (! $rsp['ok']){
				api_output_error(400, 'Error inserting fave into archive.');
			}
		} else {
			$rsp = db_write("
				DELETE FROM smol_archive
				WHERE target_id = '$esc_id'
				  AND account_id = $esc_account_id
			");
			if (! $rsp['ok']){
				api_output_error(400, 'Error deleting fave from archive.');
			}
		}

		api_output_ok(array(
			"{$action}d" => array(
				'id' => $esc_id
			)
		));
	}

	# the end
