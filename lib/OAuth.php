<?php
/**
 * OAuth wrapper
 *
 * @author Antonio Espinosa <aespinosa@teachnova.com>
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3
 * @version 0.1
 */

require_once('OAuth2Client.php');
require_once('OAuthConsumer.php');

class OAuth {
    /**
     * @var object oAuth client library to use (OAuth1Client or OAuth2Client)
     *             NOTE: Current implementation only support OAuth2
     */
    public $client = null;

    /**
     * @var string Provider identifier (LinkedIn, Facebook, Twitter, ...)
     */
    public $provider = '';

    /**
     * @var string Authentication method (client_credentials or authorization_code)
     */
    public $authtype = '';

    /**
     * @var integer oAuth version
     */
    public $version = 2;

    /**
     * @var OauthConsumer Model for persist token data
     */
    public $token = null;

    /**
     * @var string Last error description message
     */
    public $lastError = '';

    /**
     * @var string Last HTTP code received
     */
    public $lastHttpCode = '';

    /**
     * @var integer Max number of retries when authentication error
     */
    public $maxRetries  = 1;

    /**
     * @var bool Follow authorize URL, because nobody is going to authorize it
     *           We will find a HTTP 302 to redirect url, with an authentication code
     */
    public $followAuth  = false;

    /**
     * @var bool True if we are already authenticated and have a valid accesstoken
     */
    private $_isAuthenticated = false;

    /**
     * Constructor.
     * @param string $provider Provider identifier (LinkedIn, Facebook, Twitter, ...)
     * @param array $params Client credentials and paramters (depends on oAuth version)
     * @param integer $version oAuth version (1 or 2)
     */
    public function __construct($provider, $params = array(), $version = 2) {
        if ($version == 2) {
            $this->provider = $provider;
            $this->version  = $version;
            $client_id      = (!empty($params['client_id'])) ? $params['client_id'] : '';
            $client_secret  = (!empty($params['client_secret'])) ? $params['client_secret'] : '';
            $redirect_uri   = (!empty($params['redirect_uri'])) ? $params['redirect_uri'] : '';
            if (!empty($client_id)) {
                $this->client = new OAuth2Client($client_id, $client_secret, $redirect_uri);
                $this->authtype                 = (!empty($params['authtype'])) ? $params['authtype'] : 'client_credentials';
                $this->client->api_base_url     = (!empty($params['api_base_url'])) ? $params['api_base_url'] : '';
                $this->client->authorize_url    = (!empty($params['authorize_url'])) ? $params['authorize_url'] : '';
                $this->client->token_url        = (!empty($params['token_url'])) ? $params['token_url'] : '';
                $this->client->scope            = (!empty($params['scope'])) ? $params['scope'] : '';
            } else {
                throw new Exception('Client ID is a mandatory parameter.');
            }
        } else {
            $version = (int) $version;
            throw new Exception("oAuth version $version is not supported.");
        }
    }

    /**
     * Make an API call
     * @param string $url API endpoint
     * @param string $method GET, POST, PUT, DELETE
     * @param array $parameters Call parameters
     * @return mixed Decoded response or false if error (call getLastError to retreive error message)
     */
    public function api($url, $method = 'GET', $parameters = array()) {
        if ($this->authenticate() === true) {
            $try = true;
            $ntries = 0;
            while ($try) {

                $response = $this->client->api($url, $method, $parameters);
                $this->lastHttpCode = $this->client->http_code;

                // Token is not valid, renegotiate
                if ($this->lastHttpCode == 401) {
                    $ntries++;
                    // Invalidate current token
                    $this->_isAuthenticated = false;
                    $this->token->accesstoken = '';
                    if (!$this->token->save()) {
                        $reason = var_export($token->getErrors(), true);
                        throw new Exception("ERROR : Can not save OauthConsumer token. Reason : $reason");
                    }
                    // Try to get another token
                    $result = $this->authenticate();
                    if ($result === false) {
                        $this->lastError = 'ERROR : Invalid token and can not get another access token.';
                        return false;
                    } else if (is_string($result)) {
                        $this->lastError = "ERROR : Invalid token, follow this link to authenticate: $result.";
                        return false;
                    } else if ($ntries > $this->maxRetries) {
                        $this->lastError = 'ERROR : Invalid token and max retries limit overflow.';
                        return false;
                    }

                } else {
                    return $response;
                }

            }
            return $response;
        }
        return false;

        // TODO : Make auth authenticate, and maintain channel authenticated
        // Saving in OauthConsumer tokens for later use and when change
        if (!empty($this->client)) return $this->client->api($url, $method, $parameters);
        return false;
    }

