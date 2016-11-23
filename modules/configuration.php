<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require_once('lib/SystemTasks.php');

function getLegacyMode() {
    exec("/usr/bin/sudo /sbin/e-smith/config getprop nethvoice LegacyMode", $out);
    return $out[0];
}

function setLegacyMode($value) {
    exec("/usr/bin/sudo /sbin/e-smith/config setprop nethvoice LegacyMode $value", $out, $ret);
}


# get enabled mode

$app->get('/configuration/mode', function (Request $request, Response $response, $args) {
    $mode = getLegacyMode();

    # return 'unknown' if LegacyMode prop is not set
    if ( $mode == "" ) {
        return $response->withJson(['result' => 'unknown']);
    }

    exec("/usr/bin/rpm -q nethserver-directory", $out, $ret);

    # return true, if LegacyMode is enabled and nethserver-directory is installed
    if ($mode == "enabled" && $ret === 0) {
        return $response->withJson(['result' => "legacy"]);
    }
    return $response->withJson(['result' => "uc"]);
});


# set mode to legacy or uc
#
# JSON body: { "mode" : <mode> } where <mode> can be: "legacy" or "uc"
#

$app->post('/configuration/mode', function (Request $request, Response $response, $args) {
    $params = $request->getParsedBody();

    if ($params['mode'] == "legacy") {
        setLegacyMode('enabled');
        $st = new SystemTasks();
        $task = $st->startTask("/usr/bin/sudo /usr/libexec/nethserver/pkgaction --install nethserver-directory");
        return $response->withJson(['result' => $task]);
    } else if ($params['mode'] == "uc") {
        setLegacyMode('disabled');
        return $response->withJson(['result' => 'success'], 200);
    } else {
        return $response->withJson(['result' => 'Invalid mode'], 422);
    }
});

