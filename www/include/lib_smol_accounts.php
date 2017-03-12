<?php

	function smol_accounts_get_services($user){
		$services = array();
		$accounts = smol_accounts_get_accounts($user);

		foreach ($accounts as $account){
			if (! in_array($account['service'], $services)){
				$services[] = $account['service'];
			}
		}

		return $services;
	}

	########################################################################

	function smol_accounts_get_accounts($user){
		$esc_id = addslashes($user['id']);
		$rsp = db_fetch("
			SELECT *
			FROM smol_account
			WHERE user_id = $esc_id
			  AND enabled = 1
			ORDER BY added_at DESC
		");
		if ($rsp['ok']){
			return $rsp['rows'];
		} else {
			return array();
		}
	}

	########################################################################

	function smol_accounts_add_account($user, $account){

		$esc_user_id = addslashes($user['id']);
		$esc_service = addslashes($account['service']);
		$esc_ext_id = addslashes($account['ext_id']);
		$esc_screen_name = addslashes($account['screen_name']);
		$esc_token = addslashes($account['token']);
		$esc_secret = addslashes($account['secret']);

		# Make sure $user hasn't already added this account
		$rsp = db_fetch("
			SELECT *
			FROM smol_account
			WHERE user_id = $esc_user_id
			  AND ext_id = $esc_ext_id
			  AND service = '$esc_service'
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		$now = date('Y-m-d H:i:s');

		if (empty($rsp['rows'])){
			# Add the account
			$rsp = db_insert('smol_account', array(
				'user_id' => $esc_user_id,
				'service' => $esc_service,
				'ext_id' => $esc_ext_id,
				'screen_name' => $esc_screen_name,
				'token' => $esc_token,
				'secret' => $esc_secret,
				'added_at' => $now,
				'updated_at' => $now
			));
		} else {
			# Update token/secret/screen_name
			$esc_id = addslashes($rsp['rows'][0]['id']);
			$rsp = db_update('smol_account', array(
				'token' => $esc_token,
				'secret' => $esc_secret,
				'screen_name' => $esc_screen_name,
				'updated_at' => $now
			), "id = $esc_id");
		}
		return $rsp;
	}

	########################################################################

	function smol_accounts_remove_account($account_id){
		$esc_account_id = addslashes($account_id);
		$rsp = db_write("
			DELETE FROM
			smol_account
			WHERE id = $esc_account_id
		");
		return $rsp;
	}

	########################################################################

	function smol_accounts_disable_account($account_id){
		$esc_account_id = addslashes($account_id);
		$now = date('Y-m-d H:i:s');
		$rsp = db_update('smol_account', array(
			'enabled' => 0
		), "id = $esc_account_id");
		return $rsp;
	}

	########################################################################

	function smol_accounts_enable_account($account_id){
		$esc_account_id = addslashes($account_id);
		$now = date('Y-m-d H:i:s');
		$rsp = db_update('smol_account', array(
			'enabled' => 1
		), "id = $esc_account_id");
		return $rsp;
	}

	# the end
