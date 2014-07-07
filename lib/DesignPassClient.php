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
            if ($result === true)           return true;
            else if ($result === false)     {
                $this->lastError = 'Can not authenticate';
                $this->lastErrorCode = 'ERROR_AUTH';
            } else  {
                $this->lastError = 'Follow this link to authenticate : ' . $result;
                $this->lastErrorCode = 'ERROR_FOLLOW_LINK';
            }
        }
        return false;
    }

    static public function accessToken($code, $state, $redirect_uri) {
        // START : Workaround for non-persistance tokens
        $token = new OAuthConsumer();
        $token->state = $state;
        $token->save();
        // END : Workaround for non-persistance tokens

        return OAuth::accessToken($code, $state, $redirect_uri);
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
            $params[$field] = "@$filepath;filename=$name;type=$mimetype";
            // $params[$field] = "@$filepath;type=$mimetype";
            // $params['file'] = "@$filepath";
        }
        return $params;
    }
}