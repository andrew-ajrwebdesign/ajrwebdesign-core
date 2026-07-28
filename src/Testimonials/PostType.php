<?php
/**
 * Testimonials post type.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Testimonials;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `ajr_testimonial` — ONE entry per quote, both languages on the
 * same post: content/excerpt hold the English quote/role, and the German
 * lives in meta fields (edited in a sidebar panel). The slider block picks
 * the right pair for the visitor's language, falling back to English.
 *
 * A service taxonomy (`testimonial_tag`) lets the block filter which
 * testimonials appear per page (e.g. only "Performance Audit" quotes on
 * the audit page).
 */
class PostType {

	public const POST_TYPE = 'ajr_testimonial';
	public const TAXONOMY  = 'testimonial_tag';
	public const RATING    = 'ajrwd_t_rating';
	public const QUOTE_DE  = 'ajrwd_t_quote_de';
	public const ROLE_DE   = 'ajrwd_t_role_de';
	public const LOGO_ID   = 'ajrwd_t_logo_id';

	/**
	 * Hooks registration.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
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
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Registers the service-tag taxonomy (editor-facing only).
	 */
	public function register_taxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			array( self::POST_TYPE ),
			array(
				'labels'            => array(
					'name'          => __( 'Service Tags', 'ajrwebdesign-core' ),
					'singular_name' => __( 'Service Tag', 'ajrwebdesign-core' ),
					'menu_name'     => __( 'Service Tags', 'ajrwebdesign-core' ),
				),
				'hierarchical'      => false,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => false,
				'query_var'         => false,
			)
		);
	}

	/**
	 * Registers rating + German-translation meta.
	 */
	public function register_meta(): void {
		$auth = static function () {
			return current_user_can( 'edit_posts' );
		};

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
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::QUOTE_DE,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::ROLE_DE,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::LOGO_ID,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
			)
		);
	}
}
