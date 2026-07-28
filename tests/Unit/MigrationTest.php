<?php
/**
 * Migration mapping tests.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Tests\Unit;

use AJR\SiteCore\CaseStudies\Meta;
use AJR\SiteCore\CaseStudies\Migration;
use PHPUnit\Framework\TestCase;

/**
 * The key map must cover all 22 legacy keys and migrate values faithfully.
 */
class MigrationTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['ajrwd_test_meta'] = array();
	}

	public function test_key_map_covers_all_22_legacy_keys(): void {
		$map = Migration::key_map();
		$this->assertCount( 22, $map );

		$expected = array(
			'_ajr_case_study_eyebrow',
			'_ajr_case_study_summary',
			'_ajr_case_study_cwv_before_status',
			'_ajr_case_study_cwv_after_status',
			'_ajr_case_study_requests_removed',
			'_ajr_case_study_page_size_reduced',
		);
		foreach ( array( 'mobile', 'desktop' ) as $device ) {
			foreach ( array( 'before', 'after' ) as $phase ) {
				foreach ( array( 'score', 'lcp', 'cls', 'inp' ) as $metric ) {
					$expected[] = "_ajr_case_study_{$device}_{$phase}_{$metric}";
				}
			}
		}
		foreach ( $expected as $legacy_key ) {
			$this->assertArrayHasKey( $legacy_key, $map, "Missing legacy key {$legacy_key}" );
		}
	}

	public function test_migrate_post_moves_values_into_structured_meta(): void {
		update_post_meta( 7, '_ajr_case_study_eyebrow', 'ECOMMERCE' );
		update_post_meta( 7, '_ajr_case_study_mobile_before_lcp', '5.2s' );
		update_post_meta( 7, '_ajr_case_study_mobile_after_lcp', '1.4s' );
		update_post_meta( 7, '_ajr_case_study_page_size_reduced', '-62%' );

		$migrated = ( new Migration() )->migrate_post( 7 );

		$this->assertSame( 4, $migrated );
		$this->assertSame( 'ECOMMERCE', get_post_meta( 7, Meta::EYEBROW, true ) );

		$metrics = get_post_meta( 7, Meta::METRICS, true );
		$this->assertSame( '5.2s', $metrics['mobile']['before']['lcp'] );
		$this->assertSame( '1.4s', $metrics['mobile']['after']['lcp'] );
		$this->assertSame( '', $metrics['desktop']['before']['score'] );

		$impact = get_post_meta( 7, Meta::IMPACT, true );
		$this->assertSame( '-62%', $impact['page_size_reduced'] );
	}

	public function test_migrate_post_is_idempotent_and_skips_empty(): void {
		$migration = new Migration();
		$this->assertSame( 0, $migration->migrate_post( 99 ) );

		update_post_meta( 8, '_ajr_case_study_summary', 'A summary.' );
		$migration->migrate_post( 8 );
		$migration->migrate_post( 8 );
		$this->assertSame( 'A summary.', get_post_meta( 8, Meta::SUMMARY, true ) );
	}
}
