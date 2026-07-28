<?php
/**
 * Static helpers.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Small shared helpers used across modules.
 */
class Utils {

	/**
	 * Current Polylang language slug, or '' when Polylang is inactive.
	 */
	public static function current_language(): string {
		if ( function_exists( 'pll_current_language' ) ) {
			$lang = pll_current_language( 'slug' );
			if ( is_string( $lang ) && '' !== $lang ) {
				return $lang;
			}
		}
		return '';
	}

	/**
	 * Default Polylang language slug, or '' when Polylang is inactive.
	 */
	public static function default_language(): string {
		if ( function_exists( 'pll_default_language' ) ) {
			$lang = pll_default_language( 'slug' );
			if ( is_string( $lang ) && '' !== $lang ) {
				return $lang;
			}
		}
		return '';
	}

	/**
	 * Language-aware home URL (falls back to home_url()).
	 */
	public static function home_url(): string {
		if ( function_exists( 'pll_home_url' ) ) {
			$url = pll_home_url();
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}
		return home_url( '/' );
	}
}
