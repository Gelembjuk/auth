<?php 

/**
* The class to login to a web site with Xing account
*
* LICENSE: MIT
*
* @category   Auth
* @package    Gelembjuk/Auth
* @copyright  Copyright (c) 2015 Roman Gelembjuk. (http://gelembjuk.com)
* @version    1.0
* @link       https://github.com/Gelembjuk/auth
*/

namespace Gelembjuk\Auth\SocialLogin;

use \League\OAuth1\Client\Server\Xing;

class Xingapi extends Base {
	/**
	 * Xing API access token object
	 * 
	 * @var \League\OAuth1\Client\Credentials\TokenCredentials
	 */
	protected $access_token;
	/**
	 * Xing API oAuth1 process temp credentials
	 * 
	 * @var \League\OAuth1\Client\Credentials\TokenCredentials
	 */
	protected $temp_credentials;
	
	/**
	 * Returns Xing login start auth process
	 * 
	 * @param string $redirecturl URL where to redirect after login complete
	 */
	public function getLoginStartUrl($redirecturl) {
		$credentials = array(
			'identifier' => $this->options['consumer_key'],
			'secret' => $this->options['consumer_secret'],
			'callback_uri' => $redirecturl
		);

		$server = new \League\OAuth1\Client\Server\Xing($credentials);
		
		$this->temp_credentials = $server->getTemporaryCredentials();
    
		return $server->getAuthorizationUrl($this->temp_credentials);
	}
	/**
	 * Get array of GET/POST arguments posted from Xing back to complete login URL 
	 * 
	 * @return array List of POST/GET arguments names
	 */
	public function getFinalExtraInputs() {
		return array('oauth_token','oauth_verifier'); // by default no extra inputs
	}
	
	/**
	 * Completes social login. Is caled after redirect from Xing auth page
	 * 
	 * @param array $extrainputs List of POST/GET arguments names
	 */
	public function completeLogin($extrainputs = array()) {
		if (!isset($extrainputs['oauth_token']) || $extrainputs['oauth_token'] == '') {
			throw new \Exception('Xing oauth. Somethign went wrong. No token in the session');
		}
		
		$credentials = array(
			'identifier' => $this->options['consumer_key'],
			'secret' => $this->options['consumer_secret']
		);

		$server = new \League\OAuth1\Client\Server\Xing($credentials);
		
		$this->access_token = $server->getTokenCredentials($this->temp_credentials, 
			$extrainputs['oauth_token'], $extrainputs['oauth_verifier']);
		
		return $this->getUserProfile();
	}
	/**
	 * Returns short Xing profile after succes login
	 * 
	 * @return array User Profile
	 */
	public function getUserProfile() {
		$credentials = array(
			'identifier' => $this->options['consumer_key'],
			'secret' => $this->options['consumer_secret']
		);

		$server = new \League\OAuth1\Client\Server\Xing($credentials);
		
		$user = $server->getUserDetails($this->access_token);
		
		return array(
			'userid'=>$user->uid,
			'name'=>$user->display_name,
			'imageurl'=>$user->imageUrl);
	}
	
	/**
	 * Returns serialized string
	 * We presume there will not be some complex objects
	 * only integers/strings etc
	 * 
	 * @return string $skip JSON string with values of selected properties
	 */
	public function serialize() {
		return serialize($this->temp_credentials);
	}
	/**
	 * Unserialize/restore an object state to continue login
	 * 
	 * @param string $json_string JSON string with values of selected properties
	 */
	public function unSerialize($json_string) {
		$this->temp_credentials = unserialize($json_string);
		
		return true;
	}
}
