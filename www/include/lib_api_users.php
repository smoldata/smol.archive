<?php

	loadlib('users');

	########################################################################

	function api_users_user_follow(){

		$username = post_str('username');
		if (! $username){
			api_output_error(400, 'Please include a username to follow.');
		}

		$follow = users_get_by_username($username);
		$rsp = db_insert('smol_follow', array(
			'user_id' => $GLOBALS['cfg']['user']['id'],
			'follow_id' => $follow['id']
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

	# the end
