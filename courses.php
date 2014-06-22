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
$pass = new DesignPassClient($api, $key, $secret, $redirect, $mode, $scope);
if ($pass->authenticate()) {
    $response = $pass->request('course');
    if (!empty($response->courses)) {
        $courses = $response->courses;
        foreach ($courses as $label => $items) {
            $response = $pass->request('coursetype', 'GET', array('label' => $label));
            if (!empty($response->coursetype)) {
                $coursetype = $response->coursetype;
                wrout('Coursetype : ' . $coursetype->name);
                if (!empty($items)) {
                    foreach ($items as $course) {
                        wrout("   [{$course->courseid}] {$course->title}");
                    }
                } else {
                    wrout('   No courses found');
                }
            }
        }
    } else {
        wrout("ERROR : [{$pass->lastErrorCode}] - {$pass->lastError}");
    }
} else {
    wrout('ERROR : ' . $pass->lastError);
}