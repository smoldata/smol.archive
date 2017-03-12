<?php

	loadlib('users');
	loadlib('twitter_api');
	loadlib('smol_media');

	########################################################################

	function data_twitter_template_values($account, $item, $data){

		$status = json_decode($data['json'], 'as hash');
		if ($status['retweeted_status']){
			$data['retweeted'] = true;
			$status = $status['retweeted_status'];
			$data['screen_name'] = $status['user']['screen_name'];
		} else if ($item['filter'] == 'faves'){
			$data['faved'] = true;
		}

		$data['html'] = data_twitter_content($status);
		$data['profile_image'] = data_twitter_profile_image($account, $status);
		$data['display_name'] = $status['user']['name'];
		$data['permalink'] = data_twitter_permalink($status);

		return $data;
	}

	########################################################################

	function data_twitter_get_by_id($req_as_account, $id){

		$esc_id = addslashes($id);
		$rsp = db_fetch("
			SELECT *
			FROM data_twitter
			WHERE id = $esc_id
		");
		if (! $rsp['ok']){
			return $rsp;
		}

		if ($rsp['rows'] &&
		    ! $rsp['rows'][0]['protected']){
			$status = json_decode($rsp['rows'][0]['json'], 'as hash');
			return array(
				'ok' => 1,
				'status' => $status,
				'cached' => true
			);
		}

		$rsp = twitter_api_get($req_as_account, "statuses/show/$id", array(
			'tweet_mode' => 'extended'
		));
		if (! $rsp['ok']){
			return $rsp;
		}
		$status = $rsp['result'];

		$rsp = data_twitter_save($status);
		if (! $rsp['ok']){
			return $rsp;
		}

		return array(
			'ok' => 1,
			'status' => $status,
			'cached' => false
		);
	}

	########################################################################

	function data_twitter_save($status){

		$json = json_encode($status);
		$href = data_twitter_url($status);
		$options = array(
			'plaintext' => true
		);
		$esc_id = addslashes($status['id_str']);
		$esc_screen_name = addslashes($status['user']['screen_name']);
		$created_at = date('Y-m-d H:i:s', strtotime($status['created_at']));
		$now = date('Y-m-d H:i:s');

		$content = data_twitter_content($status, $options);
		$full_content = data_twitter_content($status); // download media

		$protected = ($status['user']['protected']) ? 1 : 0;
		$is_retweet = ($status['retweeted_status']) ? 1 : 0;

		// From here on out we're talking about the RT'd status
		if ($is_retweet){
			$status = $status['retweeted_status'];
		}

		$favorite_count = $status['favorite_count'];
		$retweet_count = $status['retweet_count'];
		$is_reply = ($status['in_reply_to_status_id']) ? 1 : 0;
		$has_link = ($status['entities']['urls']) ? 1 : 0;
		$has_photo = data_twitter_has_media_type($status, 'photo');
		$has_gif = data_twitter_has_media_type($status, 'animated_gif');
		$has_video = data_twitter_has_media_type($status, 'video');

		$rsp = db_fetch("
			SELECT id
			FROM data_twitter
			WHERE id = $esc_id
		");

		if (empty($rsp['rows'])){
			$rsp = db_insert('data_twitter', array(
				'id' => $esc_id,
				'href' => addslashes($href),
				'screen_name' => $esc_screen_name,
				'content' => addslashes($content),
				'json' => addslashes($json),
				'protected' => $protected,
				'favorite_count' => $favorite_count,
				'retweet_count' => $retweet_count,
				'is_retweet' => $is_retweet,
				'is_reply' => $is_reply,
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
			$rsp = db_update('data_twitter', array(
				'favorite_count' => $favorite_count,
				'retweet_count' => $retweet_count,
				'updated_at' => date('Y-m-d H:i:s')
			), "id = $esc_id");
			if (! $rsp['ok']){
				return $rsp;
			}
		}
		
		return array(
			'ok' => 1,
			'saved_id' => $esc_id,
			'content' => $content
		);
	}

	########################################################################

	function data_twitter_has_media_type($status, $type){
		if ($status['entities']['media']){
			foreach ($status['entities']['media'] as $entity){
				if ($entity['type'] == $type){
					return 1;
				}
			}
		}
		return 0;
	}

	########################################################################

	function data_twitter_content($status, $options=array()){

		if (! is_array($options)){
			$options = array();
		}
		$defaults = array(
			'is_quoted' => false,
			'plaintext' => false
		);
		$options = array_merge($defaults, $options);

		$text = $status['text'];

		# The property 'full_text' was introduced with 'tweet_mode=extended'
		# https://dev.twitter.com/overview/api/upcoming-changes-to-tweets
		if ($status['full_text']){
			$text = $status['full_text'];
		}

		$extended_content = data_twitter_extended_content($status, $options);
		$options['extended_content'] = ! empty($extended_content);

		// A quoted tweet should not display its own quoted tweets
		if (! $options['is_quoted']){
			$quoted_content = data_twitter_quoted_content($status, $options);
		}

		$entities = data_twitter_get_entities($status);
		$content = data_twitter_insert_entities($status, $text, $entities, $options);

		if ($extended_content){
			$content .= " $extended_content";
		}

		if ($quoted_content){
			if ($status['display_text_range']) {
				$start = $status['display_text_range'][0];
				$end = $status['display_text_range'][1];
				# hmm do we even need this?
			}
			$content .= $quoted_content;
		}

		$content = nl2br($content, false);
		$content = preg_replace('/(<br>)+/', '<br>', $content);

		return $content;
	}

	########################################################################

	function data_twitter_url($status){
		$screen_name = $status['user']['screen_name'];
		$id = $status['id_str'];
		return strtolower("https://twitter.com/$screen_name/status/$id");
	}

	########################################################################

	function data_twitter_extended_content($status, $options){

		if (empty($status['extended_entities']) ||
		    empty($status['extended_entities']['media'])){
			return '';
		}

		$extended_content = '';
		foreach ($status['extended_entities']['media'] as $entity){
			$extended_content .= data_twitter_entity($status, $entity, $options);
		}
		return $extended_content;
	}

	########################################################################

	function data_twitter_quoted_content($status, $options) {
		$quoted_content = '';

		if ($status['quoted_status']) {
			$name = $status['quoted_status']['user']['name'];
			$screen_name = $status['quoted_status']['user']['screen_name'];
			$permalink = data_twitter_permalink($status['quoted_status'], $options);
			$quote_user = "<div class=\"user\">" .
				"<a href=\"https://twitter.com/$screen_name\">" .
					"<span class=\"name\">{$name}</span> " .
					"<span class=\"screen_name\">@$screen_name</span>" .
				"</a>" .
				" <span class=\"meta\"> &middot; $permalink</span>" .
			"</div>";
			$options['is_quoted'] = true;
			$quoted_content = data_twitter_content($status['quoted_status'], $options);
			$quoted_content = "$quote_user $quoted_content";
			$quoted_content = "<div class=\"quoted-status\">$quoted_content</div>";
		}

		return $quoted_content;
	}

	########################################################################

	function data_twitter_get_entities($status){

		$entities = array();

		$entity_types = array('hashtags', 'urls', 'user_mentions');
		foreach ($entity_types as $entity_type){
			if ($status['entities'][$entity_type]){
				foreach ($status['entities'][$entity_type] as $entity){
					$entity['type'] = $entity_type;
					$index = $entity['indices'][0];
					$entities[$index] = $entity;
				}
			}
		}

		if ($status['entities']['media']){
			foreach ($status['entities']['media'] as $entity){
				$entity['type'] = 'media';
				$index = $entity['indices'][0];
				$entities[$index] = $entity;
			}
		}

		ksort($entities);

		return $entities;
	}

	########################################################################

	function data_twitter_insert_entities($status, $text, $entities, $options=array()){
		$pos = 0;
		$content = '';
		foreach ($entities as $index => $entity){
			$content .= mb_substr($text, $pos, $entity['indices'][0] - $pos, 'utf8');
			$content .= data_twitter_entity($status, $entity, $options);
			$pos = $entity['indices'][1];
		}
		$content .= mb_substr($text, $pos, strlen($text) - $pos, 'utf8');
		return $content;
	}

	########################################################################

	function data_twitter_entity($status, $entity, $options){
		switch ($entity['type']){
			case 'hashtags':
				return data_twitter_entity_hashtag($status, $entity, $options);
			case 'urls':
				return data_twitter_entity_url($status, $entity, $options);
		 	case 'user_mentions':
				return data_twitter_entity_user_mention($status, $entity, $options);
			case 'media':
			case 'photo':
				return data_twitter_entity_image($status, $entity, $options);
			case 'animated_gif':
				return data_twitter_entity_animated_gif($status, $entity, $options);
		 	case 'video':
				return data_twitter_entity_video($status, $entity, $options);
		}
		return '';
	}

	########################################################################

	function data_twitter_entity_hashtag($status, $entity, $options){
		if (! $options['plaintext']){
			# TODO: convert this to a Smarty template
			return "<a href=\"https://twitter.com/search?q=%23{$entity['text']}&amp;src=hash\" class=\"entity entity-hashtag\">#<span class=\"text\">{$entity['text']}</span></a>";
		}else{
			return "#{$entity['text']}";
		}
	}

	########################################################################

	function data_twitter_entity_url($status, $entity, $options){

		# Check whether the entity is the same as a quoted tweet (if one exists)
		# which would be redundant
		if ($status['quoted_status']){
			$quoted_url = data_twitter_url($status['quoted_status']);
			if ($quoted_url == strtolower($entity['expanded_url'])){
				return '';
			}
		}

		if (! $options['plaintext']){
			# TODO: convert this to a Smarty template
			return "<a href=\"{$entity['expanded_url']}\" title=\"{$entity['expanded_url']}\">{$entity['display_url']}</a>";
		}else{
			return $entity['expanded_url'];
		}
	}

	########################################################################

	function data_twitter_entity_user_mention($status, $entity, $options){
		if (! $options['plaintext']){
			# TODO: convert this to a Smarty template
			return "<a href=\"https://twitter.com/{$entity['screen_name']}\" class=\"entity entity-user_mention\" title=\"{$entity['name']}\">@<span class=\"text\">{$entity['screen_name']}</span></a>";
		}else{
			return "@{$entity['screen_name']}";
		}
	}

	########################################################################

	function data_twitter_entity_image($status, $entity, $options){

		# Don't show media if there is extended content (i.e., attached media)
		if ($options['extended_content']){
			return '';
		}

		$media_path = smol_media_path('twitter', $status['id_str'], "{$entity['media_url']}:large");
		$media_url = $GLOBALS['cfg']['abs_root_url'] . $media_path;

		if (! $options['plaintext']){
			# TODO: convert this to a Smarty template
			# TODO: find a better alt attribute
			return "<a href=\"{$entity['expanded_url']}\" class=\"entity media entity-media\"><img src=\"$media_url\" alt=\"\"></a>";
		}else{
			return $media_url;
		}
	}

	########################################################################

	function data_twitter_entity_animated_gif($status, $entity, $options){

		$id = "gif-{$status['id_str']}";
		$poster_path = smol_media_path('twitter', $status['id_str'], "{$entity['media_url']}:large");
		$video_path = smol_media_path('twitter', $status['id_str'], $entity['video_info']['variants'][0]['url']);
		$video_url = $GLOBALS['cfg']['abs_root_url'] . $video_path;
		$poster_url = $GLOBALS['cfg']['abs_root_url'] . $poster_path;

		if (! $options['plaintext']){
			# TODO: convert this to a Smarty template
			$content = '';
			$content .= "<div id=\"$id\" class=\"entity media media-gif entity-media entity-media-gif\">";
			$content .= "<video id=\"$id-video\" src=\"$video_url\" poster=\"$poster_url\" preload=\"none\" loop></video>";
			$content .= "<a id=\"$id-toggle\" href=\"$video_url\"><span class=\"text\">gif</span></a>";
			$content .= "<script>var t = document.getElementById('$id-toggle'); t.addEventListener('click', function(e) { e.preventDefault(); document.getElementById('$id-video').play(); t.className = 'playing'; });</script>";
			$content .= "</div>";
			return $content;
		}else{
			return $video_url;
		}
	}

	########################################################################

	function data_twitter_entity_video($status, $entity, $options){

		$poster_path = smol_media_path('twitter', $status['id_str'], "{$entity['media_url']}:large");
		$poster_url = $GLOBALS['cfg']['abs_root_url'] . $poster_path;

		$video_urls = array();
		foreach ($entity['video_info']['variants'] as $variant) {
			if ($variant['content_type'] != 'video/mp4') {
				continue;
			}
			$video_urls[$variant['bitrate']] = $variant['url'];
		}

		ksort($video_urls);
		$video_path = array_pop($video_urls);
		$video_path = smol_media_path('twitter', $status['id_str'], $video_path);
		$video_url = $GLOBALS['cfg']['abs_root_url'] . $video_path;

		if (! $options['plaintext']){
			# TODO: convert this to a Smarty template
			$content = '';
			$content .= "<div class=\"entity media media-video entity-media-video\">";
			$content .= "<video src=\"$video_url\" poster=\"$poster_url\" preload=\"none\" controls></video>";
			$content .= "</div>";
			return $content;
		}else{
			return $video_url;
		}
	}

	########################################################################

	function data_twitter_permalink($status, $options=array()){
		$url = data_twitter_url($status);
		$timestamp = strtotime($status['created_at']);
		$date_time = date('M j, Y, g:i a', $timestamp);
		$time_diff = time() - $timestamp;
		if ($time_diff < 60) {
			$label = 'just now';
		} else if ($time_diff < 60 * 60) {
			$label = floor($time_diff / 60) . 'min';
		} else if ($time_diff < 60 * 60 * 24) {
			$label = floor($time_diff / (60 * 60)) . 'hr';
		} else {
			$label = date('M j', $timestamp);
		}
		return "<a href=\"$url\" title=\"$date_time\">$label</a>";
	}

	########################################################################

	function data_twitter_can_display_tweet($status){
		if ($status['user']['protected']){
			if (! $status['protected']){
				$esc_id = addslashes($status['id_str']);
				db_update('data_twitter', array(
					'protected' => 1
				), "id = $esc_id");
			}
			return false;
		}
		return true;
	}

	########################################################################

	function data_twitter_profile_image($account, $status) {

		$url = str_replace('_normal', '_bigger', $status['user']['profile_image_url']);
		$path = smol_media_path('twitter', $status['id_str'], $url);

		if (! $path) {
			$rsp = twitter_api_get_profile($account, $status['user']['id_str']);
			$profile = $rsp['profile'];

			$url = str_replace('_normal', '_bigger', $profile['profile_image_url']);
			$path = smol_media_path('twitter', $status['id_str'], $url);
			$orig_url = str_replace('_normal', '_bigger', $status['user']['profile_image_url']);

			if ($orig_url != $url) {
				// Save a redirect for the original URL
				$now = date('Y-m-d H:i:s');
				$rsp = db_insert('smol_media', array(
					'status_id' => addslashes($status['id_str']),
					'path' => null,
					'href' => $orig_url,
					'redirect' => $url,
					'saved_at' => $now
				));
			}
		}

		return $path;
	}

	# the end
