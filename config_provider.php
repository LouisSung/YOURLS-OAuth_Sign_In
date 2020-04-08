<?php
// Register a new GitHub application: https://github.com/settings/developers
return [
    'clientId'     => 'PUT_YOUR_GITHUB_OAUTH_APPLICATION_ID_HERE',    // should be [0-9a-f]{20}
    'clientSecret' => 'PUT_YOUR_GITHUB_OAUTH_SECRET_HERE',    // should be 40 digits
    'redirectUri'  => YOURLS_SITE.'/admin/index.php',    // need no change, use this URI
];
