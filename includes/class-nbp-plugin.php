<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once NBP_DIR . 'includes/class-nbp-admin.php';
require_once NBP_DIR . 'includes/class-nbp-router.php';

/**
 * Bootstrapper for Nested Blog Posts.
 *
 * @since 1.0.0
 */
final class NBP_Plugin {

	/**
	 * Option name for enabling/disabling the feature.
	 */
	const OPTION_ENABLED = 'nbp_enabled';

	/**
	 * Internal flag option to flush rewrite rules once.
	 */
	const OPTION_NEEDS_FLUSH = 'nbp_needs_flush';

	/**
	 * Initialize plugin.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		// Configure post type early (before init) for Block Editor compatibility.
		add_action( 'registered_post_type', array( __CLASS__, 'configure_post_type_early' ), 10, 2 );
		add_action( 'init', array( __CLASS__, 'configure_post_type' ), 0 );

		// Ensure REST API exposes parent field for Block Editor.
		add_filter( 'rest_prepare_post', array( __CLASS__, 'ensure_parent_in_rest' ), 10, 3 );

		// Admin UI
		if ( is_admin() ) {
			NBP_Admin::init();
			// Ensure post type is configured in admin area.
			add_action( 'admin_init', array( __CLASS__, 'configure_post_type' ), 0 );
		}

		// Frontend behavior (conditional)
		add_action( 'init', array( __CLASS__, 'maybe_boot_feature' ), 0 );

		// Flush rewrites after settings change (cheap, one-time).
		add_action( 'wp_loaded', array( __CLASS__, 'maybe_flush_rewrites' ), 20 );

		// Plugin action links.
		add_filter( 'plugin_action_links_' . plugin_basename( NBP_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Activation tasks.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		if ( get_option( self::OPTION_ENABLED, null ) === null ) {
			add_option( self::OPTION_ENABLED, 1 );
		}

		// Mark for a rewrite flush on next load.
		update_option( self::OPTION_NEEDS_FLUSH, 1, false );
	}

	/**
	 * Deactivation tasks.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Do NOT delete user settings on deactivation.
		// Ensure rewrites are flushed to revert to default behavior.
		update_option( self::OPTION_NEEDS_FLUSH, 1, false );
	}

	/**
	 * Configure post type early when it's registered (for Block Editor compatibility).
	 *
	 * @since 1.0.0
	 * @param string       $post_type Post type name.
	 * @param WP_Post_Type $args      Post type object.
	 */
	public static function configure_post_type_early( $post_type, $args ) {
		if ( 'post' !== $post_type ) {
			return;
		}

		$enabled = (int) get_option( self::OPTION_ENABLED, 1 );
		if ( 1 !== $enabled ) {
			return;
		}

		self::apply_post_type_config( $args );
	}

	/**
	 * Configure post type on init hook.
	 *
	 * @since 1.0.0
	 */
	public static function configure_post_type() {
		$enabled = (int) get_option( self::OPTION_ENABLED, 1 );
		if ( 1 !== $enabled ) {
			return;
		}

		global $wp_post_types;
		if ( isset( $wp_post_types['post'] ) ) {
			self::apply_post_type_config( $wp_post_types['post'] );
		}
	}

	/**
	 * Apply hierarchical configuration to post type object.
	 *
	 * @since 1.0.0
	 * @param WP_Post_Type $post_type Post type object.
	 */
	private static function apply_post_type_config( $post_type ) {
		add_post_type_support( 'post', 'page-attributes' );
		$post_type->hierarchical = true;

		// Block Editor only shows the Parent dropdown when this label is non-empty.
		if ( isset( $post_type->labels ) && is_object( $post_type->labels ) ) {
			$post_type->labels->parent_item_colon = __( 'Parent Post:', 'nested-blog-posts' );
		}
	}

	/**
	 * Ensure parent field is included in REST API response for Block Editor.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     Post object.
	 * @param WP_REST_Request  $request  Request object.
	 * @return WP_REST_Response
	 */
	public static function ensure_parent_in_rest( $response, $post, $request ) {
		$enabled = (int) get_option( self::OPTION_ENABLED, 1 );
		if ( 1 !== $enabled || 'post' !== $post->post_type ) {
			return $response;
		}

		// Ensure parent field is in the response data.
		$data = $response->get_data();
		if ( ! isset( $data['parent'] ) ) {
			$data['parent'] = (int) $post->post_parent;
			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * Boot feature only if enabled.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_boot_feature() {
		$enabled = (int) get_option( self::OPTION_ENABLED, 1 );
		if ( 1 !== $enabled ) {
			return;
		}

		NBP_Router::init();
	}

	/**
	 * Flush rewrite rules once if needed.
	 *
	 * @since 1.0.0
	 */
	public static function maybe_flush_rewrites() {
		if ( 1 !== (int) get_option( self::OPTION_NEEDS_FLUSH, 0 ) ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::OPTION_NEEDS_FLUSH, 0, false );
	}

	/**
	 * Add Settings link on Plugins screen.
	 *
	 * @since 1.0.0
	 * @param array $links Action links.
	 * @return array
	 */
	public static function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=nested-blog-posts' );
		$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'nested-blog-posts' ) . '</a>';
		return $links;
	}
}