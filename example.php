<?php

/**
 * Example application for the A-Select library.
 * 
 * @copyright 2008 SURFnet BV
 * @version $Id: example.php 144 2010-03-09 09:38:34Z hansz $
 */
include_once('agent.php');

// NB: this page will be called twice in the authentication process:
//     1. an unauthenticated user will be redirected away from this page
//        by the as_process call below; the user will authenticate on an
//        external page
//     2. after authentication the user will be redirected back to this page,
//        in which case an additional parameter called "aselect_credentials"
//        is added to the URL; this parameter is checked by as_process

$cfg = array(
	'client' => array(
		// application identifier to be used for this application
		'app_id'	=> 'example-application',
		// when signing is required (for production):
		// 'key' => '<path-to-private-key-pem>'
	),
	'server' => array(
		// A-Select server URL
		'url'		=> 'https://sp.example.org/federate/aselect',
		// A-Select server identifier
		'server_id'	=> 'sp.example.org',
	),
);

if (array_key_exists('request', $_GET) and ($_GET['request'] == 'logout')) {
	as_logout($cfg);
}

// perform the authentication against the A-Select server
// upon return if $result != NULL, authentication was succesful
$result = as_process($cfg, NULL, NULL, NULL, TRUE);

//  if the authentication succeeded, $result will now contain an array
//  that looks like: 
//    [uid] => john
//    [organization] => Example Organisation
//    [attributes] => Array
//        (
//            [urn:mace:dir:attribute-def:sn] => Array
//                (
//                    [0] => Doe
//                )
//
//            [urn:mace:dir:attribute-def:mail] => Array
//                (
//                    [0] => John.Doe@example.org
//                    [1] => john@example.org
//                )
//
//            [urn:mace:dir:attribute-def:givenName] => Array
//                (
//                    [0] => John
//                )
//
//            [urn:mace:dir:attribute-def:uid] => Array
//                (
//                    [0] => john
//                )
//        )

// when $result != NULL a session variable should be set that avoids
// calling verify_credentials for each time this file is accessed
// this is dependant on the session management used by your application
// ie. use session_start and set an 'authenticated' variable in $_SESSION

// redirect to "clean" URL!

print '<pre>' . htmlentities(print_r($result, TRUE)) . '</pre>';

print '<a href="' . (array_key_exists('SCRIPTNAME', $_SERVER) ? $_SERVER['SCRIPTNAME'] : '') . '?request=logout">Logout</a>';

?>
