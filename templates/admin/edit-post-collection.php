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
				<th><label for="display_name"><?php esc_html_e( 'Display Name', 'friends' ); ?></label></th>
				<td><input type="text" name="display_name" id="display_name" value="<?php echo esc_attr( $args['user']->display_name ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="description"><?php esc_html_e( 'Description', 'friends' ); ?></label></th>
				<td><textarea name="description" id="description" rows="5" cols="30"><?php echo esc_html( $args['user']->description ); ?></textarea></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Posts' ); ?></label></th>
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
				<th><label><?php esc_html_e( 'Syndicate Posts' ); ?></label></th>
				<td>
					<fieldset>
						<input type="checkbox" name="publish_post_collection" value="1" <?php checked( get_user_option( 'friends_publish_post_collection', $args['user']->ID ) ); ?> />
						<?php
						$feed = trailingslashit( $args['user']->get_local_friends_page_url() . 'feed' );
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
