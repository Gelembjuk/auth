<?php

/**
* This class is a factory for auth systems objects . It is used statically to create social login object
* 
* Usage:
* 
* $socialnetwork = 'facebook';
* 
* $network = Gelembjuk\Auth\AuthFactory::getSocialLoginObject($socialnetwork,$integrations[$socialnetwork]);
* 
* $redirecturl = '.....';
* 
* $socialauthurl = $network->getLoginStartUrl($redirecturl);
* 
* header("Location: $socialauthurl",true,301);
* exit;
*
* LICENSE: MIT
*
* @category   Auth
* @package    Gelembjuk/Auth
* @copyright  Copyright (c) 2015 Roman Gelembjuk. (http://gelembjuk.com)
* @version    1.0
* @link       https://github.com/Gelembjuk/auth
*/

namespace Gelembjuk\Auth;

class AuthFactory {
	/**
	 * returns social login object with a name
	 * 
	 * @param string $network One of: facebook, twitter, google, linkedin
	 * @param array $options This are AI settings for a social network named in a first argument. Settings are different for each network integration
	 * @logger Psr\Log $logger This is logger instance 
	 * 
	 * @return Gelembjuk\Auth\SocialLogin\Base Instance of social login class
	 */
	public static function getSocialLoginObject($network,$options = array(), $logger = null) {
		// do some filters and checks for name
		$network = preg_replace('![^a-z0-9]!i','',$network);
		
		if ($network == '') {
			throw new \Exception('Social Login Network can not be empty');
		}
		
		$class = '\\Gelembjuk\\Auth\\SocialLogin\\'.ucfirst($network);
		
		if (!class_exists($class)) {
			throw new \Exception(sprintf('Integration with a class name %s not found',$class));
		}
		
		// create an object
		$object = new $class($options);

		// set logger (even if it is null then no problem)
		$object->setLogger($logger);
		
		return $object;
	}
	
}
