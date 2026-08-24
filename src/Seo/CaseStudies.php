<?php
/**
 * Case-study SEO surface: sitemap presence and meta descriptions.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Seo;

use AJR\SiteCore\Blocks\Cards;
use AJR\SiteCore\CaseStudies\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Puts the case-study section in front of search engines.
 *
 * Two gaps this closes, both found on 2026-08-24 with the section fully
 * invisible to Google ("URL is unknown to Google" on every case-study URL):
 *
 * 1. Sitemap. The SEO Framework's sitemap query mixes translatable post
 *    types with this untranslatable CPT, so Polylang's language filter
 *    silently drops every case study from every language's sitemap. Making
 *    the CPT translatable would contradict the single-entry bilingual model
 *    (German lives in per-post meta, not Polylang twins), so the URLs are
 *    appended through TSF's additional-URLs filter instead — on the default
 *    language's sitemap only, since the singles are EN URLs.
 *
 * 2. Meta descriptions. Case-study posts keep their prose in structured
 *    meta and (optionally) block content, so TSF's excerpt generator often
 *    has nothing to work with and emits no description at all. The summary
 *    meta IS the description — it is fed to TSF as the excerpt source and
 *    TSF clamps it to guideline length itself.
 */
class CaseStudies {

	/**
	 * Sitemap entries are capped well above the realistic case-study count —
	 * a portfolio with hundreds of entries needs a real archive strategy,
	 * not a longer appendix.
	 */
	public const SITEMAP_CAP = 50;

	/**
	 * Hooks the TSF filters.
	 */
	public function register(): void {
		add_filter( 'the_seo_framework_description_excerpt', array( $this, 'description_excerpt' ), 10, 2 );
		add_filter( 'the_seo_framework_sitemap_additional_urls', array( $this, 'sitemap_urls' ) );
	}

	/**
	 * Supplies the summary meta as the description source for case studies.
	 *
	 * The summary takes PRECEDENCE over whatever TSF derived from the post
	 * body: the summary is the curated, description-length blurb for the
	 * engagement, and it must stay the description even after the narrative
	 * content gets rewritten. (A first version only used it as a fallback,
	 * which silently flipped the description to the body's first paragraph
	 * the moment the posts gained content — caught in review 2026-08-24.)
	 *
	 * @param string     $excerpt The excerpt TSF derived.
	 * @param array|null $args    TSF query args; null when auto-determined.
	 * @return string
	 */
	public function description_excerpt( $excerpt, $args ): string {
		$excerpt = (string) $excerpt;

		// The archive has no post to derive a description from at all — TSF
		// emits nothing for it. Owning the copy here means it deploys with
		// the plugin instead of living in a DB option someone must remember
		// to set on every environment.
		$is_archive = isset( $args['pta'] )
			? PostType::POST_TYPE === $args['pta']
			: is_post_type_archive( PostType::POST_TYPE );
		if ( $is_archive ) {
			return __( 'Real WordPress performance and SEO engagements with measured before-and-after results — Lighthouse scores, Core Web Vitals, load times and page weight.', 'ajrwebdesign-core' );
		}

		$post_id = isset( $args['id'] ) ? (int) $args['id'] : (int) get_queried_object_id();
		if ( ! $post_id || PostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return $excerpt;
		}

		$summary = (string) ( Cards::get_case_meta( $post_id )['summary'] ?? '' );

		return '' !== $summary ? $summary : $excerpt;
	}

	/**
	 * Appends the case-study archive and singles to the sitemap.
	 *
	 * Runs once per language sitemap; only the default language's sitemap
	 * receives the URLs, because the singles exist at EN URLs only.
	 *
	 * get_posts() is deliberate, not an oversight of the WP_Query rule: its
	 * suppress_filters default guarantees Polylang can never filter this
	 * query — Polylang dropping the untranslatable CPT from TSF's own
	 * sitemap query is the exact failure this class exists to route around.
	 *
	 * @param array $urls Additional sitemap entries, URL => [ 'lastmod' => ... ].
	 * @return array
	 */
	public function sitemap_urls( $urls ): array {
		$urls = (array) $urls;

		if ( function_exists( 'pll_current_language' ) && function_exists( 'pll_default_language' )
			&& pll_current_language() !== pll_default_language() ) {
			return $urls;
		}

		$posts = get_posts(
			array(
				'post_type'              => PostType::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => self::SITEMAP_CAP,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$latest = '';
		foreach ( $posts as $post ) {
			$permalink = get_permalink( $post );
			if ( ! $permalink ) {
				continue;
			}
			$lastmod            = $post->post_modified_gmt;
			$urls[ $permalink ] = array( 'lastmod' => $lastmod );
			$latest             = max( $latest, $lastmod );
		}

		$archive = get_post_type_archive_link( PostType::POST_TYPE );
		if ( $archive ) {
			$urls[ $archive ] = '' !== $latest ? array( 'lastmod' => $latest ) : array();
		}

		return $urls;
	}
}
