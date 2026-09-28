<?php
/**
 * Block version derived from the plugin version.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Stamps every one of this plugin's blocks with the plugin's own version.
 *
 * Why this exists: WordPress uses a block's `version` as the cache-busting
 * `?ver=` on the stylesheets and scripts that block.json declares. A version
 * typed into each block.json is correct on the day it is written and silently
 * stale from the next release on, so browsers and caches keep serving the old
 * CSS after a deploy. The block.json files therefore carry no version at all,
 * and this filter supplies the plugin header version (AJRWD_CORE_VERSION) at
 * registration time — one number, bumped once per release.
 *
 * Only blocks in this plugin's namespace are touched; core and third-party
 * blocks keep whatever version they declare.
 */
class BlockVersion {

	/**
	 * Block-name namespace this plugin registers under.
	 */
	const BLOCK_NAMESPACE = 'ajrwebdesign-core/';

	/**
	 * Version to stamp on the plugin's blocks.
	 *
	 * @var string
	 */
	protected string $version;

	/**
	 * Stores the version to stamp. Hooks are added in register(), not here.
	 *
	 * @param string $version Plugin version, normally AJRWD_CORE_VERSION.
	 */
	public function __construct( string $version ) {
		$this->version = $version;
	}

	/**
	 * Hooks the metadata filter.
	 */
	public function register(): void {
		add_filter( 'block_type_metadata', array( $this, 'filter_metadata' ) );
	}

	/**
	 * Sets `version` on this plugin's block metadata; leaves every other block alone.
	 *
	 * @param array $metadata Block metadata read from block.json.
	 * @return array Metadata, with `version` set for this plugin's blocks.
	 */
	public function filter_metadata( $metadata ) {
		if ( ! is_array( $metadata ) || empty( $metadata['name'] ) || ! is_string( $metadata['name'] ) ) {
			return $metadata;
		}
		if ( 0 !== strpos( $metadata['name'], self::BLOCK_NAMESPACE ) ) {
			return $metadata;
		}
		$metadata['version'] = $this->version;
		return $metadata;
	}
}
