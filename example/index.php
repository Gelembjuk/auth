<?php 

/**
 * Example. Usage of Gelembjuk/Auth/SocialLogin library to login to a web site with social networks
 * 
 * This is the file index.php . It shows a user login status and displays login links if a user is not in a system yet
 * 
 * This example is part of gelembjuk/auth package by Roman Gelembjuk (@gelembjuk)
 */

// settings and composer autoloader connection are in a separate file
require('init.php');

if ($_SESSION['user']['id']) {
	// user is already in the system. Show view for authorized users
	echo '<h2>Hello '.$_SESSION['user']['name'].'</h2>';
	echo 'For now you can do nothing, except <a href="logout.php">Logout</a>';
} else {
	// user is not authorised. Show login options for him
	echo '<h2>Hello Guest</h2>';
	echo '<p>You can login with:</p><ul>';
	
	echo '<li><a href="startlogin.php?network=facebook">Facebook</a></li>';
	echo '<li><a href="startlogin.php?network=google">Google</a></li>';
	echo '<li><a href="startlogin.php?network=twitter">Twitter</a></li>';
	echo '<li><a href="startlogin.php?network=linkedin">LinkedIn</a></li>';
	
	echo '</ul>';
	
}
