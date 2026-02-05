<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Router + permalink generator for hierarchical Posts.
 *
 * @since 1.0.0
 */
final class NBP_Router {

	/**
	 * Maximum depth for parent chain (safety limit).
	 *
	 * @since 1.0.0
	 */
	const MAX_CHAIN_DEPTH = 100;

	/**
	 * Matched post ID for this request.
	 *
	 * @var int
	 */
	private static $matched_post_id = 0;

	/**
	 * Debug query string key.
	 */
	const DEBUG_QS = 'nbp_debug';

	/**
	 * Debug log for current request.
	 *
	 * @var array
	 */
	private static $debug = array();

	/**
	 * Init hooks (only when enabled).
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		// Post type configuration is now handled by NBP_Plugin::configure_post_type().
		// This ensures it runs early enough for Block Editor compatibility.

		add_filter( 'post_link', array( __CLASS__, 'filter_post_link' ), 10, 2 );
		add_filter( 'post_type_link', array( __CLASS__, 'filter_post_type_link' ), 10, 2 );

		add_action( 'parse_request', array( __CLASS__, 'route_nested_post_paths' ), 1 );

		// Stop WP canonical redirects when we matched a valid nested URL.
		add_filter( 'redirect_canonical', array( __CLASS__, 'stop_canonical_redirect' ), 10, 2 );

		// Optional debug headers for admins (?nbp_debug=1).
		add_action( 'send_headers', array( __CLASS__, 'send_debug_headers' ) );

		self::dbg( 'init:hierarchical-posts-enabled' );
	}

	/**
	 * Build full chain path: grandparent/parent/child
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	private static function build_full_chain_slug( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return '';
		}
		if ( empty( $post->post_name ) ) {
			return '';
		}

		$parts     = array( $post->post_name );
		$parent_id = (int) $post->post_parent;
		$depth     = 0;

		while ( $parent_id && $depth < self::MAX_CHAIN_DEPTH ) {
			$parent = get_post( $parent_id );
			if ( ! $parent || 'post' !== $parent->post_type ) {
				break;
			}

			array_unshift( $parts, $parent->post_name );
			$parent_id = (int) $parent->post_parent;
			$depth++;
		}

		return implode( '/', $parts );
	}

	/**
	 * Generate hierarchical post permalinks (frontend).
	 *
	 * @since 1.0.0
	 */
	public static function filter_post_link( $permalink, $post ) {
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
			return $permalink;
		}

