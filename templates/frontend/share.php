<div class="friends-dropdown">
	<a class="btn ml-1 friends-dropdown-toggle" tabindex="0">
		<i class="dashicons dashicons-share"></i> <?php echo esc_html( _x( 'Share', 'button', 'friends' ) ); ?>
	</a>
	<ul class="menu" style="min-width: <?php echo esc_attr( intval( _x( '250', 'dropdown-menu-width', 'friends' ) ) ); ?>px">
	<?php
	foreach ( $args['post-collections'] as $user ) {
		if ( 10000 === intval( $user->ID ) ) {
			continue;
		}
		?>
		<li class="menu-item"><a href="#" data-id="<?php echo esc_attr( get_the_ID() ); ?>" data-author="<?php echo esc_attr( $user->ID ); ?>" data-first="<?php echo esc_attr( $user->ID ); ?>" class="friends-post-collection-change-author has-icon-right">
			  <?php
				echo esc_html(
					sprintf(
					// translators: %s is the name of a post collection.
						_x( 'Move to %s', 'post-collection', 'friends' ),
						$user->display_name
					)
				);
				?>
			<i class="form-icon"></i></a>
		</li>
		<?php
	}
	?>
</ul>
</div>
