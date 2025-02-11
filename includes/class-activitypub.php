<?php
/**
 * ActivityPub Class.
 *
 * @package Activitypub
 */

namespace Activitypub;

use Exception;
use Activitypub\Transformer\Factory;
use Activitypub\Collection\Outbox;
use Activitypub\Collection\Followers;
use Activitypub\Collection\Extra_Fields;

/**
 * ActivityPub Class.
 *
 * @author Matthias Pfefferle
 */
class Activitypub {
	/**
	 * Initialize the class, registering WordPress hooks.
	 */
	public static function init() {
		\add_filter( 'template_include', array( self::class, 'render_activitypub_template' ), 99 );
		\add_action( 'template_redirect', array( self::class, 'template_redirect' ) );
		\add_filter( 'redirect_canonical', array( self::class, 'redirect_canonical' ), 10, 2 );
		\add_filter( 'query_vars', array( self::class, 'add_query_vars' ) );
		\add_filter( 'pre_get_avatar_data', array( self::class, 'pre_get_avatar_data' ), 11, 2 );

		// Add support for ActivityPub to custom post types.
		$post_types = \get_option( 'activitypub_support_post_types', array( 'post' ) );

		foreach ( $post_types as $post_type ) {
			\add_post_type_support( $post_type, 'activitypub' );
		}

		\add_action( 'wp_trash_post', array( self::class, 'trash_post' ), 1 );
		\add_action( 'untrash_post', array( self::class, 'untrash_post' ), 1 );

		\add_action( 'init', array( self::class, 'add_rewrite_rules' ), 11 );
		\add_action( 'init', array( self::class, 'theme_compat' ), 11 );

		\add_action( 'user_register', array( self::class, 'user_register' ) );

		\add_action( 'in_plugin_update_message-' . ACTIVITYPUB_PLUGIN_BASENAME, array( self::class, 'plugin_update_message' ) );

		if ( site_supports_blocks() ) {
			\add_action( 'tool_box', array( self::class, 'tool_box' ) );
		}

		\add_filter( 'activitypub_get_actor_extra_fields', array( Extra_Fields::class, 'default_actor_extra_fields' ), 10, 2 );

		\add_action( 'updated_postmeta', array( self::class, 'updated_postmeta' ), 10, 4 );
		\add_action( 'added_post_meta', array( self::class, 'updated_postmeta' ), 10, 4 );

		// Register several post_types.
		self::register_post_types();
	}

	/**
	 * Activation Hook.
	 */
	public static function activate() {
		self::flush_rewrite_rules();
		Scheduler::register_schedules();

		\add_filter( 'pre_wp_update_comment_count_now', array( Comment::class, 'pre_wp_update_comment_count_now' ), 10, 3 );
		Migration::update_comment_counts();
	}

	/**
	 * Deactivation Hook.
	 */
	public static function deactivate() {
		self::flush_rewrite_rules();
		Scheduler::deregister_schedules();

		\remove_filter( 'pre_wp_update_comment_count_now', array( Comment::class, 'pre_wp_update_comment_count_now' ) );
		Migration::update_comment_counts( 2000 );
	}

	/**
	 * Uninstall Hook.
	 */
	public static function uninstall() {
		Scheduler::deregister_schedules();

		\remove_filter( 'pre_wp_update_comment_count_now', array( Comment::class, 'pre_wp_update_comment_count_now' ) );
		Migration::update_comment_counts( 2000 );

		delete_option( 'activitypub_actor_mode' );
		delete_option( 'activitypub_attribution_domains' );
		delete_option( 'activitypub_authorized_fetch' );
		delete_option( 'activitypub_application_user_private_key' );
		delete_option( 'activitypub_application_user_public_key' );
		delete_option( 'activitypub_blog_user_private_key' );
		delete_option( 'activitypub_blog_user_public_key' );
		delete_option( 'activitypub_blog_description' );
		delete_option( 'activitypub_blog_identifier' );
		delete_option( 'activitypub_custom_post_content' );
		delete_option( 'activitypub_db_version' );
		delete_option( 'activitypub_default_extra_fields' );
		delete_option( 'activitypub_enable_blog_user' );
		delete_option( 'activitypub_enable_users' );
		delete_option( 'activitypub_header_image' );
		delete_option( 'activitypub_last_post_with_permalink_as_id' );
		delete_option( 'activitypub_mailer_new_follower' );
		delete_option( 'activitypub_mailer_new_dm' );
		delete_option( 'activitypub_max_image_attachments' );
		delete_option( 'activitypub_migration_lock' );
		delete_option( 'activitypub_object_type' );
		delete_option( 'activitypub_support_post_types' );
		delete_option( 'activitypub_use_hashtags' );
		delete_option( 'activitypub_use_opengraph' );
		delete_option( 'activitypub_use_permalink_as_id_for_blog' );
	}

