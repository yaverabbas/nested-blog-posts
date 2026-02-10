<?php
/**
 * Uninstall Nested Blog Posts.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wwhry_nbp_enabled' );
delete_option( 'wwhry_nbp_needs_flush' );
