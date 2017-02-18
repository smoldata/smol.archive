<?php

	function twitter_status_content($status, $options=array()){

		if (! is_array($options)){
			$options = array();
		}
		$defaults = array(
			'is_quoted' => false,
			'plaintext' => false
		);
		$options = array_merge($defaults, $options);

		if ($status['retweeted_status']){
			return twitter_status_content($status['retweeted_status']);
		}

		$text = $status['text'];

		# The property 'full_text' was introduced with 'tweet_mode=extended'
		# https://dev.twitter.com/overview/api/upcoming-changes-to-tweets
		if ($status['full_text']){
			$text = $status['full_text'];
		}

		$extended_content = twitter_status_extended_content($status, $options);
		$options['extended_content'] = ! empty($extended_content);

		// A quoted tweet should not display its own quoted tweets
		if (! $options['is_quoted']){
			$quoted_content = twitter_status_quoted_content($status, $options);
		}

		$entities = twitter_status_get_entities($status);
		$content = twitter_status_insert_entities($status, $text, $entities, $options);

		if ($extended_content){
			$content .= " $extended_content";
		}

		if ($quoted_content){
			if ($status['display_text_range']) {
				$start = $status['display_text_range'][0];
				$end = $status['display_text_range'][1];
			}
			$content .= $quoted_content;
		}

		$content = preg_replace('/\n+/', "\n", $content);
		$content = nl2br($content);

		return $content;
	}

	#################################################################

	function twitter_status_url($status){
		$screen_name = $status['user']['screen_name'];
		$id = $status['id'];
		return strtolower("https://twitter.com/$screen_name/status/$id");
	}

	#################################################################

	function twitter_status_extended_content($status, $options){

		if (empty($status['extended_entities']) ||
		    empty($status['extended_entities']['media'])){
			return '';
		}

		foreach ($status['extended_entities']['media'] as $entity){
			return twitter_status_entity($status, $entity, $options);
		}
		return '';
	}

	#################################################################

	function twitter_status_quoted_content($status, $options) {
		$quoted_content = '';

		if (! empty($status['quoted_status'])) {
			$is_quoted = true;
			$name = $status['quoted_status']['user']['name'];
			$screen_name = $status['quoted_status']['user']['screen_name'];
			$permalink = twitter_status_permalink($status['quoted_status'], $options);
			$quote_user = "<div class=\"user\">" .
				"<a href=\"https://twitter.com/$screen_name\">" .
					"<span class=\"name\">{$name}</span> " .
					"<span class=\"screen_name\">@$screen_name</span>" .
				"</a>" .
				" <span class=\"meta\"> &middot; $permalink</span>" .
			"</div>";
			$quoted_content = twitter_status_content($status['quoted_status'], $is_quoted);
			$quoted_content = "$quote_user $quoted_content";
			$quoted_content = "<div class=\"quoted-status\">$quoted_content</div>";
		}

		return $quoted_content;
	}

	#################################################################

	function twitter_status_get_entities($status){

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

	#################################################################

	function twitter_status_insert_entities($status, $text, $entities, $options=array()){
		$pos = 0;
		$content = '';
		foreach ($entities as $index => $entity){
			$content .= mb_substr($text, $pos, $entity['indices'][0] - $pos, 'utf8');
			$content .= twitter_status_entity($status, $entity, $options);
			$pos = $entity['indices'][1];
		}
		$content .= mb_substr($text, $pos, strlen($text) - $pos, 'utf8');
		return $content;
	}

	#################################################################

	function twitter_status_entity($status, $entity, $options){
		switch ($entity['type']){
			case 'hashtags':
				return twitter_status_entity_hashtag($status, $entity, $options);
			case 'urls':
				return twitter_status_entity_url($status, $entity, $options);
		 	case 'user_mentions':
				return twitter_status_entity_user_mention($status, $entity, $options);
			case 'media':
			case 'photo':
				return twitter_status_entity_image($status, $entity, $options);
			case 'animated_gif':
				return twitter_status_entity_animated_gif($status, $entity, $options);
		 	case 'video':
				return twitter_status_entity_video($status, $entity, $options);
		}
		return '';
	}

	#################################################################

	function twitter_status_entity_hashtag($status, $entity, $options){
		if (! $options['plaintext']){
			# TODO: convert this to a Smarty template
			return "<a href=\"https://twitter.com/search?q=%23{$entity['text']}&amp;src=hash\" class=\"entity entity-hashtag\">#<span class=\"text\">{$entity['text']}</span></a>";
		}else{
			return "#{$entity['text']}";
		}
	}

	#################################################################

	function twitter_status_entity_url($status, $entity, $options){

		# Check whether the entity is the same as a quoted tweet (if one exists)
		# which would be redundant
		if ($status['quoted_status']){
			$quoted_url = twitter_status_url($status['quoted_status']);
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

	#################################################################

	function twitter_status_entity_user_mention($status, $entity, $options){
		if (! $options['plaintext']){
			# TODO: convert this to a Smarty template
			return "<a href=\"https://twitter.com/{$entity['screen_name']}\" class=\"entity entity-user_mention\" title=\"{$entity['name']}\">@<span class=\"text\">{$entity['screen_name']}</span></a>";
		}else{
			return "@{$entity['screen_name']}";
		}
	}

	#################################################################

	function twitter_status_entity_image($status, $entity, $options){

		# Don't show media if there is extended content (i.e., attached media)
		if ($options['extended_content']){
			return '';
		}

		$media_url = twitter_status_media($status['id'], "{$entity['media_url']}:large");

		if (! $options['plaintext']){
			# TODO: convert this to a Smarty template
			# TODO: find a better alt attribute
			return "<a href=\"{$entity['expanded_url']}\" class=\"entity media entity-media\"><img src=\"$media_url\" alt=\"\"></a>";
		}else{
			return $media_url;
		}
	}

	#################################################################

	function twitter_status_entity_animated_gif($status, $entity, $options){

		$id = "gif-{$status['id']}";
		$poster_url = twitter_status_media($status['id'], "{$entity['media_url']}:large");
		$video_url = twitter_status_media($status['id'], $entity['video_info']['variants'][0]['url']);

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

	#################################################################

	function twitter_status_entity_video($status, $entity, $options){

		$poster_url = twitter_status_media($status['id'], "{$entity['media_url']}:large");

		$video_urls = array();
		foreach ($entity['video_info']['variants'] as $variant) {
			if ($variant['content_type'] != 'video/mp4') {
				continue;
			}
			$video_urls[$variant['bitrate']] = $variant['url'];
		}

		ksort($video_urls);
		$video_url = array_pop($video_urls);
		$video_url = twitter_status_media($status['id'], $video_url);

		if (! $options['plaintext']){
			# TODO: convert this to a Smarty template
			$content = '';
			$content .= "<div class=\"entity media media-video entity-media-video\">";
			$content .= "<video src=\"$video_url\" poster=\"$poster_url\" preload=\"none\" controls></video>";
			$content .= "</div>";
		}else{
			return $video_url;
		}
	}

	#################################################################

	function twitter_status_permalink($status, $options=array()) {
		$url = twitter_status_url($status);
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

	#################################################################

	function twitter_status_can_display_tweet($status) {
		if ($status['user']['protected']) {
			if (! $status['protected']){
				$esc_id = addslashes($status['id']);
				db_update('twitter_status', array(
					'protected' => 1
				), "id = $esc_id");
			}
			return false;
		}
		return true;
	}

	#################################################################

	function twitter_status_media($tweet_id, $remote_url) {

		# not yet
		return $remote_url;

		$path = twitter_status_media_get_cached($tweet_id, $remote_url);
		if ($path) {
			return $path;
		}
		if (! preg_match('#//(.+)$#', $remote_url, $matches)) {
			return $remote_url;
		}
		$path = 'data/media/' . $matches[1];
		if (preg_match('/(\.\w+):\w+$/', $path, $matches)) {
			// Don't save files that end with '.jpg:large', instead use '.jpg:large.jpg'
			$path .= $matches[1];
		}
		if (file_exists($path)) {
			twitter_status_media_set_cached($tweet_id, $remote_url, $path);
			return $path;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $remote_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 8);
		$data = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		if ($info['http_code'] < 200 ||
				$info['http_code'] > 299) {
			dbug($info);
			return false;
		}
		$dir = dirname($path);
		if (! file_exists($dir)) {
			mkdir($dir, 0755, true);
		}
		if (! file_exists($dir)) {
			return false;
		}
		file_put_contents($path, $data);

		twitter_status_media_set_cached($tweet_id, $remote_url, $path);

		return $path;
	}

	function twitter_status_media_get_cached($tweet_id, $remote_url) {
		$cached = query("
			SELECT *
			FROM twitter_media
			WHERE tweet_id = ?
				AND href = ?
		", array($tweet_id, $remote_url));
		if (! empty($cached)) {
			$media = $cached[0];
			if (! empty($media['redirect']) &&
					$media_redirect != $remote_url) {
				return twitter_status_media($tweet_id, $media['redirect']);
			}
			if (file_exists($media['path'])) {
				return $media['path'];
			} else {
				query("
					DELETE FROM twitter_media
					WHERE tweet_id = ?
						AND href = ?
				", array($tweet_id, $remote_url));
				return null;
			}
		}
	}

	function twitter_status_media_set_cached($tweet_id, $remote_url, $path) {
		$now = date('Y-m-d H:i:s');
		query("
			INSERT INTO twitter_media
			(tweet_id, href, path, saved_at)
			VALUES (?, ?, ?, ?)
		", array($tweet_id, $remote_url, $path, $now));
	}

	function twitter_status_profile_image($tweet) {
		$url = str_replace('_normal', '_bigger', $tweet['user']['profile_image_url']);

		# not yet
		return $url;

		$path = twitter_status_media($tweet['id'], $url);
		if (! $path) {
			$updated_user = twitter_users_profile($tweet['user']['id']);
			$url = str_replace('_normal', '_bigger', $updated_user['profile_image_url']);
			$path = twitter_status_media($tweet['id'], $url);
			$orig_url = str_replace('_normal', '_bigger', $tweet['user']['profile_image_url']);
			if ($orig_url != $url) {
				$now = date('Y-m-d H:i:s');
				query("
					INSERT INTO twitter_media
					(tweet_id, path, href, redirect, saved_at)
					VALUES (?, ?, ?, ?, ?)
				", array($tweet['id'], $orig_url, $url, $now));
			}
		}
		return $path;
	}

	
