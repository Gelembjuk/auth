<?php 

/**
* This class the base class for social login integrations
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

abstract class Base {
	// include logger functionality to be able to log if needed
	use \Gelembjuk\Logger\ApplicationLogger;
	
	/**
	* Options array
	*
	* @var array
	*/
	protected $options;
	
	/**
	 * Constructor.
	 * 
	 * Only saves optiosn to a property
	 * 
	 * @param int $options
	 */
	public function __construct($options) {
		$this->options = $options;
	}
	
	/**
	 * Returns list of properties to serialise.
	 * 
	 * @param array $skip Defines what properties to skip
	 */
	protected function getSerializeVars($skip = array()) {
		$vars = get_object_vars($this);
		
		$servars = array();
		
		foreach ($vars as $k=>$v) {
			// skip what is in the array
			if (in_array($k,$skip)) {
				continue;
			}
			
			// skip 2 standars properties as no sence to serialize them
			if ($k == 'options' || $k == 'logger') {
				continue;
			}
			$servars[] = $k;
		}
		return $servars;
	}
	/**
	 * Returns serialized string
	 * We presume there will not be some complex objects
	 * only integers/strings etc
	 * 
	 * @return string $skip JSON string with values of selected properties
	 */
	public function serialize() {
		$properties = $this->getSerializeVars();
		
		$data = array();
		
		foreach ($properties as $p) {
			$data[$p] = $this->$p;
		}
		
		return json_encode($data);
	}
	/**
	 * Unserialize/restore an object state to continue login
	 * 
	 * @param string $json_string JSON string with values of selected properties
	 */
	public function unSerialize($json_string) {
		$data = json_decode($json_string,true);
		
		foreach ($data as $k => $v) {
			$this->$k = $v;
		}
		
		return true;
	}
	
	/**
	 * Abstract function to get social login start auth url
	 * 
	 * @param string $redirecturl URL where to redirect after login complete
	 */
	abstract public function getLoginStartUrl($redirecturl);
	
	/**
	 * Get array of GET/POST arguments posted from social network 
	 * as part of final redirect after login back to the site
	 * 
	 * @return array List of POST/GET arguments names
	 */
	public function getFinalExtraInputs() {
		// by default no extra inputs
		return array();
	}
	
	/**
	 * Abstract function to complete social login. Is caled after redirect from a social network
	 * 
	 * @param array $extrainputs List of POST/GET arguments names
	 */
	abstract public function completeLogin($extrainputs = array());
	
	/**
	 * Get social network user ID after success auth
	 * 
	 * @return string User ID
	 */
	public function getUserID() {
		$profile = $this->getUserProfile();
		return $profile['userid'];
	}
	/**
	 * Get social network user profile after success auth
	 * A profile is array and contains keys: id, name, email, imageurl
	 * 
	 * @return array User Profile
	 */
	abstract public function getUserProfile();
}
