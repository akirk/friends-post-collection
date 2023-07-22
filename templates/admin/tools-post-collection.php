<?php
/**
 * This template contains the post collection editor.
 *
 * @version 1.0
 * @package Friends_Post_Collection
 */

?>
<div class="card">
	<h2 class="title"><?php esc_html_e( 'Friends Post Collection', 'friends' ); ?></h2>
	<h3><?php esc_html_e( 'Bookmarklets', 'friends' ); ?></h3>

	<p><?php esc_html_e( "Drag one of these bookmarklets to your bookmarks bar and click it when you're on an article you want to save from the web.", 'friends' ); ?></p>
	<p>
		<?php foreach ( $args['post_collections'] as $url => $display_name ) : ?>
		<a href="javascript:<?php echo rawurlencode( trim( str_replace( "window.document.getElementById( 'friends-post-collection-script' ).getAttribute( 'data-post-url' )", "'" . esc_url( $url ) . "'", $args['bookmarklet_js'] ), ';' ) ); ?>" style="display: inline-block; padding: .5em; border: 1px solid #999; border-radius: 4px; background-color: #ddd;text-decoration: none; margin-right: 3em">
			<?php
			echo esc_html(
				sprintf(
				// translators: %s is the name of a Post Collection user.
					__( 'Save to %s', 'friends' ),
					$display_name
				)
			);
			?>
		</a>
		<?php endforeach; ?>
	</p>
</div>
