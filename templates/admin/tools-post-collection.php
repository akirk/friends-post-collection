<?php
/**
 * This template contains the post collection editor.
 *
 * @version 1.0
 * @package Friends_Post_Collection
 */

?>
<div class="card">
	<h2 class="title"><?php _e( 'Friends Post Collection', 'friends-post-collection' ); ?></h2>
	<h3><?php _e( 'Bookmarklets', 'friends-post-collection' ); ?></h3>

	<p><?php _e( "Drag one of these bookmarklets to your bookmarks bar and click it when you're on an article you want to save from the web.", 'friends-post-collection' ); ?></p>
	<p>
		<?php foreach ( $args['post_collections'] as $url => $display_name ) : ?>
		<a href="javascript:<?php echo rawurlencode( trim( str_replace( "document.getElementById( 'friends-post-collection-script' ).getAttribute( 'data-post-url' )", "'" . esc_url( $url ) . "'", $args['bookmarklet_js'] ), ';' ) ); ?>" style="display: inline-block; padding: .5em; border: 1px solid #999; border-radius: 4px; background-color: #ddd;text-decoration: none; margin-right: 3em">
			<?php
			echo esc_html(
				sprintf(
				// translators: %s is  the name of a Post Collection user.
					__( 'Save to %s', 'friends-post-collection' ),
					$display_name
				)
			);
			?>
		</a>
		<?php endforeach; ?>
	</p>
	<h3><?php _e( 'Browser Extension', 'friends-post-collection' ); ?></h3>

	<p><?php _e( 'The Friends browser extension also allows to save the currently viewed article.', 'friends-post-collection' ); ?></p>
	<p>
		<a href="https://addons.mozilla.org/en-US/firefox/addon/wpfriends/"><?php echo esc_html_e( 'Firefox Extension', 'friends-post-collection' ); ?></a>
	</p>
</div>
