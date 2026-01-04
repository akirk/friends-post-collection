<?php
/**
 * Post Collection Subscription
 *
 * This provides the Subscription class with TAXONOMY constant.
 * It is only loaded when the Friends plugin is not active.
 *
 * @package Friends_Post_Collection
 */

namespace Friends;

/**
 * This is a standalone Subscription class for the Post Collection plugin.
 * When the Friends plugin is active, its Subscription class is used instead.
 *
 * @since 3.0
 *
 * @package Friends_Post_Collection
 * @author Alex Kirk
 */
class Subscription {
	const TAXONOMY = 'friend-subscription';
}
