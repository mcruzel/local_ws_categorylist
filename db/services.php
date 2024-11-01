<?php
$functions = [
    'local_ws_categorylist_get_categories' => [
        'classname'   => 'local_ws_categorylist_external',
        'methodname'  => 'get_categories',
        'classpath'   => 'local/ws_categorylist/externallib.php',
        'description' => 'Get a list of all course categories on the platform, including the full path',
        'type'        => 'read',
        'capabilities'=> 'moodle/category:manage'
    ],
];

$services = [
    'List Categories Service' => [
        'functions' => ['local_ws_categorylist_get_categories'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
