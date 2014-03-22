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
$pass = new DesignPassClient($key, $secret, $redirect, $mode, $scope);
if ($pass->authenticate()) {
    $response = $pass->request('coursetype');
    if (!empty($response->coursetypes)) {
        foreach($response->coursetypes as $coursetype) {
            wrout("[{$coursetype->label}] {$coursetype->name}");
            wrout("   Segment: {$coursetype->segment} - Prefix: {$coursetype->prefix}");
        }
    } else {
        wrout('No coursetype found');
    }
} else {
    wrout('ERROR : ' . $pass->lastError);
}