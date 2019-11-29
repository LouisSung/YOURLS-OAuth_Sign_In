<?php
// Change to your own config :D
return [
	    'clientId'     => 'PUT_YOUR_GITLAB_OAUTH_APPLICATION_ID_HERE',    // should be [0-9a-f]{64}
	    'clientSecret' => 'PUT_YOUR_GITLAB_OAUTH_SECRET_HERE',    // should be 64 digits too
	    'redirectUri'  => YOURLS_SITE.'/admin/index.php',    // need no change, use this URI
	    'domain'       => 'https://YOUR.GITLAB_INSTANCE_DOMAIN.HERE'    // remember to use HTTPS
	];