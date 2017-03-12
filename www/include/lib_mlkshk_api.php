<?php

	loadlib('random');

	########################################################################

	function mlkshk_api_get($account, $path, $params=array()){

		if ($params){
			$path .= '?' . http_build_query($params);
		}

		$auth_header = mlkshk_api_authorization_header(array(
			'token' => $account['token'],
			'secret' => $account['secret'],
			'method' => 'GET',
			'path' => $path
		));

		$headers = array(
			'Authorization' => $auth_header
		);
		$more = mlkshk_api_http_more();

		$url = $GLOBALS['cfg']['mlkshk_api_endpoint'] . $path;
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

	function mlkshk_api_authorization_header($args){

		$host = 'mlkshk.com';
		$port = '80'; # should this be 443?
		$timestamp = time();
		$nonce = random_string();

		$base_parts = array(
			$args['token'],
			$timestamp,
			$nonce,
			$args['method'],
			$host,
			$port,
			"/api/{$args['path']}"
		);
		$base_string = implode("\n", $base_parts) . "\n";

		$hash = hash_hmac('sha1', $base_string, $args['secret'], true);
		$signature = base64_encode($hash);

		$template = 'MAC token="%s", timestamp="%s", nonce="%s", signature="%s"';
		$header = sprintf($template, $args['token'], $timestamp, $nonce, $signature);

		return $header;
	}

	########################################################################

	function mlkshk_api_get_auth_url(){

		$url = $GLOBALS['cfg']['mlkshk_api_endpoint'] . 'authorize' .
		       '?response_type=code' .
		       '&client_id=' . $GLOBALS['cfg']['mlkshk_api_key'];

		return $url;
	}

	########################################################################

	function mlkshk_api_get_auth_token($code){

		$url = $GLOBALS['cfg']['mlkshk_api_endpoint'] . 'token';
		$redirect = $GLOBALS['cfg']['abs_root_url'] . 'auth/mlkshk/';
		$data = http_build_query(array(
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $redirect,
			'client_id' => $GLOBALS['cfg']['mlkshk_api_key'],
			'client_secret' => $GLOBALS['cfg']['mlkshk_api_secret']
		));

		$headers = array();
		$more = mlkshk_api_http_more();
		$rsp = http_post($url, $data, $headers, $more);

		return $rsp;
	}

	########################################################################

	function mlkshk_api_http_more(){
		$more = array(
			'http_timeout' => 30 # mlkshk.com is a bit slow
		);
		return $more;
	}

	# the end
