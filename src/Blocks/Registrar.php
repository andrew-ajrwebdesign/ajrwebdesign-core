<?php
/**
 * Block registration.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every block from its compiled block.json in build/. All render
 * logic lives in each block's own render.php (declared via the block.json
 * `render` field) — this class only discovers and registers.
 */
class Registrar {

	/**
	 * Hooks block registration.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Registers all compiled blocks.
	 */
	public function register_blocks(): void {
		$build_dir = AJRWD_CORE_PATH . 'build';
		if ( ! is_dir( $build_dir ) ) {
			return;
		}

		foreach ( glob( $build_dir . '/*/block.json' ) as $manifest ) {
			register_block_type( dirname( $manifest ) );
		}
	}
}
