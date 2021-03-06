<?php
/**
 * Friends Post Collector
 *
 * This contains the post collector functions.
 *
 * @package Friends_Post_Collector
 */

/**
 * This is the class for the downloading and storing posts for the Friends Plugin.
 *
 * @since 0.1
 *
 * @package Friends_Post_Collector
 * @author Alex Kirk
 */
class Friends_Post_Collector {
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
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 50 );
		add_action( 'wp_loaded', array( $this, 'save_url_endpoint' ), 100 );
		add_filter( 'get_edit_user_link', array( $this, 'edit_post_collection_link' ), 10, 2 );
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'friend_post_edit_link', array( $this, 'allow_post_editing' ), 10, 2 );
		add_action( 'friends_entry_dropdown_menu', array( $this, 'add_edit_post_collection' ) );
	}

	/**
	 * Get the Friends_Template_Loader singleton
	 *
	 * @return Friends_Template_Loader A class instance.
	 */
	public static function template_loader() {
		static $template_loader;
		if ( ! isset( $template_loader ) ) {
			require_once __DIR__ . '/class.friends-post-collector-template-loader.php';
			$template_loader = new Friends_Post_Collector_Template_Loader();
		}
		return $template_loader;
	}

	public function allow_post_editing( $link, $original ) {
		if ( $this->is_post_collection_user( get_the_author_meta( 'ID' ) ) ) {
			return $original;
		}
		return $link;
	}

	public function add_edit_post_collection() {
		$user_id = get_the_author_meta( 'ID' );
		if ( $this->is_post_collection_user( $user_id ) ) {
			?>
			<li class="menu-item"><a href="<?php echo esc_url( get_edit_user_link( $user_id ) ); ?>"><?php _e( 'Edit Post Collection', 'friends-post-collector' ); ?></a></li>
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
			wp_die( esc_html__( 'This is not a user related to this plugin.', 'friends-post-collector' ) );
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
			do_action( 'friends_edit_post_collection_after_form_submit', $user );
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

	public function enqueue_scripts() {
		if ( ! class_exists( 'Friends' ) ) {
			return;
		}

		if ( is_user_logged_in() && Friends::on_frontend() ) {
			wp_enqueue_script( 'send-to-e-reader', plugins_url( 'friends-post-collector.js', __FILE__ ), array( 'friends' ), 1.0 );
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
				__( 'Post Collector', 'friends-post-collector' ),
				__( 'Post Collector', 'friends-post-collector' ),
				'administrator',
				'friends-post-collector',
				array( $this, 'about_page' )
			);
		} else {
			$menu_title = __( 'Friends Post Collector', 'friends-post-collector' );
			$page_type = sanitize_title( $menu_title );

			add_menu_page( 'friends', __( 'Friends Post Collector', 'friends-post-collector' ), 'administrator', 'friends-settings', null, 'dashicons-groups', 3.73 );
			add_submenu_page(
				'friends-settings',
				__( 'About', 'friends-post-collector' ),
				__( 'About', 'friends-post-collector' ),
				'administrator',
				'friends-settings',
				array( $this, 'about_page_with_friends_about' )
			);

		}

		if ( isset( $_GET['page'] ) && 0 === strpos( $_GET['page'], 'edit-post-collection' ) ) {
			add_submenu_page( 'friends-settings', __( 'Edit User', 'friends' ), __( 'Edit User', 'friends' ), Friends::REQUIRED_ROLE, 'edit-post-collection' . ( 'edit-post-collection' !== $_GET['page'] && isset( $_GET['user'] ) ? '&user=' . $_GET['user'] : '' ), array( $this, 'render_edit_post_collection' ) );
			add_action( 'load-' . $page_type . '_page_edit-post-collection', array( $this, 'process_edit_post_collection' ) );
		}
	}

	/**
	 * Enable pre-filling the role in the new user form.
	 */
	function user_new_form_tag() {
		if ( isset( $_GET['role'] ) && 'post_collection' === $_GET['role'] ) {
			add_filter(
				'pre_option_default_role',
				function() {
					return 'post_collection';
				}
			);
		}
	}

	/**
	 * Display an about page for the plugin.
	 *
	 * @param      bool $display_about_friends  The display about friends section.
	 */
	public function about_page( $display_about_friends = false ) {
		$nonce_value = 'post-collector';
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $nonce_value ) ) {
			update_option( 'friends-post-collector_default_user', $_POST['user_id'] );
		}
		$default_user = get_option( 'friends-post-collector_default_user' );
		?>
	<h1><?php _e( 'Friends Post Collector', 'friends-post-collector' ); ?></h1>

	<p><?php _e( 'The Friends Post Collector plugin allows you to save external posts to your WordPress, either for just collecting them for yourself as a searchable archive, or to syndicate those posts into new feeds.' ); ?></p>

	<form method="post">
		<?php wp_nonce_field( $nonce_value ); ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Default User', 'friends-post-collector' ); ?></th>
					<td>
						<select name="user_id">
							<?php foreach ( $this->get_post_collection_users()->get_results() as $potential_default_user ) : ?>
							<option value="<?php echo esc_attr( $potential_default_user->ID ); ?>" <?php selected( $default_user, $potential_default_user->ID ); ?>><?php echo esc_html( $potential_default_user->display_name ); ?></option>

						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php
						echo wp_kses(
							sprintf(
									// translators: %s is a role name.
								__( 'Please select a user under which external posts should be saved by default. Only users with the <em>%s</em> role are shown.', 'friends-post-collector' ),
								_x( 'Post Collection', 'User role', 'friends-post-collector' )
							),
							array( 'em' => array() )
						);
						echo ' ';
						?>
						<a href="<?php echo esc_url( self_admin_url( 'user-new.php?role=post_collection' ) ); ?>"><?php _e( 'Create another user' ); ?></a></p>

					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<input type="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'friends-post-collector' ); ?>">
		</p>
	</form>

		<?php if ( $display_about_friends ) : ?>
		<p>
			<?php
			echo wp_kses(
					// translators: %s: URL to the Friends Plugin page on WordPress.org.
				sprintf( __( 'The Friends plugin is all about connecting with friends and news. Learn more on its <a href=%s>plugin page on WordPress.org</a>.', 'friends-post-collector' ), '"https://wordpress.org/plugins/friends" target="_blank" rel="noopener noreferrer"' ),
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
			sprintf( __( 'This plugin is uses information of the open source project <a href=%s>FTR Site Config</a>.', 'friends-post-collector' ), '"https://github.com/fivefilters/ftr-site-config" target="_blank" rel="noopener noreferrer"' ),
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

	/**
	 * Display the Bookmarklet at the Tools section of wp-admin
	 */
	public function toolbox_bookmarklet() {
		?>
		<div class="card">
			<h2 class="title"><?php _e( 'Friends Post Collector', 'friends-post-collector' ); ?></h2>
			<h3><?php _e( 'Bookmarklets', 'friends-post-collector' ); ?></h3>

			<p><?php _e( "Drag one of these bookmarklets to your bookmarks bar and click it when you're on an article you want to save from the web.", 'friends-post-collector' ); ?></p>
			<p>
				<a href="javascript:<?php echo rawurlencode( trim( str_replace( "document.getElementById( 'friends-post-collector-script' ).getAttribute( 'data-post-url' )", "'" . esc_url( home_url( '/' ) ) . "'", str_replace( PHP_EOL, '', preg_replace( '/\s+/', ' ', file_get_contents( __DIR__ . '/friends-post-collector-injector.js' ) ) ) ), ';' ) ); ?>" style="display: inline-block; padding: .5em; border: 1px solid #999; border-radius: 4px; background-color: #ddd;text-decoration: none; margin-right: 3em"><?php echo esc_html_e( 'Collect Post', 'friends-post-collector' ); ?></a>
			</p>
			<h3><?php _e( 'Browser Extension', 'friends-post-collector' ); ?></h3>

			<p><?php _e( 'The Friends browser extension also allows to save the currently viewed article.', 'friends-post-collector' ); ?></p>
			<p>
				<a href="https://addons.mozilla.org/en-US/firefox/addon/wpfriends/"><?php echo esc_html_e( 'Firefox Extension', 'friends-post-collector' ); ?></a>
			</p>
		</div>
		<?php
	}

	public function save_url_endpoint() {
		$delimiter = '===BODY===';
		$url = false;
		if ( isset( $_GET['friends-save-url'] ) ) {
			list( $last_url, $last_body ) = explode( $delimiter, get_option( 'friends-post-collector_last_save', $delimiter ) );
			$url = $_GET['friends-save-url'];
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

		update_option( 'friends-post-collector_last_save', $_POST['url'] . $delimiter . $_POST['body'] );

		if ( ! current_user_can( Friends::REQUIRED_ROLE ) ) {
			auth_redirect();
		}

		$user_id = get_option( 'friends-post-collector_default_user' );
		$this->save_url( $url, $user_id, $body );
	}

	/**
	 * Download and save the URL content
	 *
	 * @param  string $url The URL to save.
	 * @param      int    $user_id  The user identifier.
	 * @return WP_Error    Potentially an error message.
	 */
	public function save_url( $url, $user_id, $content = null ) {
		if ( ! is_string( $url ) || ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'invalid-url', __( 'You entered an invalid URL.', 'thinkery' ) );
		}

		$post_id = Friends::get_instance()->feed->url_to_postid( $url, $user_id );
		if ( is_null( $post_id ) ) {
			$item = $this->download( $url, $content );
			if ( is_wp_error( $item ) ) {
				return $item;
			}

			if ( ! $item->content && ! $item->title ) {
				return new WP_Error( 'invalid-content', __( 'No content was extracted.', 'thinkery' ) );
			}

			$domain = parse_url( $url, PHP_URL_HOST );

			$title   = strip_tags( trim( $item->title ) );
			$content = trim( wp_kses_post( $item->content ) );

			$post_data = array(
				'post_title'    => $title,
				'post_content'  => $content,
				'post_date_gmt' => date( 'Y-m-d H:i:s' ),
				'post_status'   => 'private',
				'post_author'   => $user_id,
				'guid'          => $item->url,
				'post_type'     => Friends::CPT,
			);

			$post_id = wp_insert_post( $post_data, true );
		}
		wp_untrash_post( $post_id );
		$friend_user = new Friend_User( $user_id );
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

			if ( in_array( $key, array( 'title', 'date', 'body', 'author' ) ) ) {
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

			if ( in_array( $key, array( 'strip', 'strip_id_or_class' ) ) ) {
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

		$item      = $this->extract_content( $content, $site_config );
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
	public function extract_content( $html, $site_config = array() ) {
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

		set_error_handler( '__return_null' );
		$readability = new Readability( '<?xml encoding="utf-8" ?>' . $html );
		restore_error_handler();
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
			$item->author = $xpath->query( str_replace('h2','h1',$site_config['author'] ));
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

	/**
	 * Actions to take upon plugin activation.
	 */
	public static function activate_plugin() {
		$post_collection = get_role( 'post_collection' );
		if ( ! $post_collection ) {
			_x( 'Post Collection', 'User role', 'friends-post-collector' );
			$post_collection = add_role( 'post_collection', 'Post Collection' );
		}
		$post_collection->add_cap( 'post_collection' );
		$post_collection->add_cap( 'level_0' );
		$default_user_id = get_option( 'friends-post-collector_default_user' );
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
			update_option( 'friends-post-collector_default_user', $user_id );
		}
	}
}
