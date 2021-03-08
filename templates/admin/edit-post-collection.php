<?php
/**
 * This template contains the post collection editor.
 *
 * @version 1.0
 * @package Friends_Post_Collection
 */

?><form method="post">
	<?php wp_nonce_field( 'edit-post-collection-' . $args['user']->ID ); ?>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label for="url"><?php esc_html_e( 'Posts' ); ?></label></th>
				<td>
					<fieldset>
					<a href="<?php echo esc_url( $args['user']->get_local_friends_page_url() ); ?>">
						<?php
						// translators: %d is the number of posts.
						echo esc_html( sprintf( _n( 'View %d post', 'View %d posts', $args['posts']->found_posts, 'friends' ), $args['posts']->found_posts ) );
						?>
					</a>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th><label for="url"><?php esc_html_e( 'Syndicate Posts' ); ?></label></th>
				<td>
					<fieldset>
						<input type="checkbox" name="publish_post_collection" value="1" <?php checked( get_user_option( 'friends_publish_post_collection', $user->ID ) ); ?> />
						<?php
						$feed = trailingslashit( $user->get_local_friends_page_url() . 'feed' );
						echo wp_kses(
							sprintf(
							// translators: %s is a URL.
								__( 'Publish this Post Collection at %s', 'friends' ),
								'<a href="' . esc_url( $feed ) . '">' . esc_url( $feed ) . '</a>'
							),
							array( 'a' => array( 'href' => array() ) )
						);
						?>
					</fieldset>
				</td>
			</tr>
			<?php do_action( 'users_edit_post_collection_table_end', $args['user'] ); ?>
		</tbody>
	</table>
	<?php do_action( 'users_edit_post_collection_after_form', $args['user'] ); ?>
	<p class="submit">
		<input type="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes' ); ?>">
	</p>
</form>
