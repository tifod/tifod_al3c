<?php

use Slim\Http\Request;
use Slim\Http\Response;

require_once __DIR__ . '/vendor/autoload.php';
session_start();
if (!empty($_SESSION['app_name']) && $_SESSION['app_name'] != dirname(__FILE__)) {
    unset($_SESSION['current_user']);
}
$_SESSION['app_name'] = dirname(__FILE__);

require_once 'src/utilities.php';
loadDotEnv();

require_once 'src/slim-config.php';

// routes
foreach (glob("src/routes/*.php") as $filename) require_once $filename;
foreach (glob("src/routes/**/*.php") as $filename) require_once $filename;

// URL ending with / redirects to URL without /
$app->get('{url:.*}/', function (Request $request, Response $response, array $args) {
    return $response->withStatus(301)->withRedirect($args["url"]);
});

// Run app
$app->run();
