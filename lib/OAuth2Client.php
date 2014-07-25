<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
* (c) 2014, Antonio Espinosa (http://www.cohaerentis.com)
*/

// A service client for the OAuth 2 flow.
// v0.2
class OAuth2Client
{
    // public $api_max_retries  = 2;
    public $api_base_url     = '';
    public $authorize_url    = '';
    public $token_url        = '';
    // AEA - This variable is un used, has no sense
    // public $token_info_url   = '';

    public $client_id        = '';
    public $client_secret    = '';
    public $redirect_uri     = '';
    public $access_token     = '';
    public $scope            = '';
    public $refresh_token    = '';
    public $tokens_changed   = false;

    public $access_token_expires_in = '' ;
    public $access_token_expires_at = '' ;

    //--

    public $client_auth_header       = false;
    public $sign_token_name          = 'header'; // 'access_token';
    public $decode_json              = true;
    public $curl_time_out            = 30;
    public $curl_connect_time_out    = 30;
    public $curl_ssl_verifypeer      = false;
    public $curl_header              = array();
    public $curl_useragent           = 'DesingPassClient; OAuth/2 Simple PHP Client v0.1; HybridAuth http://hybridauth.sourceforge.net/';
    public $curl_authenticate_method = 'POST';
    public $curl_proxy               = null;

    //--

    public $http_code             = '';
    public $http_info             = '';
    public $lastErrorCode         = 0;
    public $lastError             = '';

    //--

    public function __construct( $client_id = false, $client_secret = false, $redirect_uri='' )
    {
        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_uri  = $redirect_uri;
    }

    public function authorizeUrl( $extras = array() )
    {
        $params = array(
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'response_type' => 'code'
        );

        if (!empty($this->scope)) $params['scope'] = $this->scope;

        if( count($extras) )
            foreach( $extras as $k=>$v )
                $params[$k] = $v;

        return $this->authorize_url . '?' . http_build_query( $params );
    }

    public function accessToken( $code )
    {
        $params = array(
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'code'          => $code
        );

        if (!empty($this->scope)) $params['scope'] = $this->scope;

        if ($this->client_auth_header) {
            $this->curl_header[] = 'Authorization: Basic ' .
                                   base64_encode($this->client_id . ':' . $this->client_secret);
        } else {
            $params['client_secret'] = $this->client_secret;
        }

// wrout('OAuth2Client::accessToken : url = ' . var_export($this->token_url, true));
// wrout('OAuth2Client::accessToken : params = ' . var_export($params, true));
        $response = $this->request( $this->token_url, $params, $this->curl_authenticate_method );
        $response = $this->parseRequestResult( $response );

        if( !empty( $response->refresh_token ) ) $this->refresh_token = $response->refresh_token;
        if( !empty( $response->access_token  ) ) {
            $this->access_token            = $response->access_token;
            $this->tokens_changed          = true;
        }
        if( !empty( $response->expires_in    ) ) {
            $this->access_token_expires_in = $response->expires_in;
            // calculate when the access token expire
            $this->access_token_expires_at = time() + $response->expires_in;
        }

// wrout('OAuth2Client::accessToken : response = ' . var_export($response, true));
        return !empty( $response->access_token );
    }

    public function clientToken()
    {
        $params = array(
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->client_id,
        );

        if (!empty($this->scope)) $params['scope'] = $this->scope;

        if ($this->client_auth_header) {
            $this->curl_header[] = 'Authorization: Basic ' .
                                   base64_encode($this->client_id . ':' . $this->client_secret);
        } else {
            $params['client_secret'] = $this->client_secret;
        }

// wrout('OAuth2Client::clientToken : url = ' . var_export($this->token_url, true));
// wrout('OAuth2Client::clientToken : params = ' . var_export($params, true));
        $response = $this->request( $this->token_url, $params, $this->curl_authenticate_method );
        $response = $this->parseRequestResult( $response );

        if( !empty( $response->access_token  ) ) {
            $this->access_token            = $response->access_token;
            $this->tokens_changed          = true;
        }
        if( !empty( $response->expires_in    ) ) {
            $this->access_token_expires_in = $response->expires_in;
            // calculate when the access token expire
            $this->access_token_expires_at = time() + $response->expires_in;
        }

// wrout('OAuth2Client::clientToken : response = ' . var_export($response, true));
        return !empty( $response->access_token );
    }

    public function refreshToken() {
        $params = array(
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->client_id,
            'refresh_token' => $this->refresh_token
        );

        if (!empty($this->scope)) $params['scope'] = $this->scope;

        if ($this->client_auth_header) {
            $this->curl_header[] = 'Authorization: Basic ' .
                                   base64_encode($this->client_id . ':' . $this->client_secret);
        } else {
            $params['client_secret'] = $this->client_secret;
        }

        $response = $this->request( $this->token_url, $params, $this->curl_authenticate_method );
        $response = $this->parseRequestResult( $response );

        if( !empty( $response->refresh_token ) ) $this->refresh_token  = $response->refresh_token;
        if( !empty( $response->access_token  ) ) {
            $this->access_token            = $response->access_token;
            $this->tokens_changed          = true;
        }
        if( !empty( $response->expires_in    ) ) {
            $this->access_token_expires_in = $response->expires_in;
            // calculate when the access token expire
            $this->access_token_expires_at = time() + $response->expires_in;
        }

        return !empty( $response->access_token );
    }

    /**
    * Format and sign an oauth for provider api
    */
    public function api( $url, $method = 'GET', $parameters = array() )
    {
        if ( strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0 ) {
            $url = $this->api_base_url . $url;
        }

        if ($this->sign_token_name == 'header') {
            $this->curl_header[] = 'Authorization: Bearer ' . $this->access_token;
        } else {
            $parameters[$this->sign_token_name] = $this->access_token;
        }
        $response = null;

        switch( $method ){
            case 'POST' :
                // $this->curl_header[] = 'Content-Type: application/x-www-form-urlencoded';
                $response = $this->request( $url, $parameters, 'POST' );
                break;
            default  : $response = $this->request( $url, $parameters, $method  ); break;
        }

        if ($response && $this->decode_json) {
            $response = json_decode( $response );
        }

        return $response;
    }

    /**
    * GET wrappwer for provider apis request
    */
    function get( $url, $parameters = array() )
    {
        return $this->api( $url, 'GET', $parameters );
    }

    /**
    * POST wreapper for provider apis request
    */
    function post( $url, $parameters = array() )
    {
        return $this->api( $url, 'POST', $parameters );
    }

    // -- tokens
    /* AEA - This function has no sense
    public function tokenInfo($accesstoken)
    {
        $params['access_token'] = $this->access_token;
        $response = $this->request( $this->token_info_url, $params );
        return $this->parseRequestResult( $response );
    }
    */

    // -- utilities

    private function request( $url, $params = false, $type = 'GET' )
    {
// static $curlstderr = null;
// if (empty($curlstderr)) $curlstderr = @fopen(wrlog::$path . '/curl_stderr.txt', wrlog::FOPEN_WRITE_CREATE);
// wrlog("OAuth2Client::request: $type url = " . var_export($url, true));
// wrout("OAuth2Client::request: $type url = " . var_export($url, true));
// wrlog('OAuth2Client::request: params = ' . var_export( $params, true ) );
// wrlog('OAuth2Client::request: headers = ' . var_export( $this->curl_header, true ) );
// wrout('OAuth2Client::request: headers = ' . var_export( $this->curl_header, true ) );

        if( $type == 'GET' ){
            if (!empty($params)) $url = $url . ( strpos( $url, '?' ) ? '&' : '?' ) . http_build_query( $params );
// wrout('OAuth2Client::request: Setting GET params, new url = ' . var_export($url, true) );
        }

        $this->http_info = array();
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL            , $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
        curl_setopt($ch, CURLOPT_TIMEOUT        , $this->curl_time_out );
        curl_setopt($ch, CURLOPT_USERAGENT      , $this->curl_useragent );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $this->curl_connect_time_out );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , $this->curl_ssl_verifypeer );
        curl_setopt($ch, CURLOPT_HTTPHEADER     , $this->curl_header );
        $this->curl_header = array();
// curl_setopt($ch, CURLOPT_VERBOSE     , 1 );
// curl_setopt($ch, CURLOPT_STDERR     , $curlstderr );

        if($this->curl_proxy){
            curl_setopt( $ch, CURLOPT_PROXY        , $this->curl_proxy);
        }


        if ($type == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);

            if (!empty($params)) {
                // AEA - Do not use 'http_build_query' if you want to send files
                // if (is_array($params)) $params = http_build_query( $params );
                if (is_array($params)) {
                    $new = array();
                    foreach ($params as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                $new[$key . '[' . $k . ']'] = $v;
                            }
                        } else {
                            $new[$key] = $value;
                        }
                    }
                    $params = $new;
                }
            } else {
                $params = '';
            }
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
        }

        if ($type == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

            if (!empty($params)) {
                // AEA - Do not use 'http_build_query' if you want to send files
                if (is_array($params)) $params = http_build_query( $params );
            } else {
                $params = '';
            }
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
        }

        $response = curl_exec($ch);

        $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->http_info = array_merge($this->http_info, curl_getinfo($ch));

 // wrlog('OAuth2Client::request: http_code = ' . var_export( $this->http_code, true ) );
// wrout('OAuth2Client::request: http_code = ' . var_export( $this->http_code, true ) );
// if ($this->http_code >= 300)
     // wrlog('OAuth2Client::request: http_info = ' . var_export( $this->http_info, true ) );
//     wrout('OAuth2Client::request: http_info = ' . var_export( $this->http_info, true ) );
        // wrlog('OAuth2Client::request: result = ' . var_export( $response, true ) );
//        wrout('OAuth2Client::request: result = ' . var_export( $response, true ) );

        curl_close ($ch);

        return $response;
    }

    private function parseRequestResult( $result )
    {
        if( $decoded = json_decode( $result ) ) {
            if (!empty($decoded->status) && ($decoded->status == 'ERROR')) {
                $this->lastErrorCode = $decoded->errno;
                $this->lastError = $decoded->error;
            }
            return $decoded;
        }

        parse_str( $result, $ouput );

        $result = new StdClass();

        foreach( $ouput as $k => $v )
            $result->$k = $v;

        return $result;
    }
}
