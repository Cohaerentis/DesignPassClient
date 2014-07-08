<?php
/**
 * OAuth Consumer model
 * Sample non-persistant model
 *
 * @author Antonio Espinosa <aespinosa@teachnova.com>
 * @license http://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3
 * @version 0.1
 */

/**
 * Sample table configuration for MySQL
 *
 * CREATE TABLE IF NOT EXISTS `oauth_consumer` (
 *     `id`            bigint(20)      NOT NULL AUTO_INCREMENT,
 *     `provider`      varchar(40)     COLLATE utf8_unicode_ci NOT NULL,
 *     `scope`         varchar(100)    COLLATE utf8_unicode_ci DEFAULT NULL,
 *     `clientid`      varchar(80)     COLLATE utf8_unicode_ci NOT NULL,
 *     `accesstoken`   varchar(256)    COLLATE utf8_unicode_ci DEFAULT NULL,
 *     `refreshtoken`  varchar(256)    COLLATE utf8_unicode_ci DEFAULT NULL,
 *     `expires`       bigint(11)      NULL DEFAULT NULL,
 *     `version`       tinyint(1)      NOT NULL DEFAULT '2',
 *     PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
 *
 */

class OAuthConsumer {
    private static $tokens = array();
    private static $nextid = 1;

    private $id = null;
    private $errors = array();

    public $provider = '';
    public $scope = '';
    public $clientid = '';
    public $version = 2;
    public $expires = 0;
    public $accesstoken = '';
    public $refreshtoken = '';

    public function __construct() { }

    public function save() {
        if (empty($this->id)) {
            $this->id = self::$nextid;
            self::$nextid++;
        }
        self::$tokens[$this->id] = &$this;
        return true;

    }

    public function getErrors() {
        return $this->errors;
    }

    public static function find($clientid, $provider, $scope) {
        // Try to find token by clientid, provider and scope
        // TODO : Make it persistent using SQLite, MySQL, ...
        foreach (self::$tokens as &$token) {
            if ( ($token->clientid == $clientid) &&
                 ($token->provider == $provider) &&
                 ($token->scope == $scope) ) return $token;
        }
        return false;
    }
}