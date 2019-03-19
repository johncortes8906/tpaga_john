<?php
// Facebook PHP SDK v3
require_once( 'facebookv3/facebook.php' );
class FacebookApiHelper {
    public static $init = false;
    public static $helper = null;

    // uid is the Facebook ID of the connected Facebook user, or null if not connected
    public static $uid = null;
    public static $hasAccount = false;

    // me is the Facebook user object for the connected Facebook user
    public static $me = null;
    
    public static function init($appId = false, $secret = false) {
        if(self::$init === false || $appId && $secret) {
            $config = array(
                'appId' =>  ($appId) ?  $appId  : Configure::read('Facebook.appId'),
                'secret' => ($secret) ? $secret : Configure::read('Facebook.secret'),
                'fileUpload' => false, // optional
                'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
            );

            self::$helper = new Facebook($config);
            self::$init = true;
        }
    }

    public static function getLoginUrl($params = array('scope' => 'read_stream, friends_likes', 'redirect_url' => '/usarios/login?fb_redirect')) {
        self::init();
        return self::$helper->getLoginUrl( $params );
    }

    public static function setup($Facebook) {
        self::init($Facebook['appId'], $Facebook['secret']);
    }
    
    /*
    *   getUser: request a user using the Session
    */
    public static function getUser($access_token = false) {
        self::init();
        
        if($access_token) {
            self::$helper->setAccessToken($access_token);
        }
            
        try {
            $graphObject = self::$helper->api('/me','GET');
        } catch(FacebookApiException $e) {
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
        
        if($param == null || self::$hasAccount == false) {
            return false;
        }

        try {
             $graphObject = self::$helper->api('/me','GET');
        } catch(FacebookRequestException $e) {
            return false;
        }

        //$graphObject = $response->getGraphObject()->asArray();
        return $graphObject;
    }
}