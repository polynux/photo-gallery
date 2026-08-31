<?php

return [
    'generate_thumbnails' => (bool) env('GENERATE_THUMBNAILS', true),
    'login_rate_limit' => max((int) env('GALLERY_LOGIN_RATE_LIMIT', 5), 1),
    'thumbnail_max_dimension' => max((int) env('THUMBNAIL_MAX_DIMENSION', 1920), 1),
    'thumbnail_quality' => min(max((int) env('THUMBNAIL_QUALITY', 80), 1), 100),
];
