<?php

/**
 * A-Select (Agent) functionality for communicating with an A-Select Server.
 * 
 * This code can be used for A-Select user authentication in stand-alone
 * PHP applications, without requiring running an additional (Java-based)
 * A-Select agent on the same host.
 * 
 * @copyright 2008 SURFnet BV
 * @version $Id: agent.php 144 2010-03-09 09:38:34Z hansz $
 */

include_once('utils.php');

/**
 * Initiate authentication to A-Select server.
 */
function as_authenticate($cfg, $app_url = NULL, $remote_organization = NULL, $home_organization = NULL, $forced_logon = FALSE) {

	$req = as_message_create(
		array(
			// NB: order is important here, when signing is enabled
			'request'				=> 'authenticate',
			'a-select-server'		=> $cfg['server']['server_id'],
			'app_id'				=> $cfg['client']['app_id'],
			'app_url'				=> isset($app_url) ? $app_url : as_get_self_url($_SERVER['REQUEST_URI']),
			'forced_logon'          => $forced_logon ? 'true' : 'false',
			'remote_organization'	=> $remote_organization,
		),
		array_key_exists('key', $cfg['client']) ? $cfg['client']['key'] : NULL
	);
	
	// establish an authentication session with the server
	$rsp = as_call($cfg['server']['url'], $req, array_key_exists('ssl', $cfg['server']) ? $cfg['server']['ssl'] : NULL);

	if ($rsp['result_code'] != '0000') {
		throw new Exception('Request on remote server returned error: ' . $rsp['_result']);
	}

	// redirect to the actual login page as returned by the A-Select server
	$redirect = $rsp['as_url'] . '&' . as_message_create(
				array(
					'a-select-server'	=> $cfg['server']['server_id'],
					'rid'				=> $rsp['rid'],
					'home_organization'	=> $home_organization,
				)
			);

	header('Location: ' . 	$redirect);
	exit;		
}

/**
 * Handle browser return redirect from remote A-Select server.
 */
function as_authenticate_return($cfg) {
	
	$result = array();
	
	$credentials = $_GET['aselect_credentials'];
	$rid = $_GET['rid'];

	if ( (!isset($credentials)) || (!isset($rid)) ) {
		throw new Exception('Error on return from login at remote server!');		
	}

	$req = as_message_create(
		array(
			// NB: order is important here, when signing is enabled
			'request'				=> 'verify_credentials',
			'a-select-server'		=> $cfg['server']['server_id'],
			'aselect_credentials'	=> $credentials,
			'rid'					=> $rid,
		),
		array_key_exists('key', $cfg['client']) ? $cfg['client']['key'] : NULL
	);	

	$rsp = as_call($cfg['server']['url'], $req, array_key_exists('ssl', $cfg['server']) ? $cfg['server']['ssl'] : NULL);
	
	if ($rsp['result_code'] != '0000') {
		if ($rsp['result_code'] == '0040') {
			throw new Exception('Login cancelled.');
		}
		throw new Exception('Request on remote server (' . $cfg['server']['url'] . ') returned error: ' . $rsp['_result']);
	}

	$result['uid'] = $rsp['uid'];
	$result['organization'] = $rsp['organization'];
	
	if (array_key_exists('attributes', $rsp)) {
		$decoded = base64_decode($rsp['attributes']);
		$attributes = array();
		foreach (explode('&', $decoded) as $parm) {
			$tuple = explode('=', $parm);
			$name = urldecode($tuple[0]);
			if (substr($name, strlen($name) - 2, 2) == '[]') {
				$name = substr($name, 0, strlen($name) - 2);
			}
			if (!array_key_exists($name, $attributes)) {
				$attributes[$name] = array();
			}
			$attributes[$name][] = urldecode($tuple[1]);
		}
		$result['attributes'] = $attributes;
	}
	return $result;
}

/**
 * Perform the actual authentication (called from applications)
 */
function as_process($cfg, $app_url = NULL, $remote_organization = NULL, $home_organization = NULL, $forced_logon = FALSE) {
	$result = NULL;
	if (!array_key_exists('aselect_credentials', $_GET)) {
		as_authenticate($cfg, $app_url, $remote_organization, $home_organization, $forced_logon);
	} else {
		$result = as_authenticate_return($cfg);
	}
	return $result;
}

function as_logout($cfg) {
	header('Location: ' . 	$cfg['server']['url'] . '?request=logout&app_id=' . $cfg['client']['app_id']);
	exit;
}

?>
