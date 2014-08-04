<?php
/**
 * Desing Pass Client
 *
 * @author Antonio Espinosa <aespinosa@teachnova.com>
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3
 * @version 0.1
 */

require_once('OAuth.php');

class DesignPassClient {
    public $api             = '';
    public $authorizeurl    = '/oauth/authorize';
    public $tokenurl        = '/oauth/token';
    public $apiKey          = '';
    public $secretKey       = '';
    public $oauth           = null;
    public $lastError       = '';
    public $lastErrorCode   = '';

    // Mode:
    // - 'authorization_code'
    // - 'client_credentials'
    public function __construct($api, $key, $secret, $redirect, $mode = 'authorization_code', $scope = 'profile') {
        // OAUTH2 Client credentials
        $this->api = $api;
        $this->apiKey = $key;
        $this->secretKey = $secret;
        $this->oauth = new OAuth('DesignPass',
                           array('client_id'        => $this->apiKey,
                                 'client_secret'    => $this->secretKey,
                                 'authtype'         => $mode,
                                 'api_base_url'     => $this->api,
                                 'authorize_url'    => $this->api . $this->authorizeurl,
                                 'token_url'        => $this->api . $this->tokenurl,
                                 'scope'            => $scope,
                                 'redirect_uri'     => $redirect,
                                ),
                           2);
    }

    public function authenticate() {
        if (!empty($this->oauth)) {
            $result = $this->oauth->authenticate();
            if ($result === true) {
                // Already authenticated
                return true;
            } else {
                // If authorization_code, we need user authorization
                // In this case, you will need to call:
                // - authURLGet() to get authorization URL where redirect user
                // - stateGet() to save in DB or SESSION for checking when oAuth server callback you with state and code
                if ( ($this->oauth->authtype == 'authorization_code') &&
                     !empty($this->oauth->state) &&
                     !empty($this->oauth->auth_url) ) {
                    $this->lastError = 'We need user authorization. Call authURLGet() to get url for redirect';
                    $this->lastErrorCode = 'ERROR_FOLLOW_LINK';
                // Or there is another authentication error
                } else {
                    $this->lastError = 'Can not authenticate';
                    $this->lastErrorCode = 'ERROR_AUTH';
                }
            }
        }
        return false;
    }

    public function stateGet() {
        return $this->oauth->state;
    }

    public function authURLGet() {
        return $this->oauth->auth_url;
    }

    public function accessTokenGet() {
        return $this->oauth->accessTokenGet();
    }

    // Used only in authorization_code mode
    public function accessTokenRequest($code) {
        $token = $this->oauth->accessTokenRequest($code);
        if (empty($token)) {
            $this->lastError = $this->oauth->lastError;
            $this->lastErrorCode = 'OAUTH_ACCESS_TOKEN';
        }
        return $token;
    }

    // Used only in authorization_code mode
    public function accessTokenSet($token) {
        $this->oauth->accessTokenSet($token);
    }

    public function request($url, $method = 'GET', $params = array(), $files = array()) {
        $url = trim($url);
        if (!preg_match('#^http(s)?://#', $url)) {
            $url = trim($url, '/');
            $url = $this->api . '/' . $url;
        }
        $this->lastError = '';
        $this->lastErrorCode = '';
        $params = $this->_parseParams($params, $files);
        if ($this->authenticate()) {
            $response = $this->oauth->api($url, $method, $params);
            return $this->_responseProcess($response);
        }
        return false;
    }

    private function _responseProcess($response) {
        $result = false;
        $status = !empty($response->status) ? strtoupper($response->status) : '';
        if ($status == 'OK') {
            $result = $response;
        } else if ($status == 'ERROR') {
            $this->lastError = !empty($response->error) ? $response->error : 'Unknown error';
            $this->lastErrorCode = !empty($response->errno) ? $response->errno : 'ERROR_UNKNOWN';
        } else if (!empty($this->oauth->lastError)) {
            $this->lastError = $this->oauth->lastError;
            $this->lastErrorCode = 'ERROR_NO_AUTH';
        } else {
            $this->lastError = 'No response from DesignPass';
            $this->lastErrorCode = 'ERROR_NO_RESPONSE';
        }

        return $result;
    }

    private function _parseParams($params, $files) {
        if (!is_array($params)) $params = array();
        if (empty($files) || !is_array($files)) return $params;
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        foreach ($files as $field => $file) {
            if (is_string($file)) $file = array('tmp_name' => $file);
            if (empty($file['tmp_name']) || !file_exists($file['tmp_name'])) continue;
            $filepath = realpath($file['tmp_name']);
            $mimetype = !empty($file['type']) ? $file['type'] : finfo_file($finfo, $filepath, FILEINFO_MIME_TYPE);
            $name = !empty($file['name']) ? $file['name'] : basename($filepath);
            // Miguel A. Montañes - Support new PHP 5.5 Curl Upload files
            // $params[$field] = "@$filepath;filename=$name;type=$mimetype";
            $params[$field] = $this->_get_file_params($filepath, $mimetype, $name);
        }
        return $params;
    }

    // Miguel A. Montañes - Support new PHP 5.5 Curl Upload files
    private function _get_file_params($filepath, $mimetype, $name ) {
        if (function_exists('curl_file_create')) {
            // if current PHP version is 5.5 or later
            $file_params = curl_file_create($filepath, $mimetype, $name);
        } else {
            $file_params = "@$filepath;filename=$name;type=$mimetype";
        }
        return $file_params;
    }

}