	/**
	 * Return a AS2 JSON version of an author, post or page.
	 *
	 * @param  string $template The path to the template object.
	 *
	 * @return string The new path to the JSON template.
	 */
	public static function render_activitypub_template( $template ) {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $template;
		}

		self::add_headers();

		if ( ! is_activitypub_request() ) {
			return $template;
		}

		$activitypub_template = false;
		$activitypub_object   = Query::get_instance()->get_activitypub_object();

		if ( $activitypub_object ) {
			if ( \get_query_var( 'preview' ) ) {
				\define( 'ACTIVITYPUB_PREVIEW', true );

				/**
				 * Filter the template used for the ActivityPub preview.
				 *
				 * @param string $activitypub_template Absolute path to the template file.
				 */
				$activitypub_template = apply_filters( 'activitypub_preview_template', ACTIVITYPUB_PLUGIN_DIR . '/templates/post-preview.php' );
			} else {
				$activitypub_template = ACTIVITYPUB_PLUGIN_DIR . 'templates/activitypub-json.php';
			}
		}

		/*
		 * Check if the request is authorized.
		 *
		 * @see https://www.w3.org/wiki/SocialCG/ActivityPub/Primer/Authentication_Authorization#Authorized_fetch
		 * @see https://swicg.github.io/activitypub-http-signature/#authorized-fetch
		 */
		if ( $activitypub_template && use_authorized_fetch() ) {
			$verification = Signature::verify_http_signature( $_SERVER );
			if ( \is_wp_error( $verification ) ) {
				header( 'HTTP/1.1 401 Unauthorized' );

				// Fallback as template_loader can't return http headers.
				return $template;
			}
		}

		if ( $activitypub_template ) {
			\set_query_var( 'is_404', false );

			// Check if header already sent.
			if ( ! \headers_sent() ) {
				// Send 200 status header.
				\status_header( 200 );

				if ( ACTIVITYPUB_SEND_VARY_HEADER ) {
					// Send Vary header for Accept header.
					\header( 'Vary: Accept' );
				}
			}

			return $activitypub_template;
		}

