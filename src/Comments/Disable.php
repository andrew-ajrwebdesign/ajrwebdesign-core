<?php
/**
 * Site-wide comment removal.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Comments;

defined( 'ABSPATH' ) || exit;

/**
 * Disables comments everywhere, replacing the separate disable-comments
 * plugin previously installed on every build. Comments are closed on all
 * post types, existing comments are hidden, and the admin surface
 * (menu, dashboard widget, admin-bar node, discussion settings) is
 * removed. Pingbacks/trackbacks are closed with them.
 */
class Disable {

	/**
	 * Hooks everything.
	 */
	public function register(): void {
		// Close comments and pings on every post type, old and new.
		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );

		// Hide any comments that already exist.
		add_filter( 'comments_array', '__return_empty_array', 20 );

		// Strip comment support from post types once they are registered.
		add_action( 'init', array( $this, 'remove_post_type_support' ), 100 );

		// No comments feed link in the head.
		add_filter( 'feed_links_show_comments_feed', '__return_false' );

		// Admin surface.
		add_action( 'admin_menu', array( $this, 'remove_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'redirect_comments_screen' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widget' ) );
		add_action( 'admin_bar_menu', array( $this, 'remove_admin_bar_node' ), 999 );
	}

	/**
	 * Removes comment/trackback support from every registered post type.
	 */
	public function remove_post_type_support(): void {
		foreach ( get_post_types() as $post_type ) {
			remove_post_type_support( $post_type, 'comments' );
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}

	/**
	 * Drops the Comments menu item.
	 */
	public function remove_admin_menu(): void {
		remove_menu_page( 'edit-comments.php' );
	}

	/**
	 * Sends anyone who reaches the comments screen directly back to the
	 * dashboard.
	 */
	public function redirect_comments_screen(): void {
		global $pagenow;
		if ( 'edit-comments.php' === $pagenow ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	/**
	 * Drops the Recent Comments dashboard widget.
	 */
	public function remove_dashboard_widget(): void {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/**
	 * Drops the admin-bar comments bubble.
	 *
	 * @param \WP_Admin_Bar $admin_bar Admin bar instance.
	 */
	public function remove_admin_bar_node( \WP_Admin_Bar $admin_bar ): void {
		$admin_bar->remove_node( 'comments' );
	}
}
