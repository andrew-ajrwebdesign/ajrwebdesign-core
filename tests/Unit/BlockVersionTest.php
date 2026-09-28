<?php
/**
 * Block version filter tests.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Tests\Unit;

use AJR\SiteCore\Blocks\BlockVersion;
use PHPUnit\Framework\TestCase;

/**
 * The plugin's blocks take the plugin version; nobody else's blocks are touched.
 */
class BlockVersionTest extends TestCase {

	public function test_own_block_gets_plugin_version(): void {
		$filter = new BlockVersion( '9.9.9' );
		$out    = $filter->filter_metadata( array( 'name' => 'ajrwebdesign-core/post-intro' ) );
		$this->assertSame( '9.9.9', $out['version'] );
	}

	public function test_typed_version_is_overridden(): void {
		$filter = new BlockVersion( '9.9.9' );
		$out    = $filter->filter_metadata(
			array(
				'name'    => 'ajrwebdesign-core/post-intro',
				'version' => '1.0.0',
			)
		);
		$this->assertSame( '9.9.9', $out['version'] );
	}

	public function test_other_blocks_untouched(): void {
		$filter = new BlockVersion( '9.9.9' );
		$core   = array(
			'name'    => 'core/paragraph',
			'version' => '1.0.0',
		);
		$this->assertSame( $core, $filter->filter_metadata( $core ) );
		$lookalike = array( 'name' => 'ajrwebdesign-core-extra/thing' );
		$this->assertSame( $lookalike, $filter->filter_metadata( $lookalike ) );
		$this->assertSame( array(), $filter->filter_metadata( array() ) );
	}
}
