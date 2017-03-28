<?php

	loadlib("http");
	loadlib("random");

	########################################################################

	function twitter_api_fave($account, $id){
		$path = "favorites/create";
		$params = array(
			'id' => $id
		);
		$rsp = twitter_api_post($account, $path, $params);
		return $rsp;
	}

	########################################################################

	function twitter_api_unfave($account, $id){
		$path = "favorites/destroy";
		$params = array(
			'id' => $id
		);
		$rsp = twitter_api_post($account, $path, $params);
		return $rsp;
	}

	########################################################################

	function twitter_api_get($account, $path, $params){
		$method = 'GET';
		$query = http_build_query($params);
		$base_url = $GLOBALS['cfg']['twitter_api_endpoint'] . $path . '.json';
		$url = "$base_url?$query";
		$token = array(
			'key' => $account['access_token'],
			'secret' => $account['access_secret']
		);
		$auth_header = twitter_api_oauth_authorization_header($method, $base_url, $params, $token);
		$headers = array(
			'Authorization' => $auth_header
		);
		$more = twitter_api_http_more();
		
		$rsp = http_get($url, $headers, $more);
		if (! $rsp['ok']){
			return $rsp;
		}

		$result = json_decode($rsp['body'], 'as hash');
		return array(
			'ok' => 1,
			'result' => $result
		);
	}

	########################################################################

	function twitter_api_post($account, $path, $params){
		$method = 'POST';
		$query = http_build_query($params);
		$url = $GLOBALS['cfg']['twitter_api_endpoint'] . $path . '.json';
		$url .= "?$query";

		$token = array(
			'key' => $account['access_token'],
			'secret' => $account['access_secret']
		);
		$auth_header = twitter_api_oauth_authorization_header($method, $url, $params, $token);
		$headers = array(
			'Authorization' => $auth_header
		);
		$more = twitter_api_http_more();
		
		$rsp = http_post($url, '', $headers, $more);
		if (! $rsp['ok']){
			return $rsp;
		}

		$result = json_decode($rsp['body'], 'as hash');
		return array(
			'ok' => 1,
			'result' => $result
		);
	}
	
	########################################################################

	function twitter_api_get_profile($account, $ext_id){

		$rsp = twitter_api_get($accounts, 'users/lookup', array(
			'user_id' => $ext_id
		));
		if (! $rsp['ok']){
			return $rsp;
		}

		return array(
			'ok' => 1,
			'profile' => $rsp['result'][0]
		);
	}

	########################################################################

	function twitter_api_oauth_request_token($redir=''){

		$callback = $GLOBALS['cfg']['abs_root_url'] . 'auth/twitter/';

		if ($redir){
			$enc_redir = urlencode($redir);
			$callback .= "?redir={$enc_redir}";
		}
		$method = 'POST';
		$url = $GLOBALS['cfg']['twitter_api_oauth_endpoint'] . 'request_token';
		$params = array();
		$token = array();
		$auth_header = twitter_api_oauth_authorization_header($method, $url, $params, $token, $callback);

		$post_fields = ''; // everything goes in the Authorization header
		$headers = array(
			'Authorization' => $auth_header
		);
		$more = twitter_api_http_more();

		$rsp = http_post($url, $post_fields, $headers, $more);
		return $rsp;
	}

	########################################################################

	function twitter_api_oauth_auth_url($request_token){
		$url = $GLOBALS['cfg']['twitter_api_oauth_endpoint'] . 'authenticate';
		$url .= '?oauth_token=' . twitter_api_urlencode($request_token);
		return $url;
	}

	########################################################################

	function twitter_api_oauth_access_token($key, $secret){
		$method = 'POST';
		$url = $GLOBALS['cfg']['twitter_api_oauth_endpoint'] . 'access_token';
		$params = array(
			'oauth_verifier' => $secret
		);
		$token = array(
			'key' => $key,
			'secret' => $secret
		);
		$auth_header = twitter_api_oauth_authorization_header($method, $url, $params, $token);

		$post_fields = http_build_query($params);
		$headers = array(
			'Authorization' => $auth_header
		);
		$more = twitter_api_http_more();

		$rsp = http_post($url, $post_fields, $headers, $more);
		return $rsp;
	}

	$url = $GLOBALS['cfg']['twitter_api_oauth_endpoint'] . 'authenticate';

	########################################################################

	function twitter_api_oauth_authorization_header($method, $url, $params, $token=null, $callback=null){

		$header_parts['oauth_nonce'] = random_string(43);
		$header_parts['oauth_signature_method'] = 'HMAC-SHA1';
		$header_parts['oauth_timestamp'] = gmdate('U');
		$header_parts['oauth_consumer_key'] = $GLOBALS['cfg']['twitter_api_consumer_key'];
		$header_parts['oauth_version'] = '1.0';
		if ($callback){
			$header_parts['oauth_callback'] = $callback;
		}
		if ($token['key']){
			$header_parts['oauth_token'] = $token['key'];
		}
		$params = array_merge($params, $header_parts);
		#dumper($params);

		$header_parts['oauth_signature'] = twitter_api_oauth_signature($method, $url, $params, $token);

		$enc_header_parts = array();
		foreach ($header_parts as $key => $value){
			$enc_key = twitter_api_urlencode($key);
			$enc_value = twitter_api_urlencode($value);
			$enc_header_parts[] = "$enc_key=\"$enc_value\"";
		}
		$enc_header = implode(', ', $enc_header_parts);
		$header = 'OAuth ' . $enc_header;

		return $header;
	}

	########################################################################

	function twitter_api_oauth_signature($method, $url, $params, $token=null){
		$base_string = twitter_api_oauth_base_string($method, $url, $params);
		#dumper($base_string);

		$key_parts = array(
			twitter_api_urlencode($GLOBALS['cfg']['twitter_api_consumer_secret']),
			($token['secret']) ? twitter_api_urlencode($token['secret']) : ''
		);
		$key = implode('&', $key_parts);
		#dumper($key);

		$signature = base64_encode(hash_hmac('sha1', $base_string, $key, true));
		return $signature;
	}

	########################################################################

	function twitter_api_oauth_base_string($method, $url, $params){
		$parts = array();
		$parts[] = strtoupper($method);
		$parts[] = $url;

		$enc_params = array();
		uksort($params, 'strcmp');
		foreach ($params as $key => $value){
			$enc_key = twitter_api_urlencode($key);
			$enc_value = twitter_api_urlencode($value);
			$enc_params[] = "$enc_key=$enc_value";
		}
		$parts[] = implode('&', $enc_params);

		$enc_parts = array_map('twitter_api_urlencode', $parts);
		return implode('&', $enc_parts);
	}

	########################################################################

	function twitter_api_urlencode($input){
		$encoded = rawurlencode($input);
		$encoded = str_replace('+', ' ', $encoded);
		$encoded = str_replace('%7E', '~', $encoded);
		return $encoded;
	}
	
	########################################################################
	
	function twitter_api_http_more(){
		return array(
			'user_agent' => 'Flamework app: ' . $GLOBALS['cfg']['site_name']
		);
	}

	# the end