		$path = self::build_full_chain_slug( $post );
		return $path ? home_url( '/' . $path . '/' ) : $permalink;
	}

	/**
	 * Apply same logic on post_type_link.
	 *
	 * @since 1.0.0
	 */
	public static function filter_post_type_link( $permalink, $post ) {
		if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
			return $permalink;
		}

		$path = self::build_full_chain_slug( $post );
		return $path ? home_url( '/' . $path . '/' ) : $permalink;
	}

	/**
	 * Route nested URLs like /a/b/c/ to the correct Post by validating the FULL parent chain.
	 *
	 * @since 1.0.0
	 * @param WP $wp WP instance.
	 */
	public static function route_nested_post_paths( $wp ) {
		if ( is_admin() ) {
			return;
		}

		$path = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
		self::dbg( 'request:incoming-path', array( 'path' => $path ) );

		// Only handle nested paths: must contain at least one slash.
		if ( '' === $path || false === strpos( $path, '/' ) ) {
			return;
		}

		// Avoid intercepting obvious system routes (also keep this filterable).
		$first_segment = strtok( $path, '/' );
		$reserved = apply_filters(
			'nbp_reserved_prefixes',
			array( 'wp-admin', 'wp-json', 'feed', 'sitemap', 'robots.txt', 'category', 'tag', 'author', 'search' )
		);

		if ( in_array( $first_segment, $reserved, true ) ) {
			self::dbg( 'route:reserved-prefix-skip', array( 'first' => $first_segment ) );
			return;
		}

		// If a real PAGE exists at this exact path, let WordPress handle it.
		$page = get_page_by_path( $path, OBJECT, 'page' );
		if ( $page && 'publish' === $page->post_status ) {
			self::dbg( 'route:page-exists-skip', array( 'page_id' => $page->ID ) );
			return;
		}

		$segments          = explode( '/', $path );
		$slug              = end( $segments );
		$requested_parents = array_slice( $segments, 0, -1 );

		// Find candidate Posts by slug (handles duplicates).
		$candidates = get_posts(
			array(
				'name'                => $slug,
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'numberposts'         => 50,
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		self::dbg(
			'route:candidates',
			array(
				'slug'  => $slug,
				'count' => count( $candidates ),
			)
		);

		if ( empty( $candidates ) ) {
			return;
		}

		foreach ( $candidates as $post ) {
			$ancestors = array();
			$pid       = (int) $post->post_parent;
			$depth     = 0;

			while ( $pid && $depth < self::MAX_CHAIN_DEPTH ) {
				$p = get_post( $pid );
				if ( ! $p || 'post' !== $p->post_type ) {
					break;
				}
				array_unshift( $ancestors, $p->post_name );
				$pid   = (int) $p->post_parent;
				$depth++;
			}

			if ( $ancestors === $requested_parents ) {
				self::$matched_post_id = (int) $post->ID;

				self::dbg(
					'route:matched',
					array(
						'post_id'  => self::$matched_post_id,
						'expected' => home_url( '/' . $path . '/' ),
					)
				);

				// Force query by ID (most reliable).
				$wp->query_vars['post_type'] = 'post';
				$wp->query_vars['p']         = self::$matched_post_id;

				// Prevent page/attachment routing collisions.
				unset(
					$wp->query_vars['name'],
					$wp->query_vars['pagename'],
					$wp->query_vars['page'],
					$wp->query_vars['attachment'],
					$wp->query_vars['attachment_id'],
					$wp->query_vars['post_mime_type'],
					$wp->query_vars['error']
				);

				$wp->matched_rule  = 'nbp_hier_posts_router';
				$wp->matched_query = 'post_type=post&p=' . self::$matched_post_id;

				return;
			}
		}

		self::dbg( 'route:no-match' );
	}

	/**
	 * Stop WordPress canonical redirect when this plugin matched a valid hierarchical URL.
	 *
	 * @since 1.0.0
	 * @param string|false $redirect_url  Proposed redirect URL.
	 * @param string       $requested_url Requested URL.
	 * @return string|false
	 */
	public static function stop_canonical_redirect( $redirect_url, $requested_url ) {
		if ( self::$matched_post_id ) {
			self::dbg(
				'canonical:blocked',
				array(
					'from' => $requested_url,
					'to'   => $redirect_url,
				)
			);
			return false;
		}
		return $redirect_url;
	}

	/**
	 * Debug enabled only for admins: ?nbp_debug=1
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private static function debug_on() {
		return current_user_can( 'manage_options' ) && filter_input( INPUT_GET, self::DEBUG_QS, FILTER_SANITIZE_NUMBER_INT ) !== null;
	}

	/**
	 * Add debug entry.
	 *
	 * @since 1.0.0
	 * @param string $step Step.
	 * @param array  $data Data.
	 */
	private static function dbg( $step, $data = array() ) {
		if ( ! self::debug_on() ) {
			return;
		}

		self::$debug[] = array(
			't'    => gmdate( 'H:i:s' ),
			'step' => $step,
			'data' => $data,
		);
	}

	/**
	 * Send debug headers for admins. Check DevTools → Network → response headers.
	 *
	 * @since 1.0.0
	 */
	public static function send_debug_headers() {
		if ( ! self::debug_on() ) {
			return;
		}

		header( 'X-NBP-Matched-Post: ' . ( self::$matched_post_id ? self::$matched_post_id : 0 ) );

		$last = end( self::$debug );
		if ( $last ) {
			header( 'X-NBP-Last-Step: ' . $last['step'] );
			$json = wp_json_encode( $last['data'] );
			header( 'X-NBP-Last-Data: ' . substr( $json, 0, 900 ) );
		}

		$full = wp_json_encode( self::$debug );
		if ( is_string( $full ) ) {
			header( 'X-NBP-Debug: ' . substr( $full, 0, 1400 ) );
		}
	}
}
