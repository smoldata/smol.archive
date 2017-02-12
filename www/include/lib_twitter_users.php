<?php

	function twitter_users_add_account($user, $token){

		$esc_user_id = addslashes($user['id']);
		$esc_twitter_id = addslashes($token['user_id']);
		$esc_screen_name = addslashes($token['screen_name']);
		$esc_access_token = addslashes($token['oauth_token']);
		$esc_access_secret = addslashes($token['oauth_token_secret']);

		# Make sure $user hasn't already added this Twitter account
		$rsp = db_fetch("
			SELECT *
			FROM twitter_users
			WHERE user_id = $esc_user_id
			  AND twitter_id = $esc_twitter_id
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		if (empty($rsp['rows'])){
			# Add the account
			$rsp = db_insert('twitter_users', array(
				'user_id' => $esc_user_id,
				'twitter_id' => $esc_twitter_id,
				'screen_name' => $esc_screen_name,
				'access_token' => $esc_access_token,
				'access_secret' => $esc_access_secret,
				'account_added_at' => date('Y-m-d H:i:s')
			));
		} else {
			# Update token/secret/screen_name
			$esc_id = addslashes($rsp['rows'][0]['id']);
			$rsp = db_update('twitter_users', array(
				'access_token' => $esc_access_token,
				'access_secret' => $esc_access_secret,
				'screen_name' => $esc_screen_name
			), "id = $esc_id");
		}
		return $rsp;
	}

	#################################################################

	function twitter_users_get_accounts($user){
		$esc_id = addslashes($user['id']);
		$rsp = db_fetch("
			SELECT *
			FROM twitter_users
			WHERE user_id = $esc_id
			ORDER BY account_added_at DESC
		");
		if ($rsp['ok']){
			return $rsp['rows'];
		} else {
			return array();
		}
	}
	
	# the end
