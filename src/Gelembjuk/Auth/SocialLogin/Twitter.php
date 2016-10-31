<?php 

/**
* The class to login to a web site with Twitter account
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

use \Abraham\TwitterOAuth\TwitterOAuth;

class Twitter extends Base {
	/**
	 * Twitter API access token
	 * 
	 * @var string
	 */
	protected $access_token;
	/**
	 * Twitter API request token
	 * 
	 * @var string
	 */
	protected $request_token;
	
	/**
     * Checks if an integration is configured. All options are provided.
     * This doesn't check if options are correct
     * 
     * @return bool
     */
    public function isConfigured() {
        if (empty($this->options['consumer_ke']) ||
            empty($this->options['consumer_secret'])) {
            return false;
        }
        return true;
    }
	
	/**
	 * Returns twitter login start auth process
	 * 
	 * @param string $redirecturl URL where to redirect after login complete
	 */
	public function getLoginStartUrl($redirecturl) {
		$connection = new TwitterOAuth($this->options['consumer_key'],$this->options['consumer_secret']);
		$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $redirecturl));
		
		$this->request_token = array();
		$this->request_token['oauth_token'] = $request_token['oauth_token'];
		$this->request_token['oauth_token_secret'] = $request_token['oauth_token_secret'];
		
		return $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
	}
	/**
	 * Get array of GET/POST arguments posted from twitter back to complete login URL 
	 * 
	 * @return array List of POST/GET arguments names
	 */
	public function getFinalExtraInputs() {
		return array('oauth_token','oauth_verifier'); // by default no extra inputs
	}
	
	/**
	 * Completes social login. Is caled after redirect from twitter auth page
	 * 
	 * @param array $extrainputs List of POST/GET arguments names
	 */
	public function completeLogin($extrainputs = array()) {
		$request_token = [];
		$request_token['oauth_token'] = $this->request_token['oauth_token'];
		$request_token['oauth_token_secret'] = $this->request_token['oauth_token_secret'];

		$this->logQ('session token '.print_r($request_token,true),'twitter');
		$this->logQ('extra options '.print_r($extrainputs,true),'twitter');
		
		if (isset($extrainputs['oauth_token']) && $request_token['oauth_token'] !== $extrainputs['oauth_token']) {
			throw new \Exception('Twitter oauth. Somethign went wrong. No token in the session');
		}
		
		$connection = new TwitterOAuth($this->options['consumer_key'],$this->options['consumer_secret'], 
			$request_token['oauth_token'], $request_token['oauth_token_secret']);
		
		$access_token = $connection->oauth("oauth/access_token", array("oauth_verifier" => $extrainputs['oauth_verifier']));
		
		$this->access_token = $access_token;
		
		return $this->getUserProfile();
	}
	/**
	 * Returns short twitter profile after succes login
	 * 
	 * @return array User Profile
	 */
	public function getUserProfile() {
		$connection = new TwitterOAuth($this->options['consumer_key'],$this->options['consumer_secret'], 
			$this->access_token['oauth_token'], $this->access_token['oauth_token_secret']);
		
		$user = $connection->get("account/verify_credentials");
		
		return array(
			'userid'=>$user->id,
			'name'=>$user->screen_name,
			'imageurl'=>$user->profile_image_url);
	}
	
}
