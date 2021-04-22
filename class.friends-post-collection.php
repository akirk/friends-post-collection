<?php
/**
 * Friends Post Collection
 *
 * This contains the post collection functions.
 *
 * @package Friends_Post_Collection
 */

/**
 * This is the class for the downloading and storing posts for the Friends Plugin.
 *
 * @since 0.1
 *
 * @package Friends_Post_Collection
 * @author Alex Kirk
 */
class Friends_Post_Collection {
	/**
	 * Whether to cache the retrieved users
	 *
	 * @var boolean
	 */
	public static $cache = true;

	/**
	 * Contains a reference to the Friends class.
	 *
	 * @var Friends
	 */
	private $friends;

	/**
	 * Constructor
	 *
	 * @param Friends $friends A reference to the Friends object.
	 */
	public function __construct( Friends $friends ) {
		$this->friends = $friends;
		$this->register_hooks();
	}

	/**
	 * Register the WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'tool_box', array( $this, 'toolbox_bookmarklet' ) );
		add_action( 'user_new_form_tag', array( $this, 'user_new_form_tag' ) );
		add_filter( 'user_row_actions', array( $this, 'user_row_actions' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 50 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 99999 );
		add_action( 'wp_loaded', array( $this, 'save_url_endpoint' ), 100 );
		add_filter( 'get_edit_user_link', array( $this, 'edit_post_collection_link' ), 10, 2 );
		add_action( 'friend_post_edit_link', array( $this, 'allow_post_editing' ), 10, 2 );
		add_action( 'friends_entry_dropdown_menu', array( $this, 'add_post_collection_dropdown_items' ) );
		add_action( 'friends_friend_feed_viewable', array( $this, 'friends_friend_feed_viewable' ), 10, 2 );
		add_action( 'friend_user_role_name', array( $this, 'friend_user_role_name' ), 10, 2 );
		add_action( 'friends_widget_friend_list_after', array( $this, 'friends_widget_friend_list_after' ), 10, 2 );
		add_action( 'friends_author_header', array( $this, 'friends_author_header' ) );
		add_action( 'wp_ajax_friends-post-collection-mark-publish', array( $this, 'wp_ajax_mark_publish' ) );
		add_action( 'wp_ajax_friends-post-collection-mark-private', array( $this, 'wp_ajax_mark_private' ) );
		add_action( 'wp_ajax_friends-post-collection-change-author', array( $this, 'wp_ajax_change_author' ) );
	}

	/**
	 * Get the Friends_Template_Loader singleton
	 *
	 * @return Friends_Template_Loader A class instance.
	 */
	public static function template_loader() {
		static $template_loader;
		if ( ! isset( $template_loader ) ) {
			require_once __DIR__ . '/class.friends-post-collection-template-loader.php';
			$template_loader = new Friends_Post_Collection_Template_Loader();
		}
		return $template_loader;
	}

	public function allow_post_editing( $link, $original ) {
		if ( $this->is_post_collection_user( get_the_author_meta( 'ID' ) ) ) {
			return $original;
		}
		return $link;
	}

