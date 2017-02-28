<?php

	function twitter_meta_set($account, $name, $value){

		$format = 'text';
		if (! is_scalar($value)){
			$value = json_encode($value);
			$format = 'json';
		}

		$esc_account_id = addslashes($account['id']);
		$esc_name = addslashes($name);
		$esc_value = addslashes($value);

		$rsp = db_write("
			DELETE FROM twitter_meta
			WHERE name = '$esc_name'
			  AND account_id = $esc_account_id
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		$rsp = db_insert('twitter_meta', array(
			'account_id' => $esc_account_id,
			'name' => $esc_name,
			'value' => $esc_value,
			'format' => $format
		));
		return $rsp;
	}

	########################################################################

	function twitter_meta_get($account, $name, $default_value = null){
		$esc_name = addslashes($name);
		$esc_account_id = addslashes($account['id']);
		$rsp = db_fetch("
			SELECT value
			FROM twitter_meta
			WHERE account_id = $esc_account_id
			  AND name = '$esc_name'
		");
		if (! $rsp['rows']){
			return $default_value;
		} else {
			$result = $rsp['rows'][0];
			$value = $result['value'];
			if ($result['format'] == 'json'){
				$value = json_decode($value, 'as hash');
			}
			return $value;
		}
	}

	# the end
