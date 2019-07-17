<?php 
/**
* The class to login to a web site with LinkedIn account
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

class Linkedin extends Base {
	/**
	 * LinkedIn API object
	 * 
	 * @var LinkedIn\LinkedIn
	 */
	protected $linkedin;
	/**
	 * To remember redirect url during auth session
	 * 
	 * @var string
	 */
	protected $redirecturl;
	/**
	 * LinkedIn API token
	 * 
	 * @var string
	 */
	protected $token;
	
	/**
     * Checks if an integration is configured. All options are provided.
     * This doesn't check if options are correct
     * 
     * @return bool
     */
    public function isConfigured() {
        if (empty($this->options['api_key']) || 
            empty($this->options['api_secret'])) {
            return false;
        }
        return true;
    }
	/**
	 * Returns list of properties to serialise.
	 * 
	 * @param array $skip Defines what properties to skip
	 */
	protected function getSerializeVars($skip = array()) {
		$skip[] = 'linkedin';
		return parent::getSerializeVars($skip);
	}
	/**
	 * Returns LinkedIn API object inited with API settings
	 * 
	 * @return LinkedIn\LinkedIn
	 */
	protected function getClient($redirecturl = '') {
		// keep only one instance during a session
		if (is_object($this->linkedin)) {
			return $this->linkedin;
		}
		
		if ($redirecturl == '') {
			$redirecturl = $this->redirecturl;
		} else {
			$this->redirecturl = $redirecturl;
		}
		$this->logQ('redirect '.$redirecturl,'linkedin');
		
		$API_CONFIG = array(
			'api_key' => $this->options['api_key'],
			'api_secret' => $this->options['api_secret'],
			'callback_url' => $redirecturl
			);
			
		$this->linkedin = $linkedin = new \LinkedIn\LinkedIn($API_CONFIG);
		
		return $this->linkedin;
	}
	/**
	 * Returns linkedin login start auth process
	 * 
	 * @param string $redirecturl URL where to redirect after login complete
	 */
	public function getLoginStartUrl($redirecturl) {
		$linkedin = $this->getClient($redirecturl);
		
		$url = $linkedin->getLoginUrl(
			array(
				\LinkedIn\LinkedIn::SCOPE_BASIC_PROFILE, 
				\LinkedIn\LinkedIn::SCOPE_EMAIL_ADDRESS
				)
			);
		return $url;
	}
	/**
	 * Get array of GET/POST arguments posted from linkedin back to complete login URL 
	 * 
	 * @return array List of POST/GET arguments names
	 */
	public function getFinalExtraInputs() {
		return array('code');
	}
	/**
	 * Completes social login. Is caled after redirect from linkedin auth page
	 * 
	 * @param array $extrainputs List of POST/GET arguments names
	 */
	public function completeLogin($extrainputs = array()) {
		$linkedin = $this->getClient();
		$this->token = $linkedin->getAccessToken($extrainputs['code']);
		
		return $this->getUserProfile();
	}
	/**
	 * Returns short linkedin profile after succes login
	 * 
	 * @return array User Profile
	 */
	public function getUserProfile() {
		$linkedin = $this->getClient();
		
		$response = $linkedin->get('/people/~:(id,first-name,last-name,picture-url,public-profile-url,email-address)');
		
		if (isset($response['emailAddress'])) {
			return array(
				'userid'=>$response['id'],
				'name'=>$response['firstName'].' '.$response['lastName'],
				'email'=>$response['emailAddress'],
				'imageurl'=>$response['pictureUrl']
			);
		}
	}
	public function loginWithTokenID($tokenid)
    {
        
    }
}
