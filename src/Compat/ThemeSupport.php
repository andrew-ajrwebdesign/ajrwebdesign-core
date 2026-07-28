<?php
/**
 * Theme-support contracts.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Compat;

defined( 'ABSPATH' ) || exit;

/**
 * The plugin stays theme-independent: theme-coupled behaviour keys off
 * add_theme_support() contracts declared by the theme, so everything
 * degrades gracefully when a different theme is active.
 *
 * Contracts:
 * - `ajrwebdesign-core-cards`: the active theme styles the case-study card
 *   blocks; without it the blocks ship their own minimal fallback spacing.
 * - `ajrwebdesign-core-i18n`: the theme passes an array of translatable
 *   interface strings for Polylang registration (see I18n\Strings).
 */
class ThemeSupport {

	/**
	 * Nothing to hook yet — the class exists as the single place where
	 * contract names live and are documented.
	 */
	public function register(): void {}

	/**
	 * Whether the active theme opts into card styling.
	 */
	public static function theme_styles_cards(): bool {
		return current_theme_supports( 'ajrwebdesign-core-cards' );
	}

	/**
	 * Strings the theme registered for interface translation.
	 *
	 * @return string[]
	 */
	public static function theme_i18n_strings(): array {
		$support = get_theme_support( 'ajrwebdesign-core-i18n' );
		if ( ! is_array( $support ) || empty( $support[0] ) || ! is_array( $support[0] ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'strval', $support[0] ) ) );
	}
}
