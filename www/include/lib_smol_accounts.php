<?php

	loadlib('smol_archive_twitter');

	########################################################################
	
	function smol_accounts_get_following_usernames($user){
		$esc_user_id = addslashes($user['id']);
		$esc_follow_id = addslashes($check_user['id']);
		$rsp = db_fetch("
			SELECT users.username
			FROM users, smol_follow
			WHERE smol_follow.user_id = $esc_user_id
			  AND smol_follow.follow_id = users.id
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		$usernames = array();
		foreach ($rsp['rows'] as $user){
			$usernames[] = $user['username'];
		}

		return $usernames;
	}

	########################################################################

	function smol_accounts_get_all_usernames(){
		$rsp = db_fetch("
			SELECT username
			FROM users
			ORDER BY username
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		$usernames = array();
		foreach ($rsp['rows'] as $user){
			$usernames[] = $user['username'];
		}

		return $usernames;
	}

	########################################################################

	function smol_accounts_is_following($user, $check_user){
		$esc_user_id = addslashes($user['id']);
		$esc_follow_id = addslashes($check_user['id']);
		$rsp = db_fetch("
			SELECT *
			FROM smol_follow
			WHERE user_id = $esc_user_id
			  AND follow_id = $esc_follow_id
		");
		return ($rsp['rows']) ? true : false;
	}

	########################################################################

	function smol_accounts_get_services($accounts){

		$services = array();

		foreach ($accounts as $account){
			$service = $account['service'];
			if (! $services[$service]){
				$views_function = "smol_archive_{$service}_views";
				if (function_exists($views_function)){
					$services[$service] = $views_function($account);
				} else {
					$services[$service] = array(
						'label' => $service
					);
				}
			}
		}

		return $services;
	}

	########################################################################

	function smol_accounts_get_user_accounts($user, $include_disabled=false){

		$esc_id = addslashes($user['id']);
		$where_clause = "user_id = $esc_id";

		if (! $include_disabled){
			$where_clause .= " AND enabled = 1";
		}

		$rsp = db_fetch("
			SELECT *
			FROM smol_account
			WHERE $where_clause
			ORDER BY added_at DESC
		");
		if ($rsp['ok']){
			return $rsp['rows'];
		} else {
			return array();
		}
	}

	########################################################################

	function smol_accounts_get_following_accounts($user){

		$esc_id = addslashes($user['id']);
		$where_clause = "user_id = $esc_id";

		$rsp = db_fetch("
			SELECT *
			FROM smol_follow
			WHERE $where_clause
			ORDER BY followed_at
		");
		if (! $rsp['ok']){
			return array();
		}

		$user_ids = array();
		foreach ($rsp['rows'] as $follow){
			$user_ids[] = addslashes($follow['follow_id']);
		}
		$user_id_list = implode(', ', $user_ids);
		$where_clause = "user_id IN ($user_id_list)";

		$rsp = db_fetch("
			SELECT *
			FROM smol_account
			WHERE $where_clause
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
