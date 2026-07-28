<?php
/**
 * Image sizes for the responsive-image block.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * The three hero-image sizes the responsive-image block's srcsets rely on
 * (carried from the legacy plugin so newly uploaded hero assets keep
 * generating the same renditions).
 */
class ImageSizes {

	/**
	 * Hooks size registration.
	 */
	public function register(): void {
		add_action( 'after_setup_theme', array( $this, 'add_sizes' ) );
	}

	/**
	 * Registers the sizes (soft-crop).
	 */
	public function add_sizes(): void {
		add_image_size( 'ajr-hero-sm', 600, 0, false );
		add_image_size( 'ajr-hero-md', 900, 0, false );
		add_image_size( 'ajr-hero-lg', 1200, 0, false );
	}
}
