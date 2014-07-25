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

// Create Design Pass Client connection
$pass = new DesignPassClient($api, $key, $secret, $redirect, 'client_credentials', 'write');
if ($pass->authenticate()) {
    // Create user
    $params = array(
        'email'         => 'contacto+' . rand() . '@teachnova.com',
        'client_secret' => $secret,
        'password'      => 'userpassword',
    );
    $response = $pass->request('user', 'POST', $params);
    if (!empty($response->user)) {
        wrout('OK : user = ' . var_export($response->user, true));
        if (!empty($response->oauth_token)) {
            wrout('OK : token = ' . var_export($response->oauth_token, true));
        }
    } else {
        wrout("ERROR : Creating user [{$pass->lastErrorCode}] - {$pass->lastError}");
        exit;
    }

    $userid = $response->user->id;

    $params = array(
        'firstname'     => 'Mi nombre',
        'lastname'      => 'Mi apellido',
    );
    $response = $pass->request("user/$userid", 'PUT', $params);

    if (!empty($response->user)) {
        wrout('OK : user = ' . var_export($response->user, true));
    } else {
        wrout("ERROR : Updating user [{$pass->lastErrorCode}] - {$pass->lastError}");
        exit;
    }

} else {
    wrout('ERROR : ' . $pass->lastError);
}