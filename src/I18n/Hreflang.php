<?php
/**
 * The hreflang x-default alternate.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the x-default hreflang alternate, pointing search engines at the
 * English page for visitors matching neither language. Polylang emits the
 * per-language pairs; it exposes them through this filter but never adds
 * an x-default itself.
 */
class Hreflang {

	/**
	 * Hooks the Polylang filter.
	 */
	public function register(): void {
		add_filter( 'pll_rel_hreflang_attributes', array( $this, 'add_x_default' ) );
	}

	/**
	 * Adds x-default to Polylang's hreflang map.
	 *
	 * @param array $hreflangs Language code => URL map about to be printed.
	 * @return array Map with x-default appended.
	 */
	public function add_x_default( array $hreflangs ): array {
		if ( isset( $hreflangs['en'] ) && ! isset( $hreflangs['x-default'] ) ) {
			$hreflangs['x-default'] = $hreflangs['en'];
		}
		return $hreflangs;
	}
}
