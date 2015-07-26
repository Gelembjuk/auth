<?php 

/**
 * Example. Usage of Gelembjuk/Auth/SocialLogin library to login to a web site with social networks
 * 
 * This is the file completelogin.php . A social network redirects a user to this script after login (success or not) and 
 *  this script finalise a login and inits a session for a user 
 * 
 * This example is part of gelembjuk/auth package by Roman Gelembjuk (@gelembjuk)
 */

// settings and composer autoloader connection are in a separate file
require('init.php');

// network name was saved in a settions. get it from there
$socialnetwork = $_SESSION['socialloginnetwork'];

if (!isset($integrations[$socialnetwork])) {
	// this should not happen if all is fine. but we still have to check
	echo "Sorry, such integration is not found. Somethign went wrong";
	exit;
}

// do everything in a try-catch
try {
	// create social network login object. The second argument is array of API settings for a social network
	$network = Gelembjuk\Auth\AuthFactory::getSocialLoginObject($socialnetwork,$integrations[$socialnetwork]);
	
	// read some imput parameters needed to complete auth by this social network
	// some social network will send back extra information like a code to exchange then for a token
	$arguments = array();
	
	foreach ($network->getFinalExtraInputs() as $key) {
		$arguments[$key] = $_REQUEST[$key];
	}
	
	// restore to a state before redirect
	$network->unSerialize($_SESSION['socialloginsate_'.$network]);
			
	// if fails then throws exception and controller will catch
	$profile = $network->completeLogin($arguments);
	
	// save user info to a session
	$_SESSION['user'] = $profile;
	
	// now user is loged in!
	
	//redirect user to home page
	header("Location: index.php",true,301);
	exit;
	
} catch (Exception $e) {
	echo "Somethign went wrong during the login process<br>";
	echo "Error is: ".$e->getMessage();
}
