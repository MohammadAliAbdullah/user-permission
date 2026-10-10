<?php
/**
 * bnsis_permission connection settings (MySQL / MariaDB).
 * Override with environment variables when deploying.
 */
return [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => (int)  (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME') ?: 'bnsis_permission',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '',
    'socket'   => getenv('DB_SOCKET') ?: '/run/mysqld/mysqld.sock',
    'charset'  => 'utf8mb4',
];
