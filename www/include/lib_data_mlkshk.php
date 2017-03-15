<?php

	loadlib('smol_media');

	########################################################################

	function data_mlkshk_save($item){

		$item['id'] = $item['sharekey'];
		$json = json_encode($item);
		$esc_id = addslashes($item['id']);
		$created_at = date('Y-m-d H:i:s', strtotime($item['posted_at']));
		$now = date('Y-m-d H:i:s');

		$options = array('plaintext' => true);
		$content = data_mlkshk_content($item, $options);
		$full_content = data_mlkshk_content($item);

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

		if (empty($rsp['rows'])){
			$rsp = db_insert('data_mlkshk', array(
				'id' => $esc_id,
				'href' => addslashes($item['permalink_page']),
				'name' => addslashes($item['name']),
				'title' => addslashes($item['title']),
				'description' => addslashes($item['description']),
				'content' => '', // We will come back to this, we need a numeric ID
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
			//$options = array('plaintext' => true);
			$content = data_mlkshk_content($item);

			$rsp = db_update('data_mlkshk', array(
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
		
		return array(
			'ok' => 1,
			'data_id' => $esc_id,
			'content' => $content,
			'created_at' => $created_at
		);
	}

	########################################################################

	function data_mlkshk_content($item, $args=array()){
		if ($args['source_url']){
			return "{$item['title']} {$item['description']} {$item['source_url']}";
		} else if ($args['plaintext']){
			return "{$item['title']} {$item['description']} {$item['original_image_url']}";
		} else {
			$href = $item['original_image_url'];
			$append_file_ext = null;
			if (preg_match('/\.\w+$/', $item['name'], $matches)){
				$append_file_ext = $matches[0];
			}
			$media_path = smol_media_path('mlkshk', $item['id'], $href, $append_file_ext);
			$media_url = $GLOBALS['cfg']['abs_root_url'] . $media_path;
			return "{$item['title']} {$item['description']} <img src=\"$media_url\" alt=\"\">";
		}
	}

	########################################################################

	function data_mlkshk_template_values($account, $item, $data){
		//dumper($data);
		$details = json_decode($data['json'], 'as hash');
		$data['html'] = data_mlkshk_content($details);
		return $data;
	}

	# the end
