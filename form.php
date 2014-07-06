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

$params = array(
    'type' => 'general',
    'email' => 'contacto+pruebas@teacchnova.com',
    'user_firstname' => 'Usuario',
    'user_lastname' => 'de Pruebas',
    'user_address' => 'Mi calle',
    'user_birthdate' => '1980-06-01',
    'user_country' => 'es',
    'user_province' => 'es-m',
    'context_salesforce_campaign' => '701f0000000FxXa',
    'newsletter_signup' => 1,
    'newsletter_id' => 12,
);

$files = array(
    'file_letter'       => dirname(__FILE__) . '/sample_pdf.pdf',
    'file_portfolio'    => dirname(__FILE__) . '/sample_image.jpg',
    'file_cv'           => dirname(__FILE__) . '/sample_image.png',
    'file_image'        => dirname(__FILE__) . '/sample_image.gif',
);

// Create Design Pass Client connection
$pass = new DesignPassClient($api, $key, $secret, $redirect, 'client_credentials', 'write');
if ($pass->authenticate()) {
    $response = $pass->request('form', 'POST', $params, $files);
    if (!empty($response->log)) {
        $log = $response->log;
        foreach ($log as $action) {
            if (empty($action->errno)) wrout("OK : $action->message");
            else                       wrout("ERROR($action->errno) : $action->message");
        }
    } else {
        wrout("ERROR : [{$pass->lastErrorCode}] - {$pass->lastError}");
    }
} else {
    wrout('ERROR : ' . $pass->lastError);
}