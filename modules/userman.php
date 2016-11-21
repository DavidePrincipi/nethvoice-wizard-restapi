<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

function getUser($username) {

    # add domain part if needed
    if (strpos($username, '@') === false) {
        exec('/usr/bin/hostname -d', $out, $ret);
        $domain = $out[0];
        return "$username@$domain";
    }
    return $username;
}

function userExists($username) {
    exec("/usr/bin/getent passwd '".getUser($username)."'", $out, $ret);
    return ($ret === 0);
}

$app->get('/users', function (Request $request, Response $response, $args) {
    $users = FreePBX::create()->Userman->getAllUsers();
    return $response->withJson($users);
});


$app->get('/users/{username}', function (Request $request, Response $response, $args) {
    $username = $request->getAttribute('username');
    if (userExists($username)) {
        return $response->withJson(['result' => userExists($username)], 200);
    } else {
        return $response->withJson(['result' => 'Not found'], 404);
    }
});


# Create or edit a system user inside OpenLDAP
# Should be used only in legacy mode.
#
# JSON body:
#
# {"username" : "myuser", "fullname" : "my full name"}


$app->post('/users', function (Request $request, Response $response, $args) {
    $params = $request->getParsedBody();
    $username = $params['username'];
    $fullname = $params['fullname'];
    if ( ! $username || ! $fullname ) {
        return $response->withJson(['result' => 'User name or full name invalid'], 422);
    }
    if ( userExists($username) ) {
        exec("/usr/bin/sudo /sbin/e-smith/signal-event user-modify '$username' '$fullname' '/bin/false'", $out, $ret);
    } else {
        exec("/usr/bin/sudo /sbin/e-smith/signal-event user-create '$username' '$fullname' '/bin/false'", $out, $ret);
    }
    if ( $ret === 0 ) {
        return $response->withJson(['result' => true], 201);
    } else {
        return $response->withJson(['result' => false], 422);
    }
});


# Set the password of a given user
# Should be used only in legacy mode.
#
# JSON body:
#
# {"password" : "mypassword"}

$app->post('/users/{username}/password', function (Request $request, Response $response, $args) {
    $params = $request->getParsedBody();
    $username = $request->getAttribute('username');
    $password = $params['password'];
    if ( ! userExists($username) ) {
        return $response->withJson(['result' => "$username user doesn't exist"], 422);
    } else {
        $tmp = tempnam("/tmp","ASTPWD");
        file_put_contents($tmp, $password);

        exec("/usr/bin/sudo /sbin/e-smith/signal-event password-modify '".getUser($username)."' $tmp", $out, $ret);
        return $response->withJson(['result' => ($ret === 0)]);
    }
    return $response->withJson(['result' => false], 422);
}); 

# Return the password givent user in clear text
# Should be used only in legacy mode.

$app->get('/users/{username}/password', function (Request $request, Response $response, $args) {
    $params = $request->getParsedBody();
    $username = $request->getAttribute('username');
    return $response->withJson(['result' => ''], 404);
});

