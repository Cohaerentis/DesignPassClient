#!/usr/bin/php
<?php
/**
 * Design Pass Client Sample
 * - Authentication by authorization_code
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

$accesstoken = !empty($argv[1]) ? $argv[1] : '';
if (empty($accesstoken)) {
    wrout('ERROR : No access token');
    wrout("Usage : {$argv[0]} <accesstoken>");
    exit;
}

$pass = new DesignPassClient($api, $key, $secret, $redirect, 'authorization_code', 'profile');

// Token needs at least an accesstoken field not empty
$token = new OAuthConsumer();
$token->accesstoken = $accesstoken;

$pass->accessTokenSet($token);

wrout("Access token = $token->accesstoken");
// Get profile
$response = $pass->request('user/profile');
if (!empty($response->user)) {
    wrout('OK : User = ' . var_export($response->user, true));

    // Get usertypes
    $response = $pass->request('user/usertype');
    if (!empty($response->usertypes)) {
        wrout('OK : Usertypes = ' . var_export($response->usertypes, true));
        exit;
    }
}


wrout("ERROR : [{$pass->lastErrorCode}] - {$pass->lastError}");
