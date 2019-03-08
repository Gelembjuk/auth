<?php 

/**
* The class to login to a web site with Google account
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

class Google extends Base {
	/**
	 * To remember redirect url during auth session
	 * 
	 * @var string
	 */
	protected $redirecturl;
	/**
	 * Google API object
	 * 
	 * @var Google_Client
	 */
	protected $google;
	/**
	 * Google API token
	 * 
	 * @var string
	 */
	protected $access_token;
	
	/**
     * Checks if an integration is configured. All options are provided.
     * This doesn't check if options are correct
     * 
     * @return bool
     */
    public function isConfigured() {
        if (empty($this->options['application_name']) || 
            empty($this->options['client_id']) ||
            empty($this->options['client_secret'])) {
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
		$skip[] = 'google';
		return parent::getSerializeVars($skip);
	}
	/**
	 * Returns Google API object inited with API settings
	 * 
	 * @return Google_Client
	 */
	protected function getClient($redirecturl = '') {
		// keep only one instance during a session
		if (is_object($this->google)) {
			return $this->google;
		}
		if ($redirecturl == '') {
			$redirecturl = $this->redirecturl;
		} else {
			$this->redirecturl = $redirecturl;
		}
		
		$client = new \Google_Client();
		$client->setApplicationName($this->options['application_name']);
		$client->setClientId($this->options['client_id']);
		$client->setClientSecret($this->options['client_secret']);
		$client->setRedirectUri($redirecturl);
		$client->setDeveloperKey($this->options['api_key']);
		$client->setScopes(array('https://www.googleapis.com/auth/userinfo.profile','https://www.googleapis.com/auth/userinfo.email'));
		
		$this->google = $client;
		
		return $client;
	}
	/**
	 * Returns google login start auth process
	 * 
	 * @param string $redirecturl URL where to redirect after login complete
	 */
	public function getLoginStartUrl($redirecturl) {
		$client = $this->getClient($redirecturl);
		$authUrl = $client->createAuthUrl();
		return $authUrl;
	}
	/**
	 * Get array of GET/POST arguments posted from google back to complete login URL 
	 * 
	 * @return array List of POST/GET arguments names
	 */
	public function getFinalExtraInputs() {
		return array('code','error');
	}
	/**
	 * Completes social login. Is caled after redirect from google auth page
	 * 
	 * @param array $extrainputs List of POST/GET arguments names
	 */
	public function completeLogin($extrainputs = array()) {
		if ($extrainputs['code'] == '' && $extrainputs['error'] != '') {
			throw new \Exception($extrainputs['error']);
		}
		$client = $this->getClient();
		
		$client->authenticate($extrainputs['code']);
		$this->access_token = $client->getAccessToken();
		
		return $this->getUserProfile();
	}
	/**
	 * Returns short google profile after succes login
	 * 
	 * @return array User Profile
	 */
	public function getUserProfile() {
		$client = $this->getClient();
		
		$client->setAccessToken($this->access_token);
		
		$plus = new \Google_Service_Plus($client);
		$oauth2 = new \Google_Service_Oauth2($client);
		
		if ($client->getAccessToken()) {
            $user = $oauth2->userinfo->get();
            
			if (isset($user->id)) {
                $name = $user->givenName;
                
                if (!empty($user->familyName)) {
                    $name = $user->familyName.' '.$user->givenName;
                }
                
				$profile = array(
					'userid'=>$user->id,
					'name' => $name,
					'imageurl' => $user->picture,
					'email' => $user->email
					);

				return $profile;
			}
  		}
  		throw new \Exception('Can not get google profile');
	}
}
