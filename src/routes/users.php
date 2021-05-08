<?php

use Slim\Http\Request;
use Slim\Http\Response;

require_once __DIR__ . '/../utilities.php';
require_once __DIR__ . '/../sql-utilities.php';

$app->get('/users/{idUser}', function (Request $request, Response $response, array $args): Response {
    $db = new DB();
    $req = $db->prepareNamedQuery('select_projects_where_id_owner');
    $req->execute(['id_owner' => $args['idUser']]);
    $projects = $req->fetchAll();

    $req = $db->prepareNamedQuery('select_user_from_id_user');
    $req->execute(['id_user' => $args['idUser']]);
    $user = $req->fetch();
    
    return $response->write($this->view->render('user/user_id.html.twig', [
        'user' => $user,
        'projects' => $projects,
    ]));
});