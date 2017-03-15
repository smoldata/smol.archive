<?php

	function smol_media_path($service, $data_id, $remote_url, $more=array()){

		$defaults = array(
			'http_timeout' => 15,  # in seconds
			'follow_redirects' => 3
		);
		$more = array_merge($defaults, $more);

		# Make sure we haven't already downloaded this
		$path = smol_media_get_cached($service, $data_id, $remote_url);
		if ($path) {
			return array(
				'ok' => 1,
				'path' => $path
			);
		}

		# https://foo.com => foo.com, http://foo.bar.net => foo.bar.net
		if (! preg_match('#//(.+)$#', $remote_url, $matches)){
			return array(
				'ok' => 0,
				'error' => 'invalid remote_url'
			);
		}

		$path = 'media/' . $matches[1];

		# mlkshk does a thing where URLs don't include file extensions,
		# so we add our own.
		if ($more['append_file_ext']){
			$path .= $more['append_file_ext'];
			unset($more['append_file_ext']);
		}

		# This is something that Twitter does for image URLs
		# If the path ends with '.jpg:large', drop the ':large' part
		if (substr($path, -6, 6) == ':large'){
			$path = substr($path, 0, -6);
		}

		$abs_path = $GLOBALS['cfg']['smol_data_dir'] . $path;

		if (file_exists($abs_path)){
			smol_media_set_cached($service, $data_id, $remote_url, $path);
			return array(
				'ok' => 1,
				'path' => $path
			);
		}

		$args = array();
		$rsp = http_get($remote_url, $args, $more);
		if (! $rsp['ok']){
			$status = 'error';
			smol_media_set_cached($service, $data_id, $remote_url, $path, $status);
			return $rsp;
		}

		$dir = dirname($abs_path);
		if (! file_exists($dir)){
			mkdir($dir, 0755, true);
		}
		file_put_contents($abs_path, $rsp['body']);

		if (function_exists('mb_strlen')) {
			$size = mb_strlen($rsp['body'], '8bit');
		} else {
			$size = strlen($rsp['body']);
		}

		# Create a poster image for animated gifs. This assumes that
		# *all* gifs are animated, which ... I guessss is ok?
		# (20170315/dphiffer)
		if (preg_match('/^(.+)\.gif$/', $abs_path, $matches)){
			$poster_path = "{$matches[1]}.jpg";
			$output = array();
			$cmd = "/usr/bin/convert {$abs_path}[0] $poster_path";
			exec($cmd, $output);
		}

		$status = 'ok';
		smol_media_set_cached($service, $data_id, $remote_url, $path, $status, $size);

		return array(
			'ok' => 1,
			'path' => $path
		);
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
			  AND data_id = '$esc_data_id'
			  AND status = 'ok'
		");

		if ($rsp['rows']){

			$media = $rsp['rows'][0];

			if ($media['redirect'] &&
			    $media_redirect != $remote_url){
				$rsp = smol_media_path($service, $data_id, $media['redirect']);
				if ($rsp['ok']){
					return $rsp['path'];
				}
			}

			if (file_exists($media['path'])) {
				return $media['path'];
			} else {
				# We have a db record of something that isn't there!
				$rsp = db_write("
					DELETE FROM smol_media
					WHERE service = '$esc_service'
					  AND data_id = '$esc_data_id'
					  AND href = '$esc_remote_url'
				");
			}
		}

		return null;
	}

	########################################################################

	function smol_media_set_cached($service, $data_id, $remote_url, $path, $status='ok', $bytes=0) {
		$now = date('Y-m-d H:i:s');
		$rsp = db_insert('smol_media', array(
			'service' => addslashes($service),
			'data_id' => addslashes($data_id),
			'href' => addslashes($remote_url),
			'path' => addslashes($path),
			'status' => addslashes($status),
			'bytes' => addslashes($bytes),
			'saved_at' => $now
		));
		return $rsp;
	}

	# the end
