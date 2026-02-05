<?php
/**
 * Uninstall Nested Blog Posts.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'nbp_enabled' );
delete_option( 'nbp_needs_flush' );
