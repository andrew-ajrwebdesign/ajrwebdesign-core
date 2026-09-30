<?php
/**
 * Blog-post meta (intro + callout fields).
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Posts;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the blog-post meta consumed by the post-intro and post-callout
 * blocks. Key names match the legacy plugin so existing content carries over
 * unchanged. All REST-exposed for the block editor sidebar.
 */
class Meta {

	public const INTRO_TEXT    = 'ajr_intro_text';
	public const CALLOUT_LABEL = 'ajr_callout_label';
	public const CALLOUT_TITLE = 'ajr_callout_title';
	public const CALLOUT_TEXT  = 'ajr_callout_text';

	/**
	 * Hooks meta registration.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_filter( 'rest_prepare_post', array( $this, 'hide_protected_meta' ), 10, 3 );
	}

	/**
	 * A password-protected post's intro and callout stay behind its password in the REST API.
	 *
	 * The post-intro and post-callout blocks print nothing on a protected post (1.14.0), but
	 * WordPress blanks only the content and excerpt in REST and returns registered meta without
	 * asking for the password, so the same text was readable at /wp/v2/posts/<id> (security
	 * review, 2026-09-30). The same guard as CaseStudies\Meta::hide_protected_meta(). Someone
	 * who may edit the post still gets the fields, so the editor sidebar keeps working.
	 *
	 * @param mixed $response The REST response.
	 * @param mixed $post     The post.
	 * @param mixed $request  The REST request.
	 * @return mixed
	 */
	public function hide_protected_meta( $response, $post, $request ) {
		if ( ! $response instanceof \WP_REST_Response || ! $post instanceof \WP_Post ) {
			return $response;
		}
		$context = $request instanceof \WP_REST_Request ? (string) $request->get_param( 'context' ) : 'view';
		if ( ! post_password_required( $post ) || ( 'edit' === $context && current_user_can( 'edit_post', $post->ID ) ) ) {
			return $response;
		}

		$data = $response->get_data();
		if ( is_array( $data ) && isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
			foreach ( array( self::INTRO_TEXT, self::CALLOUT_LABEL, self::CALLOUT_TITLE, self::CALLOUT_TEXT ) as $key ) {
				unset( $data['meta'][ $key ] );
			}
			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * Registers the four post meta keys.
	 */
	public function register_meta(): void {
		$keys = array( self::INTRO_TEXT, self::CALLOUT_LABEL, self::CALLOUT_TITLE, self::CALLOUT_TEXT );
		foreach ( $keys as $key ) {
			register_post_meta(
				'post',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'sanitize_callback' => 'sanitize_textarea_field',
					'show_in_rest'      => true,
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}