    static public function accessToken($code, $state, $redirect_uri) {
        $state = preg_replace('[^a-f0-9]', '', $state);

        $token = OauthConsumer::findByState($state);
        if (!empty($token)) {
            // Erase state, we can only use it once
            $token->state = '';
            if (!$token->save()) {
                $reason = var_export($token->getErrors(), true);
                throw new Exception("ERROR : Can not save OauthConsumer token. Reason : $reason");
            }

            if ($token->version == 2) {
                $client = new OAuth2Client($token->clientid, $token->clientsecret);
                $client->redirect_uri   = $redirect_uri;
                $client->token_url      = $token->tokenurl;
                $client->scope          = $token->scope;
                $client->client_auth_header = true;
                $client->curl_authenticate_method = 'GET';
                if ($client->accessToken($code)) {
                    $token->accesstoken     = $client->access_token;
                    $token->refreshtoken    = $client->refresh_token;
                    $token->expires         = $client->access_token_expires_at;
                    if (!$token->save()) {
                        $reason = var_export($token->getErrors(), true);
                        throw new Exception("ERROR : Can not save OauthConsumer token. Reason : $reason");
                    }
                    return true;
                } else {
                    throw new Exception("oAuth error while getting accessToken.");
                }
            } else {
                throw new Exception("oAuth version {$token->version} is not supported.");
            }
        }
        throw new Exception("oAuth error invalid state.");
    }

    static public function stateRevoke($state) {
        $state = preg_replace('[^a-f0-9]', '', $state);
        $token = OauthConsumer::findByState($state);
        if (!empty($token)) {
            $token->state = '';
            if (!$token->save()) {
                $reason = var_export($token->getErrors(), true);
                throw new Exception("ERROR : Can not save OauthConsumer token. Reason : $reason");
            }
        }
    }

