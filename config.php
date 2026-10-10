<?php
declare(strict_types=1);
return [
    'host' => getenv('DB_HOST') ?: 'mysql-ccdoriv.alwaysdata.net',
    'user' => getenv('DB_USER') ?: 'ccdoriv',
    'password' => getenv('DB_PASSWORD') ?: '251804ys',
    'database' => getenv('DB_NAME') ?: 'ccdoriv_gestionarticulos',
    'charset' => 'utf8mb4',
];
