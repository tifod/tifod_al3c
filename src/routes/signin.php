<?php

use Slim\Http\Request;
use Slim\Http\Response;

require_once __DIR__ . '/../utilities.php';
require_once __DIR__ . '/../sql-utilities.php';

$app->get('/', function (Request $request, Response $response, array $args): Response {
    if (!empty($_GET['action']) and !empty($_GET['token'])) {
        // => there is a token with an associated action
        // vérifier que l'action est valide
        if (!in_array($_GET['action'], ['reset_password', 'init_password', 'mail_login'])) {
            throw new Exception("'?action=" . $_GET['action'] . "' non traitable");
        }

        // vérifier le token (et récupérer les infos utiles au cas où)
        $payload = jwt_decode($_GET['token']);
        $db = new DB();
        $req = $db->prepareNamedQuery('select_user_from_id_user');
        $req->execute(['id_user' => $payload['id_user']]);
        $res = $req->fetch();
        $user_infos = [
            'id_user' => $payload['id_user'],
            'email' => $res['email'],
            'user_role' => $res['user_role'],
        ];
        if ($user_infos['id_user'] == null || $user_infos['email'] == null || $user_infos['user_role'] == null) {
            console_log($payload);
            console_log($user_infos);
            throw new Exception("Les infos de l'utilisateur n'ont pas été correctement initialisés");
        }
        if ($res['last_user_update'] != $payload['last_user_update']) {
            throw new Exception("Ce lien n'est plus valide (votre compte a été modifié depuis l'émission de ce lien)");
        }

        // connecter l'utilisateur
        $_SESSION['current_user'] = $user_infos;

        // montrer la bonne page
        if (in_array($_GET['action'], ['reset_password', 'init_password'])) {
            return $response->withRedirect('/password-edit');
        } else if (in_array($_GET['action'], ['mail_login'])) {
            return $response->withRedirect('/');
        }
    } else if (empty($_SESSION['current_user'])) {
        // not logged => /login
        return $response->withRedirect(empty($_GET['redirect']) ? '/login' : $_GET['redirect']);
    } else if (!empty($_GET['redirect'])) {
        // s'il y a une redirection demandée, rediriger (on est pas des bêtes :p)
        return $response->withRedirect($_GET['redirect']);
    } else {
        // sinon, montrer bibliotheque
        return $response->write($this->view->render('bibliotheque.html.twig'));
    }
    // return $response;
});

$app->get('/login', function (Request $request, Response $response, array $args): Response {
    if (empty($_SESSION['current_user'])) {
        return $response->write($this->view->render('signin/login.html.twig', $_GET));
    } else {
        return $response->withRedirect(empty($_GET['redirect']) ? '/' : $_GET['redirect']);
    }
});

$app->post('/login', function (Request $request, Response $response): Response {
    // verifier login + mdp, si oui, mettre dans $_SESSION['current_user'] : user_role + id_user + prenom + nom
    $missing_fields_message = get_form_missing_fields_message(['email', 'password'], $_POST);
    if ($missing_fields_message) {
        alert($missing_fields_message, 3);
        return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
    }
    $db = new DB();
    $req = $db->prepareNamedQuery('select_user_from_email');
    $req->execute(['email' => $_POST['email']]);
    if ($req->rowCount() == 0) {
        alert("Cet email est inconnu", 3);
        return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
    }

    // get password hash (and infos at the same time)
    $res = $req->fetch();
    $user_infos = [
        'id_user' => $res['id_utilisateur'],
        'email' => $res['email'],
        'user_role' => $res['user_role'],
    ];

    // check password
    if (!password_verify($_POST['password'], $res['password_hash'])) {
        alert("Mot de passe incorrect", 3);
        return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
    }

    // 👍 all good : create session and redirect
    $_SESSION['current_user'] = $user_infos;
    return $response->withRedirect(empty($_GET['redirect']) ? '/' : $_GET['redirect']);
});

$app->get('/password-reset', function (Request $request, Response $response, array $args): Response {
    return $response->write($this->view->render('signin/password-reset.html.twig'));
});

