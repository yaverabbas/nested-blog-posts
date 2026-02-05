<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin settings for Nested Blog Posts.
 *
 * @since 1.0.0
 */
final class NBP_Admin {

	/**
	 * Init admin hooks.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Add settings page.
	 *
	 * @since 1.0.0
	 */
	public static function add_menu() {
		add_options_page(
			__( 'Nested Blog Posts', 'nested-blog-posts' ),
			__( 'Nested Blog Posts', 'nested-blog-posts' ),
			'manage_options',
			'nested-blog-posts',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Register settings using Settings API.
	 *
	 * @since 1.0.0
	 */
	public static function register_settings() {
		register_setting(
			'nbp_settings',
			NBP_Plugin::OPTION_ENABLED,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( __CLASS__, 'sanitize_enabled' ),
				'default'           => 1,
			)
		);

		add_settings_section(
			'nbp_main',
			__( 'Settings', 'nested-blog-posts' ),
			array( __CLASS__, 'render_section_intro' ),
			'nested-blog-posts'
		);

		add_settings_field(
			'nbp_enabled',
			__( 'Enable hierarchical posts for blog posts', 'nested-blog-posts' ),
			array( __CLASS__, 'render_enabled_field' ),
			'nested-blog-posts',
			'nbp_main'
		);
	}

	/**
	 * Sanitize checkbox and mark rewrites for flush if changed.
	 *
	 * @since 1.0.0
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public static function sanitize_enabled( $value ) {
		$new = empty( $value ) ? 0 : 1;
		$old = (int) get_option( NBP_Plugin::OPTION_ENABLED, 1 );

		if ( $new !== $old ) {
			update_option( NBP_Plugin::OPTION_NEEDS_FLUSH, 1, false );
		}

		return $new;
	}

	/**
	 * Section intro.
	 *
	 * @since 1.0.0
	 */
	public static function render_section_intro() {
		echo '<p>' . esc_html__( 'Enable or disable parent/child hierarchy and nested permalinks for standard blog posts (Post post type).', 'nested-blog-posts' ) . '</p>';
	}

	/**
	 * Render enabled checkbox.
	 *
	 * @since 1.0.0
	 */
	public static function render_enabled_field() {
		$enabled = (int) get_option( NBP_Plugin::OPTION_ENABLED, 1 );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( NBP_Plugin::OPTION_ENABLED ); ?>" value="1" <?php checked( 1, $enabled ); ?> />
			<?php echo esc_html__( 'Enabled', 'nested-blog-posts' ); ?>
		</label>
		<p class="description">
			<?php echo esc_html__( 'When enabled, Posts can be assigned Parents (like Pages) and will use nested URLs like /parent/child/.', 'nested-blog-posts' ); ?>
		</p>
		<?php
	}

	/**
	 * Render settings page.
	 *
	 * @since 1.0.0
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<style>.nbp-tips-list{list-style:disc;padding-left:20px;}</style>
			<h1><?php echo esc_html__( 'Nested Blog Posts', 'nested-blog-posts' ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'nbp_settings' );
				do_settings_sections( 'nested-blog-posts' );
				submit_button();
				?>
			</form>

			<hr />

			<h2><?php echo esc_html__( 'How to use', 'nested-blog-posts' ); ?></h2>
			<ol>
				<li><?php echo esc_html__( 'Install and activate the plugin.', 'nested-blog-posts' ); ?></li>
				<li><?php echo esc_html__( 'Go to Settings → Nested Blog Posts and make sure it is Enabled.', 'nested-blog-posts' ); ?></li>
				<li><?php echo esc_html__( 'Create a Parent Post (a normal blog post).', 'nested-blog-posts' ); ?></li>
				<li><?php echo esc_html__( 'Create a Child Post and, in the editor sidebar, set Parent to your parent post.', 'nested-blog-posts' ); ?></li>
				<li><?php echo esc_html__( 'For deeper nesting, set a post’s Parent to the previous child (creates /parent/child/grandchild/).', 'nested-blog-posts' ); ?></li>
				<li><?php echo esc_html__( 'After enabling/disabling the feature, WordPress will refresh rewrite rules automatically.', 'nested-blog-posts' ); ?></li>
			</ol>

			<h3><?php echo esc_html__( 'Newbie tips', 'nested-blog-posts' ); ?></h3>
			<ul class="nbp-tips-list">
				<li><?php echo esc_html__( 'If you do not see the Parent dropdown, refresh the editor and make sure the feature is Enabled.', 'nested-blog-posts' ); ?></li>
				<li><?php echo esc_html__( 'Avoid using the same slugs as Pages (e.g., /about/) to prevent URL conflicts.', 'nested-blog-posts' ); ?></li>
				<li><?php echo esc_html__( 'If you change the Parent of a post, its URL will change too. Consider redirects for SEO.', 'nested-blog-posts' ); ?></li>
			</ul>
		</div>
		<?php
	}
}
