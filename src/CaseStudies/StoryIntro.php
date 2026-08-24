<?php
/**
 * Per-engagement-type intro line for the case-study story band.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\CaseStudies;

defined( 'ABSPATH' ) || exit;

/**
 * Swaps the story band's intro paragraph for a line in the engagement
 * type's own voice — an eCommerce overhaul and a membership rescue must
 * not read the same (Andrew, 2026-08-24).
 *
 * The theme's case-study-story pattern ships a generic fallback paragraph
 * carrying the `cs-story-intro` marker class; this filter replaces its
 * text at render time from the post's eyebrow meta. It lives here and not
 * in the pattern because pattern PHP executes at REGISTRATION, outside any
 * query — a pattern can never read the rendered post (verified 2026-08-24).
 * With this plugin deactivated the theme's generic line simply stands.
 */
class StoryIntro {

	/**
	 * Marker class the theme pattern puts on the swappable paragraph.
	 */
	public const MARKER = 'cs-story-intro';

	/**
	 * Hooks the paragraph render filter.
	 */
	public function register(): void {
		add_filter( 'render_block_core/paragraph', array( $this, 'swap_intro' ), 10, 2 );
	}

	/**
	 * The engagement-type voice for an eyebrow value, or null to keep the
	 * theme's generic line.
	 *
	 * @param string $eyebrow The post's ajrwd_cs_eyebrow meta value.
	 * @return string|null
	 */
	public static function intro_for( string $eyebrow ): ?string {
		$map = array(
			'ECOMMERCE'  => __( 'An online store lives or dies on mobile speed. This is how this one got it back.', 'ajrwebdesign-core' ),
			'LOCAL SEO'  => __( 'For a local service business, being found and being fast are the same job.', 'ajrwebdesign-core' ),
			'MEMBERSHIP' => __( 'Members log in every day — but Google and every prospect only ever see the logged-out site.', 'ajrwebdesign-core' ),
			'PUBLISHING' => __( 'A publisher’s articles carry the traffic — and everything the business bolts onto them.', 'ajrwebdesign-core' ),
		);

		return $map[ $eyebrow ] ?? null;
	}

	/**
	 * Replaces the marker paragraph's text on case-study singles.
	 *
	 * @param string $block_content Rendered paragraph HTML.
	 * @param array  $block         Parsed block.
	 * @return string
	 */
	public function swap_intro( $block_content, $block ): string {
		$block_content = (string) $block_content;

		$class_name = (string) ( $block['attrs']['className'] ?? '' );
		if ( ! str_contains( $class_name, self::MARKER ) || ! is_singular( PostType::POST_TYPE ) ) {
			return $block_content;
		}

		$eyebrow = (string) get_post_meta( get_queried_object_id(), 'ajrwd_cs_eyebrow', true );
		$intro   = self::intro_for( $eyebrow );
		if ( null === $intro ) {
			return $block_content;
		}

		// Replace only the paragraph's inner text; classes and attributes
		// stay exactly as the theme rendered them. Callback, not a
		// replacement string — a $ or backslash in a translated line must
		// never be interpreted as a backreference (the String.replace trap).
		$swapped = preg_replace_callback(
			'/(<p\b[^>]*>).*?(<\/p>)/s',
			static fn( array $m ): string => $m[1] . esc_html( $intro ) . $m[2],
			$block_content,
			1
		);

		return null === $swapped ? $block_content : $swapped;
	}
}
