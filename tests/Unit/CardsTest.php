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
		$GLOBALS['ajrwd_test_meta']   = array();
		$GLOBALS['ajrwd_test_titles'] = array();
		$GLOBALS['ajrwd_test_lang']   = '';
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

	public function test_get_case_meta_ignores_german_fields_on_english_pages(): void {
		$GLOBALS['ajrwd_test_titles'][7] = 'English Title';
		update_post_meta( 7, 'ajrwd_cs_eyebrow', 'ECOMMERCE' );
		update_post_meta( 7, 'ajrwd_cs_title_de', 'Deutscher Titel' );
		update_post_meta( 7, 'ajrwd_cs_eyebrow_de', 'E-COMMERCE' );

		$data = Cards::get_case_meta( 7 );
		$this->assertSame( 'English Title', $data['title'] );
		$this->assertSame( 'ECOMMERCE', $data['eyebrow'] );
	}

	public function test_get_case_meta_uses_german_fields_with_english_fallback(): void {
		$GLOBALS['ajrwd_test_lang']      = 'de';
		$GLOBALS['ajrwd_test_titles'][7] = 'English Title';
		update_post_meta( 7, 'ajrwd_cs_eyebrow', 'ECOMMERCE' );
		update_post_meta( 7, 'ajrwd_cs_summary', 'English summary.' );
		update_post_meta( 7, 'ajrwd_cs_title_de', 'Deutscher Titel' );
		update_post_meta( 7, 'ajrwd_cs_eyebrow_de', 'E-COMMERCE' );
		// summary_de deliberately empty: must fall back to English.

		$data = Cards::get_case_meta( 7 );
		$this->assertSame( 'Deutscher Titel', $data['title'] );
		$this->assertSame( 'E-COMMERCE', $data['eyebrow'] );
		$this->assertSame( 'English summary.', $data['summary'] );
	}

	public function test_ui_label_translates_only_on_german_pages(): void {
		$this->assertSame( 'Before', Cards::ui_label( 'Before' ) );

		$GLOBALS['ajrwd_test_lang'] = 'de';
		$this->assertSame( 'Vorher', Cards::ui_label( 'Before' ) );
		$this->assertSame( 'Bestanden', Cards::ui_label( 'Passed' ) );
		// Terms used untranslated in German pass through.
		$this->assertSame( 'Core Web Vitals', Cards::ui_label( 'Core Web Vitals' ) );
		$this->assertSame( 'Desktop', Cards::ui_label( 'Desktop' ) );
	}
}
