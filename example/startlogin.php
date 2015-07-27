<?php 

/**
 * Example. Usage of Gelembjuk/Auth/SocialLogin library to login to a web site with social networks
 * 
 * This is the file startlogin.php . It prepares the social network login url and redirects a user to it
 * 
 * This example is part of gelembjuk/auth package by Roman Gelembjuk (@gelembjuk)
 */

// settings and composer autoloader connection are in a separate file
require('init.php');

$socialnetwork = $_REQUEST['network']; 

if (!isset($integrations[$socialnetwork])) {
	echo "Sorry, such integration is not found. Somethign went wrong";
	exit;
}

// create social network login object. The second argument is array of API settings for a social network
$network = Gelembjuk\Auth\AuthFactory::getSocialLoginObject($socialnetwork,$integrations[$socialnetwork]);

//IMPORTANT. his must be an absolute url of your "social login completion" script
$redirecturl = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']).'/completelogin.php';

// get auth url and use redirect url to back to complete login page
$url = $network->getLoginStartUrl($redirecturl);
		
// remember the state. it will be used when complete a social login
$_SESSION['socialloginsate_'.$socialnetwork] = $network->serialize();

// this is optional. you can include a network name in your redirect url and then extract
$_SESSION['socialloginnetwork'] = $socialnetwork;

// redirect to auth url. It will forward a user to a social network login page
header("Location: $url",true,301);
exit;
