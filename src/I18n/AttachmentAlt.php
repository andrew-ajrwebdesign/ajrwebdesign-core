<?php
/**
 * German alt text for an image, stored on the attachment.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\I18n;

use AJR\SiteCore\Core\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * One image, two languages of alt text.
 *
 * WordPress keeps a single alt text per image. A case study is one post shown on
 * both the English and the German site (the single-entry model), so its
 * screenshots printed their English alt text inside German pages (SEO review,
 * 2026-09-30). This adds a second field to the image's details in the media
 * library, "Alt text (German)", and a resolver the blocks use to pick the right
 * one for the page being shown.
 *
 * The resolver never returns nothing for a real image: German falls back to the
 * English alt, and an image with no alt at all falls back to its title, so a
 * screenshot is never silently invisible to a screen reader or to image search.
 */
class AttachmentAlt {

	/**
	 * Attachment meta key for the German alt text.
	 */
	public const META = '_ajrwd_alt_de';

	/**
	 * Hooks the media-library field.
	 */
	public function register(): void {
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_field' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_field' ), 10, 2 );
	}

	/**
	 * Adds "Alt text (German)" to an image's details.
	 *
	 * @param mixed $fields Form fields for the attachment.
	 * @param mixed $post   The attachment.
	 * @return mixed
	 */
	public function add_field( $fields, $post ) {
		if ( ! is_array( $fields ) || ! $post instanceof \WP_Post || ! wp_attachment_is_image( $post->ID ) ) {
			return $fields;
		}

		$fields['ajrwd_alt_de'] = array(
			'label' => __( 'Alt text (German)', 'ajrwebdesign-core' ),
			'input' => 'text',
			'value' => (string) get_post_meta( $post->ID, self::META, true ),
			'helps' => __( 'Read out on German pages in place of the alt text above. Left empty, German pages use the alt text above.', 'ajrwebdesign-core' ),
		);

		return $fields;
	}

	/**
	 * Saves the field. WordPress has already checked the nonce and that the user
	 * may edit this attachment before this filter runs.
	 *
	 * @param mixed $post       The attachment's post data (array).
	 * @param mixed $attachment The submitted field values for it.
	 * @return mixed
	 */
	public function save_field( $post, $attachment ) {
		if ( is_array( $post ) && is_array( $attachment ) && isset( $post['ID'], $attachment['ajrwd_alt_de'] ) && is_scalar( $attachment['ajrwd_alt_de'] ) ) {
			// attachment_fields_to_save hands over unslashed values; update_post_meta() expects slashed.
			update_post_meta( (int) $post['ID'], self::META, wp_slash( sanitize_text_field( (string) $attachment['ajrwd_alt_de'] ) ) );
		}

		return $post;
	}

	/**
	 * The alt text to print for an image on the page being shown.
	 *
	 * German pages: the German alt, else the English alt, else the image's title.
	 * Other pages: the English alt, else the image's title.
	 *
	 * @param int $attachment_id Image attachment ID.
	 */
	public static function for_page( int $attachment_id ): string {
		if ( 'de' === Utils::current_language() ) {
			$german = trim( (string) get_post_meta( $attachment_id, self::META, true ) );
			if ( '' !== $german ) {
				return $german;
			}
		}

		$alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );

		return '' !== $alt ? $alt : trim( (string) get_the_title( $attachment_id ) );
	}
}
