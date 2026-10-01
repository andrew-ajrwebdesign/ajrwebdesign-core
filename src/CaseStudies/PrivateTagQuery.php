<?php
/**
 * PrivateTagQuery — a Query Loop filtered by case-study tag shows only those case studies.
 *
 * ⚠️ WHY: CORE DROPS THE FILTER SILENTLY.
 *
 * `build_query_vars_from_query_block()` gates a Query Loop's `taxQuery` behind
 * `is_taxonomy_viewable()`, and `case_study_tag` is registered `public => false` (no tag
 * archives to compete with the real pages). So the Featured Work list and the two lists on the
 * Case Studies page, each filtered by a tag, would silently list EVERY case study instead.
 * Nothing errors and the page still looks plausible.
 *
 * Ported from AJR Core 0.16.0's `Site\Private_Taxonomy_Query` (2026-10-01): Andrew decided the
 * fix belongs in the site plugin of a site that needs it, not in Core ("query loop only on
 * client so remove from core"). The taxonomy list is this site's one tag, not a setting.
 *
 * It does NOT stand down for an older AJR Core that still has its module on: that module only
 * helped when it listed this taxonomy, and a stand-down could not see that (SEO review,
 * 2026-10-01). Both running is safe — each skips a taxonomy-and-operator pair already in the
 * query, so a clause is never doubled.
 *
 * ⛔ TWO SHAPES OF taxQuery. Core reads both (wp-includes/blocks.php): the old
 * `{"case_study_tag":[4]}` and the new `{"include":{…},"exclude":{…}}`, whose exclude list
 * becomes NOT IN. The editor's Query block deprecation migrates the old shape to the new one the
 * next time a page or pattern is saved, so reading only the old shape stops working on the
 * first edit (AJR Core code-standards review, 2026-09-29). Both are handled, told apart the way
 * core tells them apart.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\CaseStudies;

defined( 'ABSPATH' ) || exit;

/**
 * Re-applies Query Loop term filters core discarded for being non-public.
 */
class PrivateTagQuery {

	/**
	 * Non-public taxonomies whose Query Loop filters this site needs honoured.
	 */
	public const TAXONOMIES = array( PostType::TAXONOMY );

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'query_loop_block_query_vars', array( $this, 'apply_private_terms' ), 10, 2 );
	}

	/**
	 * Re-apply a term filter core discarded.
	 *
	 * Only fills gaps: a taxonomy-and-operator pair core already handled is left exactly as
	 * core left it, so this cannot change the meaning of a public-taxonomy query.
	 *
	 * @param mixed $query Query vars core built.
	 * @param mixed $block The Query Loop's inner block (post template), carrying `query` context.
	 * @return mixed
	 */
	public function apply_private_terms( $query, $block ) {
		if ( ! is_array( $query ) || ! is_object( $block ) ) {
			return $query;
		}

		$context = isset( $block->context ) && is_array( $block->context ) ? $block->context : array();
		$input   = $context['query']['taxQuery'] ?? null;

		if ( ! is_array( $input ) || array() === $input ) {
			return $query;
		}

		$allowed  = array_flip( self::TAXONOMIES );
		$existing = isset( $query['tax_query'] ) && is_array( $query['tax_query'] ) ? $query['tax_query'] : array();

		// What core already put in, by taxonomy and operator, so nothing is added twice.
		$handled = array();
		foreach ( $existing as $clause ) {
			if ( is_array( $clause ) && isset( $clause['taxonomy'] ) ) {
				$handled[ (string) $clause['taxonomy'] . '|' . strtoupper( (string) ( $clause['operator'] ?? 'IN' ) ) ] = true;
			}
		}

		$added = array();
		foreach ( self::requested( $input ) as $request ) {
			list( $taxonomy, $terms, $operator ) = $request;

			if ( ! isset( $allowed[ $taxonomy ] ) || isset( $handled[ $taxonomy . '|' . $operator ] ) ) {
				continue;
			}

			$clause = array(
				'taxonomy'         => $taxonomy,
				'terms'            => $terms,
				'include_children' => false,
			);
			if ( 'IN' !== $operator ) {
				$clause['operator'] = $operator;
			}

			$added[]                                = $clause;
			$handled[ $taxonomy . '|' . $operator ] = true;
		}

		if ( array() === $added ) {
			return $query;
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- restores the term filter the editor asked for; taxonomy lookups are indexed, and without it the loop lists EVERY case study.
		$query['tax_query'] = array_merge( $existing, $added );

		return $query;
	}

	/**
	 * Every (taxonomy, term ids, operator) the block asked for, in either taxQuery shape.
	 *
	 * @param array<mixed,mixed> $input The block's taxQuery.
	 * @return array<int,array{0:string,1:array<int,int>,2:string}>
	 */
	protected static function requested( array $input ): array {
		// Any key other than include/exclude means the old shape, exactly as core decides it.
		$old = array() !== array_diff( array_keys( $input ), array( 'include', 'exclude' ) );

		$groups = $old
			? array( 'IN' => $input )
			: array(
				'IN'     => is_array( $input['include'] ?? null ) ? $input['include'] : array(),
				'NOT IN' => is_array( $input['exclude'] ?? null ) ? $input['exclude'] : array(),
			);

		$requests = array();
		foreach ( $groups as $operator => $by_taxonomy ) {
			foreach ( $by_taxonomy as $taxonomy => $terms ) {
				$ids = array_values( array_filter( array_map( 'intval', (array) $terms ) ) );
				if ( array() !== $ids ) {
					$requests[] = array( (string) $taxonomy, $ids, $operator );
				}
			}
		}

		return $requests;
	}
}
