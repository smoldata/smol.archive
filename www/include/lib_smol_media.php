<?php

	function smol_media_path($service, $data_id, $remote_url){

		# Make sure we haven't already downloaded this
		$path = smol_media_get_cached($service, $data_id, $remote_url);
		if ($path) {
			return $path;
		}

		# https://foo.com => foo.com, http://foo.bar.net => foo.bar.net
		if (! preg_match('#//(.+)$#', $remote_url, $matches)){
			return null;
		}

		$path = 'media/' . $matches[1];

		// This is something that Twitter does for image URLs
		// If the path ends with '.jpg:large', drop the ':large' part
		if (substr($path, -6, 6) == ':large'){
			$path = substr($path, 0, -6);
		}

		$abs_path = $GLOBALS['cfg']['smol_data_dir'] . $path;

		if (file_exists($abs_path)){
			smol_media_set_cached($service, $data_id, $remote_url, $path);
			return $path;
		}

		$rsp = http_get($remote_url);
		if (! $rsp['ok']){
			return null;
		}

		$dir = dirname($abs_path);
		if (! file_exists($dir)){
			mkdir($dir, 0755, true);
		}
		file_put_contents($abs_path, $rsp['body']);

		smol_media_set_cached($service, $data_id, $remote_url, $path);

		return $path;
	}

	########################################################################

	function smol_media_get_cached($service, $data_id, $remote_url){

		$esc_service = addslashes($service);
		$esc_data_id = addslashes($data_id);
		$esc_remote_url = addslashes($remote_url);
		$rsp = db_fetch("
			SELECT *
			FROM smol_media
			WHERE href = '$esc_remote_url'
			  AND service = '$esc_service'
			  AND data_id = $esc_data_id
		");

		if ($rsp['rows']){

			$media = $rsp['rows'][0];

			if ($media['redirect'] &&
			    $media_redirect != $remote_url){
				return smol_media_path($service, $data_id, $media['redirect']);
			}

			if (file_exists($media['path'])) {
				return $media['path'];
			} else {
				# We have a db record of something that isn't there!
				$rsp = db_write("
					DELETE FROM smol_media
					WHERE service = '$esc_service'
					  AND data_id = $esc_data_id
					  AND href = '$esc_remote_url'
				");
			}
		}

		return null;
	}

	########################################################################

	function smol_media_set_cached($service, $data_id, $remote_url, $path) {
		$now = date('Y-m-d H:i:s');
		$rsp = db_insert('smol_media', array(
			'service' => addslashes($service),
			'data_id' => addslashes($data_id),
			'href' => addslashes($remote_url),
			'path' => addslashes($path),
			'saved_at' => $now
		));
		return $rsp;
	}

	# the end
