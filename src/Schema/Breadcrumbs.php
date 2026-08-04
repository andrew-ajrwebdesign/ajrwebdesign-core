<?php
/**
 * BreadcrumbList structured data.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Emits schema.org BreadcrumbList JSON-LD on singular interior content.
 * The site's page tree is flat, so the trail is Home → current page (with
 * the language-correct home when Polylang is active), plus the archive
 * level for case studies. Front pages are skipped — a one-item trail is
 * noise.
 */
class Breadcrumbs {

	/**
	 * Hooks the head output.
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'output_schema' ) );
	}

	/**
	 * Prints the BreadcrumbList JSON-LD for the current view.
	 */
	public function output_schema(): void {
		if ( ! is_singular() || is_front_page() ) {
			return;
		}
		$post = get_post();
		if ( ! $post ) {
			return;
		}

		$home  = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
		$items = array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => __( 'Home', 'ajrwebdesign-core' ),
				'item'     => $home,
			),
		);

		if ( is_singular( 'ajr_case_study' ) ) {
			$archive = get_post_type_archive_link( 'ajr_case_study' );
			if ( $archive ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => __( 'Case Studies', 'ajrwebdesign-core' ),
					'item'     => $archive,
				);
			}
		}

		$items[] = array(
			'@type'    => 'ListItem',
			'position' => count( $items ) + 1,
			'name'     => wp_strip_all_tags( get_the_title( $post ) ),
			'item'     => get_permalink( $post ),
		);

		$node = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output.
		echo '<script type="application/ld+json">' . wp_json_encode( $node, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}
