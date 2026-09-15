<?php

return [
    'generate_thumbnails' => (bool) env('GENERATE_THUMBNAILS', true),
    'login_rate_limit' => max((int) env('GALLERY_LOGIN_RATE_LIMIT', 5), 1),
    'thumbnail_max_dimension' => max((int) env('THUMBNAIL_MAX_DIMENSION', 500), 1),
    'display_max_dimension' => max((int) env('DISPLAY_MAX_DIMENSION', 2560), 1),
    'derivative_quality' => min(max((int) env('DERIVATIVE_QUALITY', 80), 1), 100),
    'univers_derivative_sizes' => array_values(array_filter(array_map(
        'intval',
        explode(',', env('UNIVERS_DERIVATIVE_SIZES', '300,500,800')),
    ))),
    'univers_derivative_quality' => min(max((int) env('UNIVERS_DERIVATIVE_QUALITY', 80), 1), 100),
];
