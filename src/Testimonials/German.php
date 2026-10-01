<?php
/**
 * German text for testimonials: the bilingual layer on Testimonials\Testimonials.
 *
 * Testimonials\Testimonials registers the post type, its rating and logo fields and the slider
 * block (AJR Core did from 1.8.0 until 1.15.0). What stays here is the bilingual
 * single-entry model: one testimonial serves both languages, English in the post itself and
 * German in two fields beside it, used on German pages with English as the fallback.
 * Testimonials::text() runs the `ajr_core_testimonial_text` filter (name kept from AJR Core)
 * for exactly this.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Testimonials;

use AJR\SiteCore\Core\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the German fields and swaps them in on German pages.
 */
class German {

	/**
	 * The testimonial post type (Testimonials\Testimonials::POST_TYPE).
	 */
	public const POST_TYPE = Testimonials::POST_TYPE;

	/**
	 * The German quote. Kept under its original folio key: this data never moved.
	 */
	public const QUOTE_DE = 'ajrwd_t_quote_de';

	/**
	 * The German role line ("Inhaber, Acme"). Same reason for the key.
	 */
	public const ROLE_DE = 'ajrwd_t_role_de';

	/**
	 * Register the German fields on init, and the German text swap on the slider's filter.
	 * Hooked here rather than in a constructor, so the class can be tested on its own.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_filter( 'ajr_core_testimonial_text', array( $this, 'german' ), 10, 2 );
	}

	/**
	 * The two German fields, editable in the block editor's "German translation" panel.
	 */
	public function register_meta(): void {
		$auth = static function () {
			return current_user_can( 'edit_posts' );
		};

		foreach ( array(
			self::QUOTE_DE => 'sanitize_textarea_field',
			self::ROLE_DE  => 'sanitize_text_field',
		) as $key => $sanitize ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'sanitize_callback' => $sanitize,
					'show_in_rest'      => true,
					'auth_callback'     => $auth,
				)
			);
		}
	}

	/**
	 * On German pages, the German quote and role where they are filled in; English otherwise.
	 *
	 * @param array<string,string> $fields  Quote and role as the slider would print them.
	 * @param int                  $post_id Testimonial post ID.
	 * @return array<string,string>
	 */
	public function german( $fields, $post_id ) {
		if ( 'de' !== Utils::current_language() || ! is_array( $fields ) ) {
			return $fields;
		}

		$quote = (string) get_post_meta( (int) $post_id, self::QUOTE_DE, true );
		$role  = (string) get_post_meta( (int) $post_id, self::ROLE_DE, true );

		if ( '' !== trim( $quote ) ) {
			$fields['quote'] = $quote;
		}
		if ( '' !== trim( $role ) ) {
			$fields['role'] = $role;
		}

		return $fields;
	}
}
