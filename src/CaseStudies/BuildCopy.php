<?php
/**
 * Results-band wording for a site-build case study.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\CaseStudies;

use AJR\SiteCore\Blocks\Build;
use AJR\SiteCore\Blocks\Cards;

defined( 'ABSPATH' ) || exit;

/**
 * Swaps the results band's heading and intro on a site-build case study.
 *
 * The theme's case-study-results pattern is written for an audit: "The results
 * at a glance", "Measured with Lighthouse before and after the engagement". A
 * site built from nothing has no "before", so on a build the heading becomes
 * "The build at a glance" and the intro becomes the case study's own line (the
 * metabox's "Results intro"), which says what was measured and when.
 *
 * Same mechanism as StoryIntro, for the same reason: pattern PHP runs at
 * registration and can never read the post being shown, so the pattern carries
 * marker classes and this filter replaces the text at render time. With this
 * plugin off, or on an audit, the theme's wording simply stands.
 */
class BuildCopy {

	/**
	 * Marker class on the results band's heading.
	 */
	public const TITLE_MARKER = 'cs-results-title';

	/**
	 * Marker class on the results band's intro paragraph.
	 */
	public const INTRO_MARKER = 'cs-results-intro';

	/**
	 * The two tag patterns replace_text() accepts.
	 */
	public const TAG_HEADING   = 'h[1-6]';
	public const TAG_PARAGRAPH = 'p';

	/**
	 * Hooks the heading and paragraph render filters.
	 */
	public function register(): void {
		add_filter( 'render_block_core/heading', array( $this, 'swap_title' ), 10, 2 );
		add_filter( 'render_block_core/paragraph', array( $this, 'swap_intro' ), 10, 2 );
	}

	/**
	 * The site build being shown, or 0 when this is not a build's own page.
	 */
	protected function build_id(): int {
		if ( ! is_singular( PostType::POST_TYPE ) ) {
			return 0;
		}
		$post_id = (int) get_queried_object_id();

		// Behind a password (or on a draft someone may not read) the theme's
		// wording stands: the results intro is one of the case study's fields.
		return Build::is_build( $post_id ) && Cards::can_show( $post_id ) ? $post_id : 0;
	}

	/**
	 * Replace the text inside the first element of a given tag, keeping the tag
	 * and its attributes exactly as the theme rendered them.
	 *
	 * A callback, not a replacement string: a $ or backslash in the new text must
	 * never be read as a backreference.
	 *
	 * @param string $html Rendered block HTML.
	 * @param string $tag  self::TAG_HEADING or self::TAG_PARAGRAPH. Anything else changes nothing:
	 *                     the value goes into a regular expression, so it is never free text.
	 * @param string $text The new text (plain; escaped here).
	 */
	public static function replace_text( string $html, string $tag, string $text ): string {
		if ( ! in_array( $tag, array( self::TAG_HEADING, self::TAG_PARAGRAPH ), true ) ) {
			return $html;
		}

		$swapped = preg_replace_callback(
			'/(<(' . $tag . ')\b[^>]*>).*?(<\/\2>)/s',
			static fn( array $m ): string => $m[1] . esc_html( $text ) . $m[3],
			$html,
			1
		);

		return null === $swapped ? $html : $swapped;
	}

	/**
	 * The heading: "The build at a glance".
	 *
	 * @param string $block_content Rendered heading HTML.
	 * @param array  $block         Parsed block.
	 */
	public function swap_title( $block_content, $block ): string {
		$block_content = (string) $block_content;

		if ( ! str_contains( (string) ( $block['attrs']['className'] ?? '' ), self::TITLE_MARKER ) || ! $this->build_id() ) {
			return $block_content;
		}

		return self::replace_text( $block_content, self::TAG_HEADING, __( 'The build at a glance', 'ajrwebdesign-core' ) );
	}

	/**
	 * The intro: the case study's own "Results intro", or a plain line when none
	 * was written.
	 *
	 * @param string $block_content Rendered paragraph HTML.
	 * @param array  $block         Parsed block.
	 */
	public function swap_intro( $block_content, $block ): string {
		$block_content = (string) $block_content;

		if ( ! str_contains( (string) ( $block['attrs']['className'] ?? '' ), self::INTRO_MARKER ) ) {
			return $block_content;
		}

		$post_id = $this->build_id();
		if ( ! $post_id ) {
			return $block_content;
		}

		$intro = Build::data( $post_id )['intro'];
		if ( '' === $intro ) {
			$intro = __( 'Measured on the live site with Google PageSpeed Insights.', 'ajrwebdesign-core' );
		}

		return self::replace_text( $block_content, self::TAG_PARAGRAPH, $intro );
	}
}
