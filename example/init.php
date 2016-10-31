<?php 

/**
 * Example. Usage of Gelembjuk/Auth/SocialLogin library to login to a web site with social networks
 * 
 * This example is part of gelembjuk/auth package by Roman Gelembjuk (@gelembjuk)
 */

// path to your composer autoloader
require ('vendor/autoload.php');

$integrations = array(
	'facebook' => array(
		'api_key' => 'fake facebook api key',
		'secret_key' => '',//'fake facebook secret key'
		),
	'twitter' => array(
		'consumer_key' => 'fake twitter consumer key',
		'consumer_secret' => '',//'fake twitter consumer secret'
		),
	'linkedin' => array(
		'api_key' => 'fake linkedin api key',
		'api_secret' => '',//'fake linkedin api secret'
		),
	'google' => array(
		'application_name' => 'Your application name',
		'client_id' => 'fake google api client id',
		'client_secret' => '',//'fake google api client secret'
		),
	'xingapi' => array(
		'consumer_key' => 'fake xing consumer key',
		'consumer_secret' => '',//'fake xing counsumer secret'
		),
	'liveid' => array(
		'consumer_key' => 'fake live id consumer key',
		'consumer_secret' => '',//'fake live id consumer secret'
		)
	);

if (file_exists('init.real.php')) {
	// this is small trick to hide real working credentials from Git
	// on practice you will not need this
	include('init.real.php');
}

session_start();
