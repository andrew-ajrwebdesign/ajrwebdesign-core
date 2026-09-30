<?php
/**
 * Drops an optional band of the case-study single when it has nothing to show.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\CaseStudies;

defined( 'ABSPATH' ) || exit;

/**
 * Removes an empty optional band from the page.
 *
 * The case-study single has bands that only some case studies fill: "What
 * changed" and "What was delivered" belong to a site build that has those
 * fields, and "More case studies" needs another case study to exist. Each band
 * is a section in the theme (its background and spacing are the theme's) with
 * one case-study-card block inside that prints the content, or nothing.
 *
 * A band whose block printed nothing would still be there: a strip of padding
 * in the band's colour. The theme marks such a section with the class
 * `cs-optional-band`, and this filter returns nothing for it when no block
 * output is inside. It can tell because every shape the block prints carries a
 * class starting `ajr-cs-` or `ajr-case-study-`.
 *
 * With this plugin off the block prints nothing either; the theme hides the
 * empty section with CSS instead.
 */
class OptionalBand {

	/**
	 * Marker class the theme puts on an optional band's section.
	 */
	public const MARKER = 'cs-optional-band';

	/**
	 * Hooks the group render filter.
	 */
	public function register(): void {
		add_filter( 'render_block_core/group', array( $this, 'drop_if_empty' ), 10, 2 );
	}

	/**
	 * Whether rendered band HTML holds any output of the case-study blocks.
	 *
	 * @param string $html The band's rendered HTML.
	 */
	public static function has_content( string $html ): bool {
		return str_contains( $html, 'class="ajr-cs-' )
			|| str_contains( $html, ' ajr-cs-' )
			|| str_contains( $html, 'ajr-case-study-' );
	}

	/**
	 * Returns nothing for a marked band with no block output inside it.
	 *
	 * @param string $block_content Rendered group HTML.
	 * @param array  $block         Parsed block.
	 */
	public function drop_if_empty( $block_content, $block ): string {
		$block_content = (string) $block_content;

		// The cheap test first: this filter runs for every group on every page.
		if ( ! str_contains( (string) ( $block['attrs']['className'] ?? '' ), self::MARKER ) ) {
			return $block_content;
		}

		return self::has_content( $block_content ) ? $block_content : '';
	}
}