	public function add_post_collection_dropdown_items() {
		$divider = '<li class="divider" data-content="' . esc_attr__( 'Post Collection', 'friends' ) . '"></li>';
		$user_id = get_the_author_meta( 'ID' );
		if ( $this->is_post_collection_user( $user_id ) ) {
			echo $divider;
			$divider = '';
			?>
			<li class="menu-item"><a href="<?php echo esc_url( get_edit_user_link( $user_id ) ); ?>"><?php _e( 'Edit Post Collection', 'friends' ); ?></a></li>
			<?php
			if ( 'private' === get_post_status() ) {
				?>
				<li class="menu-item"><a href="#" data-id="<?php echo esc_attr( get_the_ID() ); ?>" class="friends-post-collection-mark-publish"><?php _e( 'Make post public', 'friends' ); ?></a></li>
				<?php
			} elseif ( 'publish' === get_post_status() ) {
				?>
					<li class="menu-item"><a href="#" data-id="<?php echo esc_attr( get_the_ID() ); ?>" class="friends-post-collection-mark-private"><?php _e( 'Make post private', 'friends' ); ?></a></li>
				<?php
			}
		}

		foreach ( $this->get_post_collection_users()->get_results() as $user ) {
			if ( intval( $user_id ) === intval( $user->ID ) ) {
				continue;
			}
			echo $divider;
			$divider = '';
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

	}

	public function edit_post_collection_link( $link, $user_id ) {
		$user = new WP_User( $user_id );
		if ( is_multisite() && is_super_admin( $user->ID ) ) {
			return $link;
		}
		if (
			! $user->has_cap( 'post_collection' )
		) {
			return $link;
		}

		return self_admin_url( 'admin.php?page=edit-post-collection&user=' . $user_id );
	}


	public function is_post_collection_user( $user_id ) {
		static $cache = array();

		if ( ! isset( $cache[ $user_id ] ) ) {
			$user = new Friend_User( $user_id );
			$cache[ $user_id ] = $user->has_cap( 'post_collection' );
		}

		return $cache[ $user_id ];
	}

	/**
	 * Process access for the Friends Edit User page
	 */
	private function check_edit_post_collection() {
		if ( ! current_user_can( Friends::REQUIRED_ROLE ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to edit this user.' ) );
		}

		if ( ! isset( $_GET['user'] ) || ! is_numeric( $_GET['user'] ) ) {
			wp_die( esc_html__( 'Invalid user ID.' ) );
		}

		$user = new Friend_User( intval( $_GET['user'] ) );
		if ( ! $user || is_wp_error( $user ) ) {
			wp_die( esc_html__( 'Invalid user ID.' ) );
		}

		if ( is_multisite() && is_super_admin( $_GET['user'] ) ) {
			wp_die( esc_html__( 'Invalid user ID.' ) );
		}

		if (
			! $user->has_cap( 'post_collection' )
		) {
			wp_die( esc_html__( 'This is not a user related to this plugin.', 'friends' ) );
		}

		return $user;
	}

	/**
	 * Process the Friends Edit Post Collection page
	 */
	public function process_edit_post_collection() {
		$user    = $this->check_edit_post_collection();
		$arg       = 'updated';
		$arg_value = 1;

		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'edit-post-collection-' . $user->ID ) ) {

			if ( trim( $_POST['display_name'] ) ) {
				$user->display_name = trim( $_POST['display_name'] );
			}
			$user->description = trim( $_POST['description'] );
			wp_update_user( $user );

			if ( isset( $_POST['publish_post_collection'] ) && $_POST['publish_post_collection'] ) {
				update_user_option( $user->ID, 'friends_publish_post_collection', true );
			} else {
				delete_user_option( $user->ID, 'friends_publish_post_collection' );
			}
		} else {
			return;
		}

		if ( isset( $_GET['wp_http_referer'] ) ) {
			wp_safe_redirect( $_GET['wp_http_referer'] );
		} else {
			wp_safe_redirect( add_query_arg( $arg, $arg_value, remove_query_arg( array( 'wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
		}
		exit;
	}

	public function render_edit_post_collection( $user_id ) {
		$user = $this->check_edit_post_collection();
		$args = array(
			'user'  => $user,
			'posts' => new WP_Query(
				array(
					'post_type'   => Friends::CPT,
					'post_status' => array( 'publish', 'private' ),
					'author'      => $user->ID,
				)
			),
		);

		?>
		<h1><?php echo esc_html( $user->user_login ); ?></h1>
		<?php

		if ( isset( $_GET['updated'] ) ) {
			?>
			<div id="message" class="updated notice is-dismissible"><p><?php esc_html_e( 'User was updated.', 'friends' ); ?></p></div>
			<?php
		} elseif ( isset( $_GET['error'] ) ) {
			?>
			<div id="message" class="updated error is-dismissible"><p><?php esc_html_e( 'An error occurred.', 'friends' ); ?></p></div>
			<?php
		}

		$this->template_loader()->get_template_part( 'admin/edit-post-collection', null, $args );
	}

	/**
	 * Process access for the Friends create User page
	 */
	private function check_create_post_collection() {
		if ( ! current_user_can( Friends::REQUIRED_ROLE ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to create this user.' ) );
		}

		$user = (object) array(
			'user_login'   => null,
			'display_name' => null,
		);
		if ( isset( $_POST['display_name'] ) ) {
			$user->display_name = sanitize_text_field( $_POST['display_name'] );
		}

		if ( isset( $_POST['user_login'] ) ) {
			$user->user_login = sanitize_user( $_POST['user_login'] );
			if ( ! $user->user_login && $user->display_name ) {
				$user->user_login = Friend_User::sanitize_username( $user->display_name );
			}
		}
		return $user;
	}

	/**
	 * Process the Friends Create Post Collection page
	 */
	public function process_create_post_collection() {
		$errors = new WP_Error;
		$user   = $this->check_create_post_collection();

		if ( ! $user->user_login ) {
			$errors->add( 'user_login', __( '<strong>Error</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.' ) );
		} elseif ( username_exists( $user->user_login ) ) {
			$errors->add( 'user_login', __( '<strong>Error</strong>: This username is already registered. Please choose another one.' ) );
		} elseif ( ! $user->display_name ) {
			$errors->add( 'user_login', __( '<strong>Error</strong>: Please enter a valid display name.' ) );
		}

		if ( ! $errors->has_errors() ) {
			$userdata  = array(
				'user_login'   => $user->user_login,
				'display_name' => $user->display_name,
				'user_pass'    => wp_generate_password( 256 ),
				'role'         => 'post_collection',
			);
			$user_id = wp_insert_user( $userdata );
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}
			wp_safe_redirect( self_admin_url( 'admin.php?page=edit-post-collection&user=' . $user_id ) );
			exit;
		}

		return $errors;
	}

	public function render_create_post_collection() {
		$response = null;
		$user     = $this->check_create_post_collection();

		if ( ! empty( $_POST ) ) {
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'create-post-collection' ) ) {
				$response = new WP_Error( 'invalid-nonce', __( 'For security reasons, please verify the URL and click next if you want to proceed.', 'friends' ) );
			} else {
				$response = $this->process_create_post_collection();
			}
		}

		?>
		<h1><?php esc_html_e( 'Create Post Collection', 'friends' ); ?></h1>
		<?php

		if ( is_wp_error( $response ) ) {
			?>
			<div id="message" class="updated notice is-dismissible"><p>
			<?php
			echo wp_kses(
				$response->get_error_message(),
				array(
					'strong' => array(),
					'a'      => array(
						'href'   => array(),
						'rel'    => array(),
						'target' => array(),
					),
				)
			);
			?>
				</p>
			</div>
			<?php
		}

		$args = array(
			'user_login'   => $user->user_login,
			'display_name' => $user->display_name,
		);

		$this->template_loader()->get_template_part( 'admin/create-post-collection', null, $args );
	}

	public function enqueue_scripts() {
		if ( ! class_exists( 'Friends' ) ) {
			return;
		}

		if ( is_user_logged_in() && Friends::on_frontend() ) {
			wp_enqueue_script( 'send-to-e-reader', plugins_url( 'friends-post-collection.js', __FILE__ ), array( 'friends' ), 1.0 );
		}
	}

	public function admin_menu() {
		// Only show the menu if installed standalone.
		$friends_settings_exist = '' !== menu_page_url( 'friends-settings', false );
		if ( $friends_settings_exist ) {
			$friend_requests = Friend_User_Query::all_friend_requests();
			$friend_request_count = $friend_requests->get_total();
			$unread_badge = $this->friends->admin->get_unread_badge( $friend_request_count );

			$menu_title = __( 'Friends', 'friends' ) . $unread_badge;
			$page_type = sanitize_title( $menu_title );

			add_submenu_page(
				'friends-settings',
				__( 'Post Collection', 'friends' ),
				__( 'Post Collection', 'friends' ),
				'administrator',
				'friends-post-collection',
				array( $this, 'about_page' )
			);
		} else {
			$menu_title = __( 'Friends Post Collection', 'friends' );
			$page_type = sanitize_title( $menu_title );

			add_menu_page( 'friends', __( 'Friends Post Collection', 'friends' ), 'administrator', 'friends-settings', null, 'dashicons-groups', 3.73 );
			add_submenu_page(
				'friends-settings',
				__( 'About', 'friends' ),
				__( 'About', 'friends' ),
				'administrator',
				'friends-settings',
				array( $this, 'about_page_with_friends_about' )
			);

		}

		if ( isset( $_GET['page'] ) && 0 === strpos( $_GET['page'], 'create-post-collection' ) ) {
			add_submenu_page( 'friends-settings', __( 'Create Post Collection', 'friends' ), __( 'Create Post Collection', 'friends' ), Friends::REQUIRED_ROLE, 'create-post-collection', array( $this, 'render_create_post_collection' ) );
			add_action( 'load-' . $page_type . '_page_create-post-collection', array( $this, 'process_create_post_collection' ) );
		}

		if ( isset( $_GET['page'] ) && 0 === strpos( $_GET['page'], 'edit-post-collection' ) ) {
			add_submenu_page( 'friends-settings', __( 'Edit Post Collection', 'friends' ), __( 'Edit Post Collection', 'friends' ), Friends::REQUIRED_ROLE, 'edit-post-collection' . ( 'edit-post-collection' !== $_GET['page'] && isset( $_GET['user'] ) ? '&user=' . $_GET['user'] : '' ), array( $this, 'render_edit_post_collection' ) );
			add_action( 'load-' . $page_type . '_page_edit-post-collection', array( $this, 'process_edit_post_collection' ) );
		}
	}

	/**
	 * Add actions to the user rows
	 *
	 * @param  array   $actions The existing actions.
	 * @param  WP_User $user    The user in question.
	 * @return array The extended actions.
	 */
	public function user_row_actions( array $actions, WP_User $user ) {
		if (
			! current_user_can( Friends::REQUIRED_ROLE ) ||
			(
				! $user->has_cap( 'post_collection' )
			)
		) {
			return $actions;
		}

		if ( is_multisite() ) {
			if ( is_super_admin( $user->ID ) ) {
				return $actions;
			}

			$actions = array_merge( array( 'edit' => '<a href="' . esc_url( self_admin_url( 'admin.php?page=edit-post-collection&user=' . $user->ID ) ) . '">' . __( 'Edit' ) . '</a>' ), $actions );
		}

		$friend_user = new Friend_User( $user );
		$actions['view'] = '<a href="' . esc_url( $friend_user->get_local_friends_page_url() ) . '">' . __( 'View' ) . '</a>';

		unset( $actions['resetpassword'] );

		return $actions;
	}

	/**
	 * Display an about page for the plugin.
	 *
	 * @param      bool $display_about_friends  The display about friends section.
	 */
	public function about_page( $display_about_friends = false ) {
		?>
		<div class="wrap">
			<h1><?php _e( 'Friends Post Collection', 'friends' ); ?></h1>

			<p><?php _e( 'The Friends Post Collection plugin allows you to save external posts to your WordPress, either for just collecting them for yourself as a searchable archive, or to syndicate those posts into new feeds.', 'friends' ); ?></p>

			<?php
			$this->template_loader()->get_template_part(
				'admin/settings-post-collection',
				null,
				array(
					'post_collections' => $this->get_post_collection_users()->get_results(),
					'bookmarklet_js'   => $this->get_bookmarklet_js(),
				)
			);

			if ( $display_about_friends ) :
				?>
				<p>
				<?php
				echo wp_kses(
						// translators: %s: URL to the Friends Plugin page on WordPress.org.
					sprintf( __( 'The Friends plugin is all about connecting with friends and news. Learn more on its <a href=%s>plugin page on WordPress.org</a>.', 'friends' ), '"https://wordpress.org/plugins/friends" target="_blank" rel="noopener noreferrer"' ),
					array(
						'a' => array(
							'href'   => array(),
							'rel'    => array(),
							'target' => array(),
						),
					)
				);
				?>
				</p>
			<?php endif; ?>
			<p>
				<?php
				echo wp_kses(
					// translators: %s: URL to the Embed library.
					sprintf( __( 'This plugin is uses information of the open source project <a href=%s>FTR Site Config</a>.', 'friends' ), '"https://github.com/fivefilters/ftr-site-config" target="_blank" rel="noopener noreferrer"' ),
					array(
						'a' => array(
							'href'   => array(),
							'rel'    => array(),
							'target' => array(),
						),
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Display an about page for the plugin with the friends section.
	 */
	public function about_page_with_friends_about() {
		return $this->about_page( true );
	}

	public function get_post_collection_users() {
		static $users;
		if ( ! self::$cache || ! isset( $users ) ) {
			$users = new WP_User_Query(
				array(
					'role'    => 'post_collection',
					'order'   => 'ASC',
					'orderby' => 'display_name',
				)
			);
		}
		return $users;
	}

	private function get_bookmarklet_js() {
		$js = file_get_contents( __DIR__ . '/friends-post-collection-injector.js' );
		$js = str_replace( 'text.sending_article_to_your_blog', '"' . addslashes( __( 'Sending the article to your blog...', 'friends' ) ) . '"', $js );
		$js = str_replace( 'text.do_you_want_to_send_the_article_to_your_blog', '"' . addslashes( __( 'Do you want to send the article on this page to your blog?', 'friends' ) ) . '"', $js );
		$js = str_replace( PHP_EOL, '', preg_replace( '/\s+/', ' ', $js ) );
		return $js;
	}

	/**
	 * Display the Bookmarklet at the Tools section of wp-admin
	 */
	public function toolbox_bookmarklet() {
		$post_collections = array();
		foreach ( $this->get_post_collection_users()->get_results() as $user ) {
			$url = home_url( '/?user=' . $user->ID );
			$post_collections[ $url ] = $user->display_name;
		}

		$this->template_loader()->get_template_part(
			'admin/tools-post-collection',
			null,
			array(
				'post_collections' => $post_collections,
				'bookmarklet_js'   => $this->get_bookmarklet_js(),
			)
		);
	}

	public function save_url_endpoint() {
		$delimiter = '===BODY===';
		$url = false;
		if ( isset( $_REQUEST['collect-post'] ) && isset( $_REQUEST['user'] ) ) {
			if ( ! intval( $_REQUEST['user'] ) ) {
				return;
			}
			list( $last_url, $last_body ) = explode( $delimiter, get_option( 'friends-post-collection_last_save', $delimiter ) );
			$url = $_REQUEST['collect-post'];
			$body = false;
			if ( isset( $_POST['body'] ) ) {
				$body = $_POST['body'];
			} elseif ( $last_url === $url ) {
				$body = $last_body;
			}
		}

		if ( ! $url ) {
			return;
		}

		update_option( 'friends-post-collection_last_save', $_REQUEST['collect-post'] . $delimiter . $_POST['body'] );

		if ( ! current_user_can( Friends::REQUIRED_ROLE ) ) {
			auth_redirect();
		}

		$friend_user = new Friend_User( intval( $_REQUEST['user'] ) );
		if ( ! is_wp_error( $friend_user ) || ! $friend_user->has_cap( 'post_collection' ) ) {
			$this->save_url( $url, $friend_user, $body );
		}
	}

	/**
	 * Download and save the URL content
	 *
	 * @param  string      $url The URL to save.
	 * @param  Friend_User $friend_user  The user.
	 * @return WP_Error    Potentially an error message.
	 */
	public function save_url( $url, Friend_User $friend_user, $content = null ) {
		if ( ! is_string( $url ) || ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'invalid-url', __( 'You entered an invalid URL.', 'thinkery' ) );
		}

		$post_id = Friends::get_instance()->feed->url_to_postid( $url, $friend_user->ID );
		if ( is_null( $post_id ) ) {
			$item = $this->download( $url, $content );
			if ( is_wp_error( $item ) ) {
				return $item;
			}

			if ( ! $item->content && ! $item->title ) {
				return new WP_Error( 'invalid-content', __( 'No content was extracted.', 'thinkery' ) );
			}

			$title   = strip_tags( trim( $item->title ) );
			$content = trim( wp_kses_post( $item->content ) );

			$post_data = array(
				'post_title'    => $title,
				'post_content'  => $content,
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s' ),
				'post_status'   => 'private',
				'post_author'   => $friend_user->ID,
				'guid'          => $item->url,
				'post_type'     => Friends::CPT,
			);

			$post_id = wp_insert_post( $post_data, true );

			if ( $item->author ) {
				update_post_meta( $post_id, 'author', $item->author );
			}
		}
		wp_untrash_post( $post_id );
		wp_safe_redirect( $friend_user->get_local_friends_page_url( $post_id ) );
		exit;
	}

	/**
	 * Download site config for a URL if it exists
	 *
	 * @param  string $filename The filename to download.
	 * @return string|false The site config.
	 */
	public function download_site_config( $filename ) {
		$response = wp_safe_remote_get(
			'https://raw.githubusercontent.com/fivefilters/ftr-site-config/master/' . $filename,
			array(
				'timeout'     => 20,
				'redirection' => 5,
			)
		);

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Get the parsed site config for a URL
	 *
	 * @param  string $url The URL for which to retrieve the site config.
	 * @return array|false The site config.
	 */
	public function get_site_config( $url ) {
		foreach ( $this->get_site_config_filenames( $url ) as $filename ) {
			$text = $this->download_site_config( $filename );
			if ( ! $text ) {
				continue;
			}
			return $this->parse_site_config( $text );
		}
		return false;
	}

	/**
	 * Prase the site config
	 *
	 * @param  string $text The site config text.
	 * @return array The parsed site config.
	 */
	public function parse_site_config( $text ) {
		$site_config = array();
		$search      = false;
		foreach ( explode( PHP_EOL, $text ) as $line ) {
			if ( false === strpos( $line, ':' ) || '#' === substr( ltrim( $line ), 0, 1 ) ) {
				continue;
			}

			list( $key, $value ) = explode( ':', $line, 2 );
			$key                 = strtolower( trim( $key ) );
			$value               = trim( $value );

			if ( 'find_string' === $key ) {
				$search = $value;
				continue;
			}

			if ( in_array( $key, array( 'title', 'date', 'body', 'author' ), true ) ) {
				$site_config[ $key ] = $value;
				continue;
			}

			if ( 'replace_string' === $key ) {
				if ( false === $search ) {
					continue;
				}

				if ( ! isset( $site_config['replace'] ) ) {
					$site_config['replace'] = array();
				}

				$site_config['replace'][ $search ] = $value;
				$search                            = false;
				continue;

			}

			if ( 'http_header(' === substr( $key, 0, 12 ) ) {
				if ( ! isset( $site_config['http_header'] ) ) {
					$site_config['http_header'] = array();
				}

				$site_config['http_header'][ substr( $key, 12, -1 ) ] = $value;
				continue;
			}

			if ( in_array( $key, array( 'strip', 'strip_id_or_class' ), true ) ) {
				if ( ! isset( $site_config[ $key ] ) ) {
					$site_config[ $key ] = array();
				}

				$site_config[ $key ][] = $value;
				continue;
			}
		}

		return $site_config;
	}

	/**
	 * Get possible site config filenames
	 *
	 * @param  string $url The URL for which to get possible site config filenames.
	 * @return array An array of potential filenames.
	 */
	public function get_site_config_filenames( $url ) {
		$host = parse_url( $url, PHP_URL_HOST );
		if ( 'www.' === substr( $host, 0, 4 ) ) {
			$host = substr( $host, 4 );
		}

		$filenames = array( $host . '.txt' );
		if ( substr_count( $host, '.' ) > 1 ) {
			$filenames[] = substr( $host, strpos( $host, '.' ) ) . '.txt';
		}

		return $filenames;
	}

	/**
	 * Download the url from the URL
	 *
	 * @param  string $url The URL to download.
	 * @return object An item object.
	 */
	public function download( $url, $content = null ) {
		global $wp_version;
		$args = array(
			'timeout'     => 20,
			'redirection' => 5,
			'headers'     => array(
				'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ) . '; Friends/' . Friends::VERSION,
			),
		);

		$site_config = $this->get_site_config( $url );
		if ( isset( $site_config['http_header'] ) ) {
			$args['headers'] = array_merge( $args['headers'], $site_config['http_header'] );
		}

		if ( ! $content ) {
			$response = wp_safe_remote_get( $url, $args );
			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return new WP_Error( 'could-not-download', __( 'Could not download the URL.', 'thinkery' ) );
			}
			$content = wp_remote_retrieve_body( $response );
		}

		$item      = $this->extract_content( $content, $url, $site_config );
		$item->url = $url;
		return $item;

	}

	/**
	 * Extract the content of a URL
	 *
	 * @param  string $html        The HTML from which to extract the content.
	 * @param  array  $site_config The site config.
	 * @return object The parsed content.
	 */
	public function extract_content( $html, $url, $site_config = array() ) {
		if ( ! $site_config ) {
			$site_config = array();
		}

		if ( isset( $site_config['replace'] ) ) {
			foreach ( $site_config['replace'] as $search => $replace ) {
				$html = str_replace( $search, $replace, $html );
			}
		}

		$item = (object) array(
			'title'   => false,
			'content' => false,
		);

		if ( ! class_exists( 'Readability', false ) ) {
			require_once __DIR__ . '/lib/PressForward-Readability/Readability.php';
		}

		if ( ! class_exists( 'HTML5_Parser', false ) ) {
			require_once __DIR__ . '/lib/HTML5/Parser.php';
		}

		set_error_handler( '__return_null' );
		$readability = new Readability( '<' . '?xml encoding="utf-8" ?' . '>' . stripslashes( $html ), $url, 'html5lib' );
		restore_error_handler();
		$dom = new DOMDocument( '<' . '?xml encoding="utf-8" ?' . '>' . ( $html ) );
		$xpath = new DOMXpath( $readability->dom );
		$xpath = new DOMXpath( $readability->dom );

		if ( isset( $site_config['strip_id_or_class'] ) ) {
			foreach ( $site_config['strip_id_or_class'] as $id_or_class ) {
				$strip = $xpath->query( '//*[contains(@class, "' . esc_attr( $id_or_class ) . '")]|//*[@id="' . esc_attr( $id_or_class ) . '"]' );
				$this->remove_node( $strip );
			}
		}

		if ( isset( $site_config['strip'] ) ) {
			foreach ( $site_config['strip'] as $xp ) {
				$this->remove_node( $xpath->query( $xp ) );
			}
		}
		if ( isset( $site_config['title'] ) ) {
			$item->title = $xpath->query( $site_config['title'] );
			if ( $item->title ) {
				$item->title = $this->get_inner_html( $item->title );
			}
		}

		if ( isset( $site_config['author'] ) ) {
			$item->author = $xpath->query( str_replace( 'h2', 'h1', $site_config['author'] ) );
			if ( $item->author ) {
				$item->author = $this->get_inner_html( $item->author );
			}
		}

		if ( isset( $site_config['body'] ) ) {
			$item->content = $xpath->query( $site_config['body'] );
			if ( $item->content ) {
				$item->content = $this->get_inner_html( $item->content );
			}
		}

		if ( ! $item->author ) {
			$item->author = $xpath->query( "//article//*[contains(concat(' ',normalize-space(@class),' '),' entry-author ')]" );
			if ( $item->author ) {
				$item->author = $item->author[0]->textContent;
			}
		}

		if ( ! $item->title || ! $item->content ) {
			$copied_dom = clone $readability->dom;
			$result     = $readability->init();
			if ( $result ) {
				if ( ! $item->title ) {
					$item->title = $readability->getTitle()->textContent;
				}
				if ( ! $item->content ) {
					$item->content = $readability->getContent()->innerHTML;
				}
			} else {
				$xpath = new DOMXpath( $copied_dom );

				if ( ! $item->title ) {
					$item->title = $xpath->query( '(//h1)[1]' );
					if ( $item->title ) {
						$item->title = $this->get_inner_html( $item->title );
					} else {
						$item->title = $xpath->query( '//title' );
						if ( $item->title ) {
							$item->title = $this->get_inner_html( $item->title );
						}
					}
				}
				if ( ! $item->content ) {
					$urls      = array( 'url', 'blog', 'body', 'content', 'entry', 'hentry', 'main', 'page', 'post', 'text', 'story' );
					$item->content = $xpath->query( '(//*[contains(@class, "' . implode( '")]|//*[contains(@class, "', $urls ) . '")]|*[contains(@id, "' . implode( '")]|//*[contains(@id, "', $urls ) . '")])[1]' );
					if ( $item->content ) {
						$item->content = $this->get_inner_html( $item->content );
					} else {
						$item->title = $xpath->query( '//body' );
						if ( $item->title ) {
							$item->title = $this->get_inner_html( $item->title );
						}
					}
				}
			}
		}

		if ( ! $item->title ) {
			$item->title = $xpath->query( '//meta[@property="og:title"]/@content' );
			if ( $item->title ) {
				$item->title = $this->get_inner_html( $item->title );
			}
		}

		return $item;
	}

	/**
	 * Extract the innerHTML of a node
	 *
	 * @param  object $node The DOM node or a DOMNodeList.
	 * @return string The innerHTML.
	 */
	private function get_inner_html( $node ) {
		$html = '';
		if ( $node instanceof DOMNodeList ) {
			$nodelist = $node;
		} elseif ( isset( $node->childNodes ) ) { // @codingStandardsIgnoreLine
			$nodelist = $node->childNodes; // @codingStandardsIgnoreLine
		} else {
			return false;
		}

		foreach ( $nodelist as $child ) {
			$html .= $child->innerHTML; // @codingStandardsIgnoreLine
		}
		return $html;
		return $this->clean_html( $html );
	}

	/**
	 * Remove the node from the DOM.
	 *
	 * @param  object $node The DOM node or a DOMNodeList to remove.
	 */
	private function remove_node( $node ) {
		if ( $node instanceof DOMNodeList ) {
			$nodelist = $node;
		} elseif ( isset( $node->childNodes ) ) { // @codingStandardsIgnoreLine
			$nodelist = $node->childNodes; // @codingStandardsIgnoreLine
		} else {
			return false;
		}

		foreach ( $nodelist as $child ) {
			$child->parentNode->removeChild( $child ); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Clean the HTML
	 *
	 * @param  string $html The HTML to be cleaned.
	 * @return string       The cleaned HTML.
	 */
	private function clean_html( $html ) {
		$html = preg_replace( '#\n\s*\n\s*#', PHP_EOL . PHP_EOL, trim( $html ) );

		return $html;
	}

	public function friend_user_role_name( $name, $user ) {
		if ( ! $name && $user->has_cap( 'post_collection' ) ) {
			$name = _x( 'Post Collection', 'User role', 'friends' );
		}

		return $name;
	}

	/**
	 * Expose the feed for the specific user.
	 *
	 * @param      bool   $viewable      Whether it's viewable.
	 * @param      string $author_login  The author login.
	 *
	 * @return     bool    Whether it's viewable.
	 */
	function friends_friend_feed_viewable( $viewable, $author_login ) {
		$author = get_user_by( 'login', $author_login );
		if ( get_user_option( 'friends_publish_post_collection', $author->ID ) && $author->has_cap( 'post_collection' ) ) {
			add_filter( 'pre_option_rss_use_excerpt', '__return_true', 30 );
			return true;
		}
		return $viewable;
	}

	/**
	 * Amend the Friends List widget
	 *
	 * @param object $widget  The widget
	 * @param array  $args Sidebar arguments.
	 */
	public function friends_widget_friend_list_after( $widget, $args ) {
		$post_collections = $this->get_post_collection_users();
		if ( 0 !== $post_collections->get_total() ) {
			?>
			<details class="accordion" open>
				<summary class="accordion-header">
					<?php
					echo $args['before_title'];
					echo esc_html( _ex( 'Post Collections', 'widget-header', 'friends' ) );
					echo $args['after_title'];
					?>
				</summary>
				<ul class="subscriptions-list menu menu-nav accordion-body">
					<?php
					$widget->get_list_items( $post_collections->get_results() );
					?>
				</ul>
			</details>
			<?php
		}
	}

	/**
	 * Amend the Friends author header.
	 *
	 * @param      WP_User $user   The user
	 */
	public function friends_author_header( $user ) {
		if ( $user->has_cap( 'post_collection' ) ) {
			?>
			<a class="chip" href="<?php echo esc_attr( get_edit_user_link( $user->ID ) ); ?>"><?php esc_html_e( 'Edit' ); ?></a>
			<?php
		}
	}

	function wp_ajax_mark_private() {
		if ( ! current_user_can( Friends::REQUIRED_ROLE ) ) {
			wp_send_json_error( 'error' );
		}

		$post = get_post( $_POST['id'] );
		$post->post_status = 'private';
		wp_update_post( $post );

		wp_send_json_success(
			array(
				'new_text' => __( 'Make post public', 'friends' ),
			)
		);
	}

	function wp_ajax_mark_publish() {
		if ( ! current_user_can( Friends::REQUIRED_ROLE ) ) {
			wp_send_json_error( 'error' );
		}

		$post = get_post( $_POST['id'] );
		$post->post_status = 'publish';
		wp_update_post( $post );

		wp_send_json_success(
			array(
				'new_text' => __( 'Make post private', 'friends' ),
			)
		);
	}

	function wp_ajax_change_author() {
		if ( ! current_user_can( Friends::REQUIRED_ROLE ) ) {
			wp_send_json_error( 'error' );
		}

		$user = new WP_User( $_POST['author'] );
		if ( is_wp_error( $user ) ) {
			wp_send_json_error( 'error' );
		}
		if ( ! Friend_User::is_friends_plugin_user( $user ) && ! $user->has_cap( 'post_collection' ) ) {
			wp_send_json_error( 'error' );
		}

		$post = get_post( $_POST['id'] );
		$old_author = $post->post_author;
		$post->post_author = $user->ID;
		wp_update_post( $post );

		$first = new WP_User( $_POST['first'] );
		$move_to = sprintf(
		// translators: %s is the name of a post collection.
			_x( 'Move to %s', 'post-collection', 'friends' ),
			$first->display_name
		);

		wp_send_json_success(
			array(
				'new_text'   => intval( $old_author ) !== $first->ID ? __( 'Undo' ) : $move_to,
				'old_author' => $old_author,
			)
		);
	}

	/**
	 * Actions to take upon plugin activation.
	 */
	public static function activate_plugin() {
		$post_collection = get_role( 'post_collection' );
		if ( ! $post_collection ) {
			$post_collection = add_role( 'post_collection', 'Post Collection' );
		}
		$post_collection->add_cap( 'post_collection' );
		$post_collection->add_cap( 'level_0' );
		$default_user_id = get_option( 'friends-post-collection_default_user' );
		$default_user = false;
		if ( $default_user_id ) {
			$default_user = new WP_User( $default_user_id );
			if ( ! $default_user->exists() ) {
				$default_user = false;
			}
		}
		if ( ! $default_user ) {
			$userdata  = array(
				'user_login'   => 'saved-posts',
				'display_name' => 'Saved Posts',
				'user_pass'    => wp_generate_password( 256 ),
				'role'         => 'post_collection',
			);
			$user_id = wp_insert_user( $userdata );
			update_option( 'friends-post-collection_default_user', $user_id );
		}
	}
}
