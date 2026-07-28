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
