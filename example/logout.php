<?php 

/**
 * Example. Usage of Gelembjuk/Auth/SocialLogin library to login to a web site with social networks
 * 
 * This is the file logout.php . It completes a user session 
 * 
 * This example is part of gelembjuk/auth package by Roman Gelembjuk (@gelembjuk)
 */

// settings and composer autoloader connection are in a separate file
require('init.php');

unset($_SESSION['user']);

header("Location: index.php",true,301);
exit;