    public function authenticate() {
// if (empty($this->client)) wrout('OAuth::authenticate : Client is empty');
        if (empty($this->client)) return false;
        if ($this->_isAuthenticated) return true;

        // Read/Create token information from DB
        if (empty($this->token)) {
// wrout('OAuth::authenticate : Empty token');
            // 1. Try to find token information in DB
            $this->token = OauthConsumer::find($this->client->client_id,
                                               $this->provider,
                                               $this->authtype);
            // 2. Create token information for this provider/clientid if necessary
            if (empty($this->token)) {
// wrout('OAuth::authenticate : Creating token');
                $this->token = new OauthConsumer();
                $this->token->provider      = $this->provider;
                $this->token->tokenurl      = $this->client->token_url;
                $this->token->authorizeurl  = $this->client->authorize_url;
                $this->token->apiurl        = $this->client->api_base_url;
                $this->token->scope         = $this->client->scope;
                $this->token->authtype      = $this->authtype;
                $this->token->clientid      = $this->client->client_id;
                $this->token->clientsecret  = $this->client->client_secret;
                $this->token->version       = $this->version;
            }
        }

// wrlog('Oauth::authenticate : token = ' . var_export($this->token, true));
        $now = time();
        if (!empty($this->token->accesstoken) && !empty($this->token->expires) && ($this->token->expires > $now)) {
// wrout('OAuth::authenticate : Have a valid accessToken');
            // A. We have a valid accesstoken
            $this->client->access_token = $this->token->accesstoken;
            $this->_isAuthenticated = true;

            return true;

        } else {
// wrout('OAuth::authenticate : Have an old accessToken');
            // B. We have an old accestoken, try to refresh it
            if (!empty($this->token->refreshtoken)) {
                $this->client->refresh_token = $this->token->refreshtoken;
                if ($this->client->refreshToken()) {
                    // B.1 Token renewed
                    $this->_tokenSave();
                    $this->_isAuthenticated = true;

                    return true;
                }
            }
        }

        // C. Get accestoken by authentication
        if ($this->authtype == 'client_credentials') {
// wrout('OAuth::authenticate : Getting token by client_credentials');
            // C.1. Retrieve accesstoken by client_credentials grant_type
            if ($this->client->clientToken()) {
                // Token retrieved
                $this->_tokenSave();
                $this->_isAuthenticated = true;

                return true;
            }
        } else { // authorization_code
// wrout('OAuth::authenticate : Getting token by authorization code');
            // C.2. Show user authorization dialog if need
            $this->token->state = $this->_stateGenerate();
            if (!$this->token->save()) {
                $reason = var_export($this->token->getErrors(), true);
                throw new Exception("ERROR : Can not save OauthConsumer token. Reason : $reason");
            }
            $authUrl = $this->client->authorizeUrl(array('state' => $this->token->state));
            if ($this->followAuth) {
                $code = $this->_followAuth($authUrl);
                if (!empty($code) && $this->client->accessToken($code)) {
                    // Token retrieved
                    $this->_tokenSave();
                    $this->_isAuthenticated = true;

                    return true;
                }
            }

            // No token, get it manually
            if (!$this->_isAuthenticated) return $authUrl;
        }

// wrout('OAuth::authenticate : No more options to get a valid token');
        return false;
    }

    private function _tokenSave() {
        if (!empty($this->token)) {
            $this->token->accesstoken     = $this->client->access_token;
            $this->token->refreshtoken    = $this->client->refresh_token;
            $this->token->expires         = $this->client->access_token_expires_at;
            if (!$this->token->save()) {
                $reason = var_export($this->token->getErrors(), true);
                throw new Exception("ERROR : Can not save OauthConsumer token. Reason : $reason");
            }
        }
    }

    private function _stateGenerate() {
        $stateLen = 40;
        if (file_exists('/dev/urandom')) { // Get 100 bytes of random data
            $randomData = file_get_contents('/dev/urandom', false, null, 0, 100) . uniqid(mt_rand(), true);
        } else {
            $randomData = mt_rand() . mt_rand() . mt_rand() . mt_rand() . microtime(true) . uniqid(mt_rand(), true);
        }
        return substr(hash('sha512', $randomData), 0, $stateLen);
    }

    /**
     * This is a hack for InIED oAuth 2 API
     * This API does not support client_credentials neither refresh_token grant_types.
     * And surprise!, authorize URL does not show any authorization, but a HTTP 302 to redirect_uri
     * So we read HTTP header for the URL at Location, parse it and get authorization code ;)
     *
     * @param  string $url Authorize URL
     * @return string      Authorization code
     */
    private function _followAuth($url) {
        $code = false;

        wrlog('OAuth::_followAuth : url = ' . var_export($url, true));
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $matches = array();
        if (preg_match('/Location: ([^\r\n]*)/', $result, $matches)) {
            $parsedurl = !empty($matches[1]) ? parse_url($matches[1]) : false;
            if (!empty($parsedurl['query'])) {
                $vars = array();
                parse_str($parsedurl['query'], $vars);
                $code = !empty($vars['code']) ? $vars['code'] : false;
            }
        }

        wrlog('OAuth::_followAuth : code = ' . var_export($code, true));
        return $code;
    }
}