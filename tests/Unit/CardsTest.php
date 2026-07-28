<?php
/**
 * Card helper tests.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Tests\Unit;

use AJR\SiteCore\Blocks\Cards;
use PHPUnit\Framework\TestCase;

/**
 * Value parsing and improvement maths ported from the legacy plugin.
 */
class CardsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['ajrwd_test_meta'] = array();
	}

	public function test_parse_count_value(): void {
		$this->assertFalse( Cards::parse_count_value( '' ) );
		$this->assertFalse( Cards::parse_count_value( 'Passed' ) );

		$parsed = Cards::parse_count_value( '-62%' );
		$this->assertSame( '62', $parsed['number'] );
		$this->assertSame( '-', $parsed['prefix'] );
		$this->assertSame( '%', $parsed['suffix'] );
		$this->assertSame( 0, $parsed['decimals'] );

		$parsed = Cards::parse_count_value( '1.4s' );
		$this->assertSame( '1.4', $parsed['number'] );
		$this->assertSame( 1, $parsed['decimals'] );
		$this->assertSame( 's', $parsed['suffix'] );
	}

	public function test_value_to_ms(): void {
		$this->assertSame( 5200.0, Cards::value_to_ms( '5.2s' ) );
		$this->assertSame( 412.0, Cards::value_to_ms( '412ms' ) );
		$this->assertSame( 96.0, Cards::value_to_ms( '96' ) );
		$this->assertNull( Cards::value_to_ms( 'fast' ) );
		$this->assertNull( Cards::value_to_ms( '' ) );
	}

	public function test_calculate_improvement(): void {
		// 5.2s -> 1.4s is a -73% change.
		$this->assertSame( '-73%', Cards::calculate_improvement( '5.2s', '1.4s' ) );
		$this->assertSame( '', Cards::calculate_improvement( '', '1.4s' ) );
		$this->assertSame( '', Cards::calculate_improvement( '0', '1.4s' ) );
	}

	public function test_get_case_meta_falls_back_to_legacy_keys(): void {
		update_post_meta( 5, '_ajr_case_study_eyebrow', 'LEGACY' );
		update_post_meta( 5, '_ajr_case_study_mobile_before_score', '42' );

		$data = Cards::get_case_meta( 5 );
		$this->assertSame( 'LEGACY', $data['eyebrow'] );
		$this->assertSame( '42', $data['metrics']['mobile']['before']['score'] );
	}
}
