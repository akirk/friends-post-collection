<?php
/**
 * This template contains the post collection settings.
 *
 * @version 1.0
 * @package Friends_Post_Collection
 */

?>
<form method="post">
	<table class="widefat fixed striped">
		<thead>
			<th><?php _e( 'Post Collection Name', 'friends' ); ?></th>
			<th><?php _e( 'External feed', 'friends' ); ?></th>
		</thead>
		<?php foreach ( $args['post_collections'] as $user ) : ?>
		<?php $user = new Friend_User( $user ); ?>
		<tr>
		<td><a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php echo esc_html( $user->display_name ); ?></a></td>
		<td><?php
		if ( get_user_option( 'friends_publish_post_collection', $user->ID ) ) :
		?><a href="<?php echo esc_url( trailingslashit( $user->get_local_friends_page_url() . 'feed' ) ); ?>"><?php _e( 'enabled' ); ?></a><?php
	else:
			echo _e( 'disabled' );
		endif;
		?></td>
		</tr>

	<?php endforeach; ?>
</table>
<p class="description">
	<a href="<?php echo esc_url( self_admin_url( 'user-new.php?role=post_collection' ) ); ?>"><?php _e( 'Create another user' ); ?></a></p>

</form>

<p><?php echo wp_kses(
	sprintf(
		// translators: %s is a URL.
		__( 'To save posts from anywhere on the web, use the <a href=%s>bookmarklets</a>.', 'friends' ),
		self_admin_url( 'tools.php' )
	),
	array(
		'a' => array( 'href' => array())
	)
	); ?></p>
