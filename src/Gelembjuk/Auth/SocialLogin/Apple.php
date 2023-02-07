<?php 

/**
* The class to login to a web site with Apple account
*
* LICENSE: MIT
*
* @category   Auth
* @package    Gelembjuk/Auth
* @copyright  Copyright (c) 2023 Roman Gelembjuk. (http://gelembjuk.com)
* @version    1.0
* @link       https://github.com/Gelembjuk/auth
*/

namespace Gelembjuk\Auth\SocialLogin;

use Firebase\JWT\JWT;

class Apple extends Base {
	/**
	 * Apple Client ID
	 * 
	 * @var string
	 */
	protected $client_id;
	/**
	 * Apple Team ID request token
	 * 
	 * @var string
	 */
	protected $team_id;
	
	/**
	 * Session string to use when create url and when receive callback
	 * 
	 * @var string
	 */
	protected $state;

	/**
	 * Redirect url used on the first step to reuse on the second step
	 * 
	 * @var string
	 */
	protected $redirectUrl;

	/**
	 * ID of apple user
	 * 
	 * @var string
	 */
	protected $userid;

	/**
	 * Name of apple user
	 * 
	 * @var string
	 */
	protected $username;

	/**
	 * Email of apple user
	 * 
	 * @var string
	 */
	protected $useremail;

	/**
	 * The data for decryption
	 * 
	 * @var array
	 */
	public static $supported_algs = array(
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'RS256' => array('openssl', 'SHA256'),
        'RS384' => array('openssl', 'SHA384'),
        'RS512' => array('openssl', 'SHA512'),
    );
	
	/**
     * Checks if an integration is configured. All options are provided.
     * This doesn't check if options are correct
     * 
     * @return bool
     */
    public function isConfigured() {
        if (empty($this->options['client_id'])) {
            return false;
        }
		if (empty($this->options['team_id'])) {
            return false;
        }
		if (empty($this->options['key_id'])) {
            return false;
        }
		if (empty($this->options['keyfile'])) {
            return false;
        }
		if (!file_exists($this->options['keyfile'])) {
            return false;
        }
        return true;
    }
	protected function getSerializeVars($skip = array()) 
	{
		return ['state','redirectUrl'];
	}
	/**
	 * Returns apple login start auth process
	 * 
	 * @param string $redirecturl URL where to redirect after login complete
	 */
	public function getLoginStartUrl($redirecturl) 
	{
		$this->state = bin2hex(random_bytes(5));
		$this->redirectUrl = $redirecturl;

		$authorize_url = 'https://appleid.apple.com/auth/authorize'.'?'.http_build_query([
			'response_type' => 'code',
			'response_mode' => 'form_post',
			'client_id' => $this->options['client_id'],
			'redirect_uri' => $redirecturl,
			'state' => $this->state,
			'scope' => 'name email',
		  ]);
		return $authorize_url;
	}
	/**
	 * Makes Client Secret from certificate file
	 * 
	 */
	private function createClientSecret()
	{
		return JWT::encode([
			'iss' => $this->options['team_id'],
			'iat' => strtotime('now'),
			'exp' => strtotime('+60days'),
			'aud' => 'https://appleid.apple.com',
			'sub' => $this->options['client_id'],
		], file_get_contents($this->options['keyfile']), 'ES256', $this->options['key_id']);
	}
	private function decodeIdentityToken($token)
	{
		$tks = explode('.', $token);

        if (count($tks) != 3) {
            throw new \Exception('Wrong number of segments');
        }
		list($headb64, $bodyb64, $cryptob64) = $tks;

        if (null === ($header = json_decode(base64_decode($headb64)))) {
            throw new \Exception('Invalid header encoding');
        }
		if (null === $payload = json_decode(base64_decode($bodyb64))) {
            throw new \Exception('Invalid claims encoding');
        }
		/*
        if (false === ($sig = base64_decode($cryptob64))) {
            throw new \Exception('Invalid signature encoding');
        }
		*/
		// we do not check signature here. 
		return $payload;
	}
	/**
	 * Get array of GET/POST arguments posted from twitter back to complete login URL 
	 * 
	 * @return array List of POST/GET arguments names
	 */
	public function getFinalExtraInputs() {
		return array('code','state','user'); // by default no extra inputs
	}
	
	/**
	 * Completes social login. Is caled after redirect from apple auth page
	 * 
	 * @param array $extrainputs List of POST/GET arguments names
	 */
	public function completeLogin($extrainputs = array()) 
	{
		if ($this->state != $extrainputs['state']) {
			throw new \Exception('State code is different from expected');
		}
		$client_secret = $this->createClientSecret();
		
		$response = $this->httpRequest('https://appleid.apple.com/auth/token', [
			'grant_type' => 'authorization_code',
			'code' => $extrainputs['code'],
			'redirect_uri' => $this->redirectUrl,
			'client_id' => $this->options['client_id'],
			'client_secret' => $client_secret,
			]);
		
		$token_info = $this->decodeIdentityToken($response['id_token']);
		
		$this->userid = $token_info->sub;

		if (!empty($extrainputs['user'])) {
			$userData = @json_decode($extrainputs['user'],true);

			if (is_array($userData)) {
				$this->username = $userData['name']['firstName'].' '.$userData['name']['lastName'];
				$this->useremail = $userData['email'];
			}
		}

		if (empty($this->username)) {
			$this->username = 'Apple user ';
		}
		if (empty($this->useremail)) {
			$this->useremail = $token_info->email;
		}
 
		return $this->getUserProfile();
	}
	private function httpRequest($url, $params=null) 
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if ($params) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}
		  
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
		  'Accept: application/json',
		  'User-Agent: curl', # Apple requires a user agent header at the token endpoint
		]);
		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			throw new \Exception(curl_error($ch).' ('.curl_errno($ch).')');
        }
        
        $lasthttpcode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        
        curl_close($ch);

		if ($lasthttpcode != 200) {
			throw new \Exception("Unexpected response code $lasthttpcode");
		}

		if (empty($response)) {
			throw new \Exception("Empty response");
		}
		$data = @json_decode($response, true);

		if (!is_array($data)) {
			throw new \Exception("Failed to parse response");
		}
		return $data;
	  }
	/**
	 * Returns short twitter profile after succes login
	 * 
	 * @return array User Profile
	 */
	public function getUserProfile() {
		
		return array(
			'userid' => $this->userid,
			'name' => $this->username,
			'email' => $this->useremail,
			'imageurl' => '');
	}
	public function loginWithTokenID($tokenid)
    {
        $decoded = @base64_decode($tokenid);
        
        if (empty($decoded)) {
            throw new \Exception('Twitter token format is broken');
        }
        
        list($userid, $username, $useremail) = explode(':::', $decoded);
        
        if (empty($userid)) {
            throw new \Exception('Apple token format is broken');
        }
        
		return array(
			'userid' => $userid,
			'name'=>$username,
			'email' => $useremail,
			'imageurl'=> ''  
		);   
    }
	
}
