<?php

use Slim\Http\Request;
use Slim\Http\Response;

require_once __DIR__ . '/../utilities.php';
require_once __DIR__ . '/../sql-utilities.php';

$app->get('/projects', function (Request $request, Response $response, array $args): Response {
    return $response->write('cette page fonctionne');
});