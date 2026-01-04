<?php
/**
 * Post Collection User Feed
 *
 * This provides the User_Feed::TAXONOMY constant.
 * It is only loaded when the Friends plugin is not active.
 *
 * @package Friends_Post_Collection
 */

namespace Friends;

/**
 * This is a standalone User_Feed class for the Post Collection plugin.
 * When the Friends plugin is active, its User_Feed class is used instead.
 *
 * @since 3.0
 *
 * @package Friends_Post_Collection
 * @author Alex Kirk
 */
class User_Feed {
	const TAXONOMY = 'friend-user-feed';
}