$app->post('/password-reset', function (Request $request, Response $response): Response {
    if (empty($_POST['email'])) {
        alert(il_manque_les_champs(['email']), 3);
        return $response->withRedirect($request->getUri()->getPath());
    }

    // faire des tests pour vérifier que l'email renseigné est bien un primary_email
    $db = new DB();
    $req = $db->prepareNamedQuery('select_user_from_email');
    $req->execute(['email' => $_POST['email']]);
    if ($req->rowCount() == 0) {
        alert("Cet email nous est inconnu : $_POST[email])", 3);
        return $response->withRedirect($request->getUri()->getPath());
    }
    $user = $req->fetch();

    // générer un token pour que l'utilisateur puisse réinitialiser son mot de passe
    $jwt = jwt_encode([
        "last_user_update" => $user['last_user_update'],
        "id_user" => $user['id_user'],
    ], 20);

    $email = sendEmail(
        $this,
        $response,
        $_POST['email'],
        "Vous avez oublié votre de mot de passe ?",
        $this->view->render(
            'emails/password-reset.html.twig',
            ['url' => '/?action=reset_password&token=' . $jwt]
        )
    );
    if ($_ENV['app_mode'] == 'dev') {
        return $email;
    } else {
        alert("Un email avec un lien pour réinitialiser votre mot de passe vous a été envoyé", 1);
    }

    return $response->withRedirect('/login');
});

$app->get('/signup', function (Request $request, Response $response, array $args): Response {
    return $response->write($this->view->render('signin/signup.html.twig', $_GET));
});
$app->post('/signup', function (Request $request, Response $response, array $args): Response {
    // vérifier que l'email n'est pas déjà utilisé par un autre compte commercial/fournisseur
    $db = new DB();
    $req = $db->prepareNamedQuery('select_user_from_email');
    $req->execute(['email' => $_POST['email']]);
    if ($req->rowCount() > 0) {
        alert('Cette adresse email est déjà utilisée', 3);
        return $response->withRedirect($request->getUri()->getPath() . '?' . array_to_url_encoding($_POST));
    }

    // créer le compte
    $req = $db->prepareNamedQuery('insert_user');
    $req->execute([
        'email' => $_POST['email'],
        'user_role' => 'normal_user',
    ]);

    $req = $db->prepareNamedQuery('select_user_from_email');
    $req->execute(['email' => $_POST['email']]);
    $new_user = $req->fetch();

    $jwt = jwt_encode([
        "last_user_update" => $new_user['last_user_update'],
        "id_user" => $new_user['id_user'],
    ], 60 * 24);

    $email = sendEmail($this, $response, $_POST['email'], "Votre compte vient d'être créé", $this->view->render(
        'emails/email-new-user.html.twig',
        ['url' => '/?action=init_password&token=' . $jwt]
    ));
    if ($_ENV['app_mode'] == 'dev') {
        return $email;
    } else {
        alert("Votre compte vient d'être créé, un email vient de vous être envoyé</b>", 1);
    }

    return $response->withRedirect($request->getUri()->getPath());
});

$app->get('/logout', function (Request $request, Response $response, array $args): Response {
    session_destroy();
    return $response->withRedirect(empty($_GET['redirect']) ? '/' : $_GET['redirect']);
});

$app->get('/you-are-connected', function (Request $request, Response $response, array $args): Response {
    return $response->write($this->view->render('homepage.html.twig', [
        'title' => 'Welcome :)',
        'body' => "Can't wait to see what you gonna code ᕕ( ՞ ᗜ ՞ )ᕗ",
    ]));
});

$app->get('/password-edit', function (Request $request, Response $response, array $args): Response {
    if (empty($_SESSION['current_user'])) {
        throw new Exception("Vous devez être connecté pour accéder à cette page");
    }
    return $response->write($this->view->render('signin/password-edit.html.twig', ['email' => $_SESSION['current_user']['email']]));
});

$app->post('/password-edit', function (Request $request, Response $response, array $args): Response {
    if (empty($_SESSION['current_user'])) {
        throw new Exception("Vous devez être connecté pour accéder à cette page");
    }
    // vérifier que le mot de passe a bien été rentré
    if ($_POST['password1'] != $_POST['password2']) {
        alert('😕 Les deux mots de passes rentrées ne concordent pas, veuillez réessayer', 2);
        return $response->withRedirect($request->getUri()->getPath());
    } else if (strlen($_POST['password1']) < 8) {
        alert('Votre mot de passe doit contenir au moins 8 caractères', 2);
        return $response->withRedirect($request->getUri()->getPath());
    } else {
        $db = new DB();
        $req = $db->prepareNamedQuery('update_password_hash');
        $req->execute([
            "id_user" => $_SESSION["current_user"]["id_user"],
            "new_password_hash" => password_hash($_POST['password1'], PASSWORD_BCRYPT, ['cost' => 12]),
        ]);
        alert("👍 Votre mot de passe a été modifié avec succès", 1);
        return $response->withRedirect('/');
    }
});