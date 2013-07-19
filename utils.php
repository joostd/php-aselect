<?php

/**
 * A-Select utility functions.
 * 
 * @copyright 2008 SURFnet BV
 * @version $Id: utils.php 23 2008-10-24 09:57:46Z hansz $
 */

/**
 * Read certificates and keys from files.
 * 
 * @param	string	$cert_key_file	the name of the file that contains the key/cert
 * @return	string					the file contents as a string
 */
function as_read_pem($cert_key_file) {
	if (!file_exists($cert_key_file)) {
		throw new Exception('Could not find certificate/key file: ' . $cert_key_file);
	}
	$fp = fopen($cert_key_file, "r");
	$contents = fread($fp, 8192);
	fclose($fp);	
	return $contents;
}

/**
 * Create a urlencoded cgi-formatted message, possibly with a signature on it.
 * 
 * @param	array	$parms			the request parameters as key,value pairs
 * @param	string	$key			(optionally) the name of the file with the private key to sign with
 * @return	string					the cgi-formatted HTTP response
 */
function as_message_create($parms, $key = NULL) {
	$result = NULL;
	$data = '';
	foreach ($parms as $name => $value) {
		if (!isset($value)) continue;
		if ($result != NULL) $result .= '&';
		$result .= urlencode($name) . '=' . urlencode($value);
		if (isset($key) and ($name != 'request')) $data .= $value;
	}
	if (isset($key)) {
		$signature = '';
		if (openssl_sign($data, $signature, as_read_pem($key)) != TRUE) {
			throw new Exception('Signing the request failed: ' . openssl_error_string());
		}
		$result .= '&signature=' . urlencode(base64_encode($signature));
	}
	return $result;
}

/**
 * Parse a response message into an array.
 * 
 * @parm	string	$msg	the HTTP response contents
 * @return	array			an array of key,value pairs parsed from the response
 */
function as_message_parse($msg) {
	$parms = array();
	foreach (explode('&', $msg) as $p) {
		$tuple = explode('=', $p);
		$parms[urldecode($tuple[0])] = urldecode($tuple[1]);
	}
	return $parms;
}

/**
 * Perform an HTTP GET request and return the result.
 */
function as_http_get($url, $ssl = NULL, $http_code = 200, $exception_on_error = true) {
	$ch = curl_init();
	if (isset($ssl)) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl['verify_peer'] ? 1 : 0);
		if (isset($ssl['verify_host'])) curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $ssl['verify_host']);
		if (isset($ssl['capath'])) curl_setopt($ch, CURLOPT_CAPATH, $ssl['capath']);
		if (isset($ssl['cainfo'])) curl_setopt($ch, CURLOPT_CAINFO, $ssl['cainfo']);
	}
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	$result = curl_exec($ch);
	if ($result !== FALSE) {
		$error = curl_error($ch);
		if ($error != '') $result = FALSE;
		if ($result !== FALSE) {
			$info = curl_getinfo($ch);
			if ($info['http_code'] != $http_code) $result = FALSE;
		}
	}
	curl_close($ch);
	if ($result === FALSE) {
		if ($exception_on_error) {
			throw new Exception('HTTP GET request (' . $url . ') failed: ' . $error);
		} else {
			utils_error_report('HTTP GET request (' . $url . ') failed (HTTP code: ' . $http_code . '): ' . print_r($error, true));
		}
	}
	return $result;
}

/**
 * Helper function for sending a non-browser request to a remote server.
 * 
 * @param	string/array	$urls	URL(s) to send the request to
 * @param	string			$req	the request query part
 * @param	array			$ssl	SSL options
 * @return							an array with the parsed result parameters
 */
function as_call($url, $req, $ssl = NULL) {
	$result = as_http_get($url . '?' . $req, $ssl);
	$parms = as_message_parse($result);
	$parms['_result'] = $result;
	return $parms;
}

/**
 * Returns a URL to the specified file/handler/path.
 * 
 * @param	string	$file	optional handler/file
 * @param	string	$path	optional leading path (default is CFG_PATH WWW otherwise)
 * @return					the resulting URL
 */
function as_get_self_url($path = NULL) {
	$port = $_SERVER['SERVER_PORT'];
	if (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == 'on') {
		$proto = 'https';
		if ($port == 443) $port = NULL;
	} else {
		$proto = 'http';
		if ($port == 80) $port = NULL;
	}
	$url = $proto . '://' . $_SERVER['SERVER_NAME'] . (isset($port) ? ':' . $port : '');
	if (isset($path)) $url .= $path;
	return $url;
}

?>
