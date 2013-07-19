php-aselect
===========

A-Select agent library in php 

This code was originally written by Hans Zandbelt.

See example.php for an example of how to use the agent library. 
Note that library functions start with an "as_" prefix.

Configuration
-------------

All library function calls require a parameter array containing configuration data. Two entries are relevant:

* client - containing client parameters:
  * app_id - A-Select application identifier to be used for this application
  * key - key to sign requests with (if signing is required)
* server - containing server parameters (obtained from the A-select server administrator)
  * url - A-Select server URL
  * server_id - A-Select server identifier


if the authentication succeeded, as_process will return an array that looks like: 

	[uid] => john
	[organization] => Example Organisation
	[attributes] => Array
	    (
	        [urn:mace:dir:attribute-def:sn] => Array
	            (
	                [0] => Doe
	            )
	
	        [urn:mace:dir:attribute-def:mail] => Array
	            (
	                [0] => John.Doe@example.org
	                [1] => john@example.org
	            )
	
	        [urn:mace:dir:attribute-def:givenName] => Array
	            (
	                [0] => John
	            )
	
	        [urn:mace:dir:attribute-def:uid] => Array
	            (
	                [0] => john
	            )
	    )

Caveats
-------

Upon succesful authentication, i.e. `as_process` returns something different from `NULL`, a session variable should be set that avoids calling `verify_credentials` each time this file is accessed.
This is dependant on the session management used by your application, i.e. use `session_start` and set an "authenticated" variable in `$_SESSION`

Also, your apploication should redirect to a "clean" URL to avoid unnecessary re-authentication when users reload the page.

Test
----

You can easily test the example script using a php 5.4 build-in web server by running the following command and pointing your browser to http://localhost:8080/example.php

	$ php -S localhost:8080
	PHP 5.4.17 Development Server started at Fri Jul 19 16:33:25 2013
	Listening on http://localhost:8080
	Document root is /path/to/php-aselect
	Press Ctrl-C to quit.
	[Fri Jul 19 16:33:30 2013] ::1:51099 [302]: /
	[Fri Jul 19 16:33:35 2013] ::1:51102 [200]: /?rid=ffd5f592bd398c6ce812a1b3ec5340b6&a-select-server=sp.example.org&aselect_credentials=P/71M8qjygd3Pem8HFP/81ERDPECYrS9W1JxqEN1nSoH6vFuaLOc0wKIuvb5XhYgXn6sbFXHVdhoNqe6/Xjn-ZluJoFFIzV96fPPs20mnSCN5TFoyKaJMNkn6rVEepUtSxPDsjs/B1vphqVdHmWGaq62mi2wfi/W7FiW/floZUw_
	[Fri Jul 19 16:33:58 2013] ::1:51106 [302]: /?request=logout
