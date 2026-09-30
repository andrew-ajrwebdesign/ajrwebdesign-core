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
	 * Eyebrows of jobs that are shown the way a site build is (screenshots and a
	 * scorecard) but were not a build, so keep their own line in intro_for().
	 *
	 * @var string[]
	 */
	protected const SHOWN_AS_BUILD = array( 'SITE CARE' );

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
			'SITE BUILD' => __( 'A new site has one job: turn a search into an enquiry.', 'ajrwebdesign-core' ),
			'SITE CARE'  => __( 'Most slow sites do not need a rebuild. They need the right fixes, and someone keeping watch afterwards.', 'ajrwebdesign-core' ),
		);

		return $map[ $eyebrow ] ?? null;
	}

	/**
	 * Which of intro_for()'s lines a case study gets.
	 *
	 * A site build is known by its kind and gets the build's line, whatever its
	 * eyebrow says, unless the eyebrow is one of SHOWN_AS_BUILD. The eyebrow is a
	 * typed field, so it is compared in capitals and without stray spaces: a
	 * "Site care " typed by hand must not fall through to "A new site has one job".
	 *
	 * @param string $kind    The case study's kind (Meta::KIND).
	 * @param string $eyebrow The case study's eyebrow (Meta::EYEBROW).
	 */
	public static function eyebrow_for( string $kind, string $eyebrow ): string {
		$eyebrow = strtoupper( trim( $eyebrow ) );

		return Meta::KIND_BUILD === $kind && ! in_array( $eyebrow, self::SHOWN_AS_BUILD, true ) ? 'SITE BUILD' : $eyebrow;
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

		// Behind a password the theme's generic line stands: the type is a field too.
		if ( ! \AJR\SiteCore\Blocks\Cards::can_show( (int) get_queried_object_id() ) ) {
			return $block_content;
		}

		// A site build is known by its KIND, not by what its eyebrow happens to say:
		// "Site build", "WEBSITE" or a client's sector must all get the build's line.
		// The one exception is a job shown as a build that was not one (SHOWN_AS_BUILD):
		// "A new site has one job" would be untrue on a site somebody else built.
		$post_id = (int) get_queried_object_id();
		$intro   = self::intro_for( self::eyebrow_for( (string) get_post_meta( $post_id, Meta::KIND, true ), (string) get_post_meta( $post_id, Meta::EYEBROW, true ) ) );
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
