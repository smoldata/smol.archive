<?php

	loadlib('smol_media');
	loadlib('url');

	########################################################################

	function data_mlkshk_save($item, $verbose=false){

		$item['id'] = $item['sharekey'];
		$json = json_encode($item);
		$esc_id = addslashes($item['id']);
		$created_at = date('Y-m-d H:i:s', strtotime($item['posted_at']));
		$now = date('Y-m-d H:i:s');
		$content = data_mlkshk_content($item);
		$has_link = preg_match('/https?:\/\/\w+/i', $item['description']) ? 1 : 0;
		$has_photo = $item['original_image_url'] ? 1 : 0;
		$has_gif = preg_match('/\.gif$/', $item['name']) ? 1 : 0;
		$has_video = $item['source_url'] ? 1 : 0;

		if ($has_gif && $has_photo){
			$has_photo = 0;
		}

		$rsp = db_fetch("
			SELECT id
			FROM data_mlkshk
			WHERE id = '$esc_id'
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		$source_url = null;
		if ($item['source_url']){
			$source_url = $item['source_url'];
		} else if ($item['url']){
			$source_url = $item['url'];
		}

		if (empty($rsp['rows'])){
			$rsp = db_insert('data_mlkshk', array(
				'id' => $esc_id,
				'href' => addslashes($item['permalink_page']),
				'title' => addslashes($item['title']),
				'description' => addslashes($item['description']),
				'content' => addslashes($content),
				'name' => addslashes($item['name']),
				'source_url' => addslashes($source_url),
				'json' => addslashes($json),
				'like_count' => addslashes($item['likes']),
				'save_count' => addslashes($item['saves']),
				'comment_count' => addslashes($item['comments']),
				'is_nsfw' => $is_retweet,
				'has_link' => $has_link,
				'has_photo' => $has_photo,
				'has_gif' => $has_gif,
				'has_video' => $has_video,
				'created_at' => $created_at,
				'saved_at' => $now,
				'updated_at' => $now
			));
			if (! $rsp['ok']){
				return $rsp;
			}
		}

		else {
			$item['id'] = $rsp['rows'][0]['id'];
			$content = data_mlkshk_content($item);

			$rsp = db_update('data_mlkshk', array(
				'json' => addslashes($json),
				'like_count' => addslashes($item['likes']),
				'save_count' => addslashes($item['saves']),
				'comment_count' => addslashes($item['comments']),
				'is_nsfw' => addslashes($item['nsfw']),
				'updated_at' => date('Y-m-d H:i:s')
			), "id = '$esc_id'");
			if (! $rsp['ok']){
				return $rsp;
			}
		}

		$rsp = data_mlkshk_get_by_id($account, $item['id']);
		if (! $rsp['ok']){
			return $rsp;
		}

		# The docs say 'source_url', but the API returns plain 'url'
		if (! $item['url'] &&
		    ! $item['source_url']){
			data_mlkshk_download_media($rsp['data'], $verbose);
		}

		return array(
			'ok' => 1,
			'data_id' => $esc_id,
			'content' => $content,
			'created_at' => $created_at
		);
	}

	########################################################################

	function data_mlkshk_content($item){
		if ($args['source_url']){
			return "{$item['title']}\n{$item['description']}\n{$item['source_url']}";
		} else {
			return "{$item['title']}\n{$item['description']}\n{$item['original_image_url']}";
		}
	}

	########################################################################

	function data_mlkshk_get_by_id($account, $id){

		$esc_id = addslashes($id);
		$rsp = db_fetch("
			SELECT *
			FROM data_mlkshk
			WHERE id = '$esc_id'
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		$cached = false;
		if ($rsp['rows']){
			$cached = true;
			$data = $rsp['rows'][0];
		} else {
			$rsp = mlkshk_api_get($account, "sharedfile/$id");
			if (! $rsp['ok']){
				return $rsp;
			}
			$data = $rsp['result'];
		}

		return array(
			'ok' => 1,
			'data' => $data,
			'cached' => $cached
		);
	}

	########################################################################

	function data_mlkshk_download_media($data, $verbose=false){
		$details = json_decode($data['json'], 'as hash');
		$remote_url = $details['original_image_url'];
		$append_file_ext = null;
		if (preg_match('/\.\w+$/', $data['name'], $matches)){
			$append_file_ext = $matches[0];
		}
		if ($verbose){
			echo "downloading $remote_url ...";
		}
		$rsp = smol_media_path('mlkshk', $data['id'], $remote_url, array(
			'http_timeout' => 120,
			'append_file_ext' => $append_file_ext
		));
		if ($verbose && $rsp['ok']){
			echo " success\n";
		} else if ($verbose){
			echo " error\n";
		}
		return $rsp;
	}

	########################################################################

	function data_mlkshk_template_values($account, $item, $data){

		$details = json_decode($data['json'], 'as hash');

		$data['description'] = data_mlkshk_description_html($data);

		if ($data['source_url']){
			$data['video_embed'] = url_video_embedify($data['source_url']);
		} else {
			$rsp = data_mlkshk_download_media($data);
			if ($rsp['ok']){
				$media_path = $rsp['path'];
				$media_url = $GLOBALS['cfg']['abs_root_url'] . $media_path;
			} else {
				$media_url = $details['original_image_url'];
			}

			$data['image_src'] = $media_url;
			$data['image_href'] = $details['permalink_page'];

			if ($data['has_gif']){
				$data['poster_src'] = preg_replace('/\.gif$/', '.jpg', $media_url);
				$data['javascript'] = "
					\$('#gif-{$data['id']}').click(function(e) {
						if (! \$('#gif-{$data['id']}').hasClass('gif-playing')) {
							e.preventDefault();
							var \$img = \$('#gif-{$data['id']} img');
							var src = \$img.attr('src');
							src = src.replace(/\.jpg$/, '.gif');
							\$img.get(0).src = src;
							\$('#gif-{$data['id']}').addClass('gif-playing');
						}
					});
				";
			}
		}

		return $data;
	}
	
	function data_mlkshk_description_html($item){
		$html = $item['description'];
		$html = url_linker($html);
		$html = nl2br($html);
		return $html;
	}

	# the end
