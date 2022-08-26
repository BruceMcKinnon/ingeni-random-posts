<?php
/*
Plugin Name: Ingeni Create Random Posts
Version: 2022.01
Plugin URI: http://ingeni.net
Author: Bruce McKinnon - ingeni.net
Author URI: http://ingeni.net
Description: Populate a WP database with random posts. Great for getting test sites up and running quickly.
License: GPL v3

Ingeni Create Random Posts Plugin
Copyright (C) 2015, Bruce McKinnon

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

v2015.02 - Original release
v2019.01 - Code refactored and package
v2019.02 - Added support for random content from litipsum.com - defaults to Sherlock Holmes
v2019.03 - Bug fix - content wasn't random enough!
v2019.04 - Added option to set a specific post category, rather than just using a random one.
v2021.01 - Wasn't correctly handling the WP table prefix value - bad boy!
v2022.01 - attach_random_image() - Formatting error in SQL statement.
*/

const ADD_RANDOM_POSTS = "Add Random Posts";
const DELETE_RANDOM_POSTS =  "Delete Random Posts";
const INGENI_RANDOM_POSTS_QTY = "ingeni_random_posts_qty";
const INGENI_RANDOM_CONTENT_URL = "ingeni_random_posts_content_url";
const INGENI_RANDOM_CATEGORY = "ingeni_random_posts_category";

require_once ('ingeni-random-posts-class.php');

$ingeniRandomPosts;

add_action( 'init', 'init_random_posts' );
function init_random_posts() {

	// Init auto-update from GitHub repo
	require 'plugin-update-checker/plugin-update-checker.php';
	$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
		'https://github.com/BruceMcKinnon/ingeni-random-posts',
		__FILE__,
		'ingeni-random-posts'
	);
}



add_action('admin_menu', 'random_posts_submenu_page');

//
// Admin page
//
function random_posts_submenu_page() {
	add_submenu_page( 'tools.php', 'Generate Random Posts', 'Generate Random Posts', 'manage_options', 'random_posts-page', 'random_posts_options_page' );
}

function random_posts_options_page() {
	global $ingeniRandomPosts;

	if ( !$ingeniRandomPosts ) {
		$ingeniRandomPosts = new IngeniRandomPosts();
	}

	if ( (isset($_POST['random_posts_edit_hidden'])) && ($_POST['random_posts_edit_hidden'] == 'Y') ) {
		$errMsg = '';
		
		update_option(INGENI_RANDOM_POSTS_QTY, $_POST['random_posts_qty'] );
		update_option(INGENI_RANDOM_CONTENT_URL, $_POST[INGENI_RANDOM_CONTENT_URL] );
		update_option(INGENI_RANDOM_CATEGORY, $_POST[INGENI_RANDOM_CATEGORY] );
		
		switch ($_REQUEST['btn_random_posts_submit']) {
			case ADD_RANDOM_POSTS :
				$random_count = $ingeniRandomPosts->create_random_posts( get_option(INGENI_RANDOM_POSTS_QTY, 10), get_option(INGENI_RANDOM_CONTENT_URL, ''), get_option(INGENI_RANDOM_CATEGORY, ''), $errMsg );
				
				if ( $random_count >= 0 ) {
					echo('<div class="updated"><p><strong>'.$random_count.' posts created...</strong></p></div>');
				} else {
					echo('<div class="updated"><p><strong>'.$errMsg.'</strong></p></div>');		
				}
			break;
				
			case DELETE_RANDOM_POSTS :
				$delete_count = $ingeniRandomPosts->delete_random_posts( $errMsg );
				if ( $delete_count >= 0 ) {
					echo('<div class="updated"><p><strong>'.$delete_count.' posts deleted...</strong></p></div>');
				} else {
					echo('<div class="updated"><p><strong>'.$errMsg.'</strong></p></div>');		
				}
			break;
		}
	}

	echo('<div class="wrap">');
		echo('<h2>Random Post Generator</h2>');

		echo('<form action="'. str_replace( '%7E', '~', $_SERVER['REQUEST_URI']).'" method="post" name="random_posts_options_page">'); 
			echo('<input type="hidden" name="random_posts_edit_hidden" value="Y">');
			
			echo('<table class="form-table" style="max-width:90%;width:800px;">');
			
			echo('<tr valign="top">'); 
				echo('<td style="width:250px;">Number of posts to generate (1-20)</td><td><input type="number" name="random_posts_qty" min="1" max="20" value="'.get_option(INGENI_RANDOM_POSTS_QTY, 10).'"></td>'); 
			echo('</tr>');
			
			echo('<tr valign="top">'); 
				echo('<td style="width:250px;">Content geneator URL (leave blank for static content)</td><td><input type="text" style="width:100%;" name="'.INGENI_RANDOM_CONTENT_URL.'" value="'.get_option(INGENI_RANDOM_CONTENT_URL, 'https://litipsum.com/api/adventures-sherlock-holmes/10/p').'"></td>'); 
			echo('</tr>');

			echo('<tr valign="top">'); 
				echo('<td style="width:250px;">Category (leave blank for random)</td><td><input type="text" style="width:100%;" name="'.INGENI_RANDOM_CATEGORY.'" value="'.get_option(INGENI_RANDOM_CATEGORY, 'news').'"></td>'); 
			echo('</tr>');			
			
			
			echo('<tr valign="top">'); 
				echo('<td><input type="submit" value="' . ADD_RANDOM_POSTS . '" name="btn_random_posts_submit"></td>');
			echo('</tr>');
			echo('<tr valign="top">'); 
				echo('<td><input type="submit" value="' . DELETE_RANDOM_POSTS . '" name="btn_random_posts_submit"></td>');
			echo('</tr>');
			
			echo('</tbody></table><br/>');			
			
		
			
		echo('</form>');	
	echo('</div>');

}


//
// Plugin activation/deactivation hooks
//

function random_posts_activation() {
	flush_rewrite_rules( false );
}
register_activation_hook(__FILE__, 'random_posts_activation');

function random_posts_deactivation() {
	flush_rewrite_rules( false );
}
register_deactivation_hook( __FILE__, 'random_posts_deactivation' );



?>