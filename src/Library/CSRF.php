<?php
namespace Scarlets\Library;
use \Scarlets\Library\User\Session;
use \Scarlets\Library\Extend\Strings;

class CSRF{
	public static $token = '';
	public static function init(){
		if(isset(Session::$sify['csrf']) === false)
			regenerate();
		else self::$token = &Session::$sify['csrf'];
	}

	public static function hiddenInput(){
		return '<input name="CSRFToken" type="hidden" value="'.self::$token.'">';
	}

	public static function regenerate(){
		self::$token = Session::$sify['csrf'] = Strings::random([30, 40], true);
		Session::saveSify();
	}

	public static function isRequestValid(){
		if(isset($_POST['CSRFToken']) !== false)
			$token = &$_POST['CSRFToken'];

        elseif(isset($_SERVER['HTTP_CSRFTOKEN'])) // Nginx or FastCGI
            $token = &$_SERVER['HTTP_CSRFTOKEN'];

		elseif(isset($_SERVER['CSRFToken']))
            $token = &$_SERVER['CSRFToken'];

        elseif(function_exists('apache_request_headers')){
            $requestHeaders = apache_request_headers();

            if(isset($requestHeaders['CSRFToken']))
                $token = &$requestHeaders['CSRFToken'];
            else return false;
        }
        else return false;

		return self::$token === $token;
	}
}
CSRF::init();