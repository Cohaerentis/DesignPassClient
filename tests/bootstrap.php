<?php

// require_once('PHPUnit/Runner/Version.php');
// require_once('PHPUnit/Util/Filesystem.php'); // workaround for PHPUnit <= 3.6.11
//require_once('PHPUnit/Autoload.php');
require_once(dirname(__FILE__) . '/../lib/DesignPassClient.php');
require_once(dirname(__FILE__) . '/../lib/wrlog.php');
require_once(dirname(__FILE__) . '/RequestTestCase.php');

if (file_exists('config.php')) {
    include('config.php');
} else {
    wrout('ERROR : No config file');
    wrout('- Rename config-dist.php to config.php');
    wrout('- Set $key, $secret and $redirect with App credentials you have received');
    exit(1);
}

wrlog::$enabled = true;
wrlog::$path = dirname(__FILE__) . '/../logs';
wrlog_request();

wrout("API : $api");

RequestTestCase::$read = new DesignPassClient($api, $key, $secret, $redirect, $mode, 'read');
if (!RequestTestCase::$read->authenticate()) {
    echo 'READ ERROR : ' . RequestTestCase::$read->lastError;
} else {
    $token = RequestTestCase::$read->oauth->token->accesstoken;
    wrout("READ : key($key), token({$token})");
}

RequestTestCase::$write = new DesignPassClient($api, $key, $secret, $redirect, $mode, 'write');
if (!RequestTestCase::$write->authenticate()) {
    echo 'WRITE ERROR : ' . RequestTestCase::$write->lastError;
} else {
    $token = RequestTestCase::$write->oauth->token->accesstoken;
    wrout("WRITE : key($key), token({$token})");
}
