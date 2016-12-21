<?php

require_once('/etc/freepbx.conf');

try {
    # Initialize FreePBX environment
    $bootstrap_settings['freepbx_error_handler'] = false;
    define('FREEPBX_IS_AUTH',1);

    $name = $argv[1];
    $tftpdir = "/var/lib/tftpboot";
    $sql = "SELECT `id`,`model_id`,`ipv4`,`ipv4_new`,`gateway`,`mac` FROM `gateway_config` WHERE `name` = ?";
    $sth = FreePBX::Database()->prepare($sql);
    $sth->execute(array($name));
    $config = $sth->fetch(\PDO::FETCH_ASSOC);
    if ($config === false){
        /*Configuration doesn't exist*/
        error_log("Configuration not found");
        exit(1);
    }
    $sql = "SELECT `model`,`manufacturer` FROM `gateway_models` WHERE `id` = ?";
    $sth = FreePBX::Database()->prepare($sql);
    $sth->execute(array($config['model_id']));
    $res = $sth->fetch(\PDO::FETCH_ASSOC);
    $config['model'] = $res['model'];
    $config['manufacturer'] = $res['manufacturer'];

    if (!isset($config['mac'])|| $config['mac']==''){
        $config['mac'] = 'AAAAAAAAAAAA';
    }
    if ($config['manufacturer'] == 'Sangoma'){
        unlink($tftpdir."/".preg_replace('/:/','',$config['mac']).".config.txt");
        unlink($tftpdir."/".preg_replace('/:/','',$config['mac'])."script.txt");
    } else {
        unlink($tftpdir."/".preg_replace('/:/','',$config['mac']).".cfg");
    }
} catch (Exception $e){
        error_log($e->getMessage());
        exit(1);

}