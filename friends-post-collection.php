<?php
/**
 * Plugin name: Friends Post Collection
 * Plugin author: Alex Kirk
 * Plugin URI: https://github.com/akirk/friends-post-collection
 * Version: 0.6.1
 *
 * Description: Collect posts from around the web into your Friends UI.
 *
 * License: GPL2
 * Text Domain: friends
 * Domain Path: /languages/
 *
 * @package Friends_Post_Collection
 */

/**
 * This file contains the main plugin functionality.
 */

defined( 'ABSPATH' ) || exit;
define( 'FRIENDS_POST_COLLECTION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FRIENDS_POST_COLLECTION_PLUGIN_FILE', plugin_dir_path( __FILE__ ) . '/' . basename( __FILE__ ) );

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/class.friends-post-collection.php';

add_filter( 'friends_post_collection', '__return_true' );
add_action(
	'friends_init',
	function( $friends ) {
		new Friends_Post_Collection( $friends );
	}
);

register_activation_hook( __FILE__, array( 'Friends_Post_Collection', 'activate_plugin' ) );

