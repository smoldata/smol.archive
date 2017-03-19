<?php

	loadlib('users');

	########################################################################

	function api_users_follow(){

		$username = post_str('username');
		if (! $username){
			api_output_error(400, 'Please include a username to follow.');
		}

		$follow = users_get_by_username($username);
		$rsp = db_insert('smol_follow', array(
			'user_id' => addslashes($GLOBALS['cfg']['user']['id']),
			'follow_id' => addslashes($follow['id']),
			'followed_at' => date('Y-m-d H:i:s')
		));

		if (! $rsp['ok']){
			api_output_error(400, "Error following");
		} else {
			api_output_ok(array(
				'followed' => array(
					'id' => $follow['id'],
					'username' => $username
				)
			));
		}
	}

	########################################################################

	function api_users_unfollow(){

		$username = post_str('username');
		if (! $username){
			api_output_error(400, 'Please include a username to unfollow.');
		}

		$follow = users_get_by_username($username);
		$esc_user_id = addslashes($GLOBALS['cfg']['user']['id']);
		$esc_follow_id = addslashes($follow['id']);
		$rsp = db_write("
			DELETE FROM smol_follow
			WHERE user_id = $esc_user_id
			  AND follow_id = $esc_follow_id
		");

		if (! $rsp['ok']){
			api_output_error(400, "Error unfollowing");
		} else {
			api_output_ok(array(
				'unfollowed' => array(
					'id' => $follow['id'],
					'username' => $username
				)
			));
		}
	}

	# the end
