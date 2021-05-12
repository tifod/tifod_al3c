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
    
    $req = $db ->prepareNamedQuery('select_projects_where_id_participant');
    $req-> execute(['id_participant' => $args['idUser']]);
    $participants = $req->fetchAll();

    $req = $db -> prepareNamedQuery('select_projectsBrouillon_where_id_user');
    $req -> execute(['id_user' => $args['idUser']]);
    $brouillons = $req ->fetchAll();


    $req = $db -> prepareNamedQuery('count_nbProject_by_id_participants');
    $req -> execute(['id_participant' => $args['idUser']]);
    $nbProjectsParticipants = $req ->fetch();

    $req = $db -> prepareNamedQuery('count_nbProject_by_id_owners');
    $req -> execute(['id_owner' => $args['idUser']]);
    $nbProjetsProject = $req ->fetch();

    $req = $db -> prepareNamedQuery('count_nbAbonnee_by_id_user');
    $req -> execute(['id_user' => $args['idUser']]);
    $nbAbonnees = $req ->fetch();

    $req = $db -> prepareNamedQuery('count_nbAbonnement_by_id_abonnee');
    $req -> execute(['id_abonnee' => $args['idUser']]);
    $nbAbonnements = $req ->fetch();

    $req = $db -> prepareNamedQuery('count_nbBrouillon_by_id_user');
    $req -> execute(['id_user' => $args['idUser']]);
    $nbBrouillons = $req ->fetch();

    return $response->write($this->view->render('user/user_id.html.twig', [
        'user' => $user,
        'projects' => $projects,
        'participants'=>$participants,
        'brouillons' => $brouillons,
        'nbProjectsParticipants' => $nbProjectsParticipants,
        'nbProjects' =>$nbProjetsProject,
        'nbAbonnees' => $nbAbonnees,
        'nbAbonnements'=> $nbAbonnements,
       'nbBrouillons' => $nbBrouillons,
    ]));
});