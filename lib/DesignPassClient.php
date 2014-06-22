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

    public function request($url, $method = 'GET', $params = array()) {
        $url = trim($url);
        if (!preg_match('#^http(s)?://#', $url)) {
            $url = trim($url, '/');
            $url = $this->api . '/' . $url;
        }
        $this->lastError = '';
        $this->lastErrorCode = '';
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
}