<?php
return [
    'client_id' => getenv('STRAVA_CLIENT_ID') ?: 'PUT_CLIENT_ID_HERE',
    'client_secret' => getenv('STRAVA_CLIENT_SECRET') ?: 'PUT_CLIENT_SECRET_HERE',
    'redirect_uri' => getenv('STRAVA_REDIRECT_URI') ?: 'https://app.toetap.run/tapsole/strava/callback.php',
    'scope' => 'read,activity:read_all,activity:write',
];
