<?php

return [
    'generate_thumbnails' => (bool) env('GENERATE_THUMBNAILS', true),
    'login_rate_limit' => max((int) env('GALLERY_LOGIN_RATE_LIMIT', 5), 1),
];
