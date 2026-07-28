<?php
/**
 * Polylang interface-string translation.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\I18n;

use AJR\SiteCore\Compat\ThemeSupport;

defined( 'ABSPATH' ) || exit;

/**
 * Lets ONE header/footer template part serve every language: the theme
 * declares its finite set of interface strings via the
 * `ajrwebdesign-core-i18n` theme-support contract, this module registers
 * them with Polylang, and a render_block filter translates any heading,
 * paragraph, or button block carrying the `is-i18n` class at render time.
 *
 * No-ops cleanly when Polylang is inactive.
 */
class Strings {

	private const GROUP = 'ajrwebdesign-theme';

	/**
	 * Hooks registration and the render filter.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_strings' ), 20 );
		add_filter( 'render_block', array( $this, 'translate_block' ), 10, 2 );
	}

	/**
	 * Registers theme-declared strings with Polylang.
	 */
	public function register_strings(): void {
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}
		foreach ( ThemeSupport::theme_i18n_strings() as $string ) {
			pll_register_string( md5( $string ), $string, self::GROUP );
		}
	}

	/**
	 * Translates the text content of `is-i18n`-classed blocks.
	 *
	 * @param string              $content Rendered block HTML.
	 * @param array<string,mixed> $block   Parsed block.
	 * @return string
	 */
	public function translate_block( string $content, array $block ): string {
		if ( ! function_exists( 'pll__' ) || is_admin() ) {
			return $content;
		}

		$class = $block['attrs']['className'] ?? '';
		if ( ! is_string( $class ) || ! preg_match( '/\bis-i18n\b/', $class ) ) {
			return $content;
		}

		$text = trim( wp_strip_all_tags( $content ) );
		if ( '' === $text ) {
			return $content;
		}

		$translated = pll__( $text );
		if ( $translated === $text || ! is_string( $translated ) || '' === $translated ) {
			return $content;
		}

		// Replace only the text node content, preserving the block's markup.
		$replaced = str_replace( $text, esc_html( $translated ), $content );
		return is_string( $replaced ) ? $replaced : $content;
	}
}
