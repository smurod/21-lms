<?php

return [
    'levels' => [
        1 => ['name' => 'beginner',    'xp_required' => 0],
        2 => ['name' => 'intermediate', 'xp_required' => 500],
        3 => ['name' => 'advanced',     'xp_required' => 1500],
        4 => ['name' => 'expert',       'xp_required' => 3000],
        5 => ['name' => 'master',       'xp_required' => 5000],
        6 => ['name' => 'grandmaster',  'xp_required' => 8000],
    ],

    'xp_for_difficulty' => [
        'beginner'     => 50,
        'intermediate' => 150,
        'advanced'     => 300,
        'expert'       => 500,
    ],

    'xp_for_test_percent' => [
        'perfect' => 50,
        'partial' => 20,
    ],
];
