<?php

	loadlib('twitter_api');

	########################################################################

	function twitter_users_add_account($user, $token){

		$esc_user_id = addslashes($user['id']);
		$esc_twitter_id = addslashes($token['user_id']);
		$esc_screen_name = addslashes($token['screen_name']);
		$esc_access_token = addslashes($token['oauth_token']);
		$esc_access_secret = addslashes($token['oauth_token_secret']);

		# Make sure $user hasn't already added this Twitter account
		$rsp = db_fetch("
			SELECT *
			FROM twitter_account
			WHERE user_id = $esc_user_id
			  AND twitter_id = $esc_twitter_id
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		$now = date('Y-m-d H:i:s');

		if (empty($rsp['rows'])){
			# Add the account
			$rsp = db_insert('twitter_account', array(
				'user_id' => $esc_user_id,
				'twitter_id' => $esc_twitter_id,
				'screen_name' => $esc_screen_name,
				'access_token' => $esc_access_token,
				'access_secret' => $esc_access_secret,
				'added_at' => $now,
				'updated_at' => $now
			));
		} else {
			# Update token/secret/screen_name
			$esc_id = addslashes($rsp['rows'][0]['id']);
			$rsp = db_update('twitter_account', array(
				'access_token' => $esc_access_token,
				'access_secret' => $esc_access_secret,
				'screen_name' => $esc_screen_name,
				'updated_at' => $now
			), "id = $esc_id");
		}
		return $rsp;
	}

	########################################################################

	function twitter_users_get_accounts($user){
		$esc_id = addslashes($user['id']);
		$rsp = db_fetch("
			SELECT *
			FROM twitter_account
			WHERE user_id = $esc_id
			ORDER BY added_at DESC
		");
		if ($rsp['ok']){
			return $rsp['rows'];
		} else {
			return array();
		}
	}

	########################################################################

	function twitter_users_profile($id){

		// Arguably, we should pass in the Twitter account this is being
		// requested from. This approach was simpler.
		// (20170224/dphiffer)

		if (! $GLOBALS['cfg']['user']){
			return array(
				'ok' => 0,
				'error' => 'Not signed in.'
			);
		}

		$accounts = twitter_users_get_accounts($GLOBALS['cfg']['user']);
		if (empty($accounts)){
			return array(
				'ok' => 0,
				'error' => 'No Twitter accounts.'
			);
		}

		$rsp = twitter_api_get($accounts[0], 'users/lookup', array(
			'user_id' => $id
		));
		if (! $rsp['ok']){
			return $rsp;
		}

		return array(
			'ok' => 1,
			'profile' => $rsp['result'][0]
		);
	}
	
	# the end
