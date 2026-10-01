<?php
/**
 * Unit tests for CaseStudies\PrivateTagQuery (ported from AJR Core 0.16.0, 2026-10-01).
 *
 * WHY THESE TESTS EXIST
 *
 * Core drops the filter silently and the page still looks plausible (Featured Work would list
 * every case study), so the failure this guards against is invisible by nature. These pin that
 * the filter comes back for case_study_tag in both taxQuery shapes, that nothing else changes,
 * that a clause core already added is never doubled, and that it stands down while an older
 * AJR Core still does the job.
 *
 * @package AJR\SiteCore
 */

declare( strict_types=1 );

namespace AJR\SiteCore\Tests\Mocked;

use AJR\Core\Framework\Modules;
use AJR\SiteCore\CaseStudies\PrivateTagQuery;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * Tests for the Query Loop filter.
 */
class PrivateTagQueryTest extends TestCase {

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		Modules::$on = array();
		parent::tearDown();
	}

	/**
	 * A block whose Query Loop context carries this taxQuery.
	 *
	 * @param array<string,mixed> $tax_query The block's taxQuery attribute.
	 */
	protected function block( array $tax_query ): \WP_Block {
		$block          = new \WP_Block();
		$block->context = [ 'query' => [ 'taxQuery' => $tax_query ] ];

		return $block;
	}

	/**
	 * The filter is hooked.
	 */
	public function test_register_hooks(): void {
		$module = new PrivateTagQuery();

		WP_Mock::expectFilterAdded( 'query_loop_block_query_vars', [ $module, 'apply_private_terms' ], 10, 2 );

		$module->register();

		$this->assertHooksAdded();
	}

	/**
	 * ⛔ It runs even while an older AJR Core still has its own module on: that module helped only
	 * if it listed case_study_tag, which a stand-down could not see. Doubling is ruled out by
	 * test_handled_clauses_are_not_doubled().
	 */
	public function test_runs_even_while_core_has_its_module_on(): void {
		Modules::$on = [ 'private_taxonomies' ];
		$module      = new PrivateTagQuery();

		WP_Mock::expectFilterAdded( 'query_loop_block_query_vars', [ $module, 'apply_private_terms' ], 10, 2 );

		$module->register();

		$this->assertHooksAdded();
	}

	/**
	 * The case-study tag filter is put back; any other non-public taxonomy is not.
	 */
	public function test_the_case_study_tag_is_restored(): void {
		$query = ( new PrivateTagQuery() )->apply_private_terms(
			[ 'post_type' => 'ajr_case_study' ],
			$this->block(
				[
					'case_study_tag' => [ '12', 0, 'x' ],
					'secret_tax'     => [ 5 ],
				]
			)
		);

		$this->assertSame(
			[
				[
					'taxonomy'         => 'case_study_tag',
					'terms'            => [ 12 ],
					'include_children' => false,
				],
			],
			$query['tax_query']
		);
		$this->assertSame( 'ajr_case_study', $query['post_type'] );
	}

	/**
	 * A clause core (or an older AJR Core) already added is left exactly as it was.
	 */
	public function test_handled_clauses_are_not_doubled(): void {
		$core  = [
			'tax_query' => [
				[
					'taxonomy' => 'case_study_tag',
					'terms'    => [ 3 ],
				],
			],
		];
		$query = ( new PrivateTagQuery() )->apply_private_terms( $core, $this->block( [ 'case_study_tag' => [ 3 ] ] ) );

		$this->assertSame( $core, $query );
	}

	/**
	 * The shape core's Query block migrates to on the next save: include → IN, exclude → NOT IN.
	 */
	public function test_the_include_exclude_shape_is_restored(): void {
		$query = ( new PrivateTagQuery() )->apply_private_terms(
			[],
			$this->block(
				[
					'include' => [
						'case_study_tag' => [ 4 ],
						'secret_tax'     => [ 9 ],
					],
					'exclude' => [ 'case_study_tag' => [ 7 ] ],
				]
			)
		);

		$this->assertSame(
			[
				[
					'taxonomy'         => 'case_study_tag',
					'terms'            => [ 4 ],
					'include_children' => false,
				],
				[
					'taxonomy'         => 'case_study_tag',
					'terms'            => [ 7 ],
					'include_children' => false,
					'operator'         => 'NOT IN',
				],
			],
			$query['tax_query']
		);
	}

	/**
	 * A Query Loop with no tag filter is untouched.
	 */
	public function test_no_filter_changes_nothing(): void {
		$query = [ 'post_type' => 'post' ];

		$this->assertSame( $query, ( new PrivateTagQuery() )->apply_private_terms( $query, $this->block( [] ) ) );
		$this->assertSame( $query, ( new PrivateTagQuery() )->apply_private_terms( $query, $this->block( [ 'category' => [ 1 ] ] ) ) );
	}
}
