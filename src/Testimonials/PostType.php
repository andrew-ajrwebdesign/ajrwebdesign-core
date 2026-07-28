<?php
/**
 * Testimonials post type.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Testimonials;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `ajr_testimonial` — one entry per quote. Content model keeps
 * the editor stock: title = person name, content = the quote, excerpt =
 * role/company line, featured image = avatar (optional; the block renders
 * an initial bubble when absent). Star rating lives in meta (default 5).
 * Polylang-translatable so EN/DE quotes pair like pages do.
 */
class PostType {

	public const POST_TYPE = 'ajr_testimonial';
	public const RATING    = 'ajrwd_t_rating';

	/**
	 * Hooks registration.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_filter( 'pll_get_post_types', array( $this, 'make_translatable' ) );
	}

	/**
	 * Registers the post type (admin-only, REST-exposed for the editor).
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Testimonials', 'ajrwebdesign-core' ),
					'singular_name' => __( 'Testimonial', 'ajrwebdesign-core' ),
					'add_new_item'  => __( 'Add New Testimonial', 'ajrwebdesign-core' ),
					'edit_item'     => __( 'Edit Testimonial', 'ajrwebdesign-core' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_rest'    => true,
				'menu_icon'       => 'dashicons-format-quote',
				'menu_position'   => 22,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Registers the rating meta (1–5, default 5).
	 */
	public function register_meta(): void {
		register_post_meta(
			self::POST_TYPE,
			self::RATING,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 5,
				'sanitize_callback' => static function ( $value ) {
					return max( 1, min( 5, (int) $value ) );
				},
				'show_in_rest'      => true,
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Lets Polylang translate testimonials.
	 *
	 * @param array<string,string> $post_types Translatable post types.
	 * @return array<string,string>
	 */
	public function make_translatable( array $post_types ): array {
		$post_types[ self::POST_TYPE ] = self::POST_TYPE;
		return $post_types;
	}
}
