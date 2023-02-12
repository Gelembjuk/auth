<?php 

/**
* The class to login to a web site with Facebook account
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

class Facebook extends Base {
	/**
	 * Facebook API object
	 * 
	 * @var Facebook\Facebook
	 */
	protected $fb;
	
	/**
	 * Facebook API token
	 * 
	 * @var string
	 */
	protected $accesstoken; 
	/**
     * Keeps
     * 
     * @return bool
     */
	protected $userEmail = '';
	/**
     * Checks if an integration is configured. All options are provided.
     * This doesn't check if options are correct
     * 
     * @return bool
     */
	
    public function isConfigured() {
        if (empty($this->options['api_key']) || empty($this->options['secret_key'])) {
            return false;
        }
        return true;
    }
    
	/**
	 * Returns Faceboo API object inited with API settings
	 * 
	 * @return Facebook\Facebook
	 */
	protected function getFacebookObject() {
		// keep only one instance during a session
		if (is_object($this->fb)) {
			return $this->fb;
		}
		
		$fb = new \Facebook\Facebook([
			'app_id' => $this->options['api_key'],
			'app_secret' => $this->options['secret_key'],
			'default_graph_version' => 'v3.0',
			]);
		
		$this->fb = $fb;
		
		return $fb;
	}
	/**
	 * Returns list of properties to serialise.
	 * 
	 * @param array $skip Defines what properties to skip
	 */
	protected function getSerializeVars($skip = array()) {
		// skip fb property. Noo need to serialize it
		$skip[] = 'fb';
		return parent::getSerializeVars($skip);
	}
	/**
	 * Get array of GET/POST arguments posted fromfacebook back to complete login URL 
	 * 
	 * @return array List of POST/GET arguments names
	 */
	public function getFinalExtraInputs() {
		return array('code','state','error_code','error','error_reason','error_description'); 
	}
	/**
	 * Returns facebook login start auth process
	 * 
	 * @param string $redirecturl URL where to redirect after login complete
	 */
	public function getLoginStartUrl($redirecturl) {
		$facebook = $this->getFacebookObject();
		
		$helper = $facebook->getRedirectLoginHelper();

		$permissions = ['email']; // Optional permissions
		$loginUrl = $helper->getLoginUrl($redirecturl, $permissions);

		return $loginUrl;
	}
	/**
	 * Completes social login. Is caled after redirect from facebook auth page
	 * 
	 * @param array $extrainputs List of POST/GET arguments names
	 */
	public function completeLogin($extrainputs = array()) {
		$facebook = $this->getFacebookObject();
		
		// we are not sure about $_GET contains all correct data
		// as in this model data are posted with $extrainputs
		// ideally it would be good if facebook lib accept data not only from _GET but also from any array
		$old_GET = $_GET;
		$_GET = $extrainputs;
		
		$helper = $facebook->getRedirectLoginHelper();

		// don't catch exceptions. it will be done in the model or controller
		$accessToken = $helper->getAccessToken();
		
		$_GET = $old_GET;
		
		if (! isset($accessToken)) {
			if ($helper->getError()) {
				throw new \Exception($helper->getError().' '.
					$helper->getErrorCode().' '.
					$helper->getErrorReason().' '.
					$helper->getErrorDescription());
    			} else {
    				throw new \Exception('Unknown error from Facebook');
    			}
  		}

  		$this->accesstoken = $accessToken;
  		
  		return $this->getUserProfile();
	}
	/**
	 * Returns short facebook profile after succes login
	 * 
	 * @return array User Profile
	 */
	public function getUserProfile() {
		$facebook = $this->getFacebookObject();
		
		$response = $facebook->get('/me', $this->accesstoken);
		
		$me = $response->getGraphUser();

		$email = $me->getField('email');

		if (empty($email)) {
			$email = $this->userEmail;
		}
		
		return array(
			'userid' => $me->getId(),
			'name' => $me->getName(),
			'email' => $email,
			'imageurl' => 'https://graph.facebook.com/'.$me->getId().'/picture?type=large');
	}
	public function loginWithTokenID($tokenid)
    {
		$parts = explode(':::', $tokenid);

		if (count($parts) > 1) {
			$this->accesstoken = $parts[0];
			$this->userEmail = $parts[1];
		} else {
			$this->accesstoken = $tokenid;
		}
        
        return $this->getUserProfile();
    }
}
