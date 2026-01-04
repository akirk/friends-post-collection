<?php
/**
 * Plugin name: Friends Post Collection
 * Plugin author: Alex Kirk
 * Plugin URI: https://github.com/akirk/friends-post-collection
 * Version: 1.2.6
 *
 * Description: Collect posts from around the web into your Friends UI.
 *
 * License: GPL2
 * Text Domain: friends
 * Domain Path: /languages/
 *
 * @package Friends_Post_Collection
 */

namespace Friends;

/**
 * This file contains the main plugin functionality.
 */

defined( 'ABSPATH' ) || exit;
define( 'FRIENDS_POST_COLLECTION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FRIENDS_POST_COLLECTION_PLUGIN_FILE', plugin_dir_path( __FILE__ ) . '/' . basename( __FILE__ ) );

require_once __DIR__ . '/vendor/autoload.php';

if ( ! class_exists( __NAMESPACE__ . '\User' ) ) {
	require_once __DIR__ . '/class-user.php';
}
if ( ! class_exists( __NAMESPACE__ . '\User_Query' ) ) {
	require_once __DIR__ . '/class-user-query.php';
}
if ( ! class_exists( __NAMESPACE__ . '\User_Feed' ) ) {
	require_once __DIR__ . '/class-user-feed.php';
}
if ( ! class_exists( __NAMESPACE__ . '\Subscription' ) ) {
	require_once __DIR__ . '/class-subscription.php';
}

require_once __DIR__ . '/class-post-collection.php';
require_once __DIR__ . '/class-extracted-page.php';
require_once __DIR__ . '/site-configs/class-site-config.php';
require_once __DIR__ . '/site-configs/class-youtube.php';

add_filter( 'friends_post_collection', '__return_true' );

function load_friends_post_collection( $friends = null ) {
	if ( doing_action( 'init' ) && did_action( 'friends_loaded' ) ) {
		return;
	}

	$post_collection = new Post_Collection( $friends );
	$post_collection->register_site_config( new PostCollection\SiteConfig\Youtube() );
}

add_action( 'friends_loaded', 'Friends\load_friends_post_collection' );
add_action( 'init', 'Friends\load_friends_post_collection' );

register_activation_hook( __FILE__, array( __NAMESPACE__ . '\Post_Collection', 'activate_plugin' ) );
add_action( 'activate_blog', array( __NAMESPACE__ . '\Post_Collection', 'activate_plugin' ) );
add_action( 'wp_initialize_site', array( __NAMESPACE__ . '\Post_Collection', 'activate_for_blog' ) );
