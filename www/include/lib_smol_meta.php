<?php

	function smol_meta_set($account, $name, $value){

		$format = 'text';
		if (! is_scalar($value) &&
		    ! is_null($value)){
			$value = json_encode($value);
			$format = 'json';
		}

		$esc_account_id = addslashes($account['id']);
		$esc_name = addslashes($name);
		$esc_value = addslashes($value);

		$rsp = db_write("
			DELETE FROM smol_meta
			WHERE name = '$esc_name'
			  AND account_id = $esc_account_id
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		$updated_at = date('Y-m-d H:i:s');
		$rsp = db_insert('smol_meta', array(
			'account_id' => $esc_account_id,
			'name' => $esc_name,
			'value' => $esc_value,
			'format' => $format,
			'updated_at' => $updated_at
		));
		return $rsp;
	}

	########################################################################

	function smol_meta_get($account, $name){
		$esc_name = addslashes($name);
		$esc_account_id = addslashes($account['id']);
		$rsp = db_fetch("
			SELECT value, format
			FROM smol_meta
			WHERE account_id = $esc_account_id
			  AND name = '$esc_name'
		");
		if (! $rsp['rows']){
			return null;
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
