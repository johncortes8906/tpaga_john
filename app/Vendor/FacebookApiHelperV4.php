<?php
//error_reporting(E_ALL);
// Facebook PHP SDK v4.1
require_once( 'autoload.php' );

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphNodes\GraphUser;
use Facebook\Exceptions\FacebookRequestException;
use Facebook\Helpers\FacebookRedirectLoginHelper;
use Facebook\Helpers\FacebookJavaScriptLoginHelper;

class FacebookApiHelper {
    public static $init = false;
    public static $helper = null;
    public static $session = null; // Stores the Facebook Session, duh

    // uid is the Facebook ID of the connected Facebook user, or null if not connected
    public static $uid = null;
    public static $hasAccount = false;

    // me is the Facebook user object for the connected Facebook user
    public static $me = null;
    
    public static function init($appId = false, $secret = false) {
        if(self::$init === false || $appId && $secret) {
            FacebookSession::setDefaultApplication(
                ($appId) ?  $appId  : Configure::read('Facebook.appId'),
                ($secret) ? $secret : Configure::read('Facebook.secret')
            );

            FacebookSession::enableAppSecretProof(false);

            self::$helper = new FacebookJavaScriptLoginHelper();

            try {
                self::$session = self::$helper->getSession();
            } catch(FacebookRequestException $ex) {
                // When Facebook returns an error
            } catch(\Exception $ex) {
                // When validation fails or other local issues
            }

            self::$init = true;
        }
    }

    /*
    *   Generate the login URL to redirect visitors to with the getLoginUrl() method,
    *   redirect them, and then process the response from Facebook with the getSessionFromRedirect() method,
    *   which returns a FacebookSession.

    *   Requires a $redirect_url *!!!!
    */

    public static function getLoginUrl($callback_url = '/usarios/login?fb_redirect') {
        self::init();

        self::$helper = new FacebookRedirectLoginHelper();
        return self::$helper->getLoginUrl(self::$session, $callback_url);
    }

    public static function setup($Facebook) {
        self::init($Facebook['appId'], $Facebook['secret']);
    }
    
    /*
    *   getUser: request a user using the Session
    */
    public static function getUser($access_token = false) {
        self::init();

        // if there is no session, try to create one using the access token, if not, false..
        if(self::$session == null) {
            if($access_token) {
                self::$session = new FacebookSession($access_token);
            } else return false;
        }

        try {
            $request        = new FacebookRequest(self::$session, 'GET', '/me');
            $response       = $request->execute();
            $graphObject    = $response->getGraphObject()->asArray();
        } catch(FacebookRequestException $e) {
            return false;
        }

        if(is_array($graphObject) && isset($graphObject['id']) && !empty( $graphObject['id'] )) {
            self::$me = $graphObject;
            self::$uid = $graphObject['id'];
            self::$hasAccount = true;
        } else return false;

        return $graphObject;
    }
    
    
    public function api($param = null, $method = 'GET', $data = array()) {
        self::init();
        
        if($param == null) return false;
        if(self::$session == null) {
            return false;
        }

        try {
            $request        = new FacebookRequest(self::$session, $method, $param, $data);
            $response       = $request->execute();
            $graphObject    = $response->getGraphObject()->asArray();
        } catch(FacebookRequestException $e) {
            return false;
        }

        return $graphObject;
    }
}