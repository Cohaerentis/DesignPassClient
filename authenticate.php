#!/usr/bin/php
<?php
/**
 * Design Pass Client Sample
 * - Authentication by client_credentials
 *
 * @author Antonio Espinosa <aespinosa@teachnova.com>
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3
 * @version 0.1
 */

require_once(dirname(__FILE__) . '/lib/wrlog.php');
require_once(dirname(__FILE__) . '/lib/DesignPassClient.php');

// wrlog
wrlog::$enabled = true;
wrlog::$path = dirname(__FILE__) . '/logs';

if (file_exists('config.php')) {
    include('config.php');
} else {
    wrout('ERROR : No config file');
    wrout('- Rename config-dist.php to config.php');
    wrout('- Set $key, $secret and $redirect with App credentials you have received');
    exit(1);
}

if ($mode == 'client_credentials') {
    $pass = new DesignPassClient($api, $key, $secret, $redirect, $mode, 'read');
    if ($pass->authenticate()) {
        wrout("Authenticated - READ : key($key), token({$pass->oauth->token->accesstoken})");
    } else {
        wrout('ERROR : ' . $pass->lastError);
    }

    $pass = new DesignPassClient($api, $key, $secret, $redirect, $mode, 'write');
    if ($pass->authenticate()) {
        wrout("Authenticated - WRITE : key($key), token({$pass->oauth->token->accesstoken})");
    } else {
        wrout('ERROR : ' . $pass->lastError);
    }

} else { // mode = authorization_code
    $pass = new DesignPassClient($api, $key, $secret, $redirect, $mode, 'profile');
    if ($pass->authenticate()) {
        wrout("Authenticated - PROFILE : key($key), token({$pass->oauth->token->accesstoken})");
    } else {
        if ($pass->lastErrorCode == 'ERROR_FOLLOW_LINK') {
            wrout('STATE    : ' . $pass->stateGet());
            wrout('AUTH URL : ' . $pass->authURLGet());

        } else {
            wrout('ERROR : ' . $pass->lastError);
        }
    }

}