		return $template;
	}

	/**
	 * Add the 'self' link to the header.
	 */
	public static function add_headers() {
		$id = Query::get_instance()->get_activitypub_object_id();

		if ( ! $id ) {
			return;
		}

		if ( ! headers_sent() ) {
			header( 'Link: <' . esc_url( $id ) . '>; title="ActivityPub (JSON)"; rel="alternate"; type="application/activity+json"', false );
		}

		add_action(
			'wp_head',
			function () use ( $id ) {
				echo PHP_EOL . '<link rel="alternate" title="ActivityPub (JSON)" type="application/activity+json" href="' . esc_url( $id ) . '" />' . PHP_EOL;
			}
		);
	}

	/**
	 * Add support for `p` and `author` query vars.
	 *
	 * @param string $redirect_url  The URL to redirect to.
	 * @param string $requested_url The requested URL.
	 *
	 * @return string $redirect_url
	 */
	public static function redirect_canonical( $redirect_url, $requested_url ) {
		if ( ! is_activitypub_request() ) {
			return $redirect_url;
		}

		$query = \wp_parse_url( $requested_url, PHP_URL_QUERY );

		if ( ! $query ) {
			return $redirect_url;
		}

		$query_params = \wp_parse_args( $query );
		unset( $query_params['activitypub'] );

		if ( 1 !== count( $query_params ) ) {
			return $redirect_url;
		}

		if ( isset( $query_params['p'] ) ) {
			return null;
		}

		if ( isset( $query_params['author'] ) ) {
			return null;
		}

		return $requested_url;
	}

	/**
	 * Custom redirects for ActivityPub requests.
	 *
	 * @return void
	 */
	public static function template_redirect() {
		$comment_id = get_query_var( 'c', null );

		// Check if it seems to be a comment.
		if ( ! $comment_id ) {
			return;
		}

		$comment = get_comment( $comment_id );

		// Load a 404 page if `c` is set but not valid.
		if ( ! $comment ) {
			global $wp_query;
			$wp_query->set_404();
			return;
		}

		// Stop if it's not an ActivityPub comment.
		if ( is_activitypub_request() && ! is_local_comment( $comment ) ) {
			return;
		}

		wp_safe_redirect( get_comment_link( $comment ) );
		exit;
	}

	/**
	 * Add the 'activitypub' query variable so WordPress won't mangle it.
	 *
	 * @param array $vars The query variables.
	 *
	 * @return array The query variables.
	 */
	public static function add_query_vars( $vars ) {
		$vars[] = 'activitypub';
		$vars[] = 'preview';
		$vars[] = 'author';
		$vars[] = 'c';
		$vars[] = 'p';

		return $vars;
	}

	/**
	 * Replaces the default avatar.
	 *
	 * @param array             $args        Arguments passed to get_avatar_data(), after processing.
	 * @param int|string|object $id_or_email A user ID, email address, or comment object.
	 *
	 * @return array $args
	 */
	public static function pre_get_avatar_data( $args, $id_or_email ) {
		if (
			! $id_or_email instanceof \WP_Comment ||
			! isset( $id_or_email->comment_type ) ||
			$id_or_email->user_id
		) {
			return $args;
		}

		$allowed_comment_types = \apply_filters( 'get_avatar_comment_types', array( 'comment' ) );
		if (
			! empty( $id_or_email->comment_type ) &&
			! \in_array(
				$id_or_email->comment_type,
				(array) $allowed_comment_types,
				true
			)
		) {
			$args['url'] = false;
			/** This filter is documented in wp-includes/link-template.php */
			return \apply_filters( 'get_avatar_data', $args, $id_or_email );
		}

		// Check if comment has an avatar.
		$avatar = self::get_avatar_url( $id_or_email->comment_ID );

		if ( $avatar ) {
			if ( empty( $args['class'] ) ) {
				$args['class'] = array();
			} elseif ( \is_string( $args['class'] ) ) {
				$args['class'] = \explode( ' ', $args['class'] );
			}

			$args['url']     = $avatar;
			$args['class'][] = 'avatar-activitypub';
			$args['class'][] = 'u-photo';
			$args['class']   = \array_unique( $args['class'] );
		}

		return $args;
	}

	/**
	 * Function to retrieve Avatar URL if stored in meta.
	 *
	 * @param int|\WP_Comment $comment The comment ID or object.
	 *
	 * @return string The Avatar URL.
	 */
	public static function get_avatar_url( $comment ) {
		if ( \is_numeric( $comment ) ) {
			$comment = \get_comment( $comment );
		}
		return \get_comment_meta( $comment->comment_ID, 'avatar_url', true );
	}

	/**
	 * Store permalink in meta, to send delete Activity.
	 *
	 * @param string $post_id The Post ID.
	 */
	public static function trash_post( $post_id ) {
		\add_post_meta(
			$post_id,
			'_activitypub_canonical_url',
			\get_permalink( $post_id ),
			true
		);
	}

	/**
	 * Delete permalink from meta.
	 *
	 * @param string $post_id The Post ID.
	 */
	public static function untrash_post( $post_id ) {
		\delete_post_meta( $post_id, '_activitypub_canonical_url' );
	}

	/**
	 * Add rewrite rules.
	 */
	public static function add_rewrite_rules() {
		/*
		 * If another system needs to take precedence over the ActivityPub rewrite rules,
		 * they can define their own and will manually call the appropriate functions as required.
		 */
		if ( ACTIVITYPUB_DISABLE_REWRITES ) {
			return;
		}

		if ( ! \class_exists( 'Webfinger' ) ) {
			\add_rewrite_rule(
				'^.well-known/webfinger',
				'index.php?rest_route=/' . ACTIVITYPUB_REST_NAMESPACE . '/webfinger',
				'top'
			);
		}

		if ( ! \class_exists( 'Nodeinfo_Endpoint' ) && true === (bool) \get_option( 'blog_public', 1 ) ) {
			\add_rewrite_rule(
				'^.well-known/nodeinfo',
				'index.php?rest_route=/' . ACTIVITYPUB_REST_NAMESPACE . '/nodeinfo',
				'top'
			);
		}

		\add_rewrite_rule(
			'^@([\w\-\.]+)',
			'index.php?rest_route=/' . ACTIVITYPUB_REST_NAMESPACE . '/actors/$matches[1]',
			'top'
		);

		\add_rewrite_endpoint( 'activitypub', EP_AUTHORS | EP_PERMALINK | EP_PAGES );
	}

	/**
	 * Flush rewrite rules.
	 */
	public static function flush_rewrite_rules() {
		self::add_rewrite_rules();
		\flush_rewrite_rules();
	}

	/**
	 * Adds metabox on wp-admin/tools.php.
	 */
	public static function tool_box() {
		if ( \current_user_can( 'edit_posts' ) ) {
			\load_template( ACTIVITYPUB_PLUGIN_DIR . 'templates/toolbox.php' );
		}
	}

	/**
	 * Theme compatibility stuff.
	 */
	public static function theme_compat() {
		// We assume that you want to use Post-Formats when enabling the setting.
		if ( 'wordpress-post-format' === \get_option( 'activitypub_object_type', ACTIVITYPUB_DEFAULT_OBJECT_TYPE ) ) {
			if ( ! get_theme_support( 'post-formats' ) ) {
				// Add support for the Aside, Gallery Post Formats...
				add_theme_support(
					'post-formats',
					array(
						'gallery',
						'status',
						'image',
						'video',
						'audio',
					)
				);
			}
		}
	}

	/**
	 * Display plugin upgrade notice to users.
	 *
	 * @param array $data The plugin data.
	 */
	public static function plugin_update_message( $data ) {
		if ( ! isset( $data['upgrade_notice'] ) ) {
			return;
		}

		printf(
			'<div class="update-message">%s</div>',
			wp_kses(
				wpautop( $data['upgrade_notice '] ),
				array(
					'p'      => array(),
					'a'      => array( 'href', 'title' ),
					'strong' => array(),
					'em'     => array(),
				)
			)
		);
	}

	/**
	 * Register Custom Post Types.
	 */
	private static function register_post_types() {
		\register_post_type(
			Followers::POST_TYPE,
			array(
				'labels'           => array(
					'name'          => _x( 'Followers', 'post_type plural name', 'activitypub' ),
					'singular_name' => _x( 'Follower', 'post_type single name', 'activitypub' ),
				),
				'public'           => false,
				'hierarchical'     => false,
				'rewrite'          => false,
				'query_var'        => false,
				'delete_with_user' => false,
				'can_export'       => true,
				'supports'         => array(),
			)
		);

		\register_post_meta(
			Followers::POST_TYPE,
			'_activitypub_inbox',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_url',
			)
		);

		\register_post_meta(
			Followers::POST_TYPE,
			'_activitypub_errors',
			array(
				'type'              => 'string',
				'single'            => false,
				'sanitize_callback' => function ( $value ) {
					if ( ! is_string( $value ) ) {
						throw new Exception( 'Error message is no valid string' );
					}

					return esc_sql( $value );
				},
			)
		);

		\register_post_meta(
			Followers::POST_TYPE,
			'_activitypub_user_id',
			array(
				'type'              => 'string',
				'single'            => false,
				'sanitize_callback' => function ( $value ) {
					return esc_sql( $value );
				},
			)
		);

		\register_post_meta(
			Followers::POST_TYPE,
			'_activitypub_actor_json',
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => function ( $value ) {
					return sanitize_text_field( $value );
				},
			)
		);

		// Register Outbox Post-Type.
		register_post_type(
			Outbox::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => _x( 'Outbox', 'post_type plural name', 'activitypub' ),
					'singular_name' => _x( 'Outbox Item', 'post_type single name', 'activitypub' ),
				),
				'capabilities'        => array(
					'create_posts' => false,
				),
				'map_meta_cap'        => true,
				'public'              => false,
				'show_in_rest'        => true,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'editor', 'author', 'custom-fields' ),
				'delete_with_user'    => true,
				'can_export'          => true,
				'exclude_from_search' => true,
			)
		);

		/**
		 * Register Activity Type meta for Outbox items.
		 *
		 * @see https://www.w3.org/TR/activitystreams-vocabulary/#activity-types
		 */
		\register_post_meta(
			Outbox::POST_TYPE,
			'_activitypub_activity_type',
			array(
				'type'              => 'string',
				'description'       => 'The type of the activity',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $value ) {
					$value  = ucfirst( strtolower( $value ) );
					$schema = array(
						'type'    => 'string',
						'enum'    => array( 'Accept', 'Add', 'Announce', 'Arrive', 'Block', 'Create', 'Delete', 'Dislike', 'Flag', 'Follow', 'Ignore', 'Invite', 'Join', 'Leave', 'Like', 'Listen', 'Move', 'Offer', 'Question', 'Reject', 'Read', 'Remove', 'TentativeReject', 'TentativeAccept', 'Travel', 'Undo', 'Update', 'View' ),
						'default' => 'Announce',
					);

					if ( is_wp_error( rest_validate_enum( $value, $schema, '' ) ) ) {
						return $schema['default'];
					}

					return $value;
				},
			)
		);

		\register_post_meta(
			Outbox::POST_TYPE,
			'_activitypub_activity_actor',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $value ) {
					$schema = array(
						'type'    => 'string',
						'enum'    => array( 'application', 'blog', 'user' ),
						'default' => 'user',
					);

					if ( is_wp_error( rest_validate_enum( $value, $schema, '' ) ) ) {
						return $schema['default'];
					}

					return $value;
				},
			)
		);

		\register_post_meta(
			Outbox::POST_TYPE,
			'_activitypub_outbox_offset',
			array(
				'type'              => 'integer',
				'single'            => true,
				'description'       => 'Keeps track of the followers offset when processing outbox items.',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		\register_post_meta(
			Outbox::POST_TYPE,
			'activitypub_content_visibility',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $value ) {
					$schema = array(
						'type'    => 'string',
						'enum'    => array( ACTIVITYPUB_CONTENT_VISIBILITY_PUBLIC, ACTIVITYPUB_CONTENT_VISIBILITY_QUIET_PUBLIC, ACTIVITYPUB_CONTENT_VISIBILITY_PRIVATE, ACTIVITYPUB_CONTENT_VISIBILITY_LOCAL ),
						'default' => ACTIVITYPUB_CONTENT_VISIBILITY_PUBLIC,
					);

					if ( is_wp_error( rest_validate_enum( $value, $schema, '' ) ) ) {
						return $schema['default'];
					}

					return $value;
				},
			)
		);

		// Both User and Blog Extra Fields types have the same args.
		$args = array(
			'labels'              => array(
				'name'          => _x( 'Extra fields', 'post_type plural name', 'activitypub' ),
				'singular_name' => _x( 'Extra field', 'post_type single name', 'activitypub' ),
				'add_new'       => __( 'Add new', 'activitypub' ),
				'add_new_item'  => __( 'Add new extra field', 'activitypub' ),
				'new_item'      => __( 'New extra field', 'activitypub' ),
				'edit_item'     => __( 'Edit extra field', 'activitypub' ),
				'view_item'     => __( 'View extra field', 'activitypub' ),
				'all_items'     => __( 'All extra fields', 'activitypub' ),
			),
			'public'              => false,
			'hierarchical'        => false,
			'query_var'           => false,
			'has_archive'         => false,
			'publicly_queryable'  => false,
			'show_in_menu'        => false,
			'delete_with_user'    => true,
			'can_export'          => true,
			'exclude_from_search' => true,
			'show_in_rest'        => true,
			'map_meta_cap'        => true,
			'show_ui'             => true,
			'supports'            => array( 'title', 'editor', 'page-attributes' ),
		);

		\register_post_type( Extra_Fields::USER_POST_TYPE, $args );
		\register_post_type( Extra_Fields::BLOG_POST_TYPE, $args );

		/**
		 * Fires after ActivityPub custom post types have been registered.
		 */
		\do_action( 'activitypub_after_register_post_type' );
	}

	/**
	 * Add the 'activitypub' capability to users who can publish posts.
	 *
	 * @param int $user_id User ID.
	 */
	public static function user_register( $user_id ) {
		if ( \user_can( $user_id, 'publish_posts' ) ) {
			$user = \get_user_by( 'id', $user_id );
			$user->add_cap( 'activitypub' );
		}
	}

	/**
	 * Delete `activitypub_content_visibility` when updated to an empty value.
	 *
	 * @param int    $meta_id    ID of updated metadata entry.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. This will be a PHP-serialized string representation of the value
	 *                           if the value is an array, an object, or itself a PHP-serialized string.
	 */
	public static function updated_postmeta( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'activitypub_content_visibility' === $meta_key && empty( $meta_value ) ) {
			\delete_post_meta( $object_id, 'activitypub_content_visibility' );
		}
	}
